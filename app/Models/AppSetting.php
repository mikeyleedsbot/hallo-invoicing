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
    ];

    /**
     * Volgende factuurnummer (met prefix). De teller uit de instellingen
     * geldt als minimum: hoger zetten laat de nummering vooruit springen,
     * lager zetten kan nooit tot dubbele nummers leiden.
     */
    public function nextInvoiceNumber(): string
    {
        $next = max(1, (int) ($this->invoice_number_start ?? 1));

        $last = Invoice::orderBy('id', 'desc')->first();
        if ($last && preg_match('/(\d+)\s*$/', $last->invoice_number, $m)) {
            $next = max((int) $m[1] + 1, $next);
        }

        return ($this->invoice_prefix ?? 'INV') . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Volgende offertenummer (met prefix). Zelfde teller-als-minimum
     * gedrag als nextInvoiceNumber().
     */
    public function nextQuoteNumber(): string
    {
        $next = max(1, (int) ($this->quote_number_start ?? 1));

        $last = Quote::orderBy('id', 'desc')->first();
        if ($last && preg_match('/(\d+)\s*$/', $last->quote_number, $m)) {
            $next = max((int) $m[1] + 1, $next);
        }

        return ($this->quote_prefix ?? 'OFF') . str_pad($next, 5, '0', STR_PAD_LEFT);
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
