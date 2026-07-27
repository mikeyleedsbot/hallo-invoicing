<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Product;
use App\Models\InvoiceTemplate;
use App\Models\VatRate;
use App\Services\InvoicePdfGenerator;
use App\Services\VatCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        [$query, $filters, $sort, $direction] = $this->buildIndexQuery($request);

        $invoices = $query->paginate(20)->withQueryString();

        return view('invoices.index', compact('invoices', 'filters', 'sort', 'direction'));
    }

    /**
     * Gedeelde query-opbouw voor index en export (zelfde filters/sortering).
     */
    private function buildIndexQuery(Request $request): array
    {
        $filters = [
            'search'    => trim((string) $request->query('search', '')),
            'status'    => (string) $request->query('status', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to'   => (string) $request->query('date_to', ''),
        ];

        // Sorteerbare kolommen
        $allowedSorts = ['invoice_number', 'customer_name', 'invoice_date', 'due_date', 'total', 'status'];
        $sort = in_array($request->query('sort'), $allowedSorts) ? $request->query('sort') : 'invoice_date';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $query = Invoice::with('customer');

        // Sorteren op klantnaam vereist een join
        if ($sort === 'customer_name') {
            $query->join('customers', 'invoices.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $direction)
                  ->select('invoices.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoices.invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($c) use ($search) {
                      $c->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($filters['status'] !== '') {
            $query->where('invoices.status', $filters['status']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('invoices.invoice_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('invoices.invoice_date', '<=', $filters['date_to']);
        }

        return [$query, $filters, $sort, $direction];
    }

    /**
     * Exporteer de (gefilterde) factuurlijst naar Excel — één rij per
     * factuur met bedragen excl., btw en incl. (zonder artikelregels).
     */
    public function export(Request $request)
    {
        [$query] = $this->buildIndexQuery($request);
        $invoices = $query->get();

        $statusLabels = ['draft' => 'Concept', 'sent' => 'Verzonden', 'paid' => 'Betaald', 'overdue' => 'Verlopen', 'cancelled' => 'Geannuleerd'];

        // Referenties (offertenummer) in één query ophalen i.p.v. per factuur
        $quoteRefs = \App\Models\Quote::whereIn('converted_invoice_id', $invoices->pluck('id'))
            ->pluck('quote_number', 'converted_invoice_id');

        $rows = [[
            'Factuurnummer', 'Klant', 'Bedrijf', 'Factuurdatum', 'Vervaldatum',
            'Status', 'Subtotaal excl. BTW', 'BTW', 'Totaal incl. BTW',
            'BTW verlegd', 'Verzonden op', 'Betaald op', 'Referentie', 'Opmerkingen',
        ]];

        foreach ($invoices as $invoice) {
            $rows[] = [
                $invoice->invoice_number,
                $invoice->customer->name ?? '',
                $invoice->customer->company_name ?? '',
                $invoice->invoice_date?->format('d-m-Y') ?? '',
                $invoice->due_date?->format('d-m-Y') ?? '',
                $statusLabels[$invoice->status] ?? $invoice->status,
                (float) $invoice->subtotal,
                (float) $invoice->vat_amount,
                (float) $invoice->total,
                $invoice->vat_reverse_charged ? 'Ja' : 'Nee',
                $invoice->sent_at?->format('d-m-Y') ?? '',
                $invoice->paid_at?->format('d-m-Y') ?? '',
                isset($quoteRefs[$invoice->id]) ? 'Offerte ' . $quoteRefs[$invoice->id] : '',
                $invoice->notes ?? '',
            ];
        }

        $xlsx = \App\Services\SimpleXlsxWriter::make($rows, 'Facturen');

        return response($xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="facturen-' . now()->format('Y-m-d') . '.xlsx"',
        ]);
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $templates = InvoiceTemplate::orderBy('is_default', 'desc')->orderBy('name')->get();
        $defaultTemplate = InvoiceTemplate::where('is_default', true)->first();

        // Volgend factuurnummer (prefix + teller uit instellingen)
        $invoiceNumber = AppSetting::get()->nextInvoiceNumber();

        $vatRates    = VatRate::ordered()->get();
        $defaultVat  = (int)($vatRates->firstWhere('is_default', true)?->rate ?? 21);

        return view('invoices.create', compact('customers', 'products', 'templates', 'defaultTemplate', 'invoiceNumber', 'vatRates', 'defaultVat'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('user_id', auth()->id())],
            'template_id' => ['nullable', Rule::exists('invoice_templates', 'id')->where('user_id', auth()->id())],
            'invoice_number' => ['required', Rule::unique('invoices')->where('user_id', auth()->id())],
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'payment_terms' => 'nullable|integer',
            'notes' => 'nullable|string',
            'vat_reverse_charged' => 'nullable|boolean',
            'prices_include_vat' => 'nullable|boolean',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric',
            'lines.*.unit_price' => 'required|numeric',
            'lines.*.vat_rate' => ($request->boolean('vat_reverse_charged') ? 'nullable' : 'required') . '|numeric|min:0|max:100',
        ]);

        $reverseCharged = (bool) ($validated['vat_reverse_charged'] ?? false);

        // Bij verlegde BTW wordt er geen BTW berekend, dus inclusief invoeren
        // heeft geen betekenis: dan zijn de bedragen altijd exclusief.
        $pricesIncludeVat = ! $reverseCharged && (bool) ($validated['prices_include_vat'] ?? false);

        if ($reverseCharged) {
            $customer = Customer::find($validated['customer_id']);
            if (! $customer || trim((string) $customer->vat_number) === '') {
                throw ValidationException::withMessages([
                    'vat_reverse_charged' => 'BTW kan niet verlegd worden: deze klant heeft geen BTW-nummer.',
                ]);
            }

            // Voeg opmerking toe als die er nog niet staat
            $reverseNote = 'BTW verlegd. BTW-nummer afnemer: ' . trim($customer->vat_number);
            $existing = trim((string) ($validated['notes'] ?? ''));
            if (stripos($existing, 'BTW verlegd') === false) {
                $validated['notes'] = $existing === '' ? $reverseNote : $existing . "\n\n" . $reverseNote;
            }
        }

        DB::transaction(function () use ($validated, $reverseCharged, $pricesIncludeVat) {
            // Totalen (originele vat_rates blijven op de regels staan; bij verlegde
            // BTW wordt er geen BTW berekend, bij inclusieve invoer wordt de BTW
            // uit de bedragen gehaald in plaats van erbij opgeteld)
            $totals = VatCalculator::totals($validated['lines'], $pricesIncludeVat, $reverseCharged);

            $subtotal = $totals['subtotal'];
            $totalVat = $totals['vat_amount'];
            $total    = $totals['total'];

            // Create invoice
            $invoice = Invoice::create([
                'customer_id' => $validated['customer_id'],
                'template_id' => $validated['template_id'] ?? InvoiceTemplate::where('is_default', true)->first()?->id,
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'payment_terms' => $validated['payment_terms'] ?? 14,
                'subtotal' => $subtotal,
                'vat_amount' => $totalVat,
                'total' => $total,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'vat_reverse_charged' => $reverseCharged,
                'prices_include_vat' => $pricesIncludeVat,
            ]);

            // Create invoice lines (unit_price/total zoals ingevoerd: excl. of incl. BTW)
            foreach ($validated['lines'] as $line) {
                $invoice->lines()->create([
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'vat_rate' => $line['vat_rate'] ?? 0,
                    'total' => round($line['quantity'] * $line['unit_price'], 2),
                ]);
            }
        });

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Factuur succesvol aangemaakt!');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('customer', 'lines');
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $templates = InvoiceTemplate::orderBy('is_default', 'desc')->orderBy('name')->get();
        $invoice->load('lines');

        $vatRates   = VatRate::ordered()->get();
        $defaultVat = (int)($vatRates->firstWhere('is_default', true)?->rate ?? 21);

        return view('invoices.edit', compact('invoice', 'customers', 'products', 'templates', 'vatRates', 'defaultVat'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('user_id', auth()->id())],
            'template_id' => ['nullable', Rule::exists('invoice_templates', 'id')->where('user_id', auth()->id())],
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'payment_terms' => 'nullable|integer',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'vat_reverse_charged' => 'nullable|boolean',
            'prices_include_vat' => 'nullable|boolean',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric',
            'lines.*.unit_price' => 'required|numeric',
            'lines.*.vat_rate' => 'required|numeric|min:0|max:100',
        ]);

        $reverseCharged = (bool) ($validated['vat_reverse_charged'] ?? false);

        // Bij verlegde BTW wordt er geen BTW berekend, dus inclusief invoeren
        // heeft geen betekenis: dan zijn de bedragen altijd exclusief.
        $pricesIncludeVat = ! $reverseCharged && (bool) ($validated['prices_include_vat'] ?? false);

        if ($reverseCharged) {
            $customer = Customer::find($validated['customer_id']);
            if (! $customer || trim((string) $customer->vat_number) === '') {
                throw ValidationException::withMessages([
                    'vat_reverse_charged' => 'BTW kan niet verlegd worden: deze klant heeft geen BTW-nummer.',
                ]);
            }

            $reverseNote = 'BTW verlegd. BTW-nummer afnemer: ' . trim($customer->vat_number);
            $existing = trim((string) ($validated['notes'] ?? ''));
            if (stripos($existing, 'BTW verlegd') === false) {
                $validated['notes'] = $existing === '' ? $reverseNote : $existing . "\n\n" . $reverseNote;
            }
        }

        DB::transaction(function () use ($validated, $invoice, $reverseCharged, $pricesIncludeVat) {
            // Totalen (originele vat_rates blijven op de regels staan; bij verlegde
            // BTW wordt er geen BTW berekend, bij inclusieve invoer wordt de BTW
            // uit de bedragen gehaald in plaats van erbij opgeteld)
            $totals = VatCalculator::totals($validated['lines'], $pricesIncludeVat, $reverseCharged);

            $subtotal = $totals['subtotal'];
            $totalVat = $totals['vat_amount'];
            $total    = $totals['total'];

            // Update invoice
            $invoice->update([
                'customer_id' => $validated['customer_id'],
                'template_id' => $validated['template_id'] ?? InvoiceTemplate::where('is_default', true)->first()?->id,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'payment_terms' => $validated['payment_terms'] ?? 14,
                'subtotal' => $subtotal,
                'vat_amount' => $totalVat,
                'total' => $total,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'vat_reverse_charged' => $reverseCharged,
                'prices_include_vat' => $pricesIncludeVat,
            ]);

            // Delete old lines and create new ones
            $invoice->lines()->delete();

            foreach ($validated['lines'] as $line) {
                $invoice->lines()->create([
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'vat_rate' => $line['vat_rate'],
                    'total' => round($line['quantity'] * $line['unit_price'], 2),
                ]);
            }
        });

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Factuur succesvol bijgewerkt!');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Factuur verwijderd!');
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load('customer', 'lines', 'template');

        // Gekozen template, anders de standaardtemplate van het account
        $template = $invoice->template ?? InvoiceTemplate::getDefault();
        if ($template) {
            $pdfGenerator = app(\App\Services\InvoicePdfGenerator::class);
            $data = $this->prepareInvoiceData($invoice);
            $pdf = $pdfGenerator->generateFromTemplate($template, $data);
        } else {
            $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        }

        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    public function preview(Invoice $invoice)
    {
        $invoice->load('customer', 'lines', 'template');

        // Gekozen template, anders de standaardtemplate van het account
        $template = $invoice->template ?? InvoiceTemplate::getDefault();
        if ($template) {
            $pdfGenerator = app(\App\Services\InvoicePdfGenerator::class);
            $data = $this->prepareInvoiceData($invoice);
            $pdf = $pdfGenerator->generateFromTemplate($template, $data);
        } else {
            $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        }

        return $pdf->stream($invoice->invoice_number . '.pdf');
    }

    /**
     * Verstuur de factuur als PDF-bijlage via de gekoppelde mailverbinding
     * (Google Workspace / Microsoft 365) van de ingelogde gebruiker.
     */
    public function sendEmail(Invoice $invoice, \App\Services\CustomerMailService $mailer)
    {
        $invoice->load('customer', 'lines', 'template');

        $account = auth()->user()->activeMailAccount();
        if (!$account) {
            return back()->with('warning', 'Geen mailverbinding gevonden. Koppel eerst een account via Instellingen → E-mailverbindingen.');
        }

        $customer = $invoice->customer;
        if (empty($customer->email)) {
            return back()->with('warning', 'Deze klant heeft geen e-mailadres.');
        }

        // PDF genereren (zelfde logica als de download)
        $template = $invoice->template ?? InvoiceTemplate::getDefault();
        if ($template) {
            $pdfGenerator = app(\App\Services\InvoicePdfGenerator::class);
            $pdf = $pdfGenerator->generateFromTemplate($template, $this->prepareInvoiceData($invoice));
        } else {
            $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        }

        // Onderwerp en tekst uit de opmaakbare e-mailtekst (Instellingen)
        $composed = app(\App\Services\DocumentEmailComposer::class)->forInvoice($invoice);

        $sent = $mailer->send(
            $account,
            $customer->email,
            $composed['subject'],
            $composed['html'],
            $pdf->output(),
            $invoice->invoice_number . '.pdf',
        );

        if (!$sent) {
            return back()->with('warning', 'Versturen via ' . $account->from_email . ' is mislukt. Controleer je mailverbinding bij Instellingen → E-mailverbindingen.');
        }

        // Concept automatisch op Verzonden zetten.
        if ($invoice->status === 'draft') {
            $invoice->update(['status' => 'sent', 'sent_at' => now()]);
        }

        return back()->with('success', 'Factuur ' . $invoice->invoice_number . ' is per e-mail verstuurd naar ' . $customer->email . ' via ' . $account->from_email . '.');
    }

    /**
     * Prepare invoice data for template rendering.
     */
    private function prepareInvoiceData(Invoice $invoice): array
    {
        $company = \App\Models\CompanySetting::get();
        $appSettings = AppSetting::get();

        // Kredietbeperkingstoeslag (over totaal incl. btw, zonder btw over
        // de toeslag — conform Belastingdienst). Alleen als ingeschakeld;
        // anders blijven de velden leeg en rendert de template ze niet.
        $creditSurchargeData = [];
        if ($appSettings->credit_surcharge_enabled) {
            $surcharge = $appSettings->creditSurchargeAmount((float) $invoice->total);
            $creditSurchargeData = [
                'credit_surcharge'         => '€ ' . number_format($surcharge, 2, ',', '.'),
                'credit_surcharge_percent' => $appSettings->credit_surcharge_percent . '%',
                'total_with_surcharge'     => '€ ' . number_format($invoice->total + $surcharge, 2, ',', '.'),
            ];
        }

        // Referentie: het offertenummer als deze factuur uit een offerte komt
        $sourceQuote = \App\Models\Quote::where('converted_invoice_id', $invoice->id)->first();

        // Betalingsvoorwaarden: eigen voettekst, anders nette standaardtekst
        $paymentTerms = trim((string) ($company->invoice_footer ?? '')) !== ''
            ? $company->invoice_footer
            : 'Wij verzoeken u vriendelijk het totaalbedrag vóór ' . $invoice->due_date->format('d-m-Y')
                . ' over te maken' . (($company->iban ?? '') !== '' ? ' op ' . $company->iban : '')
                . ' o.v.v. factuurnummer ' . $invoice->invoice_number . '.';

        return $creditSurchargeData + [
            // Invoice data
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date->format('d-m-Y'),
            'due_date' => $invoice->due_date->format('d-m-Y'),
            'invoice_reference' => $sourceQuote ? 'Offerte ' . $sourceQuote->quote_number : '',

            // Customer data (customer_* + client_* aliassen voor template-compatibiliteit)
            'customer_name' => $invoice->customer->name,
            'customer_company' => $invoice->customer->company_name ?? '',
            'customer_address' => $invoice->customer->address ?? '',
            'customer_city' => $invoice->customer->city ?? '',
            'customer_postal_code' => $invoice->customer->postal_code ?? '',
            'customer_email' => $invoice->customer->email ?? '',
            'customer_phone' => $invoice->customer->phone ?? '',
            'client_name' => $invoice->customer->name,
            'client_address' => $invoice->customer->address ?? '',
            'client_postal_code' => $invoice->customer->postal_code ?? '',
            'client_city' => $invoice->customer->city ?? '',
            'client_email' => $invoice->customer->email ?? '',

            // Company data
            'company_name' => $company->company_name ?? '',
            'company_address' => $company->address ?? '',
            'company_postal_code' => $company->postal_code ?? '',
            'company_city' => $company->city ?? '',
            'company_country' => $company->country ?? '',
            'company_phone' => $company->phone ?? '',
            'company_email' => $company->email ?? '',
            'company_website' => $company->website ?? '',
            'company_kvk' => $company->kvk_number ?? '',
            'company_vat' => $company->vat_number ?? '',
            'company_iban' => $company->iban ?? '',
            'company_bic' => $company->bic ?? '',
            'company_bank' => $company->bank_name ?? '',

            // BTW verlegd: PDF toont geen BTW-kolommen/-bedrag, alle totalen zijn excl. BTW.
            // De verplichte vermelding wordt altijd op de PDF gezet (los van 'notes').
            'vat_reverse_charged' => (bool) $invoice->vat_reverse_charged,
            'reverse_charge_note' => $invoice->vat_reverse_charged
                ? 'BTW verlegd naar BTW-nummer afnemer'
                    . (trim((string) $invoice->customer->vat_number) !== '' ? ': ' . trim($invoice->customer->vat_number) : '') . '.'
                : '',

            // Amounts (+ tax alias voor template-compatibiliteit).
            // Bij verlegd is vat_amount 0 en total == subtotal; het BTW-bedrag
            // wordt leeggelaten zodat er geen BTW op de factuur verschijnt.
            'subtotal' => '€ ' . number_format($invoice->subtotal, 2, ',', '.'),
            'vat_amount' => $invoice->vat_reverse_charged ? '' : '€ ' . number_format($invoice->vat_amount, 2, ',', '.'),
            'tax' => $invoice->vat_reverse_charged ? '' : '€ ' . number_format($invoice->vat_amount, 2, ',', '.'),
            'total' => '€ ' . number_format($invoice->total, 2, ',', '.'),

            // Notes & items
            'notes' => $invoice->notes ?? '',
            'payment_terms' => $paymentTerms,
            'thank_you' => '',
            'invoice_footer' => $company->invoice_footer ?? '',
            // Bedragen op de regels staan zoals ingevoerd (excl. of incl. BTW);
            // de BTW per regel wordt daaruit afgeleid.
            'prices_include_vat' => (bool) $invoice->prices_include_vat,
            'items_table' => $invoice->lines->map(function ($line) use ($invoice) {
                $amounts = VatCalculator::line(
                    (float) $line->quantity,
                    (float) $line->unit_price,
                    (float) $line->vat_rate,
                    (bool) $invoice->prices_include_vat,
                    (bool) $invoice->vat_reverse_charged
                );

                return [
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'price' => $line->unit_price,
                    'vat_rate' => $line->vat_rate,
                    'vat_total' => $amounts['vat'],
                    'total' => $invoice->prices_include_vat ? $amounts['incl'] : $amounts['excl'],
                ];
            })->toArray(),
        ];
    }

    public function print(Invoice $invoice)
    {
        $invoice->load('customer', 'lines', 'template');

        // Exact dezelfde PDF als de download/preview, maar zonder
        // achtergrond (voor printen op voorbedrukt briefpapier).
        // Het logo blijft wél staan.
        $template = $invoice->template ?? InvoiceTemplate::getDefault();
        if ($template) {
            $pdfGenerator = app(\App\Services\InvoicePdfGenerator::class);
            $pdfGenerator->withBackground = false;
            $pdf = $pdfGenerator->generateFromTemplate($template, $this->prepareInvoiceData($invoice));
        } else {
            $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        }

        return $pdf->stream($invoice->invoice_number . '-print.pdf');
    }

    public function markSent(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'sent_date' => 'required|date',
        ]);

        $invoice->update([
            'status' => 'sent',
            'sent_at' => $validated['sent_date'],
        ]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Factuur gemarkeerd als verzonden!');
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'paid_date' => 'required|date',
        ]);

        $invoice->update([
            'status' => 'paid',
            'paid_at' => $validated['paid_date'],
        ]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Factuur gemarkeerd als betaald!');
    }

    /**
     * Bulk-status: meerdere facturen tegelijk van status wisselen.
     * Tenant-scope op Invoice beperkt de ids automatisch tot eigen facturen.
     */
    public function bulkStatus(Request $request)
    {
        $validated = $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
        ]);

        $status = $validated['status'];
        $invoices = Invoice::whereIn('id', $validated['ids'])->get();

        foreach ($invoices as $invoice) {
            $invoice->update([
                'status'  => $status,
                // Zelfde datumgedrag als de losse acties: verzonden/betaald
                // krijgen een datum als die er nog niet is; terug naar concept
                // maakt ze weer leeg.
                'sent_at' => match ($status) {
                    'draft' => null,
                    'sent', 'overdue', 'paid' => $invoice->sent_at ?? now(),
                    default => $invoice->sent_at,
                },
                'paid_at' => match ($status) {
                    'paid' => $invoice->paid_at ?? now(),
                    'draft', 'sent', 'overdue' => null,
                    default => $invoice->paid_at,
                },
            ]);
        }

        $labels = ['draft' => 'Concept', 'sent' => 'Verzonden', 'paid' => 'Betaald', 'overdue' => 'Verlopen', 'cancelled' => 'Geannuleerd'];

        return back()->with('success', $invoices->count() . ' facturen bijgewerkt naar "' . $labels[$status] . '".');
    }

    public function duplicate(Invoice $invoice)
    {
        // Volgend factuurnummer (prefix + teller uit instellingen)
        $newInvoiceNumber = AppSetting::get()->nextInvoiceNumber();

        // Create duplicate invoice
        $newInvoice = $invoice->replicate();
        $newInvoice->invoice_number = $newInvoiceNumber;
        $newInvoice->status = 'draft';
        $newInvoice->sent_at = null;
        $newInvoice->paid_at = null;
        $newInvoice->invoice_date = now();
        $newInvoice->due_date = now()->addDays($invoice->payment_terms ?? 14);
        $newInvoice->save();

        // Duplicate invoice lines
        foreach ($invoice->lines as $line) {
            $newLine = $line->replicate();
            $newLine->invoice_id = $newInvoice->id;
            $newLine->save();
        }

        return redirect()
            ->route('invoices.edit', $newInvoice)
            ->with('success', 'Factuur gedupliceerd! Je kunt deze nu bewerken.');
    }
}
