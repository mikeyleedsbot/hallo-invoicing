<?php

namespace Tests\Feature;

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\QuoteController;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\Quote;
use App\Models\User;
use App\Services\InvoicePdfGenerator;
use App\Services\TeamService;
use App\Services\TemplatePresets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Bedrijfsnaam en BTW-nummer van de klant als templateveld.
 *
 * De bedrijfsnaam staat standaard in elk sjabloon, het BTW-nummer niet:
 * dat voeg je zelf toe in de editor als je het op de factuur wilt.
 */
class TemplateClientFieldsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'              => 'Template Tester',
            'email'             => 'template-velden@example.test',
            'password'          => bcrypt('password'),
            'status'            => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled'       => true,
            'mfa_confirmed_at'  => now(),
        ]);
        (new TeamService())->createForOwner($this->user);

        $this->actingAs($this->user);

        $this->customer = Customer::create([
            'name'         => 'Jan de Vries',
            'company_name' => 'De Vries Installatietechniek B.V.',
            'email'        => 'jan@example.test',
            'country'      => 'Nederland',
            'vat_number'   => 'NL987654321B01',
        ]);
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    public function test_elk_sjabloon_heeft_de_bedrijfsnaam_van_de_klant_maar_geen_btw_nummer(): void
    {
        foreach (TemplatePresets::keys() as $key) {
            $positions = TemplatePresets::positions($key);

            $this->assertArrayHasKey('client_company', $positions, "sjabloon $key mist de bedrijfsnaam");
            $this->assertArrayNotHasKey('client_vat', $positions, "sjabloon $key hoort geen BTW-nummer te hebben");

            // Bedrijfsnaam staat boven de contactpersoon
            $this->assertLessThan($positions['client_name']['y'], $positions['client_company']['y'], "sjabloon $key");
        }
    }

    public function test_het_klantblok_overlapt_niets_in_de_sjablonen(): void
    {
        $blok = ['static_text_lbl_client', 'client_company', 'client_name', 'client_address', 'client_postal_code', 'client_email'];

        foreach (TemplatePresets::keys() as $key) {
            $p = TemplatePresets::positions($key);

            // Onder elkaar zonder overlap
            for ($i = 1; $i < count($blok); $i++) {
                $boven = $p[$blok[$i - 1]];
                $onder = $p[$blok[$i]];
                $this->assertLessThanOrEqual(
                    $onder['y'],
                    $boven['y'] + $boven['height'],
                    "sjabloon $key: {$blok[$i - 1]} overlapt {$blok[$i]}"
                );
            }

            // Ruimte vrij boven de artikeltabel en onder de factuurgegevens rechts
            $email = $p['client_email'];
            $this->assertLessThanOrEqual($p['items_table']['y'], $email['y'] + $email['height'], "sjabloon $key: klantblok loopt in de tabel");
            $this->assertGreaterThanOrEqual(
                $p['due_date']['y'] + $p['due_date']['height'],
                $p['static_text_lbl_client']['y'],
                "sjabloon $key: klantblok begint in de factuurgegevens"
            );

            // Modern Blauw: klantblok begint onder de blauwe band
            if (isset($p['static_rect_header'])) {
                $band = $p['static_rect_header'];
                $this->assertGreaterThanOrEqual($band['y'] + $band['height'], $p['static_text_lbl_client']['y'], "sjabloon $key");
            }
        }
    }

    public function test_nieuw_team_krijgt_een_template_met_de_bedrijfsnaam_van_de_klant(): void
    {
        $template = InvoiceTemplate::getDefaultForInvoices();

        $this->assertArrayHasKey('client_company', $template->field_positions);
    }

    public function test_factuur_pdf_toont_bedrijfsnaam_en_btw_nummer_van_de_klant(): void
    {
        $this->as($this->user)->post(route('invoices.store'), [
            'customer_id'    => $this->customer->id,
            'invoice_number' => 'F-' . uniqid(),
            'invoice_date'   => now()->format('Y-m-d'),
            'due_date'       => now()->addDays(14)->format('Y-m-d'),
            'payment_terms'  => 14,
            'lines'          => [['description' => 'Werk', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 21]],
        ])->assertSessionHasNoErrors();

        $invoice = Invoice::with('customer', 'lines', 'template')->latest('id')->first();

        $prepare = new ReflectionMethod(InvoiceController::class, 'prepareInvoiceData');
        $prepare->setAccessible(true);
        $data = $prepare->invoke(app(InvoiceController::class), $invoice);

        $this->assertSame('De Vries Installatietechniek B.V.', $data['client_company']);
        $this->assertSame('NL987654321B01', $data['client_vat']);

        // Standaardtemplate: bedrijfsnaam wel, BTW-nummer niet
        $template = InvoiceTemplate::getDefaultForInvoices();
        $html = app(InvoicePdfGenerator::class)->generateFromTemplateToHtml($template, $data);
        $this->assertStringContainsString('De Vries Installatietechniek B.V.', $html);
        $this->assertStringNotContainsString('NL987654321B01', $html);

        // Wie het BTW-nummer toevoegt in de editor, ziet het op de PDF
        $positions = $template->field_positions;
        $positions['client_vat'] = ['x' => 50, 'y' => 426, 'width' => 320, 'height' => 24, 'fontSize' => 14, 'align' => 'left'];
        $template->update(['field_positions' => $positions]);

        $html = app(InvoicePdfGenerator::class)->generateFromTemplateToHtml($template->refresh(), $data);
        $this->assertStringContainsString('NL987654321B01', $html);
    }

    public function test_offerte_geeft_dezelfde_klantvelden_mee(): void
    {
        $quote = Quote::create([
            'quote_number' => 'O-' . uniqid(),
            'customer_id'  => $this->customer->id,
            'quote_date'   => now(),
            'valid_until'  => now()->addDays(30),
            'subtotal'     => 100,
            'vat_amount'   => 21,
            'total'        => 121,
            'status'       => 'draft',
        ]);

        $prepare = new ReflectionMethod(QuoteController::class, 'prepareQuoteData');
        $prepare->setAccessible(true);
        $data = $prepare->invoke(app(QuoteController::class), $quote->load('customer', 'lines'));

        $this->assertSame('De Vries Installatietechniek B.V.', $data['client_company']);
        $this->assertSame('NL987654321B01', $data['client_vat']);
    }

    public function test_editor_scheidt_eigen_gegevens_van_klantgegevens(): void
    {
        $template = InvoiceTemplate::getDefaultForInvoices();

        $html = $this->as($this->user)
            ->get(route('templates.editor', $template))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Eigen gegevens', $html);
        $this->assertStringContainsString('Niet van de klant', $html);
        $this->assertStringContainsString("id: 'client_company'", $html);
        $this->assertStringContainsString("id: 'client_vat'", $html);
    }
}
