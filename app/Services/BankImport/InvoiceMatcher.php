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
     * Bereid de facturenlijst één keer voor.
     *
     * Zonder dit werd voor elke transactie opnieuw elk factuurnummer en elke
     * klantnaam genormaliseerd; bij duizenden facturen liep dat volledig uit
     * de hand. Het openstaande bedrag staat er ook in en wordt na een
     * koppeling bijgewerkt via markAllocated().
     *
     * @param  Collection<int, Invoice>  $invoices
     * @return array<int, array<string, mixed>>
     */
    public function prepare(Collection $invoices): array
    {
        $rows = [];
        $byNumber = [];
        $byName = [];

        foreach ($invoices as $invoice) {
            $outstanding = $invoice->outstandingAmount();

            if ($outstanding <= 0.004) {
                continue;
            }

            $names = [];
            $tokenSets = [];

            foreach ([$invoice->customer?->company_name, $invoice->customer?->name] as $candidate) {
                $name = $this->normalize((string) $candidate);

                if (strlen($name) < 3) {
                    continue;
                }

                $names[] = $name;
                $tokenSets[] = array_values(array_filter(
                    $this->tokens($candidate),
                    fn ($t) => strlen($t) >= 3
                ));
            }

            $needle = $this->normalize((string) $invoice->invoice_number);

            $rows[$invoice->id] = [
                'invoice' => $invoice,
                'outstanding' => $outstanding,
                'needle' => $needle,
                'names' => $names,
                'tokenSets' => $tokenSets,
            ];

            // Index op factuurnummer, zodat we niet elke factuur langs hoeven
            if (strlen($needle) >= 4) {
                $byNumber[$needle][] = $invoice->id;
            }

            // Index op klantnaam: er zijn veel minder klanten dan facturen
            foreach ($names as $name) {
                $byName[$name][] = $invoice->id;
            }
        }

        return ['rows' => $rows, 'byNumber' => $byNumber, 'byName' => $byName];
    }

    /** Werk het openstaande bedrag bij nadat er iets is gekoppeld. */
    public function markAllocated(array &$prepared, int $invoiceId, float $amount): void
    {
        if (! isset($prepared['rows'][$invoiceId])) {
            return;
        }

        $prepared['rows'][$invoiceId]['outstanding'] = round(
            $prepared['rows'][$invoiceId]['outstanding'] - $amount, 2
        );

        if ($prepared['rows'][$invoiceId]['outstanding'] <= 0.004) {
            unset($prepared['rows'][$invoiceId]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $prepared  uit prepare()
     * @return array<int, array{invoice: Invoice, score: int, reasons: string[], amount: float}>
     */
    public function candidatesFor(BankTransaction $transaction, array $prepared): array
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

        // Alleen facturen langslopen waar überhaupt een signaal voor is: een
        // factuurnummer uit de omschrijving of een passende klantnaam. Zonder
        // deze voorselectie werd elke transactie tegen elke factuur gescoord.
        $ids = [];

        foreach ($this->numberKeys($rawHaystack) as $key) {
            foreach ($prepared['byNumber'][$key] ?? [] as $id) {
                $ids[$id] = true;
            }
        }

        if ($party !== '') {
            foreach ($prepared['byName'] as $name => $nameIds) {
                if (str_contains($party, $name) || str_contains($name, $party)) {
                    foreach ($nameIds as $id) {
                        $ids[$id] = true;
                    }
                    continue;
                }

                // Gedeeltelijke overeenkomst op woordniveau
                foreach ($nameIds as $id) {
                    if (($prepared['rows'][$id] ?? null) && $this->nameScore($prepared['rows'][$id], $party) > 0) {
                        $ids[$id] = true;
                    }
                    break; // tokens zijn per naam gelijk; één controle volstaat
                }
            }
        }

        $candidates = [];

        foreach (array_keys($ids) as $id) {
            $row = $prepared['rows'][$id] ?? null;

            if ($row === null) {
                continue;
            }

            $invoice = $row['invoice'];
            $outstanding = $row['outstanding'];

            $score = 0;
            $reasons = [];

            $numberHit = $this->needleAppearsIn($row['needle'], $haystack, $rawHaystack);

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

            $nameScore = $this->nameScore($row, $party);
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
    /**
     * Mogelijke factuurnummers uit een omschrijving.
     *
     * Losse reeksen letters/cijfers, plus reeksen die aan elkaar geplakt zijn
     * over een scheidingsteken heen, zodat "2026-0018" ook 20260018 oplevert.
     *
     * @return string[]
     */
    private function numberKeys(string $raw): array
    {
        preg_match_all('/[0-9A-Za-z]+/', $raw, $m);
        $parts = $m[0] ?? [];
        $keys = [];

        foreach ($parts as $i => $part) {
            $joined = '';

            // Het deel zelf en maximaal drie aaneengeplakte vervolgdelen
            for ($n = 0; $n < 4 && isset($parts[$i + $n]); $n++) {
                $joined .= $parts[$i + $n];

                if (strlen($joined) >= 4) {
                    $keys[strtolower($joined)] = true;
                }

                if (strlen($joined) > 40) {
                    break;
                }
            }
        }

        return array_keys($keys);
    }

    private function needleAppearsIn(string $needle, string $haystack, string $raw): bool
    {
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
    private function nameScore(array $row, string $party): int
    {
        if ($party === '') {
            return 0;
        }

        $best = 0;

        foreach ($row['names'] as $index => $name) {
            if (str_contains($party, $name) || str_contains($name, $party)) {
                return 25;
            }

            $tokens = $row['tokenSets'][$index] ?? [];

            if ($tokens === []) {
                continue;
            }

            $hits = 0;
            foreach ($tokens as $token) {
                if (str_contains($party, $token)) {
                    $hits++;
                }
            }

            $best = max($best, (int) round(($hits / count($tokens)) * 20));
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
