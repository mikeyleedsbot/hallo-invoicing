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
    ];

    protected $casts = [
        'default_vat_rate' => 'decimal:2',
        'default_payment_terms' => 'integer',
        'quote_valid_days' => 'integer',
        'invoice_number_start' => 'integer',
        'quote_number_start' => 'integer',
    ];

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
