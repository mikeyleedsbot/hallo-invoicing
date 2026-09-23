<?php

namespace Tests\Feature;

use App\Models\BankImportSession;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\BankImport\BankImportException;
use App\Services\BankImport\BankImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Koppelen van banktransacties aan verkoopfacturen: automatisch matchen,
 * deelbetalingen, ontkoppelen en het opruimen bij afronden.
 */
class BankMatchingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;
    private BankImportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->makeUser('bank@example.test');
        $this->actingAs($this->user);

        $this->customer = Customer::create([
            'name' => 'Renoplan Bouw',
            'company_name' => 'Renoplan Bouw B.V.',
            'email' => 'klant@example.test',
            'country' => 'Nederland',
        ]);

        $this->service = new BankImportService();
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

    private function makeInvoice(string $number, float $total, string $status = 'sent'): Invoice
    {
        return Invoice::create([
            'invoice_number' => $number,
            'customer_id' => $this->customer->id,
            'invoice_date' => '2026-01-01',
            'due_date' => '2026-12-31',
            'payment_terms' => 14,
            'subtotal' => $total,
            'vat_amount' => 0,
            'total' => $total,
            'status' => $status,
        ]);
    }

    private function makeSession(?int $userId = null): BankImportSession
    {
        return BankImportSession::create([
            'user_id' => $userId ?? $this->user->id,
            'original_filename' => 'afschrift.csv',
            'format' => 'csv',
            'status' => BankImportSession::STATUS_OPEN,
        ]);
    }

    private function makeTransaction(BankImportSession $session, float $amount, string $description, ?string $party = 'Renoplan Bouw B.V.'): BankTransaction
    {
        return BankTransaction::create([
            'user_id' => $session->user_id,
            'bank_import_session_id' => $session->id,
            'booking_date' => '2026-02-01',
            'amount' => $amount,
            'currency' => 'EUR',
            'counterparty_name' => $party,
            'counterparty_iban' => 'NL02BANK0987654321',
            'description' => $description,
            'fingerprint' => hash('sha256', $description . $amount . uniqid()),
        ]);
    }

    public function test_factuurnummer_en_bedrag_leveren_een_automatische_koppeling(): void
    {
        $invoice = $this->makeInvoice('20260018', 1210.00);
        $session = $this->makeSession();
        $this->makeTransaction($session, 1210.00, 'Betaling factuur 20260018');

        $this->assertSame(1, $this->service->autoMatch($session));

        $invoice->refresh();
        $this->assertSame(1210.00, $invoice->paidAmount());
        $this->assertSame(0.0, $invoice->outstandingAmount());
        $this->assertSame('paid', $invoice->status);
    }

    public function test_scheidingstekens_in_het_nummer_maken_niet_uit(): void
    {
        $this->makeInvoice('2026-0018', 1210.00);
        $session = $this->makeSession();
        $this->makeTransaction($session, 1210.00, 'Overboeking inzake 20260018');

        $this->assertSame(1, $this->service->autoMatch($session));
    }

    public function test_deelbetaling_laat_het_restant_openstaan(): void
    {
        $invoice = $this->makeInvoice('20260019', 1000.00);
        $session = $this->makeSession();
        $transaction = $this->makeTransaction($session, 400.00, 'Aanbetaling factuur 20260019');

        $this->service->link($transaction, $invoice, 400.00);
        $invoice->refresh();

        $this->assertSame(400.00, $invoice->paidAmount());
        $this->assertSame(600.00, $invoice->outstandingAmount());
        $this->assertTrue($invoice->isPartiallyPaid());
        $this->assertFalse($invoice->isFullyPaid());
        // Deels betaald is niet betaald
        $this->assertNotSame('paid', $invoice->status);
    }

    public function test_tweede_deelbetaling_maakt_de_factuur_betaald(): void
    {
        $invoice = $this->makeInvoice('20260020', 1000.00);
        $session = $this->makeSession();

        $this->service->link($this->makeTransaction($session, 400.00, 'deel 1'), $invoice, 400.00);
        $this->service->link($this->makeTransaction($session, 600.00, 'deel 2'), $invoice->fresh(), 600.00);

        $invoice->refresh();
        $this->assertSame(0.0, $invoice->outstandingAmount());
        $this->assertSame('paid', $invoice->status);
    }

    public function test_ontkoppelen_zet_de_factuur_terug_op_openstaand(): void
    {
        $invoice = $this->makeInvoice('20260021', 500.00);
        $session = $this->makeSession();
        $payment = $this->service->link($this->makeTransaction($session, 500.00, 'betaling'), $invoice, 500.00);

        $this->assertSame('paid', $invoice->fresh()->status);

        $this->service->unlink($payment);

        $invoice->refresh();
        $this->assertSame(0.0, $invoice->paidAmount());
        $this->assertSame(500.00, $invoice->outstandingAmount());
        $this->assertSame('sent', $invoice->status);
        $this->assertNull($invoice->paid_at);
    }

    public function test_een_transactie_kan_over_twee_facturen_verdeeld_worden(): void
    {
        $a = $this->makeInvoice('20260022', 300.00);
        $b = $this->makeInvoice('20260023', 200.00);
        $session = $this->makeSession();
        $transaction = $this->makeTransaction($session, 500.00, 'betaling 20260022 en 20260023');

        $this->service->link($transaction, $a, 300.00);
        $this->service->link($transaction->fresh(), $b, 200.00);

        $this->assertSame('paid', $a->fresh()->status);
        $this->assertSame('paid', $b->fresh()->status);
        $this->assertSame(0.0, $transaction->fresh()->unallocatedAmount());
    }

    public function test_er_wordt_nooit_meer_toegewezen_dan_openstaat(): void
    {
        $invoice = $this->makeInvoice('20260024', 100.00);
        $session = $this->makeSession();
        $transaction = $this->makeTransaction($session, 500.00, 'te veel');

        $payment = $this->service->link($transaction, $invoice, 500.00);

        $this->assertSame(100.00, (float) $payment->amount);
        $this->assertSame(400.00, $transaction->fresh()->unallocatedAmount());
    }

    public function test_afschrijvingen_worden_niet_voorgesteld(): void
    {
        $this->makeInvoice('20260025', 250.00);
        $session = $this->makeSession();
        $this->makeTransaction($session, -250.00, 'betaling factuur 20260025');

        $this->assertSame(0, $this->service->autoMatch($session));
        $this->assertSame([], $this->service->suggestions($session));
    }

    public function test_twee_even_goede_kandidaten_worden_niet_automatisch_gekoppeld(): void
    {
        // Zelfde bedrag, zelfde klant, geen nummer in de omschrijving
        $this->makeInvoice('20260026', 750.00);
        $this->makeInvoice('20260027', 750.00);
        $session = $this->makeSession();
        $this->makeTransaction($session, 750.00, 'Betaling');

        $this->assertSame(0, $this->service->autoMatch($session));

        // Maar ze komen wel als suggestie terug
        $suggestions = $this->service->suggestions($session);
        $this->assertCount(1, $suggestions);
        $this->assertGreaterThanOrEqual(2, count($suggestions[0]['candidates']));
    }

    public function test_nummer_middenin_een_langere_reeks_telt_niet_mee(): void
    {
        $this->makeInvoice('20268', 500.00);
        $session = $this->makeSession();
        // 202685 bevat 20268, maar is een ander nummer
        $this->makeTransaction($session, 500.00, 'Overboeking kenmerk 202685', 'Onbekende partij');

        $suggestions = $this->service->suggestions($session);
        $candidates = $suggestions[0]['candidates'] ?? [];

        // Zonder nummertreffer en zonder naamtreffer blijft er niets over
        $this->assertSame([], $candidates);
    }

    public function test_alleen_een_gelijk_bedrag_is_geen_suggestie(): void
    {
        // Zelfde bedrag, maar niets wijst naar deze factuur
        $this->makeInvoice('20260030', 1234.00);
        $session = $this->makeSession();
        $this->makeTransaction($session, 1234.00, 'Verkoop kassa', 'Volstrekt Andere Partij');

        $suggestions = $this->service->suggestions($session);

        $this->assertCount(1, $suggestions);
        $this->assertSame([], $suggestions[0]['candidates']);
    }

    public function test_klantnaam_zonder_nummer_geeft_wel_een_suggestie(): void
    {
        $this->makeInvoice('20260031', 800.00);
        $session = $this->makeSession();
        $this->makeTransaction($session, 800.00, 'Betaling', 'Renoplan Bouw B.V.');

        $candidates = $this->service->suggestions($session)[0]['candidates'];

        $this->assertNotEmpty($candidates);
        $this->assertContains('klantnaam komt overeen', $candidates[0]['reasons']);
    }

    public function test_afronden_bewaart_alleen_gekoppelde_transacties(): void
    {
        $invoice = $this->makeInvoice('20260028', 1210.00);
        $session = $this->makeSession();

        $matched = $this->makeTransaction($session, 1210.00, 'factuur 20260028');
        $this->makeTransaction($session, -75.50, 'kosten');
        $this->makeTransaction($session, 33.00, 'onbekende bijschrijving');

        $this->service->autoMatch($session);
        $removed = $this->service->complete($session);

        $this->assertSame(2, $removed);
        $this->assertDatabaseHas('bank_transactions', ['id' => $matched->id]);
        $this->assertSame(1, BankTransaction::where('bank_import_session_id', $session->id)->count());
        $this->assertSame(BankImportSession::STATUS_COMPLETED, $session->fresh()->status);
    }

    public function test_transacties_van_een_ander_account_zijn_onzichtbaar(): void
    {
        $session = $this->makeSession();
        $this->makeTransaction($session, 100.00, 'van mij');

        $other = $this->makeUser('ander@example.test');
        $this->actingAs($other);

        $this->assertSame(0, BankTransaction::count());
        $this->assertSame(0, BankImportSession::count());
    }

    public function test_koppelen_over_accounts_heen_wordt_geweigerd(): void
    {
        $invoice = $this->makeInvoice('20260029', 100.00);

        $other = $this->makeUser('ander2@example.test');
        $otherSession = $this->makeSession($other->id);
        $otherTransaction = BankTransaction::withoutGlobalScope('belongs_to_user')->create([
            'user_id' => $other->id,
            'bank_import_session_id' => $otherSession->id,
            'booking_date' => '2026-02-01',
            'amount' => 100.00,
            'currency' => 'EUR',
            'description' => 'van iemand anders',
            'fingerprint' => hash('sha256', 'ander'),
        ]);

        $this->expectException(BankImportException::class);
        $this->service->link($otherTransaction, $invoice, 100.00);
    }

    public function test_restant_contant_afronden_zet_factuur_op_betaald(): void
    {
        $invoice = $this->makeInvoice('20260040', 1000.00);
        $session = $this->makeSession();
        $transaction = $this->makeTransaction($session, 600.00, 'Deelbetaling factuur 20260040');

        $this->service->link($transaction, $invoice, 600.00);
        $invoice->refresh();
        $this->assertTrue($invoice->isPartiallyPaid());

        $payment = $this->service->settleRemainder($invoice);
        $invoice->refresh();

        $this->assertSame(400.00, round((float) $payment->amount, 2));
        $this->assertTrue($payment->isCash());
        $this->assertNull($payment->bank_transaction_id);
        $this->assertSame(0.0, $invoice->outstandingAmount());
        $this->assertSame('paid', $invoice->status);
        $this->assertFalse($invoice->isPartiallyPaidForDisplay());
        $this->assertSame('via bank en contant', $invoice->statusSourceLabel());
    }

    public function test_contante_afronding_terugdraaien_zet_factuur_weer_op_deels_betaald(): void
    {
        $invoice = $this->makeInvoice('20260041', 1000.00);
        $session = $this->makeSession();
        $transaction = $this->makeTransaction($session, 600.00, 'Deelbetaling factuur 20260041');

        $this->service->link($transaction, $invoice, 600.00);
        $payment = $this->service->settleRemainder($invoice->refresh());

        $this->service->unlink($payment);
        $invoice->refresh();

        $this->assertSame(600.00, $invoice->paidAmount());
        $this->assertSame(400.00, $invoice->outstandingAmount());
        $this->assertTrue($invoice->isPartiallyPaidForDisplay());
        $this->assertSame('via bankkoppeling', $invoice->statusSourceLabel());
    }

    public function test_afronden_zonder_openstaand_bedrag_wordt_geweigerd(): void
    {
        $invoice = $this->makeInvoice('20260042', 250.00);
        $session = $this->makeSession();
        $transaction = $this->makeTransaction($session, 250.00, 'Betaling factuur 20260042');
        $this->service->link($transaction, $invoice, 250.00);

        $this->expectException(BankImportException::class);
        $this->service->settleRemainder($invoice->refresh());
    }

    public function test_afronden_via_de_route_kan_niet_op_een_factuur_van_een_ander(): void
    {
        $other = $this->makeUser('ander3@example.test');

        $otherCustomer = Customer::withoutGlobalScope('belongs_to_user')->create([
            'user_id' => $other->id,
            'name' => 'Andermans klant',
            'country' => 'Nederland',
        ]);

        $otherInvoice = Invoice::withoutGlobalScope('belongs_to_user')->create([
            'user_id' => $other->id,
            'invoice_number' => '20260043',
            'customer_id' => $otherCustomer->id,
            'invoice_date' => '2026-01-01',
            'due_date' => '2026-12-31',
            'payment_terms' => 14,
            'subtotal' => 100.00,
            'vat_amount' => 0,
            'total' => 100.00,
            'status' => 'sent',
        ]);

        $this->post(route('bank.settle', $otherInvoice))->assertNotFound();
        $this->assertSame(0, \App\Models\InvoicePayment::withoutGlobalScope('belongs_to_user')->count());
    }
}
