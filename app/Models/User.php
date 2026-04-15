<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    // Account status waardes
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public function invoices()
    {
        return $this->hasMany(\App\Models\Invoice::class);
    }

    public function mailAccounts()
    {
        return $this->hasMany(\App\Models\MailAccount::class);
    }

    public function defaultMailAccount()
    {
        return $this->hasOne(\App\Models\MailAccount::class)->where('is_default', true);
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    // Status helpers
    // isApproved() behandelt null/leeg ook als approved zodat bestaande accounts
    // (van voor de migratie of zonder expliciete status) niet per ongeluk worden
    // buitengesloten. Alleen expliciet pending of rejected blokkeert login.
    public function isPending(): bool  { return $this->status === self::STATUS_PENDING; }
    public function isApproved(): bool { return $this->status === self::STATUS_APPROVED || empty($this->status); }
    public function isRejected(): bool { return $this->status === self::STATUS_REJECTED; }

    public function scopePending($q)  { return $q->where('status', self::STATUS_PENDING); }
    public function scopeApproved($q) { return $q->where('status', self::STATUS_APPROVED); }
    public function scopeRejected($q) { return $q->where('status', self::STATUS_REJECTED); }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_name',
        'phone',
        'address',
        'city',
        'mfa_secret',
        'mfa_enabled',
        'mfa_confirmed_at',
        'is_admin',
        'invite_token',
        'invite_sent_at',
        'status',
        'approved_at',
        'approved_by',
        'rejection_reason',
        // OAuth-credentials per gebruiker
        'google_client_id',
        'google_client_secret',
        'microsoft_client_id',
        'microsoft_client_secret',
        'microsoft_tenant_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
        'google_client_secret',
        'microsoft_client_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'        => 'datetime',
            'mfa_confirmed_at'         => 'datetime',
            'invite_sent_at'           => 'datetime',
            'approved_at'              => 'datetime',
            'password'                 => 'hashed',
            'mfa_enabled'              => 'boolean',
            'is_admin'                 => 'boolean',
            // OAuth secrets worden encrypted opgeslagen
            'google_client_secret'     => 'encrypted',
            'microsoft_client_secret'  => 'encrypted',
        ];
    }

    // OAuth helpers — handig in views/controllers om te checken of credentials klaar staan
    public function hasGoogleOAuth(): bool
    {
        return !empty($this->google_client_id) && !empty($this->google_client_secret);
    }

    public function hasMicrosoftOAuth(): bool
    {
        return !empty($this->microsoft_client_id) && !empty($this->microsoft_client_secret);
    }
}
