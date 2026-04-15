<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Toon het registratieformulier.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Verwerk een nieuwe aanvraag.
     * Let op: de gebruiker wordt NIET automatisch ingelogd. Het account staat op
     * 'pending' en moet eerst door een admin worden goedgekeurd.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'password'     => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'company_name' => $validated['company_name'] ?? null,
            'phone'        => $validated['phone'] ?? null,
            'password'     => Hash::make($validated['password']),
            'status'       => User::STATUS_PENDING,
            'is_admin'     => false,
        ]);

        // Notificeer alle admins dat er een nieuwe aanvraag is.
        $this->notifyAdmins($user);

        return redirect()->route('register.pending')
            ->with('success', 'Je aanvraag is ontvangen! We nemen deze in behandeling en sturen je een e-mail zodra je account is goedgekeurd.');
    }

    /**
     * Bevestigingspagina na registratie.
     */
    public function pending(): View
    {
        return view('auth.pending');
    }

    private function notifyAdmins(User $newUser): void
    {
        $admins = User::where('is_admin', true)->where('status', User::STATUS_APPROVED)->get();

        if ($admins->isEmpty()) {
            Log::info('Geen admins om te notificeren over nieuwe registratie', ['user_id' => $newUser->id]);
            return;
        }

        try {
            $mailer = new MailService();
            foreach ($admins as $admin) {
                $mailer->sendNewRegistrationNotification($admin, $newUser);
            }
        } catch (\Throwable $e) {
            Log::error('Admin notificatie versturen mislukt', ['error' => $e->getMessage()]);
        }
    }
}
