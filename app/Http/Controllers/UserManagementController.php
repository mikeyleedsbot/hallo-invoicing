<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $search = trim((string) $request->query('search', ''));

        $applySearch = function ($query) use ($search) {
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            return $query;
        };

        $pendingUsers  = $applySearch(User::pending())->orderByDesc('created_at')->get();
        $approvedUsers = $applySearch(User::approved())->orderBy('name')->get();
        $rejectedUsers = $applySearch(User::rejected())->orderBy('name')->get();

        // Backwards-compat: sommige oudere views verwachten nog 'users'.
        $users = $approvedUsers;

        return view('admin.users', compact('users', 'pendingUsers', 'approvedUsers', 'rejectedUsers', 'search'));
    }

    public function approve(User $user)
    {
        abort_unless(Auth::user()->is_admin, 403);

        if ($user->isApproved()) {
            return back()->with('info', $user->name . ' is al goedgekeurd.');
        }

        $user->status      = User::STATUS_APPROVED;
        $user->approved_at = now();
        $user->approved_by = Auth::id();
        $user->rejection_reason = null;
        $user->save();

        try {
            (new MailService())->sendAccountApproved($user);
        } catch (\Throwable $e) {
            \Log::error('Approval mail versturen mislukt', ['error' => $e->getMessage()]);
        }

        return back()->with('success', $user->name . ' is goedgekeurd en heeft een e-mail ontvangen.');
    }

    public function reject(Request $request, User $user)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user->status           = User::STATUS_REJECTED;
        $user->rejection_reason = $validated['reason'] ?? null;
        $user->approved_at      = null;
        $user->approved_by      = null;
        $user->save();

        try {
            (new MailService())->sendAccountRejected($user, $validated['reason'] ?? null);
        } catch (\Throwable $e) {
            \Log::error('Rejection mail versturen mislukt', ['error' => $e->getMessage()]);
        }

        return back()->with('success', $user->name . ' is afgewezen.');
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'address'      => ['nullable', 'string', 'max:255'],
            'city'         => ['nullable', 'string', 'max:100'],
            'is_admin'     => ['boolean'],
        ]);

        $token = Str::random(64);

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'company_name' => $validated['company_name'] ?? null,
            'phone'        => $validated['phone'] ?? null,
            'address'      => $validated['address'] ?? null,
            'city'         => $validated['city'] ?? null,
            'is_admin'     => $request->boolean('is_admin'),
            'password'     => Hash::make(Str::random(32)), // tijdelijk wachtwoord
            'invite_token' => $token,
            'invite_sent_at' => now(),
        ]);

        // Stuur uitnodigingsmail
        $inviteUrl = route('invite.accept', ['token' => $token]);
        $mailer    = new MailService();
        $sent      = $mailer->sendInvite($user->email, $user->name, $user->company_name ?? '', $inviteUrl);

        $msg = $sent
            ? 'Gebruiker aangemaakt en uitnodiging verstuurd naar ' . $user->email . '.'
            : 'Gebruiker aangemaakt maar uitnodigingsmail kon niet worden verstuurd. Controleer de e-mailinstellingen.';

        return redirect()->route('users.index')->with($sent ? 'success' : 'warning', $msg);
    }

    public function update(Request $request, User $user)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'unique:users,email,' . $user->id],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'address'      => ['nullable', 'string', 'max:255'],
            'city'         => ['nullable', 'string', 'max:100'],
            'is_admin'     => ['boolean'],
        ]);

        $user->name         = $validated['name'];
        $user->email        = $validated['email'];
        $user->company_name = $validated['company_name'] ?? null;
        $user->phone        = $validated['phone'] ?? null;
        $user->address      = $validated['address'] ?? null;
        $user->city         = $validated['city'] ?? null;
        $user->is_admin     = $request->boolean('is_admin');
        $user->save();

        return redirect()->route('users.index')->with('success', 'Gebruiker bijgewerkt.');
    }

    public function resendInvite(User $user)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $token = Str::random(64);
        $user->invite_token   = $token;
        $user->invite_sent_at = now();
        $user->save();

        $inviteUrl = route('invite.accept', ['token' => $token]);
        $mailer    = new MailService();
        $sent      = $mailer->sendInvite($user->email, $user->name, $user->company_name ?? '', $inviteUrl);

        return back()->with(
            $sent ? 'success' : 'warning',
            $sent ? 'Uitnodiging opnieuw verstuurd naar ' . $user->email . '.' : 'Versturen mislukt. Controleer de e-mailinstellingen.'
        );
    }

    public function destroy(User $user)
    {
        abort_unless(Auth::user()->is_admin, 403);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Je kunt jezelf niet verwijderen.']);
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'Gebruiker verwijderd.');
    }

    public function resetMfa(User $user)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $user->mfa_enabled      = false;
        $user->mfa_secret       = null;
        $user->mfa_confirmed_at = null;
        $user->save();

        return back()->with('success', 'MFA gereset voor ' . $user->name . '.');
    }

    /**
     * Direct een nieuw wachtwoord instellen voor een gebruiker.
     *
     * Bedoeld voor het geval de resetmail niet aankomt: de beheerder stelt
     * het wachtwoord in en geeft het zelf door aan de klant.
     */
    public function setPassword(Request $request, User $user)
    {
        abort_unless(Auth::user()->is_admin, 403);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], ['password' => 'wachtwoord']);

        // De 'hashed' cast op het User-model doet het hashen
        $user->password = $validated['password'];

        // 'Onthoud mij'-cookies met het oude wachtwoord ongeldig maken
        $user->setRememberToken(Str::random(60));
        $user->save();

        $endedSessions = $this->endSessionsFor($user);

        // Wachtwoordwijziging door een ander dan de eigenaar: altijd vastleggen.
        // Het wachtwoord zelf komt hier uiteraard niet in.
        \Log::info('Wachtwoord door beheerder ingesteld', [
            'admin_id'    => Auth::id(),
            'admin_email' => Auth::user()->email,
            'user_id'     => $user->id,
            'user_email'  => $user->email,
            'ip'          => $request->ip(),
        ]);

        $message = 'Nieuw wachtwoord ingesteld voor ' . $user->name . '.';
        if ($endedSessions > 0) {
            $message .= ' ' . $endedSessions . ' actieve sessie(s) beëindigd.';
        }

        return back()->with('success', $message);
    }

    /**
     * Beëindig lopende sessies van een gebruiker, zodat iemand die nog met
     * het oude wachtwoord is ingelogd eruit gaat. De eigen sessie van de
     * beheerder blijft staan (anders logt die zichzelf uit).
     */
    private function endSessionsFor(User $user): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        try {
            return \DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', session()->getId())
                ->delete();
        } catch (\Throwable $e) {
            \Log::warning('Sessies opruimen mislukt', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
