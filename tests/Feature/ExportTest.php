<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'              => 'Export Tester',
            'email'             => 'export@example.test',
            'password'          => bcrypt('password'),
            'status'            => User::STATUS_APPROVED,
            'email_verified_at' => now(),
            'mfa_enabled'       => true,
            'mfa_confirmed_at'  => now(),
        ]);

        $this->actingAs($this->user);
        $this->customer = Customer::create([
            'name'    => 'Export Klant',
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

    /** Lees de sheet-XML uit de xlsx-response. */
    private function sheetXmlFromResponse(string $content): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsxtest');
        file_put_contents($tmp, $content);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tmp), 'xlsx is geen geldige zip');
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($tmp);

        $this->assertNotFalse($xml, 'sheet1.xml ontbreekt in xlsx');

        return $xml;
    }

    public function test_facturen_export_geeft_geldig_xlsx(): void
    {
        $this->makeInvoice('EXP-001');

        $response = $this->as($this->user)->get(route('invoices.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $response->getContent());

        $xml = $this->sheetXmlFromResponse($response->getContent());
        $this->assertStringContainsString('EXP-001', $xml);
        $this->assertStringContainsString('Factuurnummer', $xml);
        $this->assertStringContainsString('Totaal incl. BTW', $xml);
    }

    public function test_facturen_export_respecteert_statusfilter(): void
    {
        $this->makeInvoice('EXP-BETAALD', 'paid');
        $this->makeInvoice('EXP-VERZONDEN', 'sent');

        $response = $this->as($this->user)->get(route('invoices.export', ['status' => 'paid']));

        $xml = $this->sheetXmlFromResponse($response->getContent());
        $this->assertStringContainsString('EXP-BETAALD', $xml);
        $this->assertStringNotContainsString('EXP-VERZONDEN', $xml);
    }

    public function test_offertes_export_geeft_geldig_xlsx(): void
    {
        Quote::create([
            'quote_number' => 'EXPQ-001',
            'customer_id'  => $this->customer->id,
            'quote_date'   => now(),
            'valid_until'  => now()->addDays(30),
            'valid_days'   => 30,
            'subtotal'     => 100,
            'vat_amount'   => 21,
            'total'        => 121,
            'status'       => 'sent',
        ]);

        $response = $this->as($this->user)->get(route('quotes.export'));

        $response->assertOk();
        $xml = $this->sheetXmlFromResponse($response->getContent());
        $this->assertStringContainsString('EXPQ-001', $xml);
        $this->assertStringContainsString('Offertenummer', $xml);
    }
}
