<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Systeemmail (registratie, goedkeuring, uitnodiging, wachtwoord-reset).
 *
 * Verstuurt via de standaard Laravel-mailer: de MAIL_*-instellingen in .env
 * (MAIL_MAILER, MAIL_HOST, MAIL_FROM_ADDRESS, MAIL_FROM_NAME, enz.).
 * Klant-mail (facturen/offertes) loopt bewust NIET hierlangs, maar via de
 * eigen mailverbinding van de gebruiker — zie CustomerMailService.
 */
class MailService
{
    /**
     * Naam van de afzender, uit .env (MAIL_FROM_NAME).
     */
    private function fromName(): string
    {
        return config('mail.from.name') ?: config('app.name', 'Hallo');
    }

    /**
     * Verstuur een e-mail via de standaard Laravel-mailer (.env-instellingen).
     */
    public function send(string $to, string $subject, string $html): bool
    {
        try {
            Mail::html($html, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('MailService: Versturen mislukt', [
                'to'    => $to,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendTest(string $to): bool
    {
        $fromName = $this->fromName();
        $subject  = 'Testmail — ' . $fromName . ' Invoicing';
        $html     = $this->buildTestHtml($fromName, $to);

        return $this->send($to, $subject, $html);
    }

    private function buildTestHtml(string $fromName, string $to): string
    {
        $time = now()->format('d-m-Y H:i:s');

        return <<<HTML
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Testmail {$fromName} Invoicing</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
  <tr>
    <td align="center">
      <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#1e40af 0%,#3b82f6 100%);border-radius:12px 12px 0 0;padding:40px 40px 32px;text-align:center;">
            <div style="display:inline-block;width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:12px;margin-bottom:16px;line-height:56px;text-align:center;font-size:28px;">
              📧
            </div>
            <h1 style="margin:0;color:white;font-size:24px;font-weight:700;letter-spacing:-0.5px;">{$fromName} Invoicing</h1>
            <p style="margin:8px 0 0;color:rgba(255,255,255,0.8);font-size:14px;">E-mailconfiguratie test</p>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="background:white;padding:40px;">
            <h2 style="margin:0 0 8px;color:#111827;font-size:20px;font-weight:700;">✅ Verbinding geslaagd!</h2>
            <p style="margin:0 0 24px;color:#6b7280;font-size:15px;line-height:1.6;">
              De e-mailconfiguratie van <strong>{$fromName} Invoicing</strong> werkt correct. Je kunt nu uitnodigingen en notificaties versturen.
            </p>

            <!-- Info box -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;margin-bottom:28px;">
              <tr>
                <td style="padding:16px 20px;">
                  <p style="margin:0 0 10px;color:#166534;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Testdetails</p>
                  <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td style="padding:3px 0;color:#15803d;font-size:13px;width:130px;font-weight:600;">Ontvanger:</td>
                      <td style="padding:3px 0;color:#166534;font-size:13px;">{$to}</td>
                    </tr>
                    <tr>
                      <td style="padding:3px 0;color:#15803d;font-size:13px;font-weight:600;">Verzonden op:</td>
                      <td style="padding:3px 0;color:#166534;font-size:13px;">{$time}</td>
                    </tr>
                    <tr>
                      <td style="padding:3px 0;color:#15803d;font-size:13px;font-weight:600;">Platform:</td>
                      <td style="padding:3px 0;color:#166534;font-size:13px;">{$fromName} Invoicing</td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5;">
              Dit is een automatisch gegenereerde testmail. Je hoeft hier niets mee te doen.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f9fafb;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
            <p style="margin:0;color:#9ca3af;font-size:12px;">
              © {$fromName} — Dit is een automatisch gegenereerde e-mail
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
    }

    /**
     * Verstuur uitnodigingsmail voor nieuwe gebruiker.
     */
    public function sendInvite(string $to, string $name, string $companyName, string $inviteUrl): bool
    {
        $subject = 'Uitnodiging voor ' . $this->fromName() . ' Invoicing';
        $html    = $this->buildInviteHtml($name, $companyName, $inviteUrl);

        return $this->send($to, $subject, $html);
    }

    /**
     * Notificatie naar een admin dat er een nieuwe (publieke) registratie is.
     */
    public function sendNewRegistrationNotification(\App\Models\User $admin, \App\Models\User $newUser): bool
    {
        $subject = 'Nieuwe aanvraag: ' . $newUser->name;
        $url     = url(route('users.index'));
        $html    = $this->buildRegistrationNotificationHtml($admin, $newUser, $url);

        return $this->send($admin->email, $subject, $html);
    }

    /**
     * Bericht naar de gebruiker dat z'n account is goedgekeurd.
     */
    public function sendAccountApproved(\App\Models\User $user): bool
    {
        $subject  = 'Je ' . $this->fromName() . ' account is goedgekeurd';
        $loginUrl = url(route('login'));
        $html     = $this->buildAccountApprovedHtml($user, $loginUrl);

        return $this->send($user->email, $subject, $html);
    }

    /**
     * Bericht naar de gebruiker dat z'n account is afgewezen.
     */
    public function sendAccountRejected(\App\Models\User $user, ?string $reason = null): bool
    {
        $subject = 'Je aanvraag voor ' . $this->fromName() . ' is afgewezen';
        $html    = $this->buildAccountRejectedHtml($user, $reason);

        return $this->send($user->email, $subject, $html);
    }

    private function buildRegistrationNotificationHtml(\App\Models\User $admin, \App\Models\User $newUser, string $url): string
    {
        $fromName = $this->fromName();
        $company  = $newUser->company_name ?: '-';
        $phone    = $newUser->phone ?: '-';

        return <<<HTML
<!DOCTYPE html>
<html lang="nl">
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
  <tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">
      <tr><td style="background:linear-gradient(135deg,#1e40af 0%,#3b82f6 100%);border-radius:12px 12px 0 0;padding:40px 40px 32px;text-align:center;">
        <h1 style="margin:0;color:white;font-size:22px;font-weight:700;">Nieuwe aanvraag voor {$fromName}</h1>
      </td></tr>
      <tr><td style="background:white;padding:40px;">
        <p style="margin:0 0 16px;color:#111827;">Hoi {$admin->name},</p>
        <p style="margin:0 0 20px;color:#4b5563;font-size:15px;line-height:1.6;">Er is een nieuwe aanvraag binnengekomen. De gebruiker wacht op jouw goedkeuring voordat ze kunnen inloggen.</p>
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;">
          <tr><td style="padding:16px 20px;">
            <table cellpadding="0" cellspacing="0" width="100%">
              <tr><td style="padding:4px 0;color:#6b7280;font-size:13px;width:130px;font-weight:600;">Naam</td><td style="padding:4px 0;color:#111827;font-size:13px;">{$newUser->name}</td></tr>
              <tr><td style="padding:4px 0;color:#6b7280;font-size:13px;font-weight:600;">E-mail</td><td style="padding:4px 0;color:#111827;font-size:13px;">{$newUser->email}</td></tr>
              <tr><td style="padding:4px 0;color:#6b7280;font-size:13px;font-weight:600;">Bedrijf</td><td style="padding:4px 0;color:#111827;font-size:13px;">{$company}</td></tr>
              <tr><td style="padding:4px 0;color:#6b7280;font-size:13px;font-weight:600;">Telefoon</td><td style="padding:4px 0;color:#111827;font-size:13px;">{$phone}</td></tr>
            </table>
          </td></tr>
        </table>
        <table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
          <a href="{$url}" style="display:inline-block;background:linear-gradient(135deg,#1e40af,#3b82f6);color:white;text-decoration:none;font-size:15px;font-weight:700;padding:14px 36px;border-radius:8px;">Open gebruikersbeheer →</a>
        </td></tr></table>
      </td></tr>
      <tr><td style="background:#f9fafb;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
        <p style="margin:0;color:#9ca3af;font-size:12px;">© {$fromName} Invoicing — automatische notificatie</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
HTML;
    }

    private function buildAccountApprovedHtml(\App\Models\User $user, string $loginUrl): string
    {
        $fromName = $this->fromName();

        return <<<HTML
<!DOCTYPE html>
<html lang="nl">
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
  <tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">
      <tr><td style="background:linear-gradient(135deg,#059669 0%,#10b981 100%);border-radius:12px 12px 0 0;padding:40px 40px 32px;text-align:center;">
        <div style="display:inline-block;width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:12px;margin-bottom:16px;line-height:56px;text-align:center;font-size:28px;">✅</div>
        <h1 style="margin:0;color:white;font-size:24px;font-weight:700;">Account goedgekeurd!</h1>
      </td></tr>
      <tr><td style="background:white;padding:40px;">
        <p style="margin:0 0 16px;color:#111827;">Welkom bij {$fromName} Invoicing, {$user->name}!</p>
        <p style="margin:0 0 24px;color:#4b5563;font-size:15px;line-height:1.6;">Je account is goedgekeurd. Je kunt nu inloggen met het wachtwoord dat je bij registratie hebt ingesteld.</p>
        <table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
          <a href="{$loginUrl}" style="display:inline-block;background:linear-gradient(135deg,#059669,#10b981);color:white;text-decoration:none;font-size:15px;font-weight:700;padding:14px 36px;border-radius:8px;">Inloggen →</a>
        </td></tr></table>
      </td></tr>
      <tr><td style="background:#f9fafb;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
        <p style="margin:0;color:#9ca3af;font-size:12px;">© {$fromName} Invoicing</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
HTML;
    }

    private function buildAccountRejectedHtml(\App\Models\User $user, ?string $reason): string
    {
        $fromName    = $this->fromName();
        $reasonBlock = $reason
            ? '<p style="margin:16px 0 0;padding:12px 16px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#7f1d1d;font-size:14px;"><strong>Reden:</strong> ' . htmlspecialchars($reason) . '</p>'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="nl">
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
  <tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">
      <tr><td style="background:#6b7280;border-radius:12px 12px 0 0;padding:40px 40px 32px;text-align:center;">
        <h1 style="margin:0;color:white;font-size:22px;font-weight:700;">Aanvraag niet goedgekeurd</h1>
      </td></tr>
      <tr><td style="background:white;padding:40px;">
        <p style="margin:0 0 16px;color:#111827;">Hoi {$user->name},</p>
        <p style="margin:0 0 16px;color:#4b5563;font-size:15px;line-height:1.6;">Bedankt voor je aanvraag voor {$fromName} Invoicing. We hebben helaas besloten om je account nu niet goed te keuren.</p>
        {$reasonBlock}
        <p style="margin:20px 0 0;color:#6b7280;font-size:14px;">Denk je dat dit een vergissing is? Neem contact met ons op.</p>
      </td></tr>
      <tr><td style="background:#f9fafb;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
        <p style="margin:0;color:#9ca3af;font-size:12px;">© {$fromName} Invoicing</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
HTML;
    }

    /**
     * Verstuur wachtwoord-reset mail in Hallo Invoicing stijl.
     */
    public function sendPasswordReset(string $to, string $name, string $resetUrl): bool
    {
        $subject = 'Wachtwoord herstellen — ' . $this->fromName() . ' Invoicing';
        $html    = $this->buildPasswordResetHtml($name, $resetUrl);

        return $this->send($to, $subject, $html);
    }

    private function buildPasswordResetHtml(string $name, string $resetUrl): string
    {
        $fromName = $this->fromName();
        $logo = '<svg width="28" height="28" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;"><path d="M15.6,75c6.1-9.7,10.8-20.6,14.2-32.8,3.4-12.2,5.2-24.6,5.3-37.2h29.3c0,8.2-1,16.8-3,25.6-2,8.8-5,17.1-8.8,25-3.8,7.9-8.2,14.3-13.1,19.4H15.6Z" fill="white" stroke-width="0"/></svg>';

        return <<<HTML
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Wachtwoord herstellen {$fromName} Invoicing</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
  <tr>
    <td align="center">
      <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#1e40af 0%,#3b82f6 100%);border-radius:12px 12px 0 0;padding:40px 40px 32px;text-align:center;">
            <div style="display:inline-block;width:52px;height:52px;background:rgba(255,255,255,0.15);border-radius:12px;margin-bottom:12px;line-height:52px;text-align:center;">
              {$logo}
            </div>
            <h1 style="margin:0;color:white;font-size:22px;font-weight:700;letter-spacing:-0.3px;">{$fromName} Invoicing</h1>
            <p style="margin:6px 0 0;color:rgba(255,255,255,0.8);font-size:13px;">Wachtwoord herstellen</p>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="background:white;padding:40px;">
            <h2 style="margin:0 0 8px;color:#111827;font-size:20px;font-weight:700;">Hoi {$name},</h2>
            <p style="margin:0 0 24px;color:#6b7280;font-size:15px;line-height:1.6;">
              Je ontvangt deze e-mail omdat we een verzoek hebben ontvangen om het wachtwoord van je account te herstellen.
            </p>

            <!-- CTA button -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
              <tr>
                <td align="center">
                  <a href="{$resetUrl}"
                     style="display:inline-block;background:linear-gradient(135deg,#1e40af,#3b82f6);color:white;text-decoration:none;font-size:15px;font-weight:700;padding:14px 36px;border-radius:8px;letter-spacing:0.2px;">
                    Wachtwoord herstellen →
                  </a>
                </td>
              </tr>
            </table>

            <!-- Expiry notice -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#fefce8;border:1px solid #fde68a;border-radius:8px;margin-bottom:24px;">
              <tr>
                <td style="padding:12px 16px;">
                  <p style="margin:0;color:#92400e;font-size:13px;">
                    ⏱️ <strong>Let op:</strong> Deze link is 60 minuten geldig.
                  </p>
                </td>
              </tr>
            </table>

            <p style="margin:0 0 20px;color:#6b7280;font-size:14px;line-height:1.6;">
              Als je geen wachtwoord-reset hebt aangevraagd, hoef je niks te doen. Je account is veilig.
            </p>

            <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5;">
              Werkt de knop niet? Kopieer deze link:<br>
              <a href="{$resetUrl}" style="color:#3b82f6;word-break:break-all;">{$resetUrl}</a>
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f9fafb;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
            <p style="margin:0;color:#9ca3af;font-size:12px;">
              © {$fromName} Invoicing — Dit is een automatisch gegenereerde e-mail
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
    }

    private function buildInviteHtml(string $name, string $companyName, string $inviteUrl): string
    {
        $fromName = $this->fromName();

        return <<<HTML
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Uitnodiging {$fromName} Invoicing</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
  <tr>
    <td align="center">
      <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#1e40af 0%,#3b82f6 100%);border-radius:12px 12px 0 0;padding:40px 40px 32px;text-align:center;">
            <div style="display:inline-block;width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:12px;margin-bottom:16px;line-height:56px;text-align:center;font-size:28px;">
              🧾
            </div>
            <h1 style="margin:0;color:white;font-size:24px;font-weight:700;letter-spacing:-0.5px;">{$fromName} Invoicing</h1>
            <p style="margin:8px 0 0;color:rgba(255,255,255,0.8);font-size:14px;">Jouw facturatieplatform</p>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="background:white;padding:40px;">
            <h2 style="margin:0 0 8px;color:#111827;font-size:20px;font-weight:700;">Welkom, {$name}! 👋</h2>
            <p style="margin:0 0 24px;color:#6b7280;font-size:15px;line-height:1.6;">
              Je bent uitgenodigd om toegang te krijgen tot het {$fromName} Invoicing platform
              {$companyName}.
            </p>

            <!-- Info box -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;margin-bottom:28px;">
              <tr>
                <td style="padding:16px 20px;">
                  <p style="margin:0 0 12px;color:#1e40af;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Wat je moet doen:</p>
                  <table cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="padding:4px 0;color:#1d4ed8;font-size:14px;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;background:#1e40af;color:white;border-radius:50%;font-size:11px;font-weight:700;margin-right:10px;vertical-align:middle;">1</span>
                        Klik op de knop hieronder
                      </td>
                    </tr>
                    <tr>
                      <td style="padding:4px 0;color:#1d4ed8;font-size:14px;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;background:#1e40af;color:white;border-radius:50%;font-size:11px;font-weight:700;margin-right:10px;vertical-align:middle;">2</span>
                        Stel een wachtwoord in voor je account
                      </td>
                    </tr>
                    <tr>
                      <td style="padding:4px 0;color:#1d4ed8;font-size:14px;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;background:#1e40af;color:white;border-radius:50%;font-size:11px;font-weight:700;margin-right:10px;vertical-align:middle;">3</span>
                        Stel tweestapsverificatie (MFA) in
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- CTA button -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
              <tr>
                <td align="center">
                  <a href="{$inviteUrl}"
                     style="display:inline-block;background:linear-gradient(135deg,#1e40af,#3b82f6);color:white;text-decoration:none;font-size:15px;font-weight:700;padding:14px 36px;border-radius:8px;letter-spacing:0.2px;">
                    Account activeren →
                  </a>
                </td>
              </tr>
            </table>

            <!-- Expiry notice -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#fefce8;border:1px solid #fde68a;border-radius:8px;margin-bottom:24px;">
              <tr>
                <td style="padding:12px 16px;">
                  <p style="margin:0;color:#92400e;font-size:13px;">
                    ⏱️ <strong>Let op:</strong> Deze uitnodigingslink is 72 uur geldig.
                  </p>
                </td>
              </tr>
            </table>

            <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5;">
              Als je deze uitnodiging niet verwacht, kun je deze e-mail negeren.<br>
              Kopieer de link als de knop niet werkt:<br>
              <a href="{$inviteUrl}" style="color:#3b82f6;word-break:break-all;">{$inviteUrl}</a>
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f9fafb;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
            <p style="margin:0;color:#9ca3af;font-size:12px;">
              © {$fromName} — Dit is een automatisch gegenereerde e-mail
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
    }
}
