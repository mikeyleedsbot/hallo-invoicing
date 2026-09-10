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
        'status_source',
        'status_changed_at',
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
        'status_changed_at' => 'datetime',
    ];

    /**
     * Deels betaald is geen aparte status in de database, maar wel wat de
     * gebruiker moet zien. De onderliggende status blijft verzonden of
     * verlopen, zodat aanmanen en filters blijven werken.
     */
    public function isPartiallyPaidForDisplay(): bool
    {
        return ! in_array($this->status, ['draft', 'cancelled', 'paid'], true)
            && $this->isPartiallyPaid();
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->isPartiallyPaidForDisplay()) {
            return 'Deels betaald';
        }

        return __('status.' . $this->status);
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->isPartiallyPaidForDisplay()) {
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200';
        }

        return match ($this->status) {
            'draft'     => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            'sent'      => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'paid'      => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'overdue'   => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            default     => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    /**
     * Som van de gekoppelde banktransacties.
     *
     * Is de relatie al ingeladen, dan wordt daarover geteld in plaats van een
     * nieuwe query te doen. Dat scheelt bij het matchen duizenden queries.
     */
    public function paidAmount(): float
    {
        $sum = $this->relationLoaded('payments')
            ? $this->payments->sum('amount')
            : $this->payments()->sum('amount');

        return round((float) $sum, 2);
    }

    /** Wat er nog openstaat; nooit negatief. */
    public function outstandingAmount(): float
    {
        return round(max(0, (float) $this->total - $this->paidAmount()), 2);
    }

    public function isFullyPaid(): bool
    {
        return $this->outstandingAmount() <= 0.004;
    }

    public function isPartiallyPaid(): bool
    {
        return $this->paidAmount() > 0.004 && ! $this->isFullyPaid();
    }

    public const STATUS_BY_MANUAL = 'manual';
    public const STATUS_BY_BANK = 'bank';

    /** Leg vast dat iemand de status zelf heeft gezet. */
    public function markStatusSetManually(): void
    {
        $this->forceFill([
            'status_source' => self::STATUS_BY_MANUAL,
            'status_changed_at' => now(),
        ])->save();
    }

    /** Korte uitleg bij de status: waar komt hij vandaan? */
    public function statusSourceLabel(): ?string
    {
        if ($this->status_source === self::STATUS_BY_MANUAL) {
            return 'handmatig gezet';
        }

        if ($this->status_source !== self::STATUS_BY_BANK) {
            return null;
        }

        // Uit de betalingen afgeleid: benoem waar het geld vandaan kwam
        $payments = $this->relationLoaded('payments') ? $this->payments : $this->payments()->get();
        $cash = $payments->where('method', InvoicePayment::METHOD_CASH)->count();
        $bank = $payments->count() - $cash;

        return match (true) {
            $cash > 0 && $bank > 0 => 'via bank en contant',
            $cash > 0 => 'contant afgerond',
            default => 'via bankkoppeling',
        };
    }

    /**
     * Zet de status op basis van wat er betaald is. Wordt aangeroepen na het
     * koppelen én ontkoppelen van een transactie, zodat een factuur die niet
     * meer volledig betaald is weer als openstaand terugkomt.
     *
     * Geannuleerde en concept-facturen blijven met rust.
     */
    public function refreshPaymentStatus(): void
    {
        if (in_array($this->status, ['cancelled', 'draft'], true)) {
            return;
        }

        $overdue = $this->due_date && $this->due_date->isPast();

        // Geen bankbetalingen (meer): een handmatig gezette status blijft staan.
        // Alleen wat de bank zelf had gezet wordt teruggedraaid.
        if ($this->paidAmount() <= 0.004) {
            if ($this->status_source === self::STATUS_BY_BANK) {
                $this->update([
                    'status' => $overdue ? 'overdue' : 'sent',
                    'paid_at' => null,
                    'status_source' => null,
                    'status_changed_at' => now(),
                ]);
            }

            return;
        }

        // Er staan bankbetalingen tegenover: die bepalen de status
        $this->update([
            'status' => $this->isFullyPaid() ? 'paid' : ($overdue ? 'overdue' : 'sent'),
            'paid_at' => $this->isFullyPaid() ? ($this->paid_at ?? now()->toDateString()) : null,
            'status_source' => self::STATUS_BY_BANK,
            'status_changed_at' => now(),
        ]);
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
