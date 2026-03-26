<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VatRate extends Model
{
    protected $fillable = ['name', 'rate', 'is_default', 'sort_order'];

    protected $casts = [
        'rate'       => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public static function ordered()
    {
        return static::orderBy('sort_order')->orderBy('rate');
    }
}
