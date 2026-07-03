<?php

namespace Tests\Feature;

use App\Models\MailAccount;
use App\Models\User;
use App\Services\CustomerMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Verifieert de OAuth-bedrading (redirect-URL's, scopes, offline access)
 * en de daadwerkelijke API-aanroepen naar Gmail / Microsoft Graph,
 * inclusief automatische token-refresh.
 */
class MailConnectionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $extra = []): User
    {
        return User::create(array_merge([
            'name'              => 'Test User',
            'email'             => uniqid('u', true) . '@example.test',
            'password'          => bcrypt('password'),
            'status'            => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled'       => true,
            'mfa_confirmed_at'  => now(),
        ], $extra));
    }

    /** Request-builder die ingelogd is én MFA gepasseerd heeft. */
    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    private function makeAccount(User $user, string $provider, array $extra = []): MailAccount
    {
        return MailAccount::create(array_merge([
            'user_id'          => $user->id,
            'provider'         => $provider,
            'from_email'       => 'verzender@bedrijf.test',
            'from_name'        => 'Verzender',
            'access_token'     => 'geldig-token',
            'refresh_token'    => 'refresh-token-1',
            'token_expires_at' => now()->addHour(),
            'is_default'       => true,
        ], $extra));
    }

    // ---------------------------------------------------------------
    // OAuth redirect
    // ---------------------------------------------------------------

    public function test_redirect_zonder_credentials_geeft_waarschuwing(): void
    {
        $user = $this->makeUser();

        $response = $this->as($user)->get(route('mail-connections.redirect', 'google'));

        $response->assertRedirect();
        $response->assertSessionHas('warning');
    }

    public function test_google_redirect_bevat_juiste_scopes_en_offline_access(): void
    {
        $user = $this->makeUser([
            'google_client_id'     => 'test-id.apps.googleusercontent.com',
            'google_client_secret' => 'test-secret',
        ]);

        $response = $this->as($user)->get(route('mail-connections.redirect', 'google'));

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/auth', $location);
        $this->assertStringContainsString('client_id=test-id.apps.googleusercontent.com', $location);
        $this->assertStringContainsString(urlencode('https://www.googleapis.com/auth/gmail.send'), $location);
        $this->assertStringContainsString('access_type=offline', $location);
        $this->assertStringContainsString('prompt=consent', $location);
        $this->assertStringContainsString(urlencode(route('mail-connections.callback', 'google')), $location);
    }

    public function test_microsoft_redirect_bevat_juiste_scopes_en_tenant(): void
    {
        $user = $this->makeUser([
            'microsoft_client_id'     => '11111111-2222-3333-4444-555555555555',
            'microsoft_client_secret' => 'ms-secret',
            'microsoft_tenant_id'     => 'common',
        ]);

        $response = $this->as($user)->get(route('mail-connections.redirect', 'microsoft'));

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringContainsString('login.microsoftonline.com/common/oauth2/v2.0/authorize', $location);
        $this->assertStringContainsString('client_id=11111111-2222-3333-4444-555555555555', $location);
        $this->assertStringContainsString('Mail.Send', $location);
        $this->assertStringContainsString('offline_access', $location);
        $this->assertStringContainsString(urlencode(route('mail-connections.callback', 'microsoft')), $location);
    }

    public function test_onbekende_provider_geeft_404(): void
    {
        $user = $this->makeUser();

        $this->as($user)->get(route('mail-connections.redirect', 'yahoo'))->assertNotFound();
    }

    // ---------------------------------------------------------------
    // Versturen via Gmail API
    // ---------------------------------------------------------------

    public function test_gmail_versturen_bouwt_correct_mime_bericht(): void
    {
        $user = $this->makeUser([
            'google_client_id'     => 'test-id',
            'google_client_secret' => 'test-secret',
        ]);
        $account = $this->makeAccount($user, MailAccount::PROVIDER_GOOGLE);

        Http::fake([
            'gmail.googleapis.com/*' => Http::response(['id' => 'msg-1'], 200),
        ]);

        $ok = app(CustomerMailService::class)->send(
            $account,
            'klant@example.test',
            'Factuur INV00001 van Testbedrijf',
            '<p>Beste klant</p>',
            'PDF-INHOUD',
            'INV00001.pdf',
        );

        $this->assertTrue($ok);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), 'gmail.googleapis.com/gmail/v1/users/me/messages/send')) {
                return false;
            }

            $mime = base64_decode(strtr($request['raw'], '-_', '+/'));

            return $request->hasHeader('Authorization', 'Bearer geldig-token')
                && str_contains($mime, 'To: klant@example.test')
                && str_contains($mime, 'Content-Type: application/pdf; name="INV00001.pdf"')
                && str_contains($mime, chunk_split(base64_encode('PDF-INHOUD')))
                && str_contains($mime, base64_encode('Factuur INV00001 van Testbedrijf'));
        });
    }

    // ---------------------------------------------------------------
    // Versturen via Microsoft Graph
    // ---------------------------------------------------------------

    public function test_graph_versturen_bouwt_correct_sendmail_payload(): void
    {
        $user = $this->makeUser([
            'microsoft_client_id'     => 'ms-id',
            'microsoft_client_secret' => 'ms-secret',
        ]);
        $account = $this->makeAccount($user, MailAccount::PROVIDER_MICROSOFT);

        Http::fake([
            'graph.microsoft.com/*' => Http::response(null, 202),
        ]);

        $ok = app(CustomerMailService::class)->send(
            $account,
            'klant@example.test',
            'Offerte OFF00001',
            '<p>Beste klant</p>',
            'PDF-INHOUD',
            'OFF00001.pdf',
        );

        $this->assertTrue($ok);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), 'graph.microsoft.com/v1.0/me/sendMail')) {
                return false;
            }

            $message = $request['message'];

            return $request->hasHeader('Authorization', 'Bearer geldig-token')
                && $message['subject'] === 'Offerte OFF00001'
                && $message['body']['contentType'] === 'HTML'
                && $message['toRecipients'][0]['emailAddress']['address'] === 'klant@example.test'
                && $message['attachments'][0]['name'] === 'OFF00001.pdf'
                && $message['attachments'][0]['contentBytes'] === base64_encode('PDF-INHOUD')
                && $request['saveToSentItems'] === true;
        });
    }

    // ---------------------------------------------------------------
    // Token refresh
    // ---------------------------------------------------------------

    public function test_verlopen_google_token_wordt_automatisch_ververst(): void
    {
        $user = $this->makeUser([
            'google_client_id'     => 'test-id',
            'google_client_secret' => 'test-secret',
        ]);
        $account = $this->makeAccount($user, MailAccount::PROVIDER_GOOGLE, [
            'access_token'     => 'verlopen-token',
            'token_expires_at' => now()->subMinute(),
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'nieuw-token',
                'expires_in'   => 3600,
            ], 200),
            'gmail.googleapis.com/*' => Http::response(['id' => 'msg-1'], 200),
        ]);

        $ok = app(CustomerMailService::class)->send(
            $account, 'klant@example.test', 'Test', '<p>Test</p>',
        );

        $this->assertTrue($ok);

        // Refresh-aanroep met de juiste client-credentials en refresh_token
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'oauth2.googleapis.com/token')
                && $request['client_id'] === 'test-id'
                && $request['client_secret'] === 'test-secret'
                && $request['refresh_token'] === 'refresh-token-1'
                && $request['grant_type'] === 'refresh_token';
        });

        // Nieuw token opgeslagen en gebruikt bij het versturen
        $this->assertEquals('nieuw-token', $account->fresh()->access_token);
        Http::assertSent(fn ($request) =>
            str_contains($request->url(), 'gmail.googleapis.com')
            && $request->hasHeader('Authorization', 'Bearer nieuw-token'));
    }

    // ---------------------------------------------------------------
    // Tenant-isolatie op de nieuwe send-email routes
    // ---------------------------------------------------------------

    public function test_send_email_route_is_tenant_geisoleerd(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();

        // Factuur van user A aanmaken (global scope zet user_id automatisch)
        $this->actingAs($userA);
        $customer = \App\Models\Customer::create([
            'name'  => 'Klant A',
            'email' => 'klant-a@example.test',
        ]);
        $invoice = \App\Models\Invoice::create([
            'invoice_number' => 'INV-ISO-001',
            'customer_id'    => $customer->id,
            'invoice_date'   => now(),
            'due_date'       => now()->addDays(14),
            'payment_terms'  => 14,
            'subtotal'       => 100,
            'vat_amount'     => 21,
            'total'          => 121,
            'status'         => 'draft',
        ]);

        // User B (met mailverbinding) mag de factuur van A niet versturen
        $this->makeAccount($userB, MailAccount::PROVIDER_GOOGLE);
        Http::fake();

        $this->as($userB)
            ->post(route('invoices.send-email', $invoice))
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_verlopen_token_zonder_refresh_token_faalt_netjes(): void
    {
        $user    = $this->makeUser();
        $account = $this->makeAccount($user, MailAccount::PROVIDER_GOOGLE, [
            'access_token'     => 'verlopen-token',
            'refresh_token'    => null,
            'token_expires_at' => now()->subMinute(),
        ]);

        Http::fake();

        $ok = app(CustomerMailService::class)->send(
            $account, 'klant@example.test', 'Test', '<p>Test</p>',
        );

        $this->assertFalse($ok);
        Http::assertNothingSent();
    }
}
