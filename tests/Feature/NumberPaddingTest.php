<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * De breedte van factuur- en offertenummers volgt uit hoe de teller in de
 * instellingen is ingevuld: "0006" geeft 20260006, "6" geeft 20266.
 */
class NumberPaddingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'              => 'Nummer Tester',
            'email'             => 'nummers@example.test',
            'password'          => bcrypt('password'),
            'status'            => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled'       => true,
            'mfa_confirmed_at'  => now(),
        ]);

        $this->actingAs($this->user);
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    /** Sla de instellingen op met een teller zoals je hem intypt. */
    private function saveCounter(string $invoiceCounter, string $prefix = '2026'): AppSetting
    {
        $settings = AppSetting::get();

        $this->as($this->user)
            ->put(route('settings.update'), [
                'default_vat_rate'         => 21,
                'default_payment_terms'    => 14,
                'quote_valid_days'         => 30,
                'currency'                 => 'EUR',
                'currency_symbol'          => '€',
                'date_format'              => 'd-m-Y',
                'invoice_prefix'           => $prefix,
                'quote_prefix'             => $prefix,
                'invoice_number_start'     => $invoiceCounter,
                'quote_number_start'       => $invoiceCounter,
                'credit_surcharge_percent' => 2,
            ])
            ->assertSessionHasNoErrors();

        return $settings->fresh();
    }

    public function test_vier_cijfers_geeft_vier_cijfers(): void
    {
        $settings = $this->saveCounter('0006');

        $this->assertSame(4, $settings->invoice_number_padding);
        $this->assertSame(6, $settings->invoice_number_start);
        $this->assertSame('20260006', $settings->nextInvoiceNumber());
        $this->assertSame('20260006', $settings->nextQuoteNumber());
    }

    public function test_een_cijfer_geeft_geen_voorloopnullen(): void
    {
        $settings = $this->saveCounter('6');

        $this->assertSame(1, $settings->invoice_number_padding);
        $this->assertSame('20266', $settings->nextInvoiceNumber());
    }

    public function test_teller_groter_dan_de_breedte_wordt_niet_afgekapt(): void
    {
        $settings = $this->saveCounter('7');
        $settings->update(['invoice_number_start' => 1234]);

        // Breedte 1, maar het getal is langer: dan telt het getal zelf
        $this->assertSame('20261234', $settings->fresh()->nextInvoiceNumber());
    }

    public function test_formulier_toont_de_teller_met_voorloopnullen(): void
    {
        $this->saveCounter('0006');

        $this->as($this->user)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('value="0006"', false);
    }

    public function test_letters_in_de_teller_worden_geweigerd(): void
    {
        $this->as($this->user)
            ->put(route('settings.update'), [
                'default_vat_rate'         => 21,
                'default_payment_terms'    => 14,
                'quote_valid_days'         => 30,
                'currency'                 => 'EUR',
                'currency_symbol'          => '€',
                'date_format'              => 'd-m-Y',
                'invoice_prefix'           => '2026',
                'quote_prefix'             => '2026',
                'invoice_number_start'     => '00A6',
                'quote_number_start'       => '0006',
                'credit_surcharge_percent' => 2,
            ])
            ->assertSessionHasErrors('invoice_number_start');
    }

    public function test_bestaande_accounts_houden_hun_vijf_cijfers(): void
    {
        // Zolang de teller niet is aangepast blijft de oude opbouw gelden
        $settings = AppSetting::get();
        $settings->update(['invoice_prefix' => '2026', 'invoice_number_start' => 6]);

        $this->assertSame(5, $settings->fresh()->invoice_number_padding);
        $this->assertSame('202600006', $settings->fresh()->nextInvoiceNumber());
    }
}
