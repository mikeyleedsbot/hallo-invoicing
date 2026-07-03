<?php

namespace App\Http\Controllers;

use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailSettingController extends Controller
{
    /**
     * Toon de (read-only) systeemmail-configuratie uit .env.
     * Systeemmail loopt via de standaard Laravel-mailer; wijzigen
     * gebeurt in het .env-bestand op de server, niet in de UI.
     */
    public function edit()
    {
        abort_unless(Auth::user()->is_admin, 403);

        $mailConfig = [
            'mailer'       => config('mail.default'),
            'host'         => config('mail.mailers.smtp.host'),
            'port'         => config('mail.mailers.smtp.port'),
            'username'     => config('mail.mailers.smtp.username'),
            'from_address' => config('mail.from.address'),
            'from_name'    => config('mail.from.name'),
        ];

        return view('admin.email-settings', compact('mailConfig'));
    }

    public function test(Request $request)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $mailer  = new MailService();
        $success = $mailer->sendTest($request->test_email);

        if ($success) {
            return back()->with('success', 'Testmail verstuurd naar ' . $request->test_email . '. Let op: staat MAIL_MAILER op "log", dan belandt de mail in het logbestand.');
        }

        return back()->withErrors(['test_email' => 'Versturen mislukt. Controleer de MAIL_*-instellingen in het .env-bestand.']);
    }
}
