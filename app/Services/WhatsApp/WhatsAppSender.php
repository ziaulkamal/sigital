<?php

/**
 * app/Services/WhatsApp/WhatsAppSender.php
 * Pengirim pesan WhatsApp untuk OTP verifikasi akun.
 * Driver 'waha' = WhatsApp HTTP API self-host (https://waha.devlike.pro/).
 * Driver 'log' (atau WAHA tak terjangkau) = tulis pesan ke log (dev) agar alur tetap jalan.
 */

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppSender
{
    /**
     * Kirim teks ke nomor (format internasional tanpa +, mis. 62812xxxx).
     * Mengembalikan true bila terkirim (atau di-log pada mode dev).
     */
    public function send(string $phone, string $message): bool
    {
        $phone = $this->normalize($phone);
        $driver = (string) config('sigital.whatsapp.driver', 'waha');

        if ($driver === 'waha') {
            return $this->sendViaWaha($phone, $message);
        }

        return $this->logOnly($phone, $message, 'driver=log');
    }

    private function sendViaWaha(string $phone, string $message): bool
    {
        $cfg = config('sigital.whatsapp.waha');
        $baseUrl = rtrim((string) ($cfg['base_url'] ?? ''), '/');

        if ($baseUrl === '') {
            return $this->logOnly($phone, $message, 'WAHA_BASE_URL kosong');
        }

        try {
            $request = Http::timeout(10)->acceptJson();
            if (! empty($cfg['api_key'])) {
                $request = $request->withHeaders(['X-Api-Key' => $cfg['api_key']]);
            }

            $response = $request->post($baseUrl.'/api/sendText', [
                'session' => $cfg['session'] ?? 'default',
                'chatId' => $phone.'@c.us',
                'text' => $message,
            ]);

            if ($response->successful()) {
                return true;
            }

            // Gagal kirim → jangan blokir alur dev; catat + log OTP agar tetap bisa diuji.
            return $this->logOnly($phone, $message, 'WAHA HTTP '.$response->status());
        } catch (\Throwable $e) {
            return $this->logOnly($phone, $message, 'WAHA error: '.$e->getMessage());
        }
    }

    private function logOnly(string $phone, string $message, string $reason): bool
    {
        Log::info("[WhatsApp/{$reason}] ke {$phone}: {$message}");

        return true;
    }

    /** Normalisasi ke format 62… (hapus non-digit, ubah awalan 0 → 62). */
    private function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return $digits;
    }
}
