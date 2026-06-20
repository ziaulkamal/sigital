<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Phone;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Default in-memory: akun aktif. Registrasi mandiri menimpa jadi 'pending'
     * (lihat RegisterController). Selaras dengan default kolom DB.
     *
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => self::STATUS_APPROVED];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** SuperAdmin yang memblokir akun ini (bila ada). */
    public function banner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    /** Ledger credit milik user (append-only). */
    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    /** Permintaan topup yang diajukan user. */
    public function topupRequests(): HasMany
    {
        return $this->hasMany(TopupRequest::class);
    }

    /** Jejak login (IP + user-agent). */
    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    /** Pencairan royalti marketplace yang diajukan user. */
    public function marketplaceWithdrawals(): HasMany
    {
        return $this->hasMany(MarketplaceWithdrawal::class);
    }

    /** Login terakhir (untuk tampilan SuperAdmin). */
    public function latestLogin(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LoginLog::class)->latestOfMany('logged_at');
    }

    /** Paket Enterprise berlaku (aktif & belum kedaluwarsa). */
    public function isEnterprise(): bool
    {
        return $this->plan === 'enterprise'
            && $this->enterprise_expires_at !== null
            && $this->enterprise_expires_at->isFuture();
    }

    /**
     * Enterprise dengan benefit aktif: bebas-credit HANYA bila 2FA aktif.
     * Tanpa 2FA, paket tetap ada namun benefit diblokir (tetap kena potong credit).
     */
    public function isEnterpriseActive(): bool
    {
        return $this->isEnterprise() && $this->hasTwoFactorEnabled();
    }

    /** Marketplace Creator aktif: aplikasi sudah di-approve SuperAdmin (Bagian 6.1). */
    public function isMarketplaceCreator(): bool
    {
        return (bool) $this->marketplace_enabled;
    }

    /** Aplikasi pendaftaran creator sedang menunggu verifikasi SuperAdmin. */
    public function creatorApplicationPending(): bool
    {
        return $this->creator_status === 'pending';
    }

    /** Rekening pencairan sudah diverifikasi SuperAdmin. */
    public function hasVerifiedBank(): bool
    {
        return $this->bank_status === 'verified';
    }

    /**
     * Gerbang fitur creator (publish & pencairan): wajib creator aktif DAN
     * rekening terverifikasi. Ditegakkan server-side (anti-exploit).
     */
    public function canUseCreatorFeatures(): bool
    {
        return $this->isMarketplaceCreator() && $this->hasVerifiedBank();
    }

    /** SuperAdmin = user global tanpa organisasi (P1). */
    public function isSuperAdmin(): bool
    {
        return $this->organization_id === null;
    }

    /** Akun aktif (boleh memakai aplikasi) — SuperAdmin selalu aktif (P2). */
    public function isApproved(): bool
    {
        return $this->isSuperAdmin() || $this->status === self::STATUS_APPROVED;
    }

    /** Akun dinonaktifkan (oleh user sendiri atau admin). */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /** Akun diblokir SuperAdmin (memiliki jejak banned_at) — selalu disertai alasan. */
    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    /** 2FA aktif & sudah dikonfirmasi (jadi tantangan saat login) — P5. */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /** Nomor WhatsApp sudah terverifikasi via OTP (syarat onboarding). */
    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    /**
     * Profil wajib dilengkapi (NIK + nomor HP) sebelum memakai fitur.
     * Berlaku untuk user terapprove non-SuperAdmin yang NIK/HP-nya masih kosong.
     */
    public function needsProfileCompletion(): bool
    {
        return $this->isApproved()
            && ! $this->isSuperAdmin()
            && (blank($this->nik) || blank($this->phone));
    }

    /** Normalisasi nomor HP saat di-set (08… → 628…); dibaca apa adanya. */
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value) => Phone::normalize($value));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'banned_at' => 'datetime',
            'credit_balance' => 'integer',
            'enterprise_started_at' => 'datetime',
            'enterprise_expires_at' => 'datetime',
            'marketplace_enabled' => 'boolean',
            'marketplace_joined_at' => 'datetime',
            'creator_terms_accepted_at' => 'datetime',
            'creator_applied_at' => 'datetime',
            'creator_reviewed_at' => 'datetime',
            'bank_reviewed_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'phone_otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
