<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToUser;

class VatRate extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'name', 'rate', 'is_default', 'sort_order'];

    protected $casts = [
        'rate'       => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public static function ordered()
    {
        return static::orderBy('sort_order')->orderBy('rate');
    }
}
