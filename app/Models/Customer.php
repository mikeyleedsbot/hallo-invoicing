<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToUser;

class Customer extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'company_name',
        'vat_number',
        'address',
        'city',
        'postal_code',
        'country',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $withCount = [
        'invoices',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }
}
