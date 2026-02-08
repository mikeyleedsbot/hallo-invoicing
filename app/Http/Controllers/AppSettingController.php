<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    public function edit()
    {
        $settings = AppSetting::get();
        return view('settings.app', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'default_vat_rate' => 'required|numeric|min:0|max:100',
            'default_payment_terms' => 'required|integer|min:1',
            'quote_valid_days' => 'required|integer|min:1',
            'currency' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:5',
            'date_format' => 'required|string|max:20',
            'invoice_prefix' => 'required|string|max:10',
            'quote_prefix' => 'required|string|max:10',
        ]);

        $settings = AppSetting::get();
        $settings->update($validated);

        return redirect()
            ->route('settings.edit')
            ->with('success', 'Instellingen succesvol bijgewerkt!');
    }
}
