<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToUser;

class Invoice extends Model
{
    use BelongsToUser;

    protected static function booted(): void
    {
        // Factuurteller in de instellingen automatisch doorschuiven
        static::created(function (Invoice $invoice) {
            AppSetting::advanceCounter('invoice_number_start', $invoice->invoice_number, $invoice->user_id);
        });
    }

    protected $fillable = [
        'user_id',
        'invoice_number',
        'customer_id',
        'template_id',
        'invoice_date',
        'due_date',
        'payment_terms',
        'subtotal',
        'vat_amount',
        'total',
        'status',
        'notes',
        'sent_at',
        'paid_at',
        'vat_reverse_charged',
        'prices_include_vat',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'sent_at' => 'date',
        'paid_at' => 'date',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'payment_terms' => 'integer',
        'vat_reverse_charged' => 'boolean',
        'prices_include_vat' => 'boolean',
    ];

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
        return $this->hasMany(InvoiceLine::class);
    }
}
