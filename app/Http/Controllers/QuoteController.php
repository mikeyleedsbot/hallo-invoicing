<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\VatRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class QuoteController extends Controller
{
    public function index()
    {
        $quotes = Quote::with('customer')
            ->orderBy('quote_date', 'desc')
            ->paginate(20);
        
        return view('quotes.index', compact('quotes'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $templates = InvoiceTemplate::orderBy('is_default', 'desc')->orderBy('name')->get();
        $defaultTemplate = InvoiceTemplate::where('is_default', true)->first();
        
        // Generate next quote number
        $lastQuote = Quote::orderBy('id', 'desc')->first();
        $nextNumber = $lastQuote 
            ? (int)substr($lastQuote->quote_number, 3) + 1 
            : 1;
        $quoteNumber = 'OFF' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        
        $vatRates   = VatRate::ordered()->get();
        $defaultVat = (int)($vatRates->firstWhere('is_default', true)?->rate ?? 21);

        return view('quotes.create', compact('customers', 'products', 'templates', 'defaultTemplate', 'quoteNumber', 'vatRates', 'defaultVat'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'template_id' => 'nullable|exists:invoice_templates,id',
            'quote_number' => 'required|unique:quotes',
            'quote_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quote_date',
            'valid_days' => 'nullable|integer',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
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
            'customer_id' => 'required|exists:customers,id',
            'template_id' => 'nullable|exists:invoice_templates,id',
            'quote_number' => 'required|unique:quotes,quote_number,' . $quote->id,
            'quote_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quote_date',
            'valid_days' => 'nullable|integer',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,accepted,rejected,expired',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
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
        
        // Use template if selected, otherwise fall back to default view
        if ($quote->template) {
            $pdfGenerator = app(\App\Services\InvoicePdfGenerator::class);
            $data = $this->prepareQuoteData($quote);
            $pdf = $pdfGenerator->generateFromTemplate($quote->template, $data);
        } else {
            $pdf = Pdf::loadView('quotes.pdf', compact('quote'));
        }
        
        return $pdf->download($quote->quote_number . '.pdf');
    }

    public function preview(Quote $quote)
    {
        $quote->load('customer', 'lines', 'template');
        
        // Use template if selected, otherwise fall back to default view
        if ($quote->template) {
            $pdfGenerator = app(\App\Services\InvoicePdfGenerator::class);
            $data = $this->prepareQuoteData($quote);
            $pdf = $pdfGenerator->generateFromTemplate($quote->template, $data);
        } else {
            $pdf = Pdf::loadView('quotes.pdf', compact('quote'));
        }
        
        return $pdf->stream($quote->quote_number . '.pdf');
    }

    public function print(Quote $quote)
    {
        $quote->load('customer', 'lines');
        
        return view('quotes.print', compact('quote'));
    }

    public function convertToInvoice(Quote $quote)
    {
        $invoice = DB::transaction(function () use ($quote) {
            // Generate invoice number
            $lastInvoice = Invoice::orderBy('id', 'desc')->first();
            $nextNumber = $lastInvoice 
                ? (int)substr($lastInvoice->invoice_number, 3) + 1 
                : 1;
            $invoiceNumber = 'INV' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

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
        
        return [
            // Quote data
            'quote_number' => $quote->quote_number,
            'invoice_number' => $quote->quote_number, // Alias for templates that use invoice_number
            'quote_date' => $quote->quote_date->format('d-m-Y'),
            'invoice_date' => $quote->quote_date->format('d-m-Y'), // Alias
            'valid_until' => $quote->valid_until->format('d-m-Y'),
            'due_date' => $quote->valid_until->format('d-m-Y'), // Alias
            
            // Customer data
            'customer_name' => $quote->customer->name,
            'customer_company' => $quote->customer->company_name ?? '',
            'customer_address' => $quote->customer->address ?? '',
            'customer_city' => $quote->customer->city ?? '',
            'customer_postal_code' => $quote->customer->postal_code ?? '',
            'customer_email' => $quote->customer->email ?? '',
            'customer_phone' => $quote->customer->phone ?? '',
            
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
            
            // Amounts
            'subtotal' => '€ ' . number_format($quote->subtotal, 2, ',', '.'),
            'vat_amount' => '€ ' . number_format($quote->vat_amount, 2, ',', '.'),
            'total' => '€ ' . number_format($quote->total, 2, ',', '.'),
            
            // Notes & items
            'notes' => $quote->notes ?? '',
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
