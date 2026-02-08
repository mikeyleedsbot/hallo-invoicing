<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
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

    // Singleton pattern - altijd maar 1 record
    public static function get()
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'company_name' => 'Hallo ICT',
                'address' => 'Reactorweg 301',
                'postal_code' => '3542 AD',
                'city' => 'Utrecht',
                'country' => 'Nederland',
                'email' => 'info@hallo.nl',
                'phone' => '+31 (0)30 123 4567',
                'website' => 'https://hallo.nl',
                'kvk_number' => '12345678',
                'vat_number' => 'NL123456789B01',
                'iban' => 'NL12 INGB 0001 2345 67',
                'bank_name' => 'ING Bank',
            ]
        );
    }
}
