<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eén geüpload bankafschrift. Blijft "open" tot het matchen is afgerond;
 * daarna worden de niet-gekoppelde transacties gewist.
 */
class BankImportSession extends Model
{
    use BelongsToUser;

    public const STATUS_OPEN = 'open';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id', 'original_filename', 'format', 'account_iban',
        'period_from', 'period_to', 'imported_count', 'skipped_count',
        'status', 'completed_at',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'completed_at' => 'datetime',
        'imported_count' => 'integer',
        'skipped_count' => 'integer',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function formatLabel(): string
    {
        return match ($this->format) {
            'camt053' => 'CAMT.053',
            'mt940' => 'MT940',
            default => 'CSV',
        };
    }
}
