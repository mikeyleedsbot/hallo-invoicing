<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AppSettingController extends Controller
{
    public function edit()
    {
        $settings = AppSetting::get();
        return view('settings.app', compact('settings'));
    }

    // --- Gebruikersbeheer (alleen admin) ---

    public function storeUser(Request $request)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::defaults(), 'confirmed'],
            'is_admin' => ['boolean'],
        ]);

        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $request->boolean('is_admin'),
        ]);

        return redirect()->route('settings.edit')->with('success', 'Gebruiker aangemaakt.');
    }

    public function updateUser(Request $request, User $user)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email,' . $user->id],
            'is_admin' => ['boolean'],
            'password' => ['nullable', Password::defaults(), 'confirmed'],
        ]);

        $user->name     = $validated['name'];
        $user->email    = $validated['email'];
        $user->is_admin = $request->boolean('is_admin');

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('settings.edit')->with('success', 'Gebruiker bijgewerkt.');
    }

    public function destroyUser(User $user)
    {
        abort_unless(Auth::user()->is_admin, 403);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Je kunt jezelf niet verwijderen.']);
        }

        $user->delete();
        return redirect()->route('settings.edit')->with('success', 'Gebruiker verwijderd.');
    }

    public function resetUserMfa(User $user)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $user->mfa_enabled      = false;
        $user->mfa_secret       = null;
        $user->mfa_confirmed_at = null;
        $user->save();

        return back()->with('success', 'MFA gereset voor ' . $user->name . '.');
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
