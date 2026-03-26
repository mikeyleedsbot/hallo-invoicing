<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class InviteController extends Controller
{
    public function accept(string $token)
    {
        $user = User::where('invite_token', $token)->first();

        if (!$user) {
            return view('auth.invite-invalid', ['reason' => 'not_found']);
        }

        // Token verlopen na 72 uur
        if ($user->invite_sent_at && $user->invite_sent_at->diffInHours(now()) > 72) {
            return view('auth.invite-invalid', ['reason' => 'expired']);
        }

        return view('auth.invite-accept', compact('user', 'token'));
    }

    public function activate(Request $request, string $token)
    {
        $user = User::where('invite_token', $token)->first();

        if (!$user || ($user->invite_sent_at && $user->invite_sent_at->diffInHours(now()) > 72)) {
            return redirect()->route('login')->withErrors(['email' => 'Ongeldige of verlopen uitnodigingslink.']);
        }

        $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->password      = Hash::make($request->password);
        $user->invite_token  = null;
        $user->invite_sent_at = null;
        $user->save();

        Auth::login($user);

        // Stuur door naar MFA setup
        return redirect()->route('mfa.setup')->with('success', 'Wachtwoord ingesteld! Stel nu tweestapsverificatie in.');
    }
}
