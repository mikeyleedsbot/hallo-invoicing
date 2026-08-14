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
            $prefix = AppSetting::withoutGlobalScope('belongs_to_user')
                ->where('user_id', $invoice->user_id)
                ->value('invoice_prefix') ?? 'INV';

            AppSetting::advanceCounter('invoice_number_start', $invoice->invoice_number, $invoice->user_id, $prefix);
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

    public function getStatusLabelAttribute(): string
    {
        return __('status.' . $this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            'sent'      => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'paid'      => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'overdue'   => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            default     => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
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
        return $this->hasMany(InvoiceLine::class);
    }
}
