<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamInvitation extends Model
{
    protected $fillable = [
        'team_id',
        'email',
        'token',
        'role',
        'invited_by',
        'approved_at',
        'accepted_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /** Goedgekeurd door een admin (facturatie geregeld), dus uitnodiging verstuurd. */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isExpired(): bool
    {
        // De 72 uur lopen vanaf het versturen, niet vanaf de aanvraag
        return ($this->approved_at ?? $this->created_at)->diffInHours(now()) > 72;
    }
}
