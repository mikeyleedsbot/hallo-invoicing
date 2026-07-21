<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'              => 'Bulk Tester',
            'email'             => 'bulk@example.test',
            'password'          => bcrypt('password'),
            'status'            => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled'       => true,
            'mfa_confirmed_at'  => now(),
        ]);

        $this->actingAs($this->user);
        $this->customer = Customer::create([
            'name'    => 'Bulk Klant',
            'email'   => 'klant@example.test',
            'country' => 'Nederland',
        ]);
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    private function makeInvoice(string $number, string $status = 'sent'): Invoice
    {
        return Invoice::create([
            'invoice_number' => $number,
            'customer_id'    => $this->customer->id,
            'invoice_date'   => now(),
            'due_date'       => now()->addDays(14),
            'payment_terms'  => 14,
            'subtotal'       => 100,
            'vat_amount'     => 21,
            'total'          => 121,
            'status'         => $status,
        ]);
    }

    private function makeQuote(string $number, string $status = 'sent'): Quote
    {
        return Quote::create([
            'quote_number' => $number,
            'customer_id'  => $this->customer->id,
            'quote_date'   => now(),
            'valid_until'  => now()->addDays(30),
            'valid_days'   => 30,
            'subtotal'     => 100,
            'vat_amount'   => 21,
            'total'        => 121,
            'status'       => $status,
        ]);
    }

    public function test_facturen_bulk_op_betaald_zetten(): void
    {
        $a = $this->makeInvoice('BULK-001');
        $b = $this->makeInvoice('BULK-002');

        $this->as($this->user)
            ->post(route('invoices.bulk-status'), [
                'ids'    => [$a->id, $b->id],
                'status' => 'paid',
            ])
            ->assertSessionHas('success');

        $this->assertEquals('paid', $a->fresh()->status);
        $this->assertEquals('paid', $b->fresh()->status);
        $this->assertNotNull($a->fresh()->paid_at);
        $this->assertNotNull($b->fresh()->paid_at);
    }

    public function test_terug_naar_concept_wist_datums(): void
    {
        $a = $this->makeInvoice('BULK-003', 'paid');
        $a->update(['sent_at' => now(), 'paid_at' => now()]);

        $this->as($this->user)
            ->post(route('invoices.bulk-status'), ['ids' => [$a->id], 'status' => 'draft']);

        $fresh = $a->fresh();
        $this->assertEquals('draft', $fresh->status);
        $this->assertNull($fresh->sent_at);
        $this->assertNull($fresh->paid_at);
    }

    public function test_offertes_bulk_op_geaccepteerd_zetten(): void
    {
        $a = $this->makeQuote('BULKQ-001');
        $b = $this->makeQuote('BULKQ-002');

        $this->as($this->user)
            ->post(route('quotes.bulk-status'), [
                'ids'    => [$a->id, $b->id],
                'status' => 'accepted',
            ])
            ->assertSessionHas('success');

        $this->assertEquals('accepted', $a->fresh()->status);
        $this->assertEquals('accepted', $b->fresh()->status);
    }

    public function test_facturen_van_ander_account_blijven_onaangetast(): void
    {
        $invoice = $this->makeInvoice('BULK-004');

        $other = User::create([
            'name'              => 'Ander Account',
            'email'             => 'ander@example.test',
            'password'          => bcrypt('password'),
            'status'            => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled'       => true,
            'mfa_confirmed_at'  => now(),
        ]);

        $this->as($other)
            ->post(route('invoices.bulk-status'), [
                'ids'    => [$invoice->id],
                'status' => 'paid',
            ]);

        $this->assertEquals('sent', $invoice->fresh()->status);
    }

    public function test_ongeldige_status_wordt_geweigerd(): void
    {
        $invoice = $this->makeInvoice('BULK-005');

        $this->as($this->user)
            ->post(route('invoices.bulk-status'), [
                'ids'    => [$invoice->id],
                'status' => 'accepted', // offertestatus, geen factuurstatus
            ])
            ->assertSessionHasErrors('status');

        $this->assertEquals('sent', $invoice->fresh()->status);
    }
}
