<?php

namespace App\Services\BankImport;

use SimpleXMLElement;

/**
 * CAMT.053 (ISO 20022) bankafschrift.
 *
 * Werkt voor alle .001.0x-varianten: de namespace wordt weggehaald zodat de
 * elementnamen rechtstreeks aanspreekbaar zijn.
 */
class Camt053Parser
{
    public function supports(string $content): bool
    {
        return str_contains($content, 'camt.053')
            || (str_contains($content, '<BkToCstmrStmt') && str_contains($content, '<Ntry'));
    }

    /** @return array{iban: ?string, from: ?string, to: ?string, transactions: ParsedTransaction[]} */
    public function parse(string $content): array
    {
        $xml = $this->load($content);

        $iban = null;
        $from = null;
        $to = null;
        $transactions = [];

        foreach ($xml->xpath('//Stmt') ?: [] as $stmt) {
            $iban ??= $this->firstValue($stmt, './/Acct/Id/IBAN');
            $from ??= $this->date($this->firstValue($stmt, './/FrToDt/FrDtTm'));
            $to   ??= $this->date($this->firstValue($stmt, './/FrToDt/ToDtTm'));

            foreach ($stmt->xpath('./Ntry') ?: [] as $entry) {
                $transactions[] = $this->entry($entry);
            }
        }

        // Zonder expliciete periode: afleiden uit de boekingsdata
        if ($transactions !== []) {
            $dates = array_map(fn (ParsedTransaction $t) => $t->bookingDate, $transactions);
            $from ??= min($dates);
            $to   ??= max($dates);
        }

        return ['iban' => $iban, 'from' => $from, 'to' => $to, 'transactions' => $transactions];
    }

    private function load(string $content): SimpleXMLElement
    {
        // Namespaces strippen zodat xpath zonder prefix werkt
        $clean = preg_replace('/\sxmlns(:\w+)?="[^"]*"/', '', $content);

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        // LIBXML_NONET: nooit externe entiteiten ophalen bij een geüpload bestand
        $xml = simplexml_load_string($clean, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            $first = $errors[0]->message ?? 'onbekende fout';
            throw new BankImportException('Dit XML-bestand kon niet gelezen worden: ' . trim($first));
        }

        return $xml;
    }

    private function entry(SimpleXMLElement $entry): ParsedTransaction
    {
        $amount = (float) ($this->firstValue($entry, './Amt') ?? 0);
        $isCredit = strtoupper((string) ($this->firstValue($entry, './CdtDbtInd') ?? 'CRDT')) === 'CRDT';

        $booking = $this->date($this->firstValue($entry, './BookgDt/Dt') ?? $this->firstValue($entry, './BookgDt/DtTm'));
        $value   = $this->date($this->firstValue($entry, './ValDt/Dt') ?? $this->firstValue($entry, './ValDt/DtTm'));

        // Bij een bijschrijving is de tegenpartij de debiteur, bij een afschrijving de crediteur
        $partyPath = $isCredit ? 'Dbtr' : 'Cdtr';
        $name = $this->firstValue($entry, ".//RltdPties/{$partyPath}/Nm");
        $iban = $this->firstValue($entry, ".//RltdPties/{$partyPath}Acct/Id/IBAN");

        return new ParsedTransaction(
            bookingDate: $booking ?? date('Y-m-d'),
            amount: $isCredit ? abs($amount) : -abs($amount),
            currency: (string) ($entry->Amt['Ccy'] ?? 'EUR'),
            valueDate: $value,
            counterpartyName: $name,
            counterpartyIban: $iban,
            description: $this->description($entry),
            bankReference: $this->firstValue($entry, './NtryRef')
                ?? $this->firstValue($entry, './/Refs/EndToEndId'),
        );
    }

    /**
     * Omschrijving: de losse remittance-regels hebben de voorkeur, met
     * AddtlNtryInf als aanvulling of terugval.
     */
    private function description(SimpleXMLElement $entry): ?string
    {
        $parts = [];

        foreach ($entry->xpath('.//RmtInf/Ustrd') ?: [] as $line) {
            $parts[] = trim((string) $line);
        }

        $additional = $this->firstValue($entry, './AddtlNtryInf');
        if ($additional !== null && ! in_array(trim($additional), $parts, true)) {
            $parts[] = trim($additional);
        }

        $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));

        return $parts === [] ? null : implode(' ', $parts);
    }

    private function firstValue(SimpleXMLElement $node, string $path): ?string
    {
        $found = $node->xpath($path);

        if (! $found) {
            return null;
        }

        $value = trim((string) $found[0]);

        return $value === '' ? null : $value;
    }

    private function date(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }

        return substr($raw, 0, 10);
    }
}
