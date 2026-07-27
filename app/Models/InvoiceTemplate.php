<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToUser;

class InvoiceTemplate extends Model
{
    use BelongsToUser;

    /** Documentsoorten waarvoor een template standaard kan zijn */
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_QUOTE   = 'quote';

    protected $fillable = [
        'user_id',
        'name',
        'is_default_invoice',
        'is_default_quote',
        'logo_path',
        'background_path',
        'field_positions',
        'page_size',
    ];

    protected $casts = [
        'field_positions' => 'array',
        'is_default_invoice' => 'boolean',
        'is_default_quote' => 'boolean',
    ];

    /**
     * Kolomnaam bij een documentsoort.
     */
    public static function defaultColumn(string $type): string
    {
        return $type === self::TYPE_QUOTE ? 'is_default_quote' : 'is_default_invoice';
    }

    /**
     * Standaardtemplate voor een documentsoort. Valt terug op de eerste
     * template, zodat er altijd iets te renderen valt.
     */
    public static function getDefaultFor(string $type): ?self
    {
        return static::where(static::defaultColumn($type), true)->first()
            ?? static::first();
    }

    public static function getDefaultForInvoices(): ?self
    {
        return static::getDefaultFor(self::TYPE_INVOICE);
    }

    public static function getDefaultForQuotes(): ?self
    {
        return static::getDefaultFor(self::TYPE_QUOTE);
    }

    /**
     * Maak deze template standaard voor een documentsoort (en haal het
     * vinkje bij de andere templates weg).
     */
    public function setAsDefaultFor(string $type): void
    {
        $column = static::defaultColumn($type);

        static::where('id', '!=', $this->id)->update([$column => false]);
        $this->update([$column => true]);
    }

    public function isDefaultFor(string $type): bool
    {
        return (bool) $this->{static::defaultColumn($type)};
    }
}
