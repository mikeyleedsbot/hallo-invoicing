<?php

namespace App\Http\Controllers;

use App\Models\EmailSetting;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailSettingController extends Controller
{
    public function edit()
    {
        abort_unless(Auth::user()->is_admin, 403);
        $settings = EmailSetting::get();
        return view('admin.email-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $validated = $request->validate([
            'api_url'    => ['required', 'url', 'max:500'],
            'api_key'    => ['nullable', 'string', 'max:500'],
            'from_name'  => ['required', 'string', 'max:100'],
            'from_email' => ['required', 'email', 'max:100'],
        ]);

        $settings = EmailSetting::get();
        $settings->api_url    = $validated['api_url'];
        $settings->from_name  = $validated['from_name'];
        $settings->from_email = $validated['from_email'];

        // Alleen bijwerken als nieuw ingevuld (leeg = bestaande key bewaren)
        if (!empty($validated['api_key'])) {
            $settings->api_key = $validated['api_key'];
        }

        $settings->save();

        return redirect()->route('email-settings.edit')->with('success', 'E-mailinstellingen opgeslagen.');
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
            return back()->with('success', 'Testmail verstuurd naar ' . $request->test_email);
        }

        return back()->withErrors(['test_email' => 'Versturen mislukt. Controleer de URL en API key.']);
    }
}
