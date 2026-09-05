<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToUser;

class AppSetting extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'default_vat_rate',
        'default_payment_terms',
        'quote_valid_days',
        'currency',
        'currency_symbol',
        'date_format',
        'invoice_prefix',
        'quote_prefix',
        'invoice_number_start',
        'quote_number_start',
        'invoice_number_padding',
        'quote_number_padding',
        'credit_surcharge_enabled',
        'credit_surcharge_percent',
        'invoice_email_subject',
        'invoice_email_body',
        'quote_email_subject',
        'quote_email_body',
    ];

    /*
    | Standaard e-mailteksten. Worden gebruikt zolang de gebruiker nog geen
    | eigen tekst heeft opgeslagen — dus automatisch voor nieuwe accounts
    | én voor bestaande accounts met lege velden.
    */
    public const DEFAULT_INVOICE_EMAIL_SUBJECT = 'Factuur {nummer} van {bedrijfsnaam}';

    public const DEFAULT_INVOICE_EMAIL_BODY =
        '<p>Beste {contactpersoon},</p>'
        . '<p>Bijgaand ontvang je factuur <strong>{nummer}</strong> voor een bedrag van <strong>{bedrag}</strong>.</p>'
        . '<p>We verzoeken je vriendelijk het bedrag over te maken v&oacute;&oacute;r <strong>{vervaldatum}</strong>.</p>'
        . '<p>Heb je vragen over deze factuur? Neem gerust contact met ons op.</p>'
        . '<p>Met vriendelijke groet,<br><strong>{bedrijfsnaam}</strong></p>';

    public const DEFAULT_QUOTE_EMAIL_SUBJECT = 'Offerte {nummer} van {bedrijfsnaam}';

    public const DEFAULT_QUOTE_EMAIL_BODY =
        '<p>Beste {contactpersoon},</p>'
        . '<p>Bedankt voor je interesse! Bijgaand ontvang je offerte <strong>{nummer}</strong> voor een bedrag van <strong>{bedrag}</strong>.</p>'
        . '<p>Deze offerte is geldig tot en met <strong>{geldig_tot}</strong>.</p>'
        . '<p>Heb je vragen of wil je de offerte bespreken? Neem gerust contact met ons op.</p>'
        . '<p>Met vriendelijke groet,<br><strong>{bedrijfsnaam}</strong></p>';

    public function invoiceEmailSubject(): string
    {
        return trim((string) $this->invoice_email_subject) !== ''
            ? $this->invoice_email_subject
            : self::DEFAULT_INVOICE_EMAIL_SUBJECT;
    }

    public function invoiceEmailBody(): string
    {
        return trim(strip_tags((string) $this->invoice_email_body)) !== ''
            ? $this->invoice_email_body
            : self::DEFAULT_INVOICE_EMAIL_BODY;
    }

    public function quoteEmailSubject(): string
    {
        return trim((string) $this->quote_email_subject) !== ''
            ? $this->quote_email_subject
            : self::DEFAULT_QUOTE_EMAIL_SUBJECT;
    }

    public function quoteEmailBody(): string
    {
        return trim(strip_tags((string) $this->quote_email_body)) !== ''
            ? $this->quote_email_body
            : self::DEFAULT_QUOTE_EMAIL_BODY;
    }

    protected $casts = [
        'default_vat_rate' => 'decimal:2',
        'default_payment_terms' => 'integer',
        'quote_valid_days' => 'integer',
        'invoice_number_start' => 'integer',
        'quote_number_start' => 'integer',
        'credit_surcharge_enabled' => 'boolean',
        'credit_surcharge_percent' => 'integer',
    ];

    /**
     * Kredietbeperkingstoeslag over een totaalbedrag (incl. btw).
     * Conform Belastingdienst: percentage van het totaal incl. btw,
     * zonder btw over de toeslag zelf.
     */
    public function creditSurchargeAmount(float $totalInclVat): float
    {
        if (!$this->credit_surcharge_enabled) {
            return 0.0;
        }

        return round($totalInclVat * max(1, (int) $this->credit_surcharge_percent) / 100, 2);
    }

    /**
     * Volgende factuurnummer: prefix + teller uit de instellingen.
     * De teller is leidend; nummers die al bestaan (bv. geïmporteerd of
     * handmatig aangemaakt) worden overgeslagen. Na het aanmaken van een
     * factuur schuift de teller automatisch door (zie Invoice::booted).
     */
    public function nextInvoiceNumber(): string
    {
        $prefix = $this->invoice_prefix ?? 'INV';
        $next = max(1, (int) ($this->invoice_number_start ?? 1));
        $padding = $this->numberPadding('invoice_number_padding');

        do {
            $number = $prefix . str_pad($next, $padding, '0', STR_PAD_LEFT);
            $exists = Invoice::where('invoice_number', $number)->exists();
            $next++;
        } while ($exists);

        return $number;
    }

    /**
     * Volgende offertenummer — zelfde gedrag als nextInvoiceNumber().
     */
    public function nextQuoteNumber(): string
    {
        $prefix = $this->quote_prefix ?? 'OFF';
        $next = max(1, (int) ($this->quote_number_start ?? 1));
        $padding = $this->numberPadding('quote_number_padding');

        do {
            $number = $prefix . str_pad($next, $padding, '0', STR_PAD_LEFT);
            $exists = Quote::where('quote_number', $number)->exists();
            $next++;
        } while ($exists);

        return $number;
    }

    /**
     * Teller doorschuiven naar (gebruikt nummer + 1). Wordt aangeroepen
     * vanuit de created-hooks van Invoice en Quote.
     *
     * Let op: het nummer wordt losgeknipt op basis van de prefix-lengte,
     * niet met een losse "laatste cijfers"-regex. Anders vreet een prefix
     * die zelf op cijfers eindigt (bv. "2026-" of "F2026") zijn eigen
     * cijfers mee het tellerveld in, waardoor de prefix dubbel in het
     * volgende nummer terechtkomt.
     */
    /**
     * Aantal cijfers waarmee de teller wordt opgebouwd. Bepaald door hoe de
     * teller in de instellingen is ingevuld: "0006" geeft vier, "6" geeft een.
     */
    public function numberPadding(string $column): int
    {
        return max(1, min(10, (int) ($this->{$column} ?? 5)));
    }

    /**
     * De teller zoals hij in het instellingenformulier hoort te staan, dus
     * mét de voorloopnullen die de breedte bepalen.
     */
    public function formattedCounter(string $startColumn, string $paddingColumn): string
    {
        return str_pad(
            (string) max(1, (int) ($this->{$startColumn} ?? 1)),
            $this->numberPadding($paddingColumn),
            '0',
            STR_PAD_LEFT
        );
    }

    public static function advanceCounter(string $column, string $usedNumber, int $userId, string $prefix = ''): void
    {
        $tail = $prefix !== '' && str_starts_with($usedNumber, $prefix)
            ? substr($usedNumber, strlen($prefix))
            : $usedNumber;

        if (!preg_match('/^\d+$/', $tail)) {
            return;
        }

        static::withoutGlobalScope('belongs_to_user')
            ->where('user_id', $userId)
            ->where($column, '<=', (int) $tail)
            ->update([$column => (int) $tail + 1]);
    }

    // Per-user singleton: elke gebruiker heeft eigen app-instellingen
    public static function get()
    {
        $userId = auth()->id();

        if (!$userId) {
            return static::withoutGlobalScope('belongs_to_user')->firstOrCreate(['id' => 1]);
        }

        return static::firstOrCreate(['user_id' => $userId]);
    }
}
