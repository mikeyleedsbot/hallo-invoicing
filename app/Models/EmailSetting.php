<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    protected $fillable = [
        'api_url',
        'api_key',
        'from_name',
        'from_email',
    ];

    protected $casts = [
        'api_key' => 'encrypted', // Laravel built-in encryption
    ];

    public static function get(): static
    {
        return static::firstOrCreate(['id' => 1], [
            'api_url'    => '',
            'api_key'    => '',
            'from_name'  => 'Hallo ICT',
            'from_email' => 'noreply@hallo.nl',
        ]);
    }

    public function isConfigured(): bool
    {
        return !empty($this->api_url) && !empty($this->api_key);
    }
}
