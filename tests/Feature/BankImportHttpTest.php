<?php

namespace Tests\Feature;

use App\Models\BankImportSession;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Het importscherm van begin tot eind, en vooral: niemand mag bij de
 * bankgegevens van een ander bedrijf.
 */
class BankImportHttpTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->makeUser('bank-http@example.test');
        $this->actingAs($this->user);

        $this->customer = Customer::create([
            'name' => 'Testklant',
            'company_name' => 'Testklant B.V.',
            'email' => 'klant@example.test',
            'country' => 'Nederland',
        ]);
    }

    private function makeUser(string $email): User
    {
        return User::create([
            'name' => 'Tester ' . $email,
            'email' => $email,
            'password' => bcrypt('password'),
            'status' => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled' => true,
            'mfa_confirmed_at' => now(),
        ]);
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    private function upload(string $fixture): \Illuminate\Testing\TestResponse
    {
        $path = __DIR__ . '/../Fixtures/bank/' . $fixture;

        return $this->as($this->user)->post(route('bank.store'), [
            'statement' => new UploadedFile($path, $fixture, null, null, true),
        ]);
    }

    public function test_afschrift_importeren_en_automatisch_matchen(): void
    {
        Invoice::create([
            'invoice_number' => '20260018',
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-01-01',
            'due_date' => '2026-12-31',
            'payment_terms' => 14,
            'subtotal' => 1210, 'vat_amount' => 0, 'total' => 1210,
            'status' => 'sent',
        ]);

        $this->upload('camt053.xml')->assertRedirect()->assertSessionHas('success');

        $session = BankImportSession::firstOrFail();
        $this->assertSame('camt053', $session->format);
        $this->assertSame(2, $session->imported_count);

        // De bijschrijving is automatisch aan de factuur gekoppeld
        $this->assertSame(1, InvoicePayment::count());
        $this->assertSame('paid', Invoice::first()->status);
    }

    public function test_matchscherm_heeft_een_zoekbare_facturenlijst(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => '20269999',
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-01-01',
            'due_date' => '2026-12-31',
            'payment_terms' => 14,
            'subtotal' => 400, 'vat_amount' => 0, 'total' => 400,
            'status' => 'sent',
        ]);

        $this->upload('camt053.xml');
        $session = BankImportSession::firstOrFail();

        $html = $this->as($this->user)
            ->get(route('bank.show', $session))
            ->assertOk()
            ->getContent();

        // Zoekbare dropdown in plaats van een gewone select
        $this->assertStringContainsString('x-tom-select', $html);
        $this->assertStringContainsString('Zoek op factuurnummer of klant', $html);

        // De lijst staat één keer als data op de pagina, niet per transactie herhaald
        $this->assertStringContainsString('window.__bankInvoices', $html);
        $this->assertStringContainsString('20269999', $html);
        $this->assertSame(1, substr_count($html, 'window.__bankInvoices ='));
    }

    public function test_sterke_suggestie_wordt_groen_getoond(): void
    {
        // Nummer in de omschrijving + exact bedrag = boven de automatchdrempel,
        // maar met twee even goede kandidaten koppelt hij niet vanzelf
        foreach (['20260018', '20260018-B'] as $number) {
            Invoice::create([
                'invoice_number' => $number,
                'customer_id' => $this->customer->id,
                'invoice_date' => '2026-01-01',
                'due_date' => '2026-12-31',
                'payment_terms' => 14,
                'subtotal' => 1210, 'vat_amount' => 0, 'total' => 1210,
                'status' => 'sent',
            ]);
        }

        $this->upload('camt053.xml');
        $session = BankImportSession::firstOrFail();

        $html = $this->as($this->user)
            ->get(route('bank.show', $session))
            ->assertOk()
            ->getContent();

        // Legenda met de drempel erin
        $this->assertStringContainsString('Vanaf 85%', $html);
        // En een groen gemarkeerde kandidaat
        $this->assertStringContainsString('bg-green-600', $html);
    }

    public function test_opnieuw_importeren_neemt_gekoppelde_transacties_niet_dubbel_mee(): void
    {
        Invoice::create([
            'invoice_number' => '20260018',
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-01-01',
            'due_date' => '2026-12-31',
            'payment_terms' => 14,
            'subtotal' => 1210, 'vat_amount' => 0, 'total' => 1210,
            'status' => 'sent',
        ]);

        // Eerste import: bijschrijving wordt automatisch gekoppeld
        $this->upload('camt053.xml');
        $eerste = BankImportSession::firstOrFail();
        $this->assertSame(2, $eerste->imported_count);
        $this->assertSame(1, InvoicePayment::count());

        // Afronden: de niet-gekoppelde afschrijving verdwijnt
        $this->as($this->user)->post(route('bank.complete', $eerste));
        $this->assertSame(1, BankTransaction::count());

        // Tweede import van hetzelfde afschrift (overlappende periode)
        $this->upload('camt053.xml');
        $tweede = BankImportSession::latest('id')->firstOrFail();

        // De gekoppelde regel wordt herkend en overgeslagen, de weggegooide
        // afschrijving komt gewoon terug als nieuwe regel
        $this->assertSame(1, $tweede->skipped_count);
        $this->assertSame(1, $tweede->imported_count);

        // En er is niets dubbel gekoppeld
        $this->assertSame(1, InvoicePayment::count());
        $this->assertSame(1210.00, Invoice::first()->paidAmount());

        // Het matchscherm laat zien welke regel al bestond en waaraan die hangt
        $this->as($this->user)
            ->get(route('bank.show', $tweede))
            ->assertOk()
            ->assertSee('Al eerder geïmporteerd (1)')
            ->assertSee('gekoppeld aan')
            ->assertSee('20260018');
    }

    public function test_afronden_gooit_niet_gekoppelde_transacties_weg(): void
    {
        $this->upload('camt053.xml');
        $session = BankImportSession::firstOrFail();

        $this->assertSame(2, BankTransaction::count());

        $this->as($this->user)
            ->post(route('bank.complete', $session))
            ->assertRedirect(route('bank.index'));

        // Zonder factuur om aan te koppelen blijft er niets over
        $this->assertSame(0, BankTransaction::count());
        $this->assertSame('completed', $session->fresh()->status);
    }

    public function test_melding_zolang_er_nog_gematcht_moet_worden(): void
    {
        $this->upload('camt053.xml');

        $this->as($this->user)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('Er staat nog een bankimport open');
    }

    public function test_melding_verdwijnt_na_afronden(): void
    {
        $this->upload('camt053.xml');
        $session = BankImportSession::firstOrFail();
        $this->as($this->user)->post(route('bank.complete', $session));

        $this->as($this->user)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertDontSee('Er staat nog een bankimport open');
    }

    public function test_onherkenbaar_bestand_geeft_een_nette_melding(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bank') . '.txt';
        file_put_contents($path, "zomaar wat tekst\nzonder structuur\n");

        $this->as($this->user)
            ->post(route('bank.store'), ['statement' => new UploadedFile($path, 'onzin.txt', null, null, true)])
            ->assertSessionHasErrors('statement');

        @unlink($path);
        $this->assertSame(0, BankImportSession::count());
    }

    public function test_import_van_een_ander_account_is_niet_op_te_vragen(): void
    {
        $this->upload('camt053.xml');
        $session = BankImportSession::firstOrFail();

        $other = $this->makeUser('indringer@example.test');

        $this->as($other)->get(route('bank.show', $session))->assertNotFound();
        $this->as($other)->post(route('bank.complete', $session))->assertNotFound();
        $this->as($other)->delete(route('bank.destroy', $session))->assertNotFound();

        // En de import staat er nog gewoon
        $this->assertSame('open', $session->fresh()->status);
    }

    public function test_transactie_van_een_ander_account_is_niet_te_koppelen(): void
    {
        $this->upload('camt053.xml');
        $transaction = BankTransaction::where('amount', '>', 0)->firstOrFail();

        $other = $this->makeUser('indringer2@example.test');
        $this->actingAs($other);
        $otherInvoice = Invoice::create([
            'invoice_number' => 'X-1',
            'customer_id' => Customer::create(['name' => 'Ander', 'country' => 'NL'])->id,
            'invoice_date' => '2026-01-01', 'due_date' => '2026-12-31', 'payment_terms' => 14,
            'subtotal' => 100, 'vat_amount' => 0, 'total' => 100, 'status' => 'sent',
        ]);

        $this->as($other)
            ->post(route('bank.link', $transaction), ['invoice_id' => $otherInvoice->id, 'amount' => 100])
            ->assertNotFound();

        $this->assertSame(0, InvoicePayment::count());
    }

    public function test_koppelen_aan_een_factuur_van_een_ander_wordt_geweigerd(): void
    {
        $this->upload('camt053.xml');
        $transaction = BankTransaction::where('amount', '>', 0)->firstOrFail();

        // Factuur van een ander account
        $other = $this->makeUser('indringer3@example.test');
        $this->actingAs($other);
        $otherInvoice = Invoice::create([
            'invoice_number' => 'X-2',
            'customer_id' => Customer::create(['name' => 'Ander', 'country' => 'NL'])->id,
            'invoice_date' => '2026-01-01', 'due_date' => '2026-12-31', 'payment_terms' => 14,
            'subtotal' => 100, 'vat_amount' => 0, 'total' => 100, 'status' => 'sent',
        ]);

        $this->as($this->user)
            ->post(route('bank.link', $transaction), ['invoice_id' => $otherInvoice->id, 'amount' => 100])
            ->assertSessionHasErrors('invoice_id');

        $this->assertSame(0, InvoicePayment::count());
    }

    public function test_uitgelogde_bezoeker_komt_er_niet_in(): void
    {
        $this->post(route('bank.store'), [])->assertRedirect();
        $this->get(route('bank.index'))->assertRedirect();
    }

    public function test_weggooien_zet_gekoppelde_facturen_terug_op_openstaand(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => '20260018',
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-01-01',
            'due_date' => '2026-12-31',
            'payment_terms' => 14,
            'subtotal' => 1210, 'vat_amount' => 0, 'total' => 1210,
            'status' => 'sent',
        ]);

        $this->upload('camt053.xml');
        $this->assertSame('paid', $invoice->fresh()->status);

        $session = BankImportSession::firstOrFail();
        $this->as($this->user)->delete(route('bank.destroy', $session))->assertRedirect();

        $invoice->refresh();
        $this->assertSame('sent', $invoice->status);
        $this->assertSame(0.0, $invoice->paidAmount());
        $this->assertSame(0, BankTransaction::count());
    }
}
