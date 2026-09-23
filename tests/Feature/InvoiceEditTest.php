<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bewerken van een factuur: het factuurnummer moet aanpasbaar zijn en een
 * mislukte validatie moet zichtbaar zijn in plaats van stil terug te vallen.
 */
class InvoiceEditTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'              => 'Factuur Tester',
            'email'             => 'facturen@example.test',
            'password'          => bcrypt('password'),
            'status'            => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled'       => true,
            'mfa_confirmed_at'  => now(),
        ]);

        $this->actingAs($this->user);

        $this->customer = Customer::create([
            'name'    => 'Klant',
            'email'   => 'klant@example.test',
            'country' => 'Nederland',
        ]);
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    private function makeInvoice(string $number = '202600018'): Invoice
    {
        $this->as($this->user)->post(route('invoices.store'), [
            'customer_id'    => $this->customer->id,
            'invoice_number' => $number,
            'invoice_date'   => '2026-09-01',
            'due_date'       => '2026-09-15',
            'payment_terms'  => 14,
            'lines'          => [['description' => 'Werk', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 21]],
        ])->assertSessionHasNoErrors();

        return Invoice::latest('id')->firstOrFail();
    }

    /** @param array<string, mixed> $overrides */
    private function updatePayload(array $overrides = []): array
    {
        return array_merge([
            'invoice_number' => '202618',
            'customer_id'    => $this->customer->id,
            'invoice_date'   => '2026-09-01',
            'due_date'       => '2026-09-15',
            'payment_terms'  => 14,
            'status'         => 'draft',
            'lines'          => [['description' => 'Werk', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 21]],
        ], $overrides);
    }

    public function test_factuurnummer_kan_gewijzigd_worden(): void
    {
        $invoice = $this->makeInvoice('202600018');

        $this->as($this->user)
            ->put(route('invoices.update', $invoice), $this->updatePayload())
            ->assertSessionHasNoErrors();

        $this->assertSame('202618', $invoice->fresh()->invoice_number);
    }

    public function test_bestaand_factuurnummer_wordt_geweigerd(): void
    {
        $this->makeInvoice('202600018');
        $tweede = $this->makeInvoice('202600019');

        $response = $this->as($this->user)
            ->from(route('invoices.edit', $tweede))
            ->put(route('invoices.update', $tweede), $this->updatePayload(['invoice_number' => '202600018']));

        $response->assertSessionHasErrors('invoice_number');

        // Leesbare melding, geen ruwe sleutel als "validation.unique"
        $this->assertSame(
            'Factuurnummer is al in gebruik.',
            session('errors')->first('invoice_number')
        );

        $this->assertSame('202600019', $tweede->fresh()->invoice_number);

        // En die tekst hoort ook echt op het scherm te staan
        $this->as($this->user)
            ->get(route('invoices.edit', $tweede))
            ->assertOk()
            ->assertSee('Factuurnummer is al in gebruik.')
            ->assertDontSee('validation.');
    }

    public function test_eigen_nummer_ongewijzigd_laten_mag(): void
    {
        $invoice = $this->makeInvoice('202600018');

        $this->as($this->user)
            ->put(route('invoices.update', $invoice), $this->updatePayload(['invoice_number' => '202600018']))
            ->assertSessionHasNoErrors();

        $this->assertSame('202600018', $invoice->fresh()->invoice_number);
    }

    public function test_vervaldatum_voor_factuurdatum_geeft_een_zichtbare_fout(): void
    {
        $invoice = $this->makeInvoice();

        // Factuurdatum voorbij de vervaldatum schuiven laat de validatie falen
        $this->as($this->user)
            ->put(route('invoices.update', $invoice), $this->updatePayload([
                'invoice_date' => '2026-10-01',
                'due_date'     => '2026-09-15',
            ]))
            ->assertSessionHasErrors('due_date');

        // En die fout moet de gebruiker ook echt te zien krijgen
        $this->as($this->user)
            ->get(route('invoices.edit', $invoice))
            ->assertOk()
            ->assertSee('Opslaan is niet gelukt');
    }

    /** Na een fout moet je ingetypte nummer blijven staan, niet het oude. */
    public function test_ingetypte_waarde_blijft_staan_na_een_fout(): void
    {
        $this->makeInvoice('202600018');
        $tweede = $this->makeInvoice('202600019');

        $this->as($this->user)
            ->from(route('invoices.edit', $tweede))
            ->put(route('invoices.update', $tweede), $this->updatePayload([
                'invoice_number' => '202600018',
            ]))
            ->assertSessionHasErrors('invoice_number');

        $this->as($this->user)
            ->get(route('invoices.edit', $tweede))
            ->assertOk()
            ->assertSee('value="202600018"', false);
    }

    public function test_bewerkformulier_toont_foutmeldingen(): void
    {
        $invoice = $this->makeInvoice();

        $this->as($this->user)
            ->from(route('invoices.edit', $invoice))
            ->put(route('invoices.update', $invoice), $this->updatePayload(['invoice_number' => '']))
            ->assertRedirect(route('invoices.edit', $invoice));

        $this->as($this->user)
            ->get(route('invoices.edit', $invoice))
            ->assertSee('Opslaan is niet gelukt');
    }
}
