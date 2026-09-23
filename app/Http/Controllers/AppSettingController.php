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
            'invoice_number_start' => ['required', 'regex:/^\\d{1,10}$/'],
            'quote_number_start' => ['required', 'regex:/^\\d{1,10}$/'],
            'credit_surcharge_enabled' => 'nullable|boolean',
            'credit_surcharge_percent' => 'required|integer|in:1,2,3,4,5',
            'invoice_email_subject' => 'nullable|string|max:255',
            'invoice_email_body'    => 'nullable|string|max:20000',
            'quote_email_subject'   => 'nullable|string|max:255',
            'quote_email_body'      => 'nullable|string|max:20000',
        ], [
            'required' => ':attribute is verplicht.',
            'integer'  => ':attribute moet een geheel getal zijn.',
            'numeric'  => ':attribute moet een getal zijn.',
            'min'      => ':attribute moet minimaal :min zijn.',
            'max'      => ':attribute mag maximaal :max zijn.',
            'in'       => ':attribute heeft een ongeldige waarde.',
            'string'   => ':attribute moet tekst zijn.',
            'boolean'  => ':attribute moet aan of uit zijn.',
            'regex'    => ':attribute mag alleen cijfers bevatten (maximaal 10).',
        ], [
            'default_vat_rate'         => 'Standaard BTW-tarief',
            'default_payment_terms'    => 'Standaard betalingstermijn',
            'quote_valid_days'         => 'Geldigheidsduur offerte',
            'currency'                 => 'Valuta',
            'currency_symbol'          => 'Valutasymbool',
            'date_format'              => 'Datumformaat',
            'invoice_prefix'           => 'Factuur prefix',
            'quote_prefix'             => 'Offerte prefix',
            'invoice_number_start'     => 'Factuurteller',
            'quote_number_start'       => 'Offerteteller',
            'credit_surcharge_enabled' => 'Kredietbeperking',
            'credit_surcharge_percent' => 'Kredietbeperkingspercentage',
            'invoice_email_subject'    => 'Onderwerp factuurmail',
            'invoice_email_body'       => 'Tekst factuurmail',
            'quote_email_subject'      => 'Onderwerp offertemail',
            'quote_email_body'         => 'Tekst offertemail',
        ]);

        // E-mailteksten opschonen: alleen veilige opmaak-tags toestaan en
        // event-handlers/javascript-URI's strippen.
        foreach (['invoice_email_body', 'quote_email_body'] as $field) {
            if (isset($validated[$field])) {
                $validated[$field] = $this->sanitizeEmailHtml($validated[$field]);
            }
        }

        // Lege e-mailteksten (alleen opmaak zonder inhoud) opslaan als NULL,
        // zodat de standaardtekst automatisch blijft gelden.
        foreach (['invoice_email_body', 'quote_email_body'] as $field) {
            if (isset($validated[$field]) && trim(strip_tags($validated[$field])) === '') {
                $validated[$field] = null;
            }
        }
        foreach (['invoice_email_subject', 'quote_email_subject'] as $field) {
            if (isset($validated[$field]) && trim($validated[$field]) === '') {
                $validated[$field] = null;
            }
        }

        // Het aantal cijfers van de teller bepaalt de breedte van het nummer:
        // "0006" geeft 20260006, "6" geeft 20266. De teller zelf is een int,
        // dus de breedte wordt apart bewaard.
        foreach ([
            'invoice_number_start' => 'invoice_number_padding',
            'quote_number_start'   => 'quote_number_padding',
        ] as $startField => $paddingField) {
            $typed = trim((string) $validated[$startField]);

            $validated[$paddingField] = max(1, min(10, strlen($typed)));
            $validated[$startField]   = max(1, (int) $typed);
        }

        // Checkbox: niet meegestuurd = uit
        $validated['credit_surcharge_enabled'] = $request->boolean('credit_surcharge_enabled');

        $settings = AppSetting::get();
        $settings->update($validated);

        return redirect()
            ->route('settings.edit')
            ->with('success', 'Instellingen succesvol bijgewerkt!');
    }

    /**
     * Sta alleen eenvoudige opmaak toe in de e-mailteksten en verwijder
     * alles wat script kan uitvoeren (event-handlers, javascript:-URI's).
     */
    private function sanitizeEmailHtml(string $html): string
    {
        $html = strip_tags($html, '<p><div><br><b><strong><i><em><u><s><ul><ol><li><a><span>');

        // Event-handler attributen (onclick, onerror, ...) verwijderen
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        // javascript:/data:-URI's in href/src neutraliseren
        $html = preg_replace('/\s(href|src)\s*=\s*("|\')?\s*(javascript|data|vbscript):[^"\'>\s]*("|\')?/i', '', $html);

        // Inline styles strippen (niet nodig voor de toegestane opmaak)
        $html = preg_replace('/\sstyle\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html);

        return $html;
    }
}
