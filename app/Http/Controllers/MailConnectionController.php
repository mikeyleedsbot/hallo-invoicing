<?php

namespace App\Http\Controllers;

use App\Models\MailAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MailConnectionController extends Controller
{
    /**
     * Laat alle mail-verbindingen + OAuth-credentials van de ingelogde gebruiker zien.
     */
    public function index()
    {
        $user     = Auth::user();
        $accounts = $user->mailAccounts()->orderByDesc('is_default')->orderBy('from_email')->get();

        // Redirect-URI's die de user in z'n Google/Azure console moet whitelisten.
        // Deze worden in de UI getoond met een copy-knop.
        $googleRedirectUri    = route('mail-connections.callback', 'google');
        $microsoftRedirectUri = route('mail-connections.callback', 'microsoft');

        return view('mail-connections.index', [
            'accounts'             => $accounts,
            'user'                 => $user,
            'googleConfigured'     => $user->hasGoogleOAuth(),
            'microsoftConfigured'  => $user->hasMicrosoftOAuth(),
            'googleRedirectUri'    => $googleRedirectUri,
            'microsoftRedirectUri' => $microsoftRedirectUri,
        ]);
    }

    /**
     * Sla OAuth-credentials van de ingelogde gebruiker op.
     * Alleen client_id + client_secret worden via het formulier ontvangen;
     * voor Microsoft wordt optioneel ook tenant_id meegegeven.
     */
    public function saveCredentials(Request $request, string $provider)
    {
        abort_unless(in_array($provider, ['google', 'microsoft']), 404);

        $rules = [
            'client_id'     => ['required', 'string', 'max:255'],
            'client_secret' => ['required', 'string', 'max:500'],
        ];
        if ($provider === 'microsoft') {
            $rules['tenant_id'] = ['nullable', 'string', 'max:255'];
        }

        $data = $request->validate($rules);

        $user = Auth::user();
        if ($provider === 'google') {
            $user->google_client_id     = $data['client_id'];
            $user->google_client_secret = $data['client_secret'];
        } else {
            $user->microsoft_client_id     = $data['client_id'];
            $user->microsoft_client_secret = $data['client_secret'];
            $user->microsoft_tenant_id     = $data['tenant_id'] ?: 'common';
        }
        $user->save();

        $label = $provider === 'google' ? 'Google Workspace' : 'Microsoft 365';
        return back()->with('success', $label . ' OAuth-credentials zijn opgeslagen. Je kunt nu een account koppelen.');
    }

    /**
     * Verwijder OAuth-credentials van de ingelogde gebruiker voor een provider.
     */
    public function deleteCredentials(string $provider)
    {
        abort_unless(in_array($provider, ['google', 'microsoft']), 404);

        $user = Auth::user();
        if ($provider === 'google') {
            $user->google_client_id     = null;
            $user->google_client_secret = null;
        } else {
            $user->microsoft_client_id     = null;
            $user->microsoft_client_secret = null;
            $user->microsoft_tenant_id     = 'common';
        }
        $user->save();

        $label = $provider === 'google' ? 'Google Workspace' : 'Microsoft 365';
        return back()->with('success', $label . ' OAuth-credentials zijn verwijderd.');
    }

    /**
     * Configureer Socialite at runtime met de credentials van de ingelogde gebruiker.
     * Wordt vlak voor elke OAuth-call uitgevoerd zodat we geen statische config nodig hebben.
     */
    protected function configureSocialiteForUser(string $provider): bool
    {
        $user = Auth::user();

        if ($provider === 'google') {
            if (!$user->hasGoogleOAuth()) {
                return false;
            }
            config()->set('services.google', [
                'client_id'     => $user->google_client_id,
                'client_secret' => $user->google_client_secret,
                'redirect'      => route('mail-connections.callback', 'google'),
            ]);
        } else {
            if (!$user->hasMicrosoftOAuth()) {
                return false;
            }
            config()->set('services.microsoft', [
                'client_id'     => $user->microsoft_client_id,
                'client_secret' => $user->microsoft_client_secret,
                'redirect'      => route('mail-connections.callback', 'microsoft'),
                'tenant'        => $user->microsoft_tenant_id ?: 'common',
            ]);
        }

        return true;
    }

    /**
     * Start de OAuth redirect met de credentials van de ingelogde gebruiker.
     * De daadwerkelijke Socialite-call wordt geactiveerd zodra laravel/socialite
     * geïnstalleerd is — de controller is hier al volledig op voorbereid.
     */
    public function redirect(string $provider)
    {
        abort_unless(in_array($provider, [MailAccount::PROVIDER_GOOGLE, MailAccount::PROVIDER_MICROSOFT]), 404);

        if (!$this->configureSocialiteForUser($provider)) {
            return back()->with('warning',
                'Je hebt nog geen ' . ucfirst($provider) . ' OAuth-credentials ingesteld. ' .
                'Vul ze eerst in bij je e-mailverbindingen.'
            );
        }

        // Na installatie van laravel/socialite:
        // return \Socialite::driver($provider)->scopes([...])->redirect();

        return back()->with('warning',
            'OAuth flow staat klaar voor je credentials. Installeer laravel/socialite om de redirect te activeren.'
        );
    }

    /**
     * OAuth callback — slaat tokens op in mail_accounts.
     * Placeholder tot socialite geïnstalleerd is.
     */
    public function callback(string $provider)
    {
        abort_unless(in_array($provider, [MailAccount::PROVIDER_GOOGLE, MailAccount::PROVIDER_MICROSOFT]), 404);

        if (!$this->configureSocialiteForUser($provider)) {
            return redirect()->route('mail-connections.index')
                ->with('warning', 'OAuth-credentials ontbreken. Vul ze opnieuw in.');
        }

        // Na installatie van laravel/socialite:
        // $oauthUser = \Socialite::driver($provider)->user();
        //
        // MailAccount::updateOrCreate(
        //     ['user_id' => Auth::id(), 'from_email' => $oauthUser->getEmail()],
        //     [
        //         'provider'         => $provider,
        //         'from_name'        => $oauthUser->getName(),
        //         'access_token'     => $oauthUser->token,
        //         'refresh_token'    => $oauthUser->refreshToken,
        //         'token_expires_at' => now()->addSeconds($oauthUser->expiresIn ?? 3600),
        //     ]
        // );

        return redirect()->route('mail-connections.index')
            ->with('warning', 'OAuth callback nog niet geactiveerd. Installeer eerst laravel/socialite.');
    }

    public function setDefault(MailAccount $account)
    {
        abort_unless($account->user_id === Auth::id(), 403);

        Auth::user()->mailAccounts()->update(['is_default' => false]);
        $account->update(['is_default' => true]);

        return back()->with('success', $account->from_email . ' is nu je standaard verzendadres.');
    }

    public function destroy(MailAccount $account)
    {
        abort_unless($account->user_id === Auth::id(), 403);

        $account->delete();

        return back()->with('success', 'Mailverbinding verwijderd.');
    }
}
