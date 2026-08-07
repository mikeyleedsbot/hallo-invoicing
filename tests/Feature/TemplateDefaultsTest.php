<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Een template kan los standaard zijn voor facturen en voor offertes,
 * en kan gedupliceerd worden.
 */
class TemplateDefaultsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'              => 'Template Tester',
            'email'             => 'templates@example.test',
            'password'          => bcrypt('password'),
            'status'            => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled'       => true,
            'mfa_confirmed_at'  => now(),
        ]);

        $this->actingAs($this->user);

        $this->customer = Customer::create([
            'name'    => 'Template Klant',
            'email'   => 'klant@example.test',
            'country' => 'Nederland',
        ]);

        // Nieuwe gebruikers krijgen automatisch een standaardtemplate; die
        // weghalen zodat elke test zelf bepaalt wat er staat.
        InvoiceTemplate::query()->delete();
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    private function makeTemplate(string $name, array $attributes = []): InvoiceTemplate
    {
        return InvoiceTemplate::create(array_merge([
            'name'      => $name,
            'page_size' => 'A4',
        ], $attributes));
    }

    public function test_standaard_voor_facturen_en_offertes_staan_los_van_elkaar(): void
    {
        $a = $this->makeTemplate('Facturen-sjabloon');
        $b = $this->makeTemplate('Offertes-sjabloon');

        $this->as($this->user)
            ->post(route('templates.set-default', $a), ['type' => 'invoice'])
            ->assertSessionHas('success');

        $this->as($this->user)
            ->post(route('templates.set-default', $b), ['type' => 'quote'])
            ->assertSessionHas('success');

        $this->assertTrue($a->fresh()->is_default_invoice);
        $this->assertFalse($a->fresh()->is_default_quote);
        $this->assertFalse($b->fresh()->is_default_invoice);
        $this->assertTrue($b->fresh()->is_default_quote);
    }

    public function test_nieuwe_factuurstandaard_laat_de_offertestandaard_met_rust(): void
    {
        $a = $this->makeTemplate('Eerste', ['is_default_invoice' => true, 'is_default_quote' => true]);
        $b = $this->makeTemplate('Tweede');

        $this->as($this->user)->post(route('templates.set-default', $b), ['type' => 'invoice']);

        // Alleen de factuurstandaard verschuift
        $this->assertFalse($a->fresh()->is_default_invoice);
        $this->assertTrue($a->fresh()->is_default_quote);
        $this->assertTrue($b->fresh()->is_default_invoice);
        $this->assertFalse($b->fresh()->is_default_quote);
    }

    public function test_beide_tegelijk_instellen(): void
    {
        $a = $this->makeTemplate('Alles');

        $this->as($this->user)->post(route('templates.set-default', $a), ['type' => 'both']);

        $this->assertTrue($a->fresh()->is_default_invoice);
        $this->assertTrue($a->fresh()->is_default_quote);
    }

    public function test_ongeldige_soort_wordt_geweigerd(): void
    {
        $a = $this->makeTemplate('Sjabloon');

        $this->as($this->user)
            ->post(route('templates.set-default', $a), ['type' => 'onzin'])
            ->assertSessionHasErrors('type');

        $this->assertFalse($a->fresh()->is_default_invoice);
    }

    public function test_nieuwe_factuur_en_offerte_pakken_hun_eigen_standaard(): void
    {
        $invoiceTpl = $this->makeTemplate('Voor facturen', ['is_default_invoice' => true]);
        $quoteTpl   = $this->makeTemplate('Voor offertes', ['is_default_quote' => true]);

        // Zonder expliciete template moet de juiste standaard gekozen worden
        $this->as($this->user)->post(route('invoices.store'), [
            'customer_id'    => $this->customer->id,
            'invoice_number' => 'F-TPL-1',
            'invoice_date'   => now()->format('Y-m-d'),
            'due_date'       => now()->addDays(14)->format('Y-m-d'),
            'payment_terms'  => 14,
            'lines'          => [['description' => 'Werk', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 21]],
        ])->assertSessionHasNoErrors();

        $this->as($this->user)->post(route('quotes.store'), [
            'customer_id'  => $this->customer->id,
            'quote_number' => 'OF-TPL-1',
            'quote_date'   => now()->format('Y-m-d'),
            'valid_until'  => now()->addDays(30)->format('Y-m-d'),
            'valid_days'   => 30,
            'lines'        => [['description' => 'Advies', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 21]],
        ])->assertSessionHasNoErrors();

        $this->assertSame($invoiceTpl->id, Invoice::latest('id')->first()->template_id);
        $this->assertSame($quoteTpl->id, Quote::latest('id')->first()->template_id);
    }

    public function test_dupliceren_kopieert_de_lay_out_maar_niet_de_standaard(): void
    {
        $positions = ['invoice_number' => ['x' => 10, 'y' => 20, 'width' => 100]];

        $original = $this->makeTemplate('Mijn sjabloon', [
            'is_default_invoice' => true,
            'is_default_quote'   => true,
            'page_size'          => 'Letter',
            'field_positions'    => $positions,
        ]);

        $this->as($this->user)
            ->post(route('templates.duplicate', $original))
            ->assertSessionHas('success');

        $copy = InvoiceTemplate::where('name', 'Mijn sjabloon (kopie)')->first();

        $this->assertNotNull($copy);
        $this->assertSame($positions, $copy->field_positions);
        $this->assertSame('Letter', $copy->page_size);

        // Een kopie mag de standaard-status niet overnemen
        $this->assertFalse($copy->is_default_invoice);
        $this->assertFalse($copy->is_default_quote);
        $this->assertTrue($original->fresh()->is_default_invoice);
    }

    public function test_dupliceren_maakt_losse_kopieen_van_logo_en_achtergrond(): void
    {
        Storage::fake();

        Storage::put('template-files/logos/logo.png', 'logo-inhoud');
        Storage::put('template-files/backgrounds/bg.png', 'achtergrond-inhoud');

        $original = $this->makeTemplate('Met afbeeldingen', [
            'logo_path'       => 'template-files/logos/logo.png',
            'background_path' => 'template-files/backgrounds/bg.png',
        ]);

        $this->as($this->user)->post(route('templates.duplicate', $original));

        $copy = InvoiceTemplate::where('name', 'Met afbeeldingen (kopie)')->firstOrFail();

        // Eigen pad, zelfde inhoud
        $this->assertNotSame($original->logo_path, $copy->logo_path);
        $this->assertNotSame($original->background_path, $copy->background_path);
        Storage::assertExists($copy->logo_path);
        Storage::assertExists($copy->background_path);
        $this->assertSame('logo-inhoud', Storage::get($copy->logo_path));

        // Het origineel verwijderen mag de kopie niet kapotmaken
        $this->as($this->user)->delete(route('templates.destroy', $original));

        Storage::assertMissing('template-files/logos/logo.png');
        Storage::assertExists($copy->logo_path);
    }

    public function test_meerdere_kopieen_krijgen_oplopende_namen(): void
    {
        $original = $this->makeTemplate('Sjabloon');

        $this->as($this->user)->post(route('templates.duplicate', $original));
        $this->as($this->user)->post(route('templates.duplicate', $original));

        $this->assertDatabaseHas('invoice_templates', ['name' => 'Sjabloon (kopie)']);
        $this->assertDatabaseHas('invoice_templates', ['name' => 'Sjabloon (kopie 2)']);
    }

    public function test_verwijderen_van_standaard_wijst_een_andere_aan(): void
    {
        $a = $this->makeTemplate('Eerste', ['is_default_invoice' => true, 'is_default_quote' => true]);
        $b = $this->makeTemplate('Tweede');

        $this->as($this->user)->delete(route('templates.destroy', $a));

        $this->assertTrue($b->fresh()->is_default_invoice);
        $this->assertTrue($b->fresh()->is_default_quote);
    }

    /**
     * De templatekeuze in de formulieren markeert de standaard van het juiste
     * soort. Verwees eerder naar de weggehaalde kolom is_default.
     */
    public function test_formulieren_markeren_de_juiste_standaardtemplate(): void
    {
        $invoiceTpl = $this->makeTemplate('Factuursjabloon', ['is_default_invoice' => true]);
        $quoteTpl   = $this->makeTemplate('Offertesjabloon', ['is_default_quote' => true]);

        foreach ([route('invoices.create'), route('quotes.create')] as $url) {
            $this->as($this->user)->get($url)->assertOk()->assertSee('(standaard)');
        }

        // Op het factuurformulier hoort de markering bij de factuurstandaard
        $html = $this->as($this->user)->get(route('invoices.create'))->getContent();
        $this->assertMatchesRegularExpression(
            '/' . preg_quote($invoiceTpl->name, '/') . '\s*\(standaard\)/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/' . preg_quote($quoteTpl->name, '/') . '\s*\(standaard\)/',
            $html
        );

        // En op het offerteformulier bij de offertestandaard
        $html = $this->as($this->user)->get(route('quotes.create'))->getContent();
        $this->assertMatchesRegularExpression(
            '/' . preg_quote($quoteTpl->name, '/') . '\s*\(standaard\)/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/' . preg_quote($invoiceTpl->name, '/') . '\s*\(standaard\)/',
            $html
        );
    }

    public function test_overzicht_toont_beide_badges(): void
    {
        $this->makeTemplate('Facturen-sjabloon', ['is_default_invoice' => true]);
        $this->makeTemplate('Offertes-sjabloon', ['is_default_quote' => true]);

        $this->as($this->user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertSee('Standaard facturen')
            ->assertSee('Standaard offertes')
            ->assertSee('Dupliceer');
    }
}
