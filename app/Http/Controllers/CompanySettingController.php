<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use Illuminate\Http\Request;

class CompanySettingController extends Controller
{
    public function edit()
    {
        $settings = CompanySetting::get();
        return view('settings.company', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'required|email|max:255',
            'website' => 'nullable|url|max:255',
            'kvk_number' => 'nullable|string|max:50',
            'vat_number' => 'required|string|max:50',
            'iban' => 'required|string|max:50',
            'bic' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:100',
            'invoice_footer' => 'nullable|string|max:1000',
        ]);

        $settings = CompanySetting::get();
        $settings->update($validated);

        return redirect()
            ->route('company.edit')
            ->with('success', 'Bedrijfsgegevens succesvol bijgewerkt!');
    }
}
