<?php

namespace App\Services\BankImport;

/**
 * Eén transactieregel, losgekoppeld van het bronformaat.
 */
class ParsedTransaction
{
    public function __construct(
        public string $bookingDate,              // Y-m-d
        public float $amount,                    // negatief = af, positief = bij
        public string $currency = 'EUR',
        public ?string $valueDate = null,
        public ?string $counterpartyName = null,
        public ?string $counterpartyIban = null,
        public ?string $description = null,
        public ?string $bankReference = null,
    ) {
    }

    /**
     * Herkenningsvingerafdruk om dezelfde regel niet twee keer te importeren.
     */
    public function fingerprint(): string
    {
        return hash('sha256', implode('|', [
            $this->bookingDate,
            number_format($this->amount, 2, '.', ''),
            $this->currency,
            $this->counterpartyIban ?? '',
            $this->bankReference ?? '',
            preg_replace('/\s+/', ' ', trim((string) $this->description)),
        ]));
    }
}
