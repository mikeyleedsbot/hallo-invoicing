<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bedragen kunnen per factuur/offerte exclusief of inclusief BTW worden
 * ingevoerd. De keuze wordt opgeslagen zodat bewerken op dezelfde manier
 * doorgaat.
 */
class PricesIncludeVatTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'              => 'BTW Tester',
            'email'             => 'btw@example.test',
            'password'          => bcrypt('password'),
            'status'            => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled'       => true,
            'mfa_confirmed_at'  => now(),
        ]);

        $this->actingAs($this->user);

        $this->customer = Customer::create([
            'name'       => 'BTW Klant',
            'email'      => 'klant@example.test',
            'country'    => 'Nederland',
            'vat_number' => 'NL123456789B01',
        ]);
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    private function invoicePayload(array $overrides = [], ?array $lines = null): array
    {
        return array_merge([
            'customer_id'    => $this->customer->id,
            'invoice_number' => 'F-' . uniqid(),
            'invoice_date'   => now()->format('Y-m-d'),
            'due_date'       => now()->addDays(14)->format('Y-m-d'),
            'payment_terms'  => 14,
            'lines'          => $lines ?? [
                ['description' => 'Werk', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 21],
            ],
        ], $overrides);
    }

    public function test_standaard_is_exclusief_btw(): void
    {
        $this->as($this->user)
            ->post(route('invoices.store'), $this->invoicePayload())
            ->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->first();

        $this->assertFalse($invoice->prices_include_vat);
        $this->assertSame(100.00, (float) $invoice->subtotal);
        $this->assertSame(21.00, (float) $invoice->vat_amount);
        $this->assertSame(121.00, (float) $invoice->total);
        // Ingevoerde prijs blijft ongewijzigd op de regel staan
        $this->assertSame(100.00, (float) $invoice->lines->first()->unit_price);
    }

    public function test_inclusief_btw_haalt_de_btw_uit_het_bedrag(): void
    {
        $this->as($this->user)
            ->post(route('invoices.store'), $this->invoicePayload(
                ['prices_include_vat' => 1],
                [['description' => 'Werk', 'quantity' => 1, 'unit_price' => 121, 'vat_rate' => 21]]
            ))
            ->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->first();

        $this->assertTrue($invoice->prices_include_vat);
        $this->assertSame(100.00, (float) $invoice->subtotal);
        $this->assertSame(21.00, (float) $invoice->vat_amount);
        $this->assertSame(121.00, (float) $invoice->total);
        // De regel bewaart het bedrag zoals ingevoerd (incl. BTW)
        $this->assertSame(121.00, (float) $invoice->lines->first()->unit_price);
    }

    /**
     * Het totaal moet exact het ingetypte bedrag zijn. Zou de prijs naar
     * excl. BTW omgerekend worden opgeslagen, dan kwam hier 99,99 uit.
     */
    public function test_rond_bedrag_inclusief_btw_blijft_exact(): void
    {
        $this->as($this->user)
            ->post(route('invoices.store'), $this->invoicePayload(
                ['prices_include_vat' => 1],
                [['description' => 'Pakket', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 21]]
            ))
            ->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->first();

        $this->assertSame(82.64, (float) $invoice->subtotal);
        $this->assertSame(17.36, (float) $invoice->vat_amount);
        $this->assertSame(100.00, (float) $invoice->total);
    }

    public function test_instelling_blijft_behouden_bij_bewerken(): void
    {
        $this->as($this->user)->post(route('invoices.store'), $this->invoicePayload(
            ['prices_include_vat' => 1],
            [['description' => 'Werk', 'quantity' => 1, 'unit_price' => 121, 'vat_rate' => 21]]
        ));

        $invoice = Invoice::latest('id')->first();
        $this->assertTrue($invoice->prices_include_vat);

        // Bewerken met dezelfde instelling: bedragen blijven hetzelfde betekenen
        $this->as($this->user)
            ->put(route('invoices.update', $invoice), [
                'invoice_number'     => $invoice->invoice_number,
                'customer_id'        => $this->customer->id,
                'invoice_date'       => now()->format('Y-m-d'),
                'due_date'           => now()->addDays(14)->format('Y-m-d'),
                'payment_terms'      => 14,
                'status'             => 'draft',
                'prices_include_vat' => 1,
                'lines'              => [
                    ['description' => 'Werk', 'quantity' => 2, 'unit_price' => 121, 'vat_rate' => 21],
                ],
            ])
            ->assertSessionHasNoErrors();

        $invoice->refresh();

        $this->assertTrue($invoice->prices_include_vat);
        $this->assertSame(200.00, (float) $invoice->subtotal);
        $this->assertSame(42.00, (float) $invoice->vat_amount);
        $this->assertSame(242.00, (float) $invoice->total);
    }

    public function test_omzetten_naar_exclusief_bij_bewerken(): void
    {
        $this->as($this->user)->post(route('invoices.store'), $this->invoicePayload(
            ['prices_include_vat' => 1],
            [['description' => 'Werk', 'quantity' => 1, 'unit_price' => 121, 'vat_rate' => 21]]
        ));

        $invoice = Invoice::latest('id')->first();

        // Zelfde bedrag, maar nu als exclusief bedoeld: totaal wordt hoger
        $this->as($this->user)
            ->put(route('invoices.update', $invoice), [
                'invoice_number' => $invoice->invoice_number,
                'customer_id'   => $this->customer->id,
                'invoice_date'  => now()->format('Y-m-d'),
                'due_date'      => now()->addDays(14)->format('Y-m-d'),
                'payment_terms' => 14,
                'status'        => 'draft',
                'lines'         => [
                    ['description' => 'Werk', 'quantity' => 1, 'unit_price' => 121, 'vat_rate' => 21],
                ],
            ])
            ->assertSessionHasNoErrors();

        $invoice->refresh();

        $this->assertFalse($invoice->prices_include_vat);
        $this->assertSame(121.00, (float) $invoice->subtotal);
        $this->assertSame(25.41, (float) $invoice->vat_amount);
        $this->assertSame(146.41, (float) $invoice->total);
    }

    public function test_btw_verlegd_forceert_exclusieve_bedragen(): void
    {
        $this->as($this->user)
            ->post(route('invoices.store'), $this->invoicePayload([
                'vat_reverse_charged' => 1,
                'prices_include_vat'  => 1,
            ]))
            ->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->first();

        // Zonder BTW is inclusief invoeren betekenisloos; de vlag wordt genegeerd
        $this->assertTrue($invoice->vat_reverse_charged);
        $this->assertFalse($invoice->prices_include_vat);
        $this->assertSame(100.00, (float) $invoice->subtotal);
        $this->assertSame(0.00, (float) $invoice->vat_amount);
        $this->assertSame(100.00, (float) $invoice->total);
    }

    public function test_formulier_toont_de_keuzeknoppen_en_onthoudt_de_stand(): void
    {
        // Nieuw formulier: keuze zichtbaar en standaard exclusief
        $this->as($this->user)
            ->get(route('invoices.create'))
            ->assertOk()
            ->assertSee('Bedragen invoeren')
            ->assertSee('Excl. BTW')
            ->assertSee('Incl. BTW')
            ->assertSee('pricesIncludeVat: false', false);

        $this->as($this->user)->post(route('invoices.store'), $this->invoicePayload(
            ['prices_include_vat' => 1],
            [['description' => 'Werk', 'quantity' => 1, 'unit_price' => 121, 'vat_rate' => 21]]
        ));

        // Bewerken opent in dezelfde stand
        $this->as($this->user)
            ->get(route('invoices.edit', Invoice::latest('id')->first()))
            ->assertOk()
            ->assertSee('pricesIncludeVat: true', false);
    }

    public function test_overzicht_toont_of_het_bedrag_inclusief_btw_is(): void
    {
        // Normale factuur en een factuur met verlegde BTW
        $this->as($this->user)->post(route('invoices.store'), $this->invoicePayload());
        $this->as($this->user)->post(route('invoices.store'), $this->invoicePayload([
            'vat_reverse_charged' => 1,
        ]));

        $this->as($this->user)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('incl. BTW')
            ->assertSee('BTW verlegd');

        // Bij offertes is er geen verlegging: altijd inclusief BTW
        $this->as($this->user)->post(route('quotes.store'), [
            'customer_id'  => $this->customer->id,
            'quote_number' => 'OF-IDX',
            'quote_date'   => now()->format('Y-m-d'),
            'valid_until'  => now()->addDays(30)->format('Y-m-d'),
            'valid_days'   => 30,
            'lines'        => [
                ['description' => 'Advies', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 21],
            ],
        ]);

        $this->as($this->user)
            ->get(route('quotes.index'))
            ->assertOk()
            ->assertSee('incl. BTW');
    }

    public function test_offerte_inclusief_btw_en_conversie_naar_factuur(): void
    {
        $this->as($this->user)
            ->post(route('quotes.store'), [
                'customer_id'        => $this->customer->id,
                'quote_number'       => 'OF-001',
                'quote_date'         => now()->format('Y-m-d'),
                'valid_until'        => now()->addDays(30)->format('Y-m-d'),
                'valid_days'         => 30,
                'prices_include_vat' => 1,
                'lines'              => [
                    ['description' => 'Advies', 'quantity' => 1, 'unit_price' => 121, 'vat_rate' => 21],
                ],
            ])
            ->assertSessionHasNoErrors();

        $quote = Quote::latest('id')->first();

        $this->assertTrue($quote->prices_include_vat);
        $this->assertSame(100.00, (float) $quote->subtotal);
        $this->assertSame(121.00, (float) $quote->total);

        // De invoerwijze moet meeverhuizen naar de factuur
        $this->as($this->user)->post(route('quotes.convert', $quote));

        $invoice = Invoice::latest('id')->first();

        $this->assertTrue($invoice->prices_include_vat);
        $this->assertSame(121.00, (float) $invoice->lines->first()->unit_price);
        $this->assertSame(100.00, (float) $invoice->subtotal);
        $this->assertSame(121.00, (float) $invoice->total);
    }
}
