<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eén regel van een bankafschrift. Regels die aan geen enkele factuur
 * gekoppeld zijn, worden bij het afronden van de import verwijderd.
 */
class BankTransaction extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id', 'bank_import_session_id', 'booking_date', 'value_date',
        'amount', 'currency', 'counterparty_name', 'counterparty_iban',
        'description', 'bank_reference', 'fingerprint',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'value_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(BankImportSession::class, 'bank_import_session_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    /** Al aan facturen toegewezen bedrag. */
    public function allocatedAmount(): float
    {
        $sum = $this->relationLoaded('payments')
            ? $this->payments->sum('amount')
            : $this->payments()->sum('amount');

        return round((float) $sum, 2);
    }

    /** Wat er van deze transactie nog te verdelen is. */
    public function unallocatedAmount(): float
    {
        return round((float) $this->amount - $this->allocatedAmount(), 2);
    }

    public function isFullyAllocated(): bool
    {
        return $this->unallocatedAmount() <= 0.004;
    }

    /** Alleen bijschrijvingen kunnen een verkoopfactuur betalen. */
    public function isIncoming(): bool
    {
        return (float) $this->amount > 0;
    }
}
