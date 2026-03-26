<?php

namespace App\Http\Controllers;

use App\Models\VatRate;
use Illuminate\Http\Request;

class VatRateController extends Controller
{
    public function index()
    {
        $vatRates = VatRate::ordered()->get();
        return view('vat-rates.index', compact('vatRates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $maxOrder = VatRate::max('sort_order') ?? 0;

        VatRate::create([
            'name'       => $validated['name'],
            'rate'       => $validated['rate'],
            'is_default' => false,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('vat-rates.index')->with('success', 'BTW tarief toegevoegd.');
    }

    public function update(Request $request, VatRate $vatRate)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $vatRate->update($validated);

        return redirect()->route('vat-rates.index')->with('success', 'BTW tarief bijgewerkt.');
    }

    public function setDefault(VatRate $vatRate)
    {
        VatRate::query()->update(['is_default' => false]);
        $vatRate->update(['is_default' => true]);

        return redirect()->route('vat-rates.index')->with('success', '"' . $vatRate->name . '" ingesteld als standaard.');
    }

    public function destroy(VatRate $vatRate)
    {
        if (VatRate::count() <= 1) {
            return back()->withErrors(['error' => 'Je moet minimaal één BTW tarief hebben.']);
        }

        if ($vatRate->is_default) {
            return back()->withErrors(['error' => 'Verwijder eerst het standaard-vinkje voordat je dit tarief verwijdert.']);
        }

        $vatRate->delete();
        return redirect()->route('vat-rates.index')->with('success', 'BTW tarief verwijderd.');
    }
}
