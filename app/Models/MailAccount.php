<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailAccount extends Model
{
    use HasFactory;

    public const PROVIDER_GOOGLE    = 'google';
    public const PROVIDER_MICROSOFT = 'microsoft';

    protected $fillable = [
        'user_id',
        'provider',
        'from_email',
        'from_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_default',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'is_default'       => 'boolean',
            // Encrypted casts zorgen dat tokens versleuteld in de DB staan.
            'access_token'     => 'encrypted',
            'refresh_token'    => 'encrypted',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isGoogle(): bool    { return $this->provider === self::PROVIDER_GOOGLE; }
    public function isMicrosoft(): bool { return $this->provider === self::PROVIDER_MICROSOFT; }

    public function isExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function providerLabel(): string
    {
        return match ($this->provider) {
            self::PROVIDER_GOOGLE    => 'Google Workspace',
            self::PROVIDER_MICROSOFT => 'Microsoft 365',
            default                  => ucfirst($this->provider),
        };
    }
}
