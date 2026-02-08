<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('customer')
            ->orderBy('invoice_date', 'desc')
            ->paginate(20);
        
        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        
        // Generate next invoice number
        $lastInvoice = Invoice::orderBy('id', 'desc')->first();
        $nextNumber = $lastInvoice 
            ? (int)substr($lastInvoice->invoice_number, 3) + 1 
            : 1;
        $invoiceNumber = 'INV' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        
        return view('invoices.create', compact('customers', 'products', 'invoiceNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_number' => 'required|unique:invoices',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'payment_terms' => 'nullable|integer',
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
            
            // Create invoice
            $invoice = Invoice::create([
                'customer_id' => $validated['customer_id'],
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'payment_terms' => $validated['payment_terms'] ?? 14,
                'subtotal' => $subtotal,
                'vat_amount' => $totalVat,
                'total' => $total,
                'status' => 'draft',
                'notes' => $validated['notes'],
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
        $invoice->load('lines');
        
        return view('invoices.edit', compact('invoice', 'customers', 'products'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'payment_terms' => 'nullable|integer',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.vat_rate' => 'required|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated, $invoice) {
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
            
            // Update invoice
            $invoice->update([
                'customer_id' => $validated['customer_id'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'payment_terms' => $validated['payment_terms'] ?? 14,
                'subtotal' => $subtotal,
                'vat_amount' => $totalVat,
                'total' => $total,
                'status' => $validated['status'],
                'notes' => $validated['notes'],
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
}
