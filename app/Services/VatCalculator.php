<?php

namespace App\Services;

/**
 * BTW-berekening voor factuur- en offerteregels.
 *
 * Bedragen kunnen per factuur/offerte exclusief of inclusief BTW zijn
 * ingevoerd (`prices_include_vat`). De opgeslagen `unit_price` is altijd
 * het bedrag zoals ingevoerd; deze service leidt daar het bedrag excl.
 * BTW, het BTW-bedrag en het bedrag incl. BTW uit af.
 *
 * Bij inclusieve invoer wordt de BTW er per regel uitgehaald en is het
 * BTW-bedrag het restant. Daardoor telt subtotaal + BTW altijd exact op
 * tot het ingevoerde bedrag — geen centverschillen op de factuur.
 *
 * Bij BTW verlegd wordt er geen BTW berekend (tarief telt als 0%).
 */
class VatCalculator
{
    /**
     * Splits één regel in bedrag excl. BTW, BTW-bedrag en bedrag incl. BTW.
     *
     * @return array{excl: float, vat: float, incl: float}
     */
    public static function line(
        float $quantity,
        float $unitPrice,
        float $vatRate,
        bool $pricesIncludeVat = false,
        bool $reverseCharged = false
    ): array {
        // Bij verlegde BTW wordt er geen BTW in rekening gebracht
        $rate = $reverseCharged ? 0.0 : max(0.0, $vatRate);
        $lineTotal = round($quantity * $unitPrice, 2);

        if ($pricesIncludeVat) {
            $excl = round($lineTotal / (1 + $rate / 100), 2);

            return [
                'excl' => $excl,
                'vat'  => round($lineTotal - $excl, 2),
                'incl' => $lineTotal,
            ];
        }

        $vat = round($lineTotal * $rate / 100, 2);

        return [
            'excl' => $lineTotal,
            'vat'  => $vat,
            'incl' => round($lineTotal + $vat, 2),
        ];
    }

    /**
     * Tel de totalen op over alle regels.
     *
     * @param  iterable<array{quantity?: mixed, unit_price?: mixed, vat_rate?: mixed}>  $lines
     * @return array{subtotal: float, vat_amount: float, total: float}
     */
    public static function totals(
        iterable $lines,
        bool $pricesIncludeVat = false,
        bool $reverseCharged = false
    ): array {
        $subtotal = 0.0;
        $vat      = 0.0;

        foreach ($lines as $line) {
            $amounts = self::line(
                (float) ($line['quantity'] ?? 0),
                (float) ($line['unit_price'] ?? 0),
                (float) ($line['vat_rate'] ?? 0),
                $pricesIncludeVat,
                $reverseCharged
            );

            $subtotal += $amounts['excl'];
            $vat      += $amounts['vat'];
        }

        $subtotal = round($subtotal, 2);
        $vat      = round($vat, 2);

        return [
            'subtotal'   => $subtotal,
            'vat_amount' => $vat,
            'total'      => round($subtotal + $vat, 2),
        ];
    }
}
