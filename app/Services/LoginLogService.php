<?php

/**
 * app/Services/LoginLogService.php
 * Catat jejak login (IP + user-agent) di satu tempat agar dipanggil dari jalur
 * login biasa maupun setelah tantangan 2FA tanpa duplikasi.
 *
 * Kolom country dibiarkan null — slot GeoIP. enrichCountry() stub disiapkan
 * untuk diisi belakangan (lookup IP → negara).
 */

namespace App\Services;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;

class LoginLogService
{
    public function record(User $user, Request $request): LoginLog
    {
        return LoginLog::create([
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'logged_at' => now(),
            // country / country_code diisi via enrichCountry() nanti (GeoIP).
        ]);
    }

    /**
     * TODO(GeoIP): isi negara dari IP (mis. MaxMind GeoLite2 / layanan eksternal).
     * Saat ini no-op; dipanggil terjadwal/queue untuk mengisi baris dengan country null.
     */
    public function enrichCountry(LoginLog $log): void
    {
        // Stub — implementasi lookup IP→negara menyusul.
    }
}
