<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTeam;

class Product extends Model
{
    use BelongsToTeam;

    protected $fillable = [
        'user_id',
        'team_id',
        'name',
        'description',
        'price',
        'active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean',
    ];
}
