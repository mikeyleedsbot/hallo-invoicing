<?php

namespace Tests\Unit;

use App\Services\VatCalculator;
use PHPUnit\Framework\TestCase;

class VatCalculatorTest extends TestCase
{
    public function test_exclusieve_invoer_telt_btw_erbij(): void
    {
        $t = VatCalculator::totals([
            ['quantity' => 1, 'unit_price' => 100, 'vat_rate' => 21],
        ]);

        $this->assertSame(100.00, $t['subtotal']);
        $this->assertSame(21.00, $t['vat_amount']);
        $this->assertSame(121.00, $t['total']);
    }

    public function test_inclusieve_invoer_haalt_btw_eruit(): void
    {
        $t = VatCalculator::totals([
            ['quantity' => 1, 'unit_price' => 121, 'vat_rate' => 21],
        ], pricesIncludeVat: true);

        $this->assertSame(100.00, $t['subtotal']);
        $this->assertSame(21.00, $t['vat_amount']);
        $this->assertSame(121.00, $t['total']);
    }

    /**
     * De kern van inclusieve invoer: het totaal is exact het bedrag dat is
     * ingetypt. Bij afronden naar excl. en weer terug zou hier 99,99 uitkomen.
     */
    public function test_rond_bedrag_incl_btw_blijft_exact(): void
    {
        $t = VatCalculator::totals([
            ['quantity' => 1, 'unit_price' => 100, 'vat_rate' => 21],
        ], pricesIncludeVat: true);

        $this->assertSame(82.64, $t['subtotal']);
        $this->assertSame(17.36, $t['vat_amount']);
        $this->assertSame(100.00, $t['total']);
    }

    public function test_consumentenprijs_49_95_incl_btw(): void
    {
        $t = VatCalculator::totals([
            ['quantity' => 3, 'unit_price' => 49.95, 'vat_rate' => 21],
        ], pricesIncludeVat: true);

        // 3 x 49,95 = 149,85 incl.
        $this->assertSame(123.84, $t['subtotal']);
        $this->assertSame(26.01, $t['vat_amount']);
        $this->assertSame(149.85, $t['total']);
    }

    public function test_nul_procent_werkt_in_beide_richtingen(): void
    {
        $excl = VatCalculator::totals([
            ['quantity' => 2, 'unit_price' => 50, 'vat_rate' => 0],
        ]);
        $incl = VatCalculator::totals([
            ['quantity' => 2, 'unit_price' => 50, 'vat_rate' => 0],
        ], pricesIncludeVat: true);

        $this->assertSame(100.00, $excl['total']);
        $this->assertSame(0.00, $excl['vat_amount']);
        $this->assertSame(100.00, $incl['total']);
        $this->assertSame(0.00, $incl['vat_amount']);
    }

    public function test_btw_verlegd_rekent_geen_btw(): void
    {
        $t = VatCalculator::totals([
            ['quantity' => 1, 'unit_price' => 100, 'vat_rate' => 21],
            ['quantity' => 2, 'unit_price' => 50, 'vat_rate' => 9],
        ], reverseCharged: true);

        $this->assertSame(200.00, $t['subtotal']);
        $this->assertSame(0.00, $t['vat_amount']);
        $this->assertSame(200.00, $t['total']);
    }

    public function test_gemengde_tarieven_per_regel(): void
    {
        $t = VatCalculator::totals([
            ['quantity' => 1, 'unit_price' => 100, 'vat_rate' => 21],
            ['quantity' => 1, 'unit_price' => 100, 'vat_rate' => 9],
            ['quantity' => 1, 'unit_price' => 100, 'vat_rate' => 0],
        ]);

        $this->assertSame(300.00, $t['subtotal']);
        $this->assertSame(30.00, $t['vat_amount']);
        $this->assertSame(330.00, $t['total']);
    }

    public function test_gemengde_tarieven_inclusief_telt_op_tot_ingevoerd_bedrag(): void
    {
        $t = VatCalculator::totals([
            ['quantity' => 1, 'unit_price' => 121, 'vat_rate' => 21],
            ['quantity' => 1, 'unit_price' => 109, 'vat_rate' => 9],
        ], pricesIncludeVat: true);

        $this->assertSame(200.00, $t['subtotal']);
        $this->assertSame(30.00, $t['vat_amount']);
        $this->assertSame(230.00, $t['total']);
    }

    public function test_regelsplitsing_geeft_excl_btw_en_incl(): void
    {
        $r = VatCalculator::line(2, 60.50, 21, pricesIncludeVat: true);

        $this->assertSame(100.00, $r['excl']);
        $this->assertSame(21.00, $r['vat']);
        $this->assertSame(121.00, $r['incl']);
    }

    public function test_subtotaal_plus_btw_is_altijd_gelijk_aan_totaal(): void
    {
        foreach ([[3, 33.33, 21], [7, 12.95, 9], [1, 0.01, 21], [11, 9.99, 21]] as [$qty, $price, $rate]) {
            foreach ([false, true] as $incl) {
                $t = VatCalculator::totals(
                    [['quantity' => $qty, 'unit_price' => $price, 'vat_rate' => $rate]],
                    pricesIncludeVat: $incl
                );

                $this->assertSame(
                    round($t['subtotal'] + $t['vat_amount'], 2),
                    $t['total'],
                    "Totaal klopt niet voor {$qty} x {$price} @ {$rate}%"
                );
            }
        }
    }
}
