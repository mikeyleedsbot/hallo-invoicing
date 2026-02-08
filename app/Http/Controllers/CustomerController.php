<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::latest()->paginate(15);
        return view('customers.index', compact('customers'));
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

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Klant succesvol verwijderd!');
    }
}
