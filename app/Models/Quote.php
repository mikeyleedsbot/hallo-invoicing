<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToUser;

class Quote extends Model
{
    use BelongsToUser;

    protected static function booted(): void
    {
        // Offerteteller in de instellingen automatisch doorschuiven
        static::created(function (Quote $quote) {
            AppSetting::advanceCounter('quote_number_start', $quote->quote_number, $quote->user_id);
        });
    }

    protected $fillable = [
        'user_id',
        'quote_number',
        'customer_id',
        'template_id',
        'quote_date',
        'valid_until',
        'valid_days',
        'subtotal',
        'vat_amount',
        'total',
        'status',
        'notes',
        'converted_invoice_id',
        'converted_at',
        'sent_at',
        'prices_include_vat',
    ];

    protected $casts = [
        'quote_date' => 'date',
        'valid_until' => 'date',
        'sent_at' => 'date',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'valid_days' => 'integer',
        'converted_at' => 'datetime',
        'prices_include_vat' => 'boolean',
    ];

    public function getStatusLabelAttribute(): string
    {
        return __('status.' . $this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft'    => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            'sent'     => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'accepted' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'expired'  => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            default    => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InvoiceTemplate::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }
}
