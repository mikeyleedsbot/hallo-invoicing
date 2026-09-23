<?php

namespace Tests\Feature;

use App\Http\Controllers\InvoiceController;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\User;
use App\Services\InvoicePdfGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Bij verlegde BTW blijft op de PDF alleen het totaal over: geen
 * BTW-kolommen, geen BTW-bedrag en geen "Totaal excl. BTW".
 *
 * Een regel met 0% BTW is iets wezenlijk anders — daar moet alles
 * gewoon blijven staan.
 */
class ReverseChargePdfTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'              => 'PDF Tester',
            'email'             => 'pdf@example.test',
            'password'          => bcrypt('password'),
            'status'            => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled'       => true,
            'mfa_confirmed_at'  => now(),
        ]);

        $this->actingAs($this->user);

        $this->customer = Customer::create([
            'name'       => 'PDF Klant',
            'email'      => 'klant@example.test',
            'country'    => 'Nederland',
            'vat_number' => 'BE0123456789',
        ]);
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['mfa_verified' => true]);
    }

    /** Maak een factuur via de gewone route en render de PDF-HTML. */
    private function pdfHtmlFor(array $overrides, float $vatRate = 21): string
    {
        $this->as($this->user)->post(route('invoices.store'), array_merge([
            'customer_id'    => $this->customer->id,
            'invoice_number' => 'F-' . uniqid(),
            'invoice_date'   => now()->format('Y-m-d'),
            'due_date'       => now()->addDays(14)->format('Y-m-d'),
            'payment_terms'  => 14,
            'lines'          => [
                ['description' => 'Werk', 'quantity' => 2, 'unit_price' => 100, 'vat_rate' => $vatRate],
            ],
        ], $overrides))->assertSessionHasNoErrors();

        $invoice = Invoice::with('customer', 'lines', 'template')->latest('id')->first();

        $prepare = new ReflectionMethod(InvoiceController::class, 'prepareInvoiceData');
        $prepare->setAccessible(true);
        $data = $prepare->invoke(app(InvoiceController::class), $invoice);

        $template = $invoice->template ?? InvoiceTemplate::getDefaultForInvoices();

        return app(InvoicePdfGenerator::class)->generateFromTemplateToHtml($template, $data);
    }

    public function test_verlegd_laat_alleen_het_totaal_over(): void
    {
        $html = $this->pdfHtmlFor(['vat_reverse_charged' => 1]);

        $this->assertStringNotContainsString('Totaal excl. BTW', $html);
        $this->assertStringNotContainsString('incl. BTW', $html);
        $this->assertStringNotContainsString('BTW%', $html);

        $this->assertStringContainsString('Totaal:', $html);
        $this->assertStringContainsString('BTW verlegd naar BTW-nummer afnemer', $html);
        $this->assertStringContainsString('200,00', $html);
    }

    public function test_nul_procent_btw_verbergt_juist_niks(): void
    {
        $html = $this->pdfHtmlFor([], vatRate: 0);

        // 0% is geen verlegging: de volledige opbouw blijft staan
        $this->assertStringContainsString('Totaal excl. BTW', $html);
        $this->assertStringContainsString('Totaal incl. BTW', $html);
        $this->assertStringContainsString('BTW%', $html);
        $this->assertStringNotContainsString('BTW verlegd', $html);
    }

    /** Positie waarop het verlegd-veld in de PDF terechtkomt (left in mm). */
    private function noteLeftMm(string $html): ?string
    {
        preg_match_all('/<div class="abs" style="left:([\d.]+)mm;top:([\d.]+)mm;[^"]*">([^<]*BTW verlegd[^<]*)</', $html, $m);

        return $m[1][0] ?? null;
    }

    public function test_vermelding_volgt_de_positie_uit_de_template(): void
    {
        $template = InvoiceTemplate::getDefaultForInvoices();
        $positions = $template->field_positions ?: InvoicePdfGenerator::getDefaultPositions();

        // Verplaats de vermelding naar een duidelijk afwijkende plek
        $positions['reverse_charge_note'] = array_merge(
            $positions['reverse_charge_note'] ?? [],
            ['x' => 400, 'y' => 300, 'width' => 300, 'height' => 30]
        );
        $template->update(['field_positions' => $positions]);

        $html = $this->pdfHtmlFor(['vat_reverse_charged' => 1, 'template_id' => $template->id]);

        $this->assertStringContainsString('BTW verlegd naar BTW-nummer afnemer', $html);

        // 400 van 850 canvas-px op 210mm breed = ~98,8mm, niet de standaard ~12,4mm
        $left = $this->noteLeftMm($html);
        $this->assertNotNull($left, 'verlegd-veld niet teruggevonden in de HTML');
        $this->assertEqualsWithDelta(98.8, (float) $left, 0.5);
    }

    public function test_template_zonder_het_veld_houdt_de_oude_plek(): void
    {
        // Bestaande templates kennen het veld nog niet
        $template = InvoiceTemplate::getDefaultForInvoices();
        $positions = $template->field_positions ?: InvoicePdfGenerator::getDefaultPositions();
        unset($positions['reverse_charge_note']);
        $template->update(['field_positions' => $positions]);

        $html = $this->pdfHtmlFor(['vat_reverse_charged' => 1, 'template_id' => $template->id]);

        $this->assertStringContainsString('BTW verlegd naar BTW-nummer afnemer', $html);

        // Standaardpositie x=50 van 850 op 210mm = ~12,4mm
        $this->assertEqualsWithDelta(12.4, (float) $this->noteLeftMm($html), 0.5);
    }

    public function test_zonder_verlegging_wordt_het_veld_niet_gerenderd(): void
    {
        $html = $this->pdfHtmlFor([]);

        // Het veld zit in elke template, maar mag op een gewone factuur geen
        // (leeg, gekleurd) vlak achterlaten
        $this->assertStringNotContainsString('BTW verlegd', $html);
        $this->assertStringNotContainsString('#fffbeb', $html);
    }

    public function test_editor_heeft_geen_vaste_verlegd_melding_meer(): void
    {
        $template = InvoiceTemplate::getDefaultForInvoices();

        $html = $this->as($this->user)
            ->get(route('templates.editor', $template))
            ->assertOk()
            ->getContent();

        // De oude, niet-verplaatsbare weergave mag niet terugkomen
        $this->assertStringNotContainsString('Vaste BTW verlegd melding', $html);
        $this->assertStringNotContainsString('Ghost: verlegde BTW', $html);

        // Het veld is er wel als gewoon, beschermd veld
        $this->assertStringContainsString('BTW verlegd-vermelding', $html);
        $this->assertStringContainsString("protectedFields: ['reverse_charge_note']", $html);
    }

    public function test_nieuwe_template_bevat_het_veld_op_de_oude_plek(): void
    {
        // Standaardtemplate die bij een nieuwe gebruiker wordt aangemaakt
        $positions = InvoiceTemplate::getDefaultForInvoices()->field_positions
            ?: InvoicePdfGenerator::getDefaultPositions();

        $this->assertArrayHasKey('reverse_charge_note', $positions);
        $this->assertSame(50, $positions['reverse_charge_note']['x']);
        $this->assertSame(862, $positions['reverse_charge_note']['y']);
    }

    public function test_gewone_factuur_houdt_alle_velden(): void
    {
        $html = $this->pdfHtmlFor([]);

        $this->assertStringContainsString('Totaal excl. BTW', $html);
        $this->assertStringContainsString('Totaal incl. BTW', $html);
        $this->assertStringContainsString('BTW%', $html);
        $this->assertStringContainsString('242,00', $html);
    }
}
