<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'phone_number',
        'email',
        'full_name',
        'bvn',
        'nin',
        'date_of_birth',
        'gender',
        'lga',
        'state',
        'kyc_level',
        'kyc_verified_at',
        'nin_verified_at',
        'bvn_verified_at',
        'next_of_kin_submitted_at',
        'pin_hash',
        'login_pin_hash',
        'transfer_pin_hash',
        'login_password',
        'device_id',
        'last_login_at',
        'status',
        'referral_code',
        'referred_by',
        'next_of_kin_name',
        'next_of_kin_relationship',
        'next_of_kin_phone',
        'registered_by_agent_id',
        'must_change_password',
        'identity_verification_consent_at',
        'notify_email',
        'notify_sms',
        'notify_payout',
        'notify_contribution',
        'notify_agent_activity',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'pin_hash',
        'login_pin_hash',
        'transfer_pin_hash',
        'login_password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kyc_level' => 'integer',
            'kyc_verified_at' => 'datetime',
            'nin_verified_at' => 'datetime',
            'bvn_verified_at' => 'datetime',
            'next_of_kin_submitted_at' => 'datetime',
            'last_login_at' => 'datetime',
            'date_of_birth' => 'date',
            'must_change_password' => 'boolean',
            'identity_verification_consent_at' => 'datetime',
        ];
    }

    public function ajoOwner(): HasOne
    {
        return $this->hasOne(AjoOwner::class);
    }

    public function agent(): HasOne
    {
        return $this->hasOne(Agent::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function kycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    public function referredBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function registeredByAgent(): HasOne
    {
        return $this->hasOne(Agent::class, 'id', 'registered_by_agent_id');
    }

    public function verifyLoginPin(string $pin): bool
    {
        return $this->login_pin_hash
            ? \Illuminate\Support\Facades\Hash::check($pin, $this->login_pin_hash)
            : ($this->pin_hash ? \Illuminate\Support\Facades\Hash::check($pin, $this->pin_hash) : false);
    }

    public function verifyTransferPin(string $pin): bool
    {
        return $this->transfer_pin_hash
            ? \Illuminate\Support\Facades\Hash::check($pin, $this->transfer_pin_hash)
            : ($this->pin_hash ? \Illuminate\Support\Facades\Hash::check($pin, $this->pin_hash) : false);
    }

    public function setLoginPin(string $pin): void
    {
        $this->update(['login_pin_hash' => \Illuminate\Support\Facades\Hash::make($pin, ['rounds' => 12])]);
    }

    public function setTransferPin(string $pin): void
    {
        $this->update(['transfer_pin_hash' => \Illuminate\Support\Facades\Hash::make($pin, ['rounds' => 12])]);
    }
}
