<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FALaravel\Facade as Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class MfaController extends Controller
{
    /**
     * Toon MFA setup pagina (QR code genereren).
     */
    public function setup(Request $request)
    {
        $user = Auth::user();

        // Genereer nieuw secret als nog niet aanwezig
        if (!$user->mfa_secret) {
            $secret = Google2FA::generateSecretKey();
            $user->mfa_secret = $secret;
            $user->save();
        }

        $qrCodeUrl = Google2FA::getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->mfa_secret
        );

        // SVG QR code genereren
        $renderer = new ImageRenderer(
            new RendererStyle(300),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);

        return view('auth.mfa-setup', [
            'secret'    => $user->mfa_secret,
            'qrCodeSvg' => $qrCodeSvg,
        ]);
    }

    /**
     * Bevestig MFA setup via ingevulde code.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user   = Auth::user();
        $valid  = Google2FA::verifyKey($user->mfa_secret, $request->code);

        if (!$valid) {
            return back()->withErrors(['code' => 'Ongeldige code. Probeer opnieuw.']);
        }

        $user->mfa_enabled       = true;
        $user->mfa_confirmed_at  = now();
        $user->save();

        // Markeer sessie als MFA-geverifieerd
        $request->session()->put('mfa_verified', true);

        return redirect()->route('dashboard')->with('success', 'MFA succesvol ingesteld! 🔐');
    }

    /**
     * Toon MFA verificatie pagina (bij elke login).
     */
    public function verify(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if ($request->session()->get('mfa_verified')) {
            return redirect()->route('dashboard');
        }

        return view('auth.mfa-verify');
    }

    /**
     * Verwerk MFA verificatie code.
     */
    public function check(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user  = Auth::user();
        $valid = Google2FA::verifyKey($user->mfa_secret, $request->code);

        if (!$valid) {
            return back()->withErrors(['code' => 'Ongeldige code. Probeer opnieuw.']);
        }

        $request->session()->put('mfa_verified', true);

        $intended = $request->session()->pull('url.intended', route('dashboard'));
        return redirect($intended);
    }

    /**
     * MFA uitschakelen.
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = Auth::user();
        $user->mfa_enabled      = false;
        $user->mfa_secret       = null;
        $user->mfa_confirmed_at = null;
        $user->save();

        $request->session()->forget('mfa_verified');

        return redirect()->route('profile.edit')->with('success', 'MFA uitgeschakeld.');
    }
}
