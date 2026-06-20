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
            'phone_verified_at' => 'datetime',
            'phone_otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
