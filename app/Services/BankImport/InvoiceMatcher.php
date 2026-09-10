<?php

namespace App\Services\BankImport;

use App\Models\BankTransaction;
use App\Models\Invoice;
use Illuminate\Support\Collection;

/**
 * Zoekt bij een bijschrijving de factuur die erbij hoort.
 *
 * Er wordt gescoord op vier signalen, van sterk naar zwak:
 *   1. het factuurnummer staat in de omschrijving
 *   2. het bedrag komt overeen met wat er nog openstaat
 *   3. de klantnaam lijkt op de tegenpartij
 *   4. de transactie valt op of na de factuurdatum
 *
 * Alleen bij een hoge score én één duidelijke kandidaat wordt automatisch
 * gekoppeld. De rest komt als suggestie in de lijst, zodat de gebruiker zelf
 * beslist.
 */
class InvoiceMatcher
{
    /** Vanaf deze score koppelen we zonder tussenkomst. */
    public const AUTO_THRESHOLD = 85;

    /** Daaronder tonen we het als suggestie. */
    public const SUGGEST_THRESHOLD = 30;

    /**
     * @param  Collection<int, Invoice>  $invoices  openstaande facturen van deze gebruiker
     * @return array<int, array{invoice: Invoice, score: int, reasons: string[], amount: float}>
     */
    public function candidatesFor(BankTransaction $transaction, Collection $invoices): array
    {
        if (! $transaction->isIncoming()) {
            return [];
        }

        $available = $transaction->unallocatedAmount();

        if ($available <= 0.004) {
            return [];
        }

        $rawHaystack = ($transaction->description ?? '') . ' ' . ($transaction->bank_reference ?? '');
        $haystack = $this->normalize($rawHaystack);
        $party = $this->normalize($transaction->counterparty_name ?? '');

        $candidates = [];

        foreach ($invoices as $invoice) {
            $outstanding = $invoice->outstandingAmount();

            if ($outstanding <= 0.004) {
                continue;
            }

            $score = 0;
            $reasons = [];

            $numberHit = $this->numberAppearsIn($invoice->invoice_number, $haystack, $rawHaystack);

            if ($numberHit) {
                $score += 60;
                $reasons[] = 'factuurnummer in omschrijving';
            }

            if (abs($outstanding - $available) < 0.005) {
                $score += 30;
                $reasons[] = 'bedrag komt exact overeen';
            } elseif ($available < $outstanding) {
                $score += 12;
                $reasons[] = 'deelbetaling';
            } elseif ($available > $outstanding) {
                $score += 8;
                $reasons[] = 'bedrag hoger dan openstaand';
            }

            $nameScore = $this->nameScore($invoice, $party);
            if ($nameScore > 0) {
                $score += $nameScore;
                $reasons[] = 'klantnaam komt overeen';
            }

            if ($invoice->invoice_date && $transaction->booking_date
                && $transaction->booking_date->gte($invoice->invoice_date)) {
                $score += 5;
            }

            // Een gelijk bedrag alleen is geen signaal: bij honderden facturen
            // levert dat vooral toevalstreffers op. Er moet een factuurnummer
            // of een herkenbare klantnaam bij zitten.
            $hasRealSignal = $numberHit || $nameScore > 0;

            if ($hasRealSignal && $score >= self::SUGGEST_THRESHOLD) {
                $candidates[] = [
                    'invoice' => $invoice,
                    'score' => min(100, $score),
                    'reasons' => $reasons,
                    'amount' => round(min($available, $outstanding), 2),
                ];
            }
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $candidates;
    }

    /**
     * De kandidaat die zonder tussenkomst gekoppeld mag worden: hoge score en
     * duidelijk beter dan de nummer twee.
     *
     * @param  array<int, array{invoice: Invoice, score: int, reasons: string[], amount: float}>  $candidates
     * @return array{invoice: Invoice, score: int, reasons: string[], amount: float}|null
     */
    public function autoMatch(array $candidates): ?array
    {
        if ($candidates === [] || $candidates[0]['score'] < self::AUTO_THRESHOLD) {
            return null;
        }

        // Twee even goede kandidaten: laat de gebruiker kiezen
        if (isset($candidates[1]) && $candidates[1]['score'] >= $candidates[0]['score'] - 10) {
            return null;
        }

        return $candidates[0];
    }

    /**
     * Factuurnummer zoeken in de omschrijving. Cijferreeksen worden ook los
     * herkend, zodat "Factuur 2026-0018" ook matcht op "20260018". Te korte
     * nummers slaan we over: die leveren toevalstreffers op.
     */
    private function numberAppearsIn(string $invoiceNumber, string $haystack, string $raw): bool
    {
        $needle = $this->normalize($invoiceNumber);

        if (strlen($needle) < 4 || $haystack === '') {
            return false;
        }

        if (! str_contains($haystack, $needle)) {
            return false;
        }

        // Het nummer moet als losstaand geheel voorkomen: "20268" mag niet
        // matchen op "202685". Scheidingstekens binnen het nummer zijn wel
        // toegestaan, zodat "2026-0018" ook op "20260018" past.
        $pattern = '/(?<![0-9A-Za-z])'
            . implode('[^0-9A-Za-z]?', array_map(
                fn ($c) => preg_quote($c, '/'),
                str_split($needle)
            ))
            . '(?![0-9A-Za-z])/i';

        return (bool) preg_match($pattern, $raw);
    }

    /**
     * Overlap tussen klantnaam en tegenpartij, op woordniveau zodat
     * "Renoplan Bouw B.V." ook matcht op "Renoplan Bouw BV".
     */
    private function nameScore(Invoice $invoice, string $party): int
    {
        if ($party === '') {
            return 0;
        }

        $best = 0;

        foreach ([$invoice->customer?->company_name, $invoice->customer?->name] as $candidate) {
            $name = $this->normalize((string) $candidate);

            if (strlen($name) < 3) {
                continue;
            }

            if (str_contains($party, $name) || str_contains($name, $party)) {
                return 25;
            }

            $tokens = array_filter($this->tokens($candidate), fn ($t) => strlen($t) >= 3);

            if ($tokens === []) {
                continue;
            }

            $hits = 0;
            foreach ($tokens as $token) {
                if (str_contains($party, $token)) {
                    $hits++;
                }
            }

            $ratio = $hits / count($tokens);
            $best = max($best, (int) round($ratio * 20));
        }

        return $best;
    }

    /** @return string[] */
    private function tokens(?string $value): array
    {
        $clean = strtolower((string) $value);
        $clean = preg_replace('/\b(bv|b\.v\.|nv|n\.v\.|vof|holding|beheer)\b/', ' ', $clean);

        return array_values(array_filter(preg_split('/[^a-z0-9]+/', (string) $clean) ?: []));
    }

    /** Kleine letters, alleen letters en cijfers: scheidingstekens mogen niet uitmaken. */
    private function normalize(string $value): string
    {
        return strtolower(preg_replace('/[^A-Za-z0-9]/', '', $value) ?? '');
    }
}
