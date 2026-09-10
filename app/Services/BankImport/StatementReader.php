<?php

namespace App\Services\BankImport;

use ZipArchive;

/**
 * Herkent zelf welk soort bankbestand er is geüpload en laat de juiste parser
 * los. Ondersteund: CAMT.053 (XML), MT940 en CSV met puntkomma of komma.
 * Een zip met precies één bestand erin wordt uitgepakt.
 */
class StatementReader
{
    public const MAX_BYTES = 12 * 1024 * 1024;

    public function __construct(
        private Camt053Parser $camt = new Camt053Parser(),
        private Mt940Parser $mt940 = new Mt940Parser(),
        private CsvParser $csv = new CsvParser(),
    ) {
    }

    /**
     * @return array{format: string, iban: ?string, from: ?string, to: ?string, transactions: ParsedTransaction[]}
     */
    public function read(string $path, string $originalName = ''): array
    {
        $content = $this->contents($path, $originalName);

        if ($this->camt->supports($content)) {
            return ['format' => 'camt053'] + $this->camt->parse($content);
        }

        if ($this->mt940->supports($content)) {
            return ['format' => 'mt940'] + $this->mt940->parse($content);
        }

        if ($this->csv->supports($content)) {
            return ['format' => 'csv'] + $this->csv->parse($content);
        }

        throw new BankImportException(
            'Dit bestandstype herkennen we niet. Upload een CSV, een CAMT.053-XML of een MT940-bestand.'
        );
    }

    private function contents(string $path, string $originalName): string
    {
        if (! is_readable($path)) {
            throw new BankImportException('Het bestand kon niet gelezen worden.');
        }

        if (filesize($path) > self::MAX_BYTES) {
            throw new BankImportException('Het bestand is te groot (maximaal 12 MB).');
        }

        $content = (string) file_get_contents($path);

        if (str_starts_with($content, "PK\x03\x04")) {
            $content = $this->unzip($path);
        }

        $content = $this->toUtf8($content);

        if (trim($content) === '') {
            throw new BankImportException('Het bestand is leeg.');
        }

        return $content;
    }

    private function unzip(string $path): string
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new BankImportException('Dit zip-bestand kon niet geopend worden.');
        }

        // Alleen een zip met één bestand: anders is niet te bepalen welk
        // afschrift bedoeld wordt
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name !== false && ! str_ends_with($name, '/')) {
                $names[] = $name;
            }
        }

        if (count($names) !== 1) {
            $zip->close();
            throw new BankImportException('Deze zip bevat ' . count($names) . ' bestanden. Pak hem uit en upload het afschrift zelf.');
        }

        $stat = $zip->statName($names[0]);
        if (($stat['size'] ?? 0) > self::MAX_BYTES) {
            $zip->close();
            throw new BankImportException('Het bestand in de zip is te groot (maximaal 12 MB).');
        }

        $content = $zip->getFromName($names[0], self::MAX_BYTES);
        $zip->close();

        if ($content === false) {
            throw new BankImportException('Het bestand in de zip kon niet gelezen worden.');
        }

        return $content;
    }

    private function toUtf8(string $content): string
    {
        if (mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        return mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
    }
}
