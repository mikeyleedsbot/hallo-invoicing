<?php

namespace App\Services;

use App\Models\MailAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verstuurt e-mail namens de klant via zijn eigen gekoppelde mailprovider:
 * - Google Workspace  → Gmail API (users/me/messages/send)
 * - Microsoft 365     → Microsoft Graph (me/sendMail)
 *
 * De tokens staan encrypted in mail_accounts; verlopen access-tokens worden
 * automatisch ververst met de refresh_token en de OAuth-credentials van de
 * eigenaar van het account (users-tabel).
 */
class CustomerMailService
{
    /**
     * Verstuur een HTML-mail met optionele PDF-bijlage via het gegeven account.
     */
    public function send(
        MailAccount $account,
        string $to,
        string $subject,
        string $html,
        ?string $attachmentContent = null,
        ?string $attachmentName = null,
    ): bool {
        // Header-injectie voorkomen: regeleindes uit ontvanger strippen en
        // bijlagenaam beperken tot veilige tekens.
        $to = trim(preg_replace('/[\r\n]+/', '', $to));
        if ($attachmentName !== null) {
            $attachmentName = preg_replace('/[^A-Za-z0-9._-]/', '_', $attachmentName);
        }

        if (!$this->ensureFreshToken($account)) {
            return false;
        }

        try {
            return $account->isGoogle()
                ? $this->sendViaGmail($account, $to, $subject, $html, $attachmentContent, $attachmentName)
                : $this->sendViaGraph($account, $to, $subject, $html, $attachmentContent, $attachmentName);
        } catch (\Throwable $e) {
            Log::error('CustomerMailService: exception bij versturen', [
                'account' => $account->from_email,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Ververs het access-token als het (bijna) verlopen is.
     */
    private function ensureFreshToken(MailAccount $account): bool
    {
        // Nog minstens 2 minuten geldig? Dan direct gebruiken.
        if ($account->access_token
            && $account->token_expires_at
            && $account->token_expires_at->gt(now()->addMinutes(2))) {
            return true;
        }

        if (empty($account->refresh_token)) {
            Log::warning('CustomerMailService: token verlopen en geen refresh_token', [
                'account' => $account->from_email,
            ]);
            return false;
        }

        $user = $account->user;

        if ($account->isGoogle()) {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id'     => $user->google_client_id,
                'client_secret' => $user->google_client_secret,
                'refresh_token' => $account->refresh_token,
                'grant_type'    => 'refresh_token',
            ]);
        } else {
            $tenant   = $user->microsoft_tenant_id ?: 'common';
            $response = Http::asForm()->post("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
                'client_id'     => $user->microsoft_client_id,
                'client_secret' => $user->microsoft_client_secret,
                'refresh_token' => $account->refresh_token,
                'grant_type'    => 'refresh_token',
                'scope'         => 'openid profile email offline_access User.Read Mail.Send',
            ]);
        }

        if (!$response->successful()) {
            Log::error('CustomerMailService: token-refresh mislukt', [
                'account' => $account->from_email,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
            return false;
        }

        $data = $response->json();

        $account->access_token     = $data['access_token'];
        $account->token_expires_at = now()->addSeconds($data['expires_in'] ?? 3600);
        // Sommige providers roteren de refresh_token mee.
        if (!empty($data['refresh_token'])) {
            $account->refresh_token = $data['refresh_token'];
        }
        $account->save();

        return true;
    }

    /**
     * Versturen via de Gmail API: het bericht als raw (base64url) MIME-message.
     */
    private function sendViaGmail(
        MailAccount $account,
        string $to,
        string $subject,
        string $html,
        ?string $attachmentContent,
        ?string $attachmentName,
    ): bool {
        $mime = $this->buildMimeMessage($account, $to, $subject, $html, $attachmentContent, $attachmentName);
        $raw  = rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');

        $response = Http::withToken($account->access_token)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $raw,
            ]);

        if ($response->successful()) {
            return true;
        }

        Log::error('CustomerMailService: Gmail versturen mislukt', [
            'account' => $account->from_email,
            'status'  => $response->status(),
            'body'    => $response->body(),
        ]);
        return false;
    }

    /**
     * Versturen via Microsoft Graph sendMail.
     */
    private function sendViaGraph(
        MailAccount $account,
        string $to,
        string $subject,
        string $html,
        ?string $attachmentContent,
        ?string $attachmentName,
    ): bool {
        $message = [
            'subject'      => $subject,
            'body'         => [
                'contentType' => 'HTML',
                'content'     => $html,
            ],
            'toRecipients' => [
                ['emailAddress' => ['address' => $to]],
            ],
        ];

        if ($attachmentContent !== null && $attachmentName !== null) {
            $message['attachments'] = [[
                '@odata.type'  => '#microsoft.graph.fileAttachment',
                'name'         => $attachmentName,
                'contentType'  => 'application/pdf',
                'contentBytes' => base64_encode($attachmentContent),
            ]];
        }

        $response = Http::withToken($account->access_token)
            ->post('https://graph.microsoft.com/v1.0/me/sendMail', [
                'message'         => $message,
                'saveToSentItems' => true,
            ]);

        // Graph geeft 202 Accepted terug bij succes.
        if ($response->successful()) {
            return true;
        }

        Log::error('CustomerMailService: Graph versturen mislukt', [
            'account' => $account->from_email,
            'status'  => $response->status(),
            'body'    => $response->body(),
        ]);
        return false;
    }

    /**
     * Bouw een multipart MIME-bericht (HTML + optionele PDF-bijlage) voor Gmail.
     */
    private function buildMimeMessage(
        MailAccount $account,
        string $to,
        string $subject,
        string $html,
        ?string $attachmentContent,
        ?string $attachmentName,
    ): string {
        $fromName       = $account->from_name ?: $account->from_email;
        $encodedFrom    = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $boundary       = 'hallo-' . bin2hex(random_bytes(16));

        $headers = implode("\r\n", [
            "From: {$encodedFrom} <{$account->from_email}>",
            "To: {$to}",
            "Subject: {$encodedSubject}",
            'MIME-Version: 1.0',
            "Content-Type: multipart/mixed; boundary=\"{$boundary}\"",
        ]);

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($html));

        if ($attachmentContent !== null && $attachmentName !== null) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: application/pdf; name=\"{$attachmentName}\"\r\n";
            $body .= "Content-Disposition: attachment; filename=\"{$attachmentName}\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($attachmentContent));
        }

        $body .= "--{$boundary}--";

        return $headers . "\r\n\r\n" . $body;
    }
}
