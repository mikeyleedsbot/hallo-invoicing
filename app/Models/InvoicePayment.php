<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Koppeling tussen een banktransactie en een factuur, met het bedrag dat aan
 * die factuur is toegewezen. Deelbetalingen zijn dus gewoon meerdere rijen.
 */
class InvoicePayment extends Model
{
    use BelongsToUser;

    public const BY_AUTO = 'auto';
    public const BY_MANUAL = 'manual';

    /** Waar het geld vandaan kwam. */
    public const METHOD_BANK = 'bank';
    public const METHOD_CASH = 'cash';

    protected $fillable = [
        'user_id', 'invoice_id', 'bank_transaction_id', 'amount', 'method', 'matched_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isCash(): bool
    {
        return $this->method === self::METHOD_CASH;
    }

    public function methodLabel(): string
    {
        return $this->isCash() ? 'contant/handmatig' : 'bank';
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class, 'bank_transaction_id');
    }
}
