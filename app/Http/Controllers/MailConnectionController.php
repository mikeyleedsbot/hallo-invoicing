<?php

namespace App\Http\Controllers;

use App\Models\MailAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

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

        if ($provider === MailAccount::PROVIDER_GOOGLE) {
            // gmail.send is voldoende om te versturen; offline + consent
            // zorgen dat we een refresh_token krijgen.
            return Socialite::driver('google')
                ->scopes(['openid', 'profile', 'email', 'https://www.googleapis.com/auth/gmail.send'])
                ->with(['access_type' => 'offline', 'prompt' => 'consent'])
                ->redirect();
        }

        return Socialite::driver('microsoft')
            ->scopes(['openid', 'profile', 'email', 'offline_access', 'User.Read', 'Mail.Send'])
            ->redirect();
    }

    /**
     * OAuth callback — slaat tokens (encrypted) op in mail_accounts.
     */
    public function callback(Request $request, string $provider)
    {
        abort_unless(in_array($provider, [MailAccount::PROVIDER_GOOGLE, MailAccount::PROVIDER_MICROSOFT]), 404);

        // Gebruiker heeft geannuleerd of de provider gaf een fout terug.
        if ($request->filled('error')) {
            return redirect()->route('mail-connections.index')
                ->with('warning', 'Koppelen geannuleerd of geweigerd: ' . $request->query('error_description', $request->query('error')));
        }

        if (!$this->configureSocialiteForUser($provider)) {
            return redirect()->route('mail-connections.index')
                ->with('warning', 'OAuth-credentials ontbreken. Vul ze opnieuw in.');
        }

        try {
            $oauthUser = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            Log::error('MailConnection: OAuth callback mislukt', [
                'provider' => $provider,
                'error'    => $e->getMessage(),
            ]);

            return redirect()->route('mail-connections.index')
                ->with('warning', 'Koppelen mislukt. Controleer je credentials en de redirect URI in je ' .
                    ($provider === 'google' ? 'Google Cloud Console' : 'Azure Portal') . ' en probeer opnieuw.');
        }

        $email = $oauthUser->getEmail();
        if (empty($email)) {
            return redirect()->route('mail-connections.index')
                ->with('warning', 'De provider gaf geen e-mailadres terug. Controleer of de juiste scopes zijn toegekend.');
        }

        $values = [
            'provider'         => $provider,
            'from_name'        => $oauthUser->getName(),
            'access_token'     => $oauthUser->token,
            'token_expires_at' => now()->addSeconds($oauthUser->expiresIn ?? 3600),
        ];

        // Google geeft alleen bij het eerste consent een refresh_token terug;
        // een bestaande refresh_token nooit overschrijven met null.
        if (!empty($oauthUser->refreshToken)) {
            $values['refresh_token'] = $oauthUser->refreshToken;
        }

        $account = MailAccount::updateOrCreate(
            ['user_id' => Auth::id(), 'from_email' => $email],
            $values
        );

        // Eerste verbinding meteen als standaard instellen.
        if (!Auth::user()->mailAccounts()->where('is_default', true)->exists()) {
            $account->update(['is_default' => true]);
        }

        return redirect()->route('mail-connections.index')
            ->with('success', $email . ' is gekoppeld. Je kunt facturen en offertes nu rechtstreeks versturen.');
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
