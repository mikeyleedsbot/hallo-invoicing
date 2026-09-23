<?php

namespace Tests\Unit;

use App\Services\BankImport\BankImportException;
use App\Services\BankImport\StatementReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Elk formaat moet dezelfde twee transacties opleveren: een bijschrijving van
 * 1210,00 en een afschrijving van 75,50. Zo is te zien dat de herkenning en de
 * losse parsers hetzelfde resultaat geven, ongeacht wat de bank levert.
 */
class StatementReaderTest extends TestCase
{
    private function fixture(string $name): string
    {
        return __DIR__ . '/../Fixtures/bank/' . $name;
    }

    private function read(string $name): array
    {
        return (new StatementReader())->read($this->fixture($name), $name);
    }

    public static function bestanden(): array
    {
        return [
            'ING met puntkomma'   => ['ing_semicolon.csv', 'csv'],
            'ING met komma'       => ['ing_comma.csv', 'csv'],
            'ASN met puntkomma'   => ['asn_semicolon.csv', 'csv'],
            'ASN met komma'       => ['asn_comma.csv', 'csv'],
            'CAMT.053'            => ['camt053.xml', 'camt053'],
            'CAMT.053 in een zip' => ['camt053.zip', 'camt053'],
            'MT940'               => ['mt940.sta', 'mt940'],
        ];
    }

    #[DataProvider('bestanden')]
    public function test_elk_formaat_geeft_dezelfde_transacties(string $file, string $expectedFormat): void
    {
        $result = $this->read($file);

        $this->assertSame($expectedFormat, $result['format'], "Formaat van {$file}");
        $this->assertCount(2, $result['transactions']);

        [$incoming, $outgoing] = $result['transactions'];

        $this->assertSame('2026-01-15', $incoming->bookingDate);
        $this->assertSame(1210.00, $incoming->amount);
        $this->assertStringContainsString('20260018', (string) $incoming->description);

        $this->assertSame('2026-01-16', $outgoing->bookingDate);
        $this->assertSame(-75.50, $outgoing->amount);
    }

    #[DataProvider('bestanden')]
    public function test_tegenpartij_wordt_uit_elk_formaat_gehaald(string $file): void
    {
        [$incoming] = $this->read($file)['transactions'];

        $this->assertStringContainsString('Testklant', (string) $incoming->counterpartyName);
        $this->assertSame('NL02BANK0987654321', $incoming->counterpartyIban);
    }

    public function test_periode_wordt_afgeleid(): void
    {
        $result = $this->read('camt053.xml');

        $this->assertSame('2026-01-15', $result['from']);
        $this->assertSame('2026-01-16', $result['to']);
        $this->assertSame('NL01BANK0123456789', $result['iban']);
    }

    public function test_zelfde_regel_krijgt_dezelfde_vingerafdruk(): void
    {
        // Dezelfde transactie uit twee verschillende CSV-varianten
        [$a] = $this->read('asn_semicolon.csv')['transactions'];
        [$b] = $this->read('asn_comma.csv')['transactions'];

        $this->assertSame($a->fingerprint(), $b->fingerprint());
    }

    public function test_onbekend_bestand_wordt_geweigerd(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bank');
        file_put_contents($path, "dit is geen bankafschrift\nzomaar wat tekst\n");

        try {
            $this->expectException(BankImportException::class);
            (new StatementReader())->read($path, 'onzin.txt');
        } finally {
            @unlink($path);
        }
    }

    public function test_leeg_bestand_wordt_geweigerd(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bank');
        file_put_contents($path, '   ');

        try {
            $this->expectException(BankImportException::class);
            (new StatementReader())->read($path, 'leeg.csv');
        } finally {
            @unlink($path);
        }
    }
}
