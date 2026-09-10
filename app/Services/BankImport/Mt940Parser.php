<?php

namespace App\Services\BankImport;

/**
 * MT940 (SWIFT) bankafschrift.
 *
 * Relevante tags:
 *   :25:  rekeningnummer
 *   :61:  transactieregel — datum, D/C, bedrag
 *   :86:  omschrijving bij de voorgaande :61:, mag over meerdere regels lopen
 */
class Mt940Parser
{
    public function supports(string $content): bool
    {
        return (bool) preg_match('/^:(20|25|60F|61):/m', $content);
    }

    /** @return array{iban: ?string, from: ?string, to: ?string, transactions: ParsedTransaction[]} */
    public function parse(string $content): array
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        $iban = null;
        if (preg_match('/^:25:(.+)$/m', $content, $m)) {
            $iban = $this->iban($m[1]);
        }

        $transactions = [];
        $blocks = $this->blocks($content);

        foreach ($blocks as [$statementLine, $descriptionLines]) {
            $transaction = $this->transaction($statementLine, $descriptionLines);
            if ($transaction !== null) {
                $transactions[] = $transaction;
            }
        }

        if ($transactions === []) {
            throw new BankImportException('Er zijn geen transacties in dit MT940-bestand gevonden.');
        }

        $dates = array_map(fn (ParsedTransaction $t) => $t->bookingDate, $transactions);

        return ['iban' => $iban, 'from' => min($dates), 'to' => max($dates), 'transactions' => $transactions];
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function blocks(string $content): array
    {
        $blocks = [];
        $current = null;
        $description = '';

        foreach (explode("\n", $content) as $line) {
            if (str_starts_with($line, ':61:')) {
                if ($current !== null) {
                    $blocks[] = [$current, $description];
                }
                $current = substr($line, 4);
                $description = '';
            } elseif (str_starts_with($line, ':86:')) {
                $description = substr($line, 4);
            } elseif ($current !== null && $description !== '' && ! str_starts_with($line, ':')) {
                // Vervolgregel van de omschrijving
                $description .= ' ' . trim($line);
            } elseif (str_starts_with($line, ':62') && $current !== null) {
                $blocks[] = [$current, $description];
                $current = null;
                $description = '';
            }
        }

        if ($current !== null) {
            $blocks[] = [$current, $description];
        }

        return $blocks;
    }

    private function transaction(string $line, string $description): ?ParsedTransaction
    {
        // JJMMDD [boekdatum MMDD] C/D[credit-letter] bedrag met komma
        if (! preg_match('/^(\d{6})(\d{4})?(C|D|RC|RD)([A-Z])?([\d,]+)/i', $line, $m)) {
            return null;
        }

        $date = \DateTime::createFromFormat('!ymd', $m[1]);
        if (! $date) {
            return null;
        }

        $isDebit = str_starts_with(strtoupper($m[3]), 'D');
        $amount = (float) str_replace(',', '.', rtrim($m[5], ','));

        $description = trim(preg_replace('/\s+/', ' ', $description));

        return new ParsedTransaction(
            bookingDate: $date->format('Y-m-d'),
            amount: $isDebit ? -$amount : $amount,
            counterpartyName: $this->field($description, 'NAME') ?? $this->subfield($description, '32'),
            counterpartyIban: $this->iban($this->field($description, 'IBAN') ?? $this->subfield($description, '38') ?? ''),
            description: $this->remittance($description),
            bankReference: $this->field($description, 'EREF'),
        );
    }

    /** Waarde uit een /TAG/waarde-structuur. */
    private function field(string $text, string $tag): ?string
    {
        if (preg_match('#/' . $tag . '/([^/]+)#i', $text, $m)) {
            return trim($m[1]) ?: null;
        }

        return null;
    }

    /** Waarde uit een ?NN-subveld (veel Nederlandse banken). */
    private function subfield(string $text, string $code): ?string
    {
        if (preg_match('/\?' . $code . '([^?]+)/', $text, $m)) {
            return trim($m[1]) ?: null;
        }

        return null;
    }

    private function remittance(string $text): string
    {
        $remi = $this->field($text, 'REMI');
        if ($remi !== null) {
            return $remi;
        }

        // ?20 t/m ?29 bevatten de omschrijvingsregels
        if (preg_match_all('/\?2\d([^?]*)/', $text, $m)) {
            $joined = trim(implode(' ', array_map('trim', $m[1])));
            if ($joined !== '') {
                return $joined;
            }
        }

        return $text;
    }

    private function iban(string $raw): ?string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $raw));

        return preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{4,30}$/', $clean) ? $clean : null;
    }
}
