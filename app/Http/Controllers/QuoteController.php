<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Quote;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\VatRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'search'    => trim((string) $request->query('search', '')),
            'status'    => (string) $request->query('status', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to'   => (string) $request->query('date_to', ''),
        ];

        // Sorteerbare kolommen
        $allowedSorts = ['quote_number', 'customer_name', 'quote_date', 'valid_until', 'total', 'status'];
        $sort = in_array($request->query('sort'), $allowedSorts) ? $request->query('sort') : 'quote_date';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $query = Quote::with('customer');

        // Sorteren op klantnaam vereist een join
        if ($sort === 'customer_name') {
            $query->join('customers', 'quotes.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $direction)
                  ->select('quotes.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('quotes.quote_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($c) use ($search) {
                      $c->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($filters['status'] !== '') {
            $query->where('quotes.status', $filters['status']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('quotes.quote_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('quotes.quote_date', '<=', $filters['date_to']);
        }

        $quotes = $query->paginate(20)->withQueryString();

        return view('quotes.index', compact('quotes', 'filters', 'sort', 'direction'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $templates = InvoiceTemplate::orderBy('is_default', 'desc')->orderBy('name')->get();
        $defaultTemplate = InvoiceTemplate::where('is_default', true)->first();

        // Volgend offertenummer (prefix + teller uit instellingen)
        $quoteNumber = AppSetting::get()->nextQuoteNumber();

        $vatRates   = VatRate::ordered()->get();
        $defaultVat = (int)($vatRates->firstWhere('is_default', true)?->rate ?? 21);

        return view('quotes.create', compact('customers', 'products', 'templates', 'defaultTemplate', 'quoteNumber', 'vatRates', 'defaultVat'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('user_id', auth()->id())],
            'template_id' => ['nullable', Rule::exists('invoice_templates', 'id')->where('user_id', auth()->id())],
            'quote_number' => ['required', Rule::unique('quotes')->where('user_id', auth()->id())],
            'quote_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quote_date',
            'valid_days' => 'nullable|integer',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric',
            'lines.*.unit_price' => 'required|numeric',
            'lines.*.vat_rate' => 'required|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated) {
            // Calculate totals
            $subtotal = 0;
            $totalVat = 0;

            foreach ($validated['lines'] as $line) {
                $lineTotal = $line['quantity'] * $line['unit_price'];
                $lineVat = $lineTotal * ($line['vat_rate'] / 100);
                $subtotal += $lineTotal;
                $totalVat += $lineVat;
            }

            $total = $subtotal + $totalVat;

            // Create quote
            $quote = Quote::create([
                'customer_id' => $validated['customer_id'],
                'template_id' => $validated['template_id'] ?? InvoiceTemplate::where('is_default', true)->first()?->id,
                'quote_number' => $validated['quote_number'],
                'quote_date' => $validated['quote_date'],
                'valid_until' => $validated['valid_until'],
                'valid_days' => $validated['valid_days'] ?? 30,
                'subtotal' => $subtotal,
                'vat_amount' => $totalVat,
                'total' => $total,
                'status' => 'draft',
                'notes' => $validated['notes'],
            ]);

            // Create quote lines
            foreach ($validated['lines'] as $line) {
                $quote->lines()->create([
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'vat_rate' => $line['vat_rate'],
                    'total' => $line['quantity'] * $line['unit_price'],
                ]);
            }
        });

        return redirect()
            ->route('quotes.index')
            ->with('success', 'Offerte succesvol aangemaakt!');
    }

    public function show(Quote $quote)
    {
        $quote->load('customer', 'lines', 'convertedInvoice');
        return view('quotes.show', compact('quote'));
    }

    public function edit(Quote $quote)
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $templates = InvoiceTemplate::orderBy('is_default', 'desc')->orderBy('name')->get();
        $quote->load('lines');

        $vatRates   = VatRate::ordered()->get();
        $defaultVat = (int)($vatRates->firstWhere('is_default', true)?->rate ?? 21);

        return view('quotes.edit', compact('quote', 'customers', 'products', 'templates', 'vatRates', 'defaultVat'));
    }

    public function update(Request $request, Quote $quote)
    {
        $validated = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('user_id', auth()->id())],
            'template_id' => ['nullable', Rule::exists('invoice_templates', 'id')->where('user_id', auth()->id())],
            'quote_number' => ['required', Rule::unique('quotes')->where('user_id', auth()->id())->ignore($quote->id)],
            'quote_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quote_date',
            'valid_days' => 'nullable|integer',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,accepted,rejected,expired',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric',
            'lines.*.unit_price' => 'required|numeric',
            'lines.*.vat_rate' => 'required|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated, $quote) {
            // Calculate totals
            $subtotal = 0;
            $totalVat = 0;

            foreach ($validated['lines'] as $line) {
                $lineTotal = $line['quantity'] * $line['unit_price'];
                $lineVat = $lineTotal * ($line['vat_rate'] / 100);
                $subtotal += $lineTotal;
                $totalVat += $lineVat;
            }

            $total = $subtotal + $totalVat;

            // Update quote
            $quote->update([
                'customer_id' => $validated['customer_id'],
                'template_id' => $validated['template_id'] ?? InvoiceTemplate::where('is_default', true)->first()?->id,
                'quote_number' => $validated['quote_number'],
                'quote_date' => $validated['quote_date'],
                'valid_until' => $validated['valid_until'],
                'valid_days' => $validated['valid_days'] ?? 30,
                'subtotal' => $subtotal,
                'vat_amount' => $totalVat,
                'total' => $total,
                'status' => $validated['status'],
                'notes' => $validated['notes'],
            ]);

            // Delete old lines and create new ones
            $quote->lines()->delete();

            foreach ($validated['lines'] as $line) {
                $quote->lines()->create([
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'vat_rate' => $line['vat_rate'],
                    'total' => $line['quantity'] * $line['unit_price'],
                ]);
            }
        });

        return redirect()
            ->route('quotes.index')
            ->with('success', 'Offerte succesvol bijgewerkt!');
    }

    public function destroy(Quote $quote)
    {
        $quote->delete();

        return redirect()
            ->route('quotes.index')
            ->with('success', 'Offerte verwijderd!');
    }

    public function pdf(Quote $quote)
    {
        $quote->load('customer', 'lines', 'template');

        // Gekozen template, anders de standaardtemplate van het account
        $template = $quote->template ?? InvoiceTemplate::getDefault();
        if ($template) {
            $pdfGenerator = app(\App\Services\InvoicePdfGenerator::class);
            $data = $this->prepareQuoteData($quote);
            $pdf = $pdfGenerator->generateFromTemplate($template, $data);
        } else {
            $pdf = Pdf::loadView('quotes.pdf', compact('quote'));
        }

        return $pdf->download($quote->quote_number . '.pdf');
    }

    public function preview(Quote $quote)
    {
        $quote->load('customer', 'lines', 'template');

        // Gekozen template, anders de standaardtemplate van het account
        $template = $quote->template ?? InvoiceTemplate::getDefault();
        if ($template) {
            $pdfGenerator = app(\App\Services\InvoicePdfGenerator::class);
            $data = $this->prepareQuoteData($quote);
            $pdf = $pdfGenerator->generateFromTemplate($template, $data);
        } else {
            $pdf = Pdf::loadView('quotes.pdf', compact('quote'));
        }

        return $pdf->stream($quote->quote_number . '.pdf');
    }

    /**
     * Verstuur de offerte als PDF-bijlage via de gekoppelde mailverbinding
     * (Google Workspace / Microsoft 365) van de ingelogde gebruiker.
     */
    public function sendEmail(Quote $quote, \App\Services\CustomerMailService $mailer)
    {
        $quote->load('customer', 'lines', 'template');

        $account = auth()->user()->activeMailAccount();
        if (!$account) {
            return back()->with('warning', 'Geen mailverbinding gevonden. Koppel eerst een account via Instellingen → E-mailverbindingen.');
        }

        $customer = $quote->customer;
        if (empty($customer->email)) {
            return back()->with('warning', 'Deze klant heeft geen e-mailadres.');
        }

        // PDF genereren (zelfde logica als de download)
        $template = $quote->template ?? InvoiceTemplate::getDefault();
        if ($template) {
            $pdfGenerator = app(\App\Services\InvoicePdfGenerator::class);
            $pdf = $pdfGenerator->generateFromTemplate($template, $this->prepareQuoteData($quote));
        } else {
            $pdf = Pdf::loadView('quotes.pdf', compact('quote'));
        }

        // Onderwerp en tekst uit de opmaakbare e-mailtekst (Instellingen)
        $composed = app(\App\Services\DocumentEmailComposer::class)->forQuote($quote);

        $sent = $mailer->send(
            $account,
            $customer->email,
            $composed['subject'],
            $composed['html'],
            $pdf->output(),
            $quote->quote_number . '.pdf',
        );

        if (!$sent) {
            return back()->with('warning', 'Versturen via ' . $account->from_email . ' is mislukt. Controleer je mailverbinding bij Instellingen → E-mailverbindingen.');
        }

        // Concept automatisch op Verzonden zetten.
        if ($quote->status === 'draft') {
            $quote->update(['status' => 'sent', 'sent_at' => now()]);
        }

        return back()->with('success', 'Offerte ' . $quote->quote_number . ' is per e-mail verstuurd naar ' . $customer->email . ' via ' . $account->from_email . '.');
    }

    public function print(Quote $quote)
    {
        $quote->load('customer', 'lines', 'template');

        // Exact dezelfde PDF als de download/preview, maar zonder
        // achtergrond (voor printen op voorbedrukt briefpapier).
        // Het logo blijft wél staan.
        $template = $quote->template ?? InvoiceTemplate::getDefault();
        if ($template) {
            $pdfGenerator = app(\App\Services\InvoicePdfGenerator::class);
            $pdfGenerator->withBackground = false;
            $pdf = $pdfGenerator->generateFromTemplate($template, $this->prepareQuoteData($quote));
        } else {
            $pdf = Pdf::loadView('quotes.pdf', compact('quote'));
        }

        return $pdf->stream($quote->quote_number . '-print.pdf');
    }

    public function convertToInvoice(Quote $quote)
    {
        $invoice = DB::transaction(function () use ($quote) {
            // Volgend factuurnummer (prefix + teller uit instellingen)
            $invoiceNumber = AppSetting::get()->nextInvoiceNumber();

            // Create invoice from quote
            $invoice = Invoice::create([
                'customer_id' => $quote->customer_id,
                'template_id' => $quote->template_id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => now(),
                'due_date' => now()->addDays(14),
                'payment_terms' => 14,
                'subtotal' => $quote->subtotal,
                'vat_amount' => $quote->vat_amount,
                'total' => $quote->total,
                'status' => 'draft',
                'notes' => $quote->notes,
            ]);

            // Copy quote lines to invoice lines
            foreach ($quote->lines as $line) {
                $invoice->lines()->create([
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'vat_rate' => $line->vat_rate,
                    'total' => $line->total,
                ]);
            }

            // Mark quote as converted (only if not already converted)
            if (!$quote->converted_invoice_id) {
                $quote->update([
                    'converted_invoice_id' => $invoice->id,
                    'converted_at' => now(),
                ]);
            }

            return $invoice;
        });

        return redirect()
            ->route('invoices.edit', $invoice)
            ->with('success', 'Factuur aangemaakt op basis van offerte! Pas aan indien nodig en sla op.');
    }

    /**
     * Prepare quote data for template rendering.
     */
    private function prepareQuoteData(Quote $quote): array
    {
        $company = \App\Models\CompanySetting::get();

        // Betalingsvoorwaarden-veld op offertes: eigen voettekst, anders geldigheid
        $paymentTerms = trim((string) ($company->invoice_footer ?? '')) !== ''
            ? $company->invoice_footer
            : 'Deze offerte is geldig tot en met ' . $quote->valid_until->format('d-m-Y') . '.';

        return [
            // Quote data
            'quote_number' => $quote->quote_number,
            'invoice_number' => $quote->quote_number, // Alias for templates that use invoice_number
            'quote_date' => $quote->quote_date->format('d-m-Y'),
            'invoice_date' => $quote->quote_date->format('d-m-Y'), // Alias
            'valid_until' => $quote->valid_until->format('d-m-Y'),
            'due_date' => $quote->valid_until->format('d-m-Y'), // Alias
            'invoice_reference' => '',

            // Customer data (customer_* + client_* aliassen voor template-compatibiliteit)
            'customer_name' => $quote->customer->name,
            'customer_company' => $quote->customer->company_name ?? '',
            'customer_address' => $quote->customer->address ?? '',
            'customer_city' => $quote->customer->city ?? '',
            'customer_postal_code' => $quote->customer->postal_code ?? '',
            'customer_email' => $quote->customer->email ?? '',
            'customer_phone' => $quote->customer->phone ?? '',
            'client_name' => $quote->customer->name,
            'client_address' => $quote->customer->address ?? '',
            'client_postal_code' => $quote->customer->postal_code ?? '',
            'client_city' => $quote->customer->city ?? '',
            'client_email' => $quote->customer->email ?? '',

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

            // Amounts (+ tax alias voor template-compatibiliteit)
            'subtotal' => '€ ' . number_format($quote->subtotal, 2, ',', '.'),
            'vat_amount' => '€ ' . number_format($quote->vat_amount, 2, ',', '.'),
            'tax' => '€ ' . number_format($quote->vat_amount, 2, ',', '.'),
            'total' => '€ ' . number_format($quote->total, 2, ',', '.'),

            // Notes & items
            'notes' => $quote->notes ?? '',
            'payment_terms' => $paymentTerms,
            'thank_you' => '',
            'invoice_footer' => $company->invoice_footer ?? '',
            'items_table' => $quote->lines->map(function($line) {
                return [
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'price' => $line->unit_price,
                    'vat_rate' => $line->vat_rate,
                ];
            })->toArray(),
        ];
    }

    public function markSent(Request $request, Quote $quote)
    {
        $validated = $request->validate([
            'sent_date' => 'required|date',
        ]);

        $quote->update([
            'status' => 'sent',
            'sent_at' => $validated['sent_date'],
        ]);

        return redirect()
            ->route('quotes.show', $quote)
            ->with('success', 'Offerte gemarkeerd als verzonden!');
    }
}
