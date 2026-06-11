<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToUser;

class CompanySetting extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'company_name',
        'address',
        'postal_code',
        'city',
        'country',
        'phone',
        'email',
        'website',
        'kvk_number',
        'vat_number',
        'iban',
        'bic',
        'bank_name',
        'invoice_footer',
        'logo_path',
    ];

    // Per-user singleton: elke gebruiker heeft eigen bedrijfsgegevens
    public static function get()
    {
        $userId = auth()->id();

        $defaults = [
            'company_name' => 'Mijn Bedrijf',
            'address' => '',
            'postal_code' => '',
            'city' => '',
            'country' => 'Nederland',
            'email' => '',
            'vat_number' => '',
            'iban' => '',
        ];

        if (!$userId) {
            // CLI / seeder context: pak het eerste record
            return static::withoutGlobalScope('belongs_to_user')->firstOrCreate(
                ['id' => 1],
                $defaults
            );
        }

        // Per-user singleton: vul defaults met bekende userdata
        $defaults['company_name'] = auth()->user()->company_name ?? 'Mijn Bedrijf';
        $defaults['email'] = auth()->user()->email ?? '';

        return static::firstOrCreate(
            ['user_id' => $userId],
            $defaults
        );
    }
}
