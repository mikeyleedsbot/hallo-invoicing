<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        // Sorteerbare kolommen
        $allowedSorts = ['name', 'company_name', 'email', 'phone', 'address', 'created_at', 'invoices_count'];
        $sort = in_array($request->query('sort'), $allowedSorts) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $search = trim((string) $request->query('search', ''));

        $query = Customer::query()
            ->where(function ($query) {
                $query->whereNotNull('name')->where('name', '!=', '')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('company_name')->where('company_name', '!=', '');
                    });
            })
            ->orderBy($sort, $direction);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('postal_code', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(15)->withQueryString();

        return view('customers.index', compact('customers', 'sort', 'direction', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|min:3',
            'email' => 'nullable|email',
            'phone' => 'nullable',
            'company_name' => 'nullable',
            'vat_number' => 'nullable',
            'address' => 'nullable',
            'city' => 'nullable',
            'postal_code' => 'nullable',
            'country' => 'required',
        ]);

        Customer::create($validated);

        return redirect()->route('customers.index')->with('success', 'Klant succesvol toegevoegd!');
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|min:3',
            'email' => 'nullable|email',
            'phone' => 'nullable',
            'company_name' => 'nullable',
            'vat_number' => 'nullable',
            'address' => 'nullable',
            'city' => 'nullable',
            'postal_code' => 'nullable',
            'country' => 'required',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'Klant succesvol bijgewerkt!');
    }

    public function invoices(Customer $customer)
    {
        $invoices = $customer->invoices()
            ->orderBy('invoice_date', 'desc')
            ->take(10)
            ->get(['id', 'invoice_number', 'invoice_date', 'due_date', 'total', 'status'])
            ->map(fn ($i) => array_merge($i->toArray(), [
                'status_label' => $i->status_label,
                'status_color' => $i->status_color,
            ]));

        return response()->json($invoices);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Klant succesvol verwijderd!');
    }
}
