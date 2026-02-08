<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;
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
        
        // Generate next quote number
        $lastQuote = Quote::orderBy('id', 'desc')->first();
        $nextNumber = $lastQuote 
            ? (int)substr($lastQuote->quote_number, 3) + 1 
            : 1;
        $quoteNumber = 'OFF' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        
        return view('quotes.create', compact('customers', 'products', 'quoteNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
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
        $quote->load('lines');
        
        return view('quotes.edit', compact('quote', 'customers', 'products'));
    }

    public function update(Request $request, Quote $quote)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
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
        $quote->load('customer', 'lines');
        
        $pdf = Pdf::loadView('quotes.pdf', compact('quote'));
        
        return $pdf->download($quote->quote_number . '.pdf');
    }

    public function preview(Quote $quote)
    {
        $quote->load('customer', 'lines');
        
        $pdf = Pdf::loadView('quotes.pdf', compact('quote'));
        
        return $pdf->stream($quote->quote_number . '.pdf');
    }

    public function print(Quote $quote)
    {
        $quote->load('customer', 'lines');
        
        return view('quotes.print', compact('quote'));
    }

    public function convertToInvoice(Quote $quote)
    {
        // Check if already converted
        if ($quote->converted_invoice_id) {
            return redirect()
                ->route('quotes.show', $quote)
                ->with('error', 'Deze offerte is al omgezet naar een factuur!');
        }

        // Check if accepted
        if ($quote->status !== 'accepted') {
            return redirect()
                ->route('quotes.show', $quote)
                ->with('error', 'Alleen geaccepteerde offertes kunnen worden omgezet naar een factuur!');
        }

        DB::transaction(function () use ($quote) {
            // Generate invoice number
            $lastInvoice = Invoice::orderBy('id', 'desc')->first();
            $nextNumber = $lastInvoice 
                ? (int)substr($lastInvoice->invoice_number, 3) + 1 
                : 1;
            $invoiceNumber = 'INV' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            // Create invoice from quote
            $invoice = Invoice::create([
                'customer_id' => $quote->customer_id,
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

            // Mark quote as converted
            $quote->update([
                'converted_invoice_id' => $invoice->id,
                'converted_at' => now(),
            ]);
        });

        return redirect()
            ->route('invoices.show', $quote->convertedInvoice)
            ->with('success', 'Offerte succesvol omgezet naar factuur!');
    }
}
