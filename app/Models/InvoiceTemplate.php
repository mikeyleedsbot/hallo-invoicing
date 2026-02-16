<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceTemplate extends Model
{
    protected $fillable = [
        'name',
        'is_default',
        'logo_path',
        'background_path',
        'field_positions',
        'page_size',
    ];

    protected $casts = [
        'field_positions' => 'array',
        'is_default' => 'boolean',
    ];

    /**
     * Get the default template.
     */
    public static function getDefault()
    {
        return static::where('is_default', true)->first() 
            ?? static::first();
    }

    /**
     * Set this template as default (and unset others).
     */
    public function setAsDefault()
    {
        static::where('is_default', true)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }
}
