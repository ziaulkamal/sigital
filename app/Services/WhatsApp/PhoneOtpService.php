<?php

/**
 * app/Services/WhatsApp/PhoneOtpService.php
 * OTP verifikasi nomor WhatsApp (anti akun palsu). OTP disimpan ter-hash + kedaluwarsa;
 * dikirim via WhatsAppSender (WAHA). Verifikasi sekali pakai.
 */

namespace App\Services\WhatsApp;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PhoneOtpService
{
    public function __construct(private readonly WhatsAppSender $sender) {}

    /** Buat OTP baru, simpan ter-hash, kirim ke WhatsApp user. Mengembalikan kode (untuk test/dev). */
    public function sendOtp(User $user): string
    {
        $length = (int) config('sigital.whatsapp.otp.length', 6);
        $ttl = (int) config('sigital.whatsapp.otp.ttl_minutes', 10);

        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        $user->forceFill([
            'phone_otp' => Hash::make($code),
            'phone_otp_expires_at' => now()->addMinutes($ttl),
        ])->save();

        $brand = config('sigital.brand.name', 'SIGITAL');
        $this->sender->send(
            (string) $user->phone,
            "Kode verifikasi {$brand} Anda: {$code}. Berlaku {$ttl} menit. Jangan bagikan kode ini kepada siapa pun.",
        );

        return $code;
    }

    /** Verifikasi kode; bila benar & belum kedaluwarsa → tandai nomor terverifikasi. */
    public function verify(User $user, string $code): bool
    {
        if ($user->phone_otp === null || $user->phone_otp_expires_at === null) {
            return false;
        }

        if ($user->phone_otp_expires_at->isPast()) {
            return false;
        }

        if (! Hash::check($code, $user->phone_otp)) {
            return false;
        }

        $user->forceFill([
            'phone_verified_at' => now(),
            'phone_otp' => null,
            'phone_otp_expires_at' => null,
        ])->save();

        return true;
    }
}
