<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\MailAccount;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Models\VatRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bewijst dat een ingelogd account op GEEN enkele manier bij data van een
 * ander account kan. Dit is de kernaanname van de gedeelde SaaS-oplossing.
 *
 * Voor elke resource: user B mag data van user A niet zien, wijzigen,
 * verwijderen, of via een foreign key aan eigen records koppelen.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = $this->makeUser('a@example.test');
        $this->userB = $this->makeUser('b@example.test');
    }

    private function makeUser(string $email): User
    {
        return User::create([
            'name'              => 'User ' . $email,
            'email'             => $email,
            'password'          => bcrypt('password'),
            'status'            => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled'       => true,
            'mfa_confirmed_at'  => now(),
        ]);
    }

    /** Geef een request-builder die als $user ingelogd is én MFA gepasseerd heeft. */
    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    /** Maak een volledige dataset aan die eigendom is van $user. */
    private function seedFor(User $user): array
    {
        $this->actingAs($user); // global scope zet user_id automatisch

        $customer = Customer::create([
            'name'       => 'Klant van ' . $user->email,
            'email'      => 'klant-' . $user->id . '@example.test',
            'country'    => 'Nederland',
            'vat_number' => 'NL00000000' . $user->id . 'B01',
        ]);

        $product = Product::create([
            'name'  => 'Product van ' . $user->email,
            'price' => 100,
        ]);

        $vatRate = VatRate::create([
            'name'       => 'BTW ' . $user->id,
            'rate'       => 21,
            'is_default' => true,
            'sort_order' => 1,
        ]);

        $template = InvoiceTemplate::create([
            'name'      => 'Template van ' . $user->email,
            'page_size' => 'A4',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-' . $user->id . '-001',
            'customer_id'    => $customer->id,
            'template_id'    => $template->id,
            'invoice_date'   => now(),
            'due_date'       => now()->addDays(14),
            'payment_terms'  => 14,
            'subtotal'       => 100,
            'vat_amount'     => 21,
            'total'          => 121,
            'status'         => 'sent',
        ]);
        $invoice->lines()->create([
            'description' => 'Regel A',
            'quantity'    => 1,
            'unit_price'  => 100,
            'vat_rate'    => 21,
            'total'       => 100,
        ]);

        $quote = Quote::create([
            'quote_number' => 'QUO-' . $user->id . '-001',
            'customer_id'  => $customer->id,
            'template_id'  => $template->id,
            'quote_date'   => now(),
            'valid_until'  => now()->addDays(30),
            'valid_days'   => 30,
            'subtotal'     => 100,
            'vat_amount'   => 21,
            'total'        => 121,
            'status'       => 'sent',
        ]);
        $quote->lines()->create([
            'description' => 'Regel A',
            'quantity'    => 1,
            'unit_price'  => 100,
            'vat_rate'    => 21,
            'total'       => 100,
        ]);

        $mailAccount = MailAccount::create([
            'user_id'    => $user->id,
            'provider'   => MailAccount::PROVIDER_GOOGLE,
            'from_email' => 'mail-' . $user->id . '@example.test',
            'from_name'  => 'Mail ' . $user->id,
            'is_default' => true,
        ]);

        return compact('customer', 'product', 'vatRate', 'template', 'invoice', 'quote', 'mailAccount');
    }

    public function test_indexen_tonen_geen_data_van_ander_account(): void
    {
        $a = $this->seedFor($this->userA);
        $this->seedFor($this->userB);

        $this->as($this->userB)->get('/customers')->assertOk()->assertDontSee($a['customer']->name);
        $this->as($this->userB)->get('/products')->assertOk()->assertDontSee($a['product']->name);
        $this->as($this->userB)->get('/invoices')->assertOk()->assertDontSee($a['invoice']->invoice_number);
        $this->as($this->userB)->get('/quotes')->assertOk()->assertDontSee($a['quote']->quote_number);
        $this->as($this->userB)->get('/btw-tarieven')->assertOk()->assertDontSee($a['vatRate']->name);
        $this->as($this->userB)->get('/templates')->assertOk()->assertDontSee($a['template']->name);
        $this->as($this->userB)->get('/dashboard')->assertOk()->assertDontSee($a['invoice']->invoice_number);
        $this->as($this->userB)->get('/mailverbindingen')->assertOk()->assertDontSee($a['mailAccount']->from_email);
    }

    public function test_directe_toegang_tot_records_van_ander_account_geeft_404(): void
    {
        $a = $this->seedFor($this->userA);

        // Bekijken
        $this->as($this->userB)->get("/invoices/{$a['invoice']->id}")->assertNotFound();
        $this->as($this->userB)->get("/invoices/{$a['invoice']->id}/pdf")->assertNotFound();
        $this->as($this->userB)->get("/invoices/{$a['invoice']->id}/preview")->assertNotFound();
        $this->as($this->userB)->get("/invoices/{$a['invoice']->id}/print")->assertNotFound();
        $this->as($this->userB)->get("/quotes/{$a['quote']->id}")->assertNotFound();
        $this->as($this->userB)->get("/quotes/{$a['quote']->id}/pdf")->assertNotFound();

        // Wijzigen
        $this->as($this->userB)->put("/customers/{$a['customer']->id}", [
            'name' => 'Gehackt', 'country' => 'Nederland',
        ])->assertNotFound();
        $this->as($this->userB)->put("/products/{$a['product']->id}", [
            'name' => 'Gehackt', 'price' => 1,
        ])->assertNotFound();

        // Verwijderen
        $this->as($this->userB)->delete("/customers/{$a['customer']->id}")->assertNotFound();
        $this->as($this->userB)->delete("/invoices/{$a['invoice']->id}")->assertNotFound();
        $this->as($this->userB)->delete("/quotes/{$a['quote']->id}")->assertNotFound();

        // Status-acties
        $this->as($this->userB)->post("/invoices/{$a['invoice']->id}/mark-paid", [
            'paid_date' => now()->toDateString(),
        ])->assertNotFound();
        $this->as($this->userB)->post("/invoices/{$a['invoice']->id}/duplicate")->assertNotFound();
        $this->as($this->userB)->post("/quotes/{$a['quote']->id}/convert")->assertNotFound();

        // Data van A is onaangetast
        $this->assertDatabaseHas('customers', ['id' => $a['customer']->id, 'name' => $a['customer']->name]);
        $this->assertDatabaseHas('invoices', ['id' => $a['invoice']->id]);
    }

    public function test_mailverbinding_van_ander_account_is_niet_te_beheren(): void
    {
        $a = $this->seedFor($this->userA);

        $this->as($this->userB)->post("/mailverbindingen/{$a['mailAccount']->id}/default")->assertForbidden();
        $this->as($this->userB)->delete("/mailverbindingen/{$a['mailAccount']->id}")->assertForbidden();

        $this->assertDatabaseHas('mail_accounts', ['id' => $a['mailAccount']->id]);
    }

    public function test_kan_geen_factuur_maken_met_klant_van_ander_account(): void
    {
        $a = $this->seedFor($this->userA);

        $response = $this->as($this->userB)->post('/invoices', [
            'customer_id'    => $a['customer']->id, // klant van A!
            'invoice_number' => 'INV-HACK-001',
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(14)->toDateString(),
            'lines'          => [[
                'description' => 'x', 'quantity' => 1, 'unit_price' => 10, 'vat_rate' => 21,
            ]],
        ]);

        $response->assertSessionHasErrors('customer_id');
        $this->assertDatabaseMissing('invoices', ['invoice_number' => 'INV-HACK-001']);
    }

    public function test_kan_geen_factuur_maken_met_template_van_ander_account(): void
    {
        $a = $this->seedFor($this->userA);
        $this->actingAs($this->userB);
        $ownCustomer = Customer::create(['name' => 'Eigen klant', 'country' => 'Nederland']);

        $response = $this->as($this->userB)->post('/invoices', [
            'customer_id'    => $ownCustomer->id,
            'template_id'    => $a['template']->id, // template van A!
            'invoice_number' => 'INV-HACK-002',
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(14)->toDateString(),
            'lines'          => [[
                'description' => 'x', 'quantity' => 1, 'unit_price' => 10, 'vat_rate' => 21,
            ]],
        ]);

        $response->assertSessionHasErrors('template_id');
        $this->assertDatabaseMissing('invoices', ['invoice_number' => 'INV-HACK-002']);
    }

    public function test_kan_geen_offerte_maken_met_klant_van_ander_account(): void
    {
        $a = $this->seedFor($this->userA);

        $response = $this->as($this->userB)->post('/quotes', [
            'customer_id'  => $a['customer']->id, // klant van A!
            'quote_number' => 'QUO-HACK-001',
            'quote_date'   => now()->toDateString(),
            'valid_until'  => now()->addDays(30)->toDateString(),
            'lines'        => [[
                'description' => 'x', 'quantity' => 1, 'unit_price' => 10, 'vat_rate' => 21,
            ]],
        ]);

        $response->assertSessionHasErrors('customer_id');
        $this->assertDatabaseMissing('quotes', ['quote_number' => 'QUO-HACK-001']);
    }
}
