<?php

namespace App\Services\BankImport;

/**
 * Bank-CSV in wisselende vormen.
 *
 * Herkent zelf het scheidingsteken (puntkomma of komma), of er een kopregel
 * is, en waar de kolommen staan. Twee smaken komen veel voor:
 *
 * - mét kopregel (o.a. ING): kolommen worden op naam gevonden, met een
 *   aparte kolom die aangeeft of het bij- of afschrijving is
 * - zonder kopregel (o.a. ASN/RegioBank/Rabobank): vaste kolomvolgorde,
 *   waarbij het bedrag zelf al een min-teken heeft
 */
class CsvParser
{
    /** Kopregel-namen per veld, in kleine letters. */
    private const HEADERS = [
        'date'        => ['date', 'datum', 'boekingsdatum', 'transactiedatum', 'interestdatum'],
        'amount'      => ['amount (eur)', 'amount', 'bedrag (eur)', 'bedrag', 'transactiebedrag'],
        'direction'   => ['debit/credit', 'af bij', 'af/bij', 'debet/credit', 'bij/af'],
        'name'        => ['name / description', 'naam / omschrijving', 'naam tegenpartij', 'tegenrekening houder', 'naam'],
        'iban'        => ['counterparty', 'tegenrekening', 'tegenrekening iban', 'iban/bban tegenpartij'],
        'own_iban'    => ['account', 'rekening', 'iban/bban'],
        'description' => ['notifications', 'mededelingen', 'omschrijving', 'omschrijving-1', 'mededeling'],
        'reference'   => ['tag', 'kenmerk', 'transactiereferentie'],
    ];

    public function supports(string $content): bool
    {
        $line = strtok(ltrim($content), "\r\n");

        return $line !== false && (str_contains($line, ';') || str_contains($line, ','));
    }

    /** @return array{iban: ?string, from: ?string, to: ?string, transactions: ParsedTransaction[]} */
    public function parse(string $content): array
    {
        $rows = $this->rows($content);

        if ($rows === []) {
            throw new BankImportException('Dit CSV-bestand bevat geen regels.');
        }

        $map = $this->headerMap($rows[0]);

        if ($map !== null) {
            array_shift($rows);
            $transactions = $this->fromHeaderRows($rows, $map);
            $ownIban = isset($map['own_iban']) ? ($rows[0][$map['own_iban']] ?? null) : null;
        } else {
            $transactions = $this->fromPositionalRows($rows);
            $ownIban = $rows[0][1] ?? null;
        }

        if ($transactions === []) {
            throw new BankImportException('Er zijn geen bruikbare transacties in dit bestand gevonden.');
        }

        $dates = array_map(fn (ParsedTransaction $t) => $t->bookingDate, $transactions);

        return [
            'iban' => $this->iban($ownIban),
            'from' => min($dates),
            'to' => max($dates),
            'transactions' => $transactions,
        ];
    }

    /** @return array<int, array<int, string>> */
    private function rows(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $delimiter = $this->delimiter($content);

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            // Lege regels overslaan
            if ($row === [null] || implode('', array_map(fn ($c) => (string) $c, $row)) === '') {
                continue;
            }
            $rows[] = array_map(fn ($c) => trim((string) $c), $row);
        }
        fclose($handle);

        return $rows;
    }

    /**
     * Scheidingsteken bepalen door te tellen buiten aanhalingstekens; het
     * teken dat het vaakst voorkomt op de eerste regel wint.
     */
    private function delimiter(string $content): string
    {
        $line = strtok(ltrim($content), "\r\n") ?: '';
        $outside = preg_replace('/"[^"]*"/', '', $line);

        return substr_count($outside, ';') >= substr_count($outside, ',') ? ';' : ',';
    }

    /** @return array<string, int>|null */
    private function headerMap(array $first): ?array
    {
        $map = [];

        foreach ($first as $index => $cell) {
            $needle = strtolower(trim($cell));
            foreach (self::HEADERS as $field => $names) {
                if (! isset($map[$field]) && in_array($needle, $names, true)) {
                    $map[$field] = $index;
                }
            }
        }

        // Zonder datum- en bedragkolom is het geen kopregel
        return isset($map['date'], $map['amount']) ? $map : null;
    }

    /** @param array<int, array<int, string>> $rows */
    private function fromHeaderRows(array $rows, array $map): array
    {
        $get = function (array $row, string $field) use ($map): ?string {
            $index = $map[$field] ?? null;

            return $index !== null && isset($row[$index]) && $row[$index] !== '' ? $row[$index] : null;
        };

        $transactions = [];

        foreach ($rows as $row) {
            $date = $this->date($get($row, 'date'));
            $raw = $get($row, 'amount');

            if ($date === null || $raw === null) {
                continue;
            }

            $amount = $this->amount($raw);
            $direction = strtolower((string) $get($row, 'direction'));

            if ($direction !== '') {
                $isDebit = str_starts_with($direction, 'debit')
                    || str_starts_with($direction, 'af')
                    || str_starts_with($direction, 'd');
                $amount = $isDebit ? -abs($amount) : abs($amount);
            }

            $transactions[] = new ParsedTransaction(
                bookingDate: $date,
                amount: $amount,
                valueDate: null,
                counterpartyName: $get($row, 'name'),
                counterpartyIban: $this->iban($get($row, 'iban')),
                description: $get($row, 'description'),
                bankReference: $get($row, 'reference'),
            );
        }

        return $transactions;
    }

    /**
     * Zonder kopregel: vaste volgorde zoals ASN, RegioBank en Rabobank die
     * leveren. Datum staat vooraan, het bedrag is de eerste kolom met een
     * teken erin na de valuta, en de omschrijving is de langste tekstkolom.
     *
     * @param array<int, array<int, string>> $rows
     */
    private function fromPositionalRows(array $rows): array
    {
        $transactions = [];

        foreach ($rows as $row) {
            $date = $this->date($row[0] ?? null);

            if ($date === null || count($row) < 12) {
                continue;
            }

            // Kolom 10 is het transactiebedrag, kolom 8 het saldo (beide met
            // valutakolom ervoor). Valt terug op zoeken als de vorm afwijkt.
            $amountRaw = $row[10] ?? '';
            if (! $this->looksNumeric($amountRaw)) {
                $amountRaw = $this->findAmount($row);
            }

            if ($amountRaw === null) {
                continue;
            }

            $description = $row[17] ?? '';
            if (trim($description, "' ") === '') {
                $description = $this->longestText($row);
            }

            $transactions[] = new ParsedTransaction(
                bookingDate: $date,
                amount: $this->amount($amountRaw),
                valueDate: $this->date($row[12] ?? null),
                counterpartyName: ($row[3] ?? '') !== '' ? $row[3] : null,
                counterpartyIban: $this->iban($row[2] ?? null),
                description: trim($description, "' "),
                bankReference: ($row[15] ?? '') !== '' ? $row[15] : null,
            );
        }

        return $transactions;
    }

    private function findAmount(array $row): ?string
    {
        // Vanaf achteren zoeken naar een getal met decimalen
        foreach (array_slice($row, 8, 6) as $cell) {
            if ($this->looksNumeric($cell)) {
                return $cell;
            }
        }

        return null;
    }

    private function longestText(array $row): string
    {
        $longest = '';

        foreach ($row as $cell) {
            if (! $this->looksNumeric($cell) && strlen($cell) > strlen($longest)) {
                $longest = $cell;
            }
        }

        return $longest;
    }

    private function looksNumeric(string $cell): bool
    {
        return (bool) preg_match('/^-?\d{1,3}([.,]?\d{3})*[.,]\d{2}$|^-?\d+$/', trim($cell));
    }

    /**
     * Bedragen komen binnen als "1.234,56", "1,234.56", "-235,00" of "208.33".
     */
    private function amount(string $raw): float
    {
        $raw = trim(str_replace([' ', "\u{a0}"], '', $raw));
        $negative = str_starts_with($raw, '-');
        $raw = ltrim($raw, '+-');

        $lastComma = strrpos($raw, ',');
        $lastDot = strrpos($raw, '.');

        if ($lastComma !== false && $lastDot !== false) {
            // Het laatste teken is het decimaalteken, de ander groepeert
            $decimal = $lastComma > $lastDot ? ',' : '.';
        } elseif ($lastComma !== false) {
            // Alleen komma: decimaal, tenzij het duidelijk groepering is (1,234)
            $decimal = preg_match('/,\d{3}$/', $raw) ? '.' : ',';
        } else {
            $decimal = '.';
        }

        $clean = $decimal === ','
            ? str_replace(',', '.', str_replace('.', '', $raw))
            : str_replace(',', '', $raw);

        $value = (float) $clean;

        return $negative ? -$value : $value;
    }

    private function date(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $raw = trim($raw, "' \t");

        foreach (['Ymd', 'd-m-Y', 'Y-m-d', 'd/m/Y', 'd.m.Y'] as $format) {
            $date = \DateTime::createFromFormat('!' . $format, $raw);
            if ($date && $date->format($format) === $raw) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function iban(?string $raw): ?string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $raw));

        return preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{4,30}$/', $clean) ? $clean : null;
    }
}
