<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Product;
use App\Models\InvoiceTemplate;
use App\Models\VatRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
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

        $invoices = $query->paginate(20)->withQueryString();

        return view('invoices.index', compact('invoices', 'filters', 'sort', 'direction'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $templates = InvoiceTemplate::orderBy('is_default', 'desc')->orderBy('name')->get();
        $defaultTemplate = InvoiceTemplate::where('is_default', true)->first();
        
        // Generate next invoice number met prefix uit instellingen
        $appSettings = AppSetting::get();
        $prefix = $appSettings->invoice_prefix ?? 'INV';
        $lastInvoice = Invoice::orderBy('id', 'desc')->first();
        if ($lastInvoice && preg_match('/(\d+)\s*$/', $lastInvoice->invoice_number, $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        } else {
            $nextNumber = $appSettings->invoice_number_start ?? 1;
        }
        $invoiceNumber = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

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
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.vat_rate' => 'required|numeric|min:0|max:100',
        ]);

        $reverseCharged = (bool) ($validated['vat_reverse_charged'] ?? false);

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

        DB::transaction(function () use ($validated, $reverseCharged) {
            // Calculate totals (originele vat_rates blijven op de regels staan,
            // maar als BTW verlegd is wordt er geen BTW berekend op het totaal)
            $subtotal = 0;
            $totalVat = 0;

            foreach ($validated['lines'] as $line) {
                $lineTotal = $line['quantity'] * $line['unit_price'];
                $subtotal += $lineTotal;
                if (! $reverseCharged) {
                    $totalVat += $lineTotal * ($line['vat_rate'] / 100);
                }
            }

            $total = $subtotal + $totalVat;
            
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
                'notes' => $validated['notes'],
                'vat_reverse_charged' => $reverseCharged,
            ]);
            
            // Create invoice lines
            foreach ($validated['lines'] as $line) {
                $invoice->lines()->create([
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'vat_rate' => $line['vat_rate'],
                    'total' => $line['quantity'] * $line['unit_price'],
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
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.vat_rate' => 'required|numeric|min:0|max:100',
        ]);

        $reverseCharged = (bool) ($validated['vat_reverse_charged'] ?? false);

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

        DB::transaction(function () use ($validated, $invoice, $reverseCharged) {
            // Calculate totals (originele vat_rates blijven op de regels staan,
            // maar als BTW verlegd is wordt er geen BTW berekend op het totaal)
            $subtotal = 0;
            $totalVat = 0;

            foreach ($validated['lines'] as $line) {
                $lineTotal = $line['quantity'] * $line['unit_price'];
                $subtotal += $lineTotal;
                if (! $reverseCharged) {
                    $totalVat += $lineTotal * ($line['vat_rate'] / 100);
                }
            }

            $total = $subtotal + $totalVat;
            
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
                'notes' => $validated['notes'],
                'vat_reverse_charged' => $reverseCharged,
            ]);
            
            // Delete old lines and create new ones
            $invoice->lines()->delete();
            
            foreach ($validated['lines'] as $line) {
                $invoice->lines()->create([
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'vat_rate' => $line['vat_rate'],
                    'total' => $line['quantity'] * $line['unit_price'],
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
        
        // Use template if selected, otherwise fall back to default view
        if ($invoice->template) {
            $pdfGenerator = app(\App\Services\InvoicePdfGenerator::class);
            $data = $this->prepareInvoiceData($invoice);
            $pdf = $pdfGenerator->generateFromTemplate($invoice->template, $data);
        } else {
            $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        }
        
        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    public function preview(Invoice $invoice)
    {
        $invoice->load('customer', 'lines', 'template');
        
        // Use template if selected, otherwise fall back to default view
        if ($invoice->template) {
            $pdfGenerator = app(\App\Services\InvoicePdfGenerator::class);
            $data = $this->prepareInvoiceData($invoice);
            $pdf = $pdfGenerator->generateFromTemplate($invoice->template, $data);
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
        if ($invoice->template) {
            $pdfGenerator = app(\App\Services\InvoicePdfGenerator::class);
            $pdf = $pdfGenerator->generateFromTemplate($invoice->template, $this->prepareInvoiceData($invoice));
        } else {
            $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        }

        $sender    = auth()->user()->company_name ?: auth()->user()->name;
        $salutation = $customer->contact_person ?: $customer->name;
        $amount    = number_format($invoice->total, 2, ',', '.');
        $dueDate   = $invoice->due_date?->format('d-m-Y');

        $subject = 'Factuur ' . $invoice->invoice_number . ' van ' . $sender;
        $html    = view('emails.document', [
            'salutation' => $salutation,
            'lines'      => array_filter([
                'Bijgaand ontvang je factuur <strong>' . e($invoice->invoice_number) . '</strong> voor een bedrag van <strong>€ ' . $amount . '</strong>.',
                $dueDate ? 'We verzoeken je het bedrag over te maken vóór <strong>' . $dueDate . '</strong>.' : null,
            ]),
            'sender'     => $sender,
        ])->render();

        $sent = $mailer->send(
            $account,
            $customer->email,
            $subject,
            $html,
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
        
        return [
            // Invoice data
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date->format('d-m-Y'),
            'due_date' => $invoice->due_date->format('d-m-Y'),
            
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
            
            // Amounts (+ tax alias voor template-compatibiliteit)
            'subtotal' => '€ ' . number_format($invoice->subtotal, 2, ',', '.'),
            'vat_amount' => '€ ' . number_format($invoice->vat_amount, 2, ',', '.'),
            'tax' => '€ ' . number_format($invoice->vat_amount, 2, ',', '.'),
            'total' => '€ ' . number_format($invoice->total, 2, ',', '.'),

            // Notes & items
            'notes' => $invoice->notes ?? '',
            'payment_terms' => $company->invoice_footer ?? '',
            'thank_you' => '',
            'invoice_footer' => $company->invoice_footer ?? '',
            'items_table' => $invoice->lines->map(function($line) {
                return [
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'price' => $line->unit_price,
                    'vat_rate' => $line->vat_rate,
                ];
            })->toArray(),
        ];
    }

    public function print(Invoice $invoice)
    {
        $invoice->load('customer', 'lines');
        
        return view('invoices.print', compact('invoice'));
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

    public function duplicate(Invoice $invoice)
    {
        // Generate new invoice number met prefix uit instellingen
        $appSettings = AppSetting::get();
        $prefix = $appSettings->invoice_prefix ?? 'INV';
        $lastInvoice = Invoice::orderBy('id', 'desc')->first();
        if ($lastInvoice && preg_match('/(\d+)\s*$/', $lastInvoice->invoice_number, $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        } else {
            $nextNumber = $appSettings->invoice_number_start ?? 1;
        }
        $newInvoiceNumber = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

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
