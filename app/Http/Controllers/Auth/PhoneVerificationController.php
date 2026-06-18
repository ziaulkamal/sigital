<?php

/**
 * app/Http/Controllers/Auth/PhoneVerificationController.php
 * Verifikasi OTP nomor WhatsApp setelah registrasi (anti akun palsu). Setelah terverifikasi,
 * akun lanjut ke alur "menunggu persetujuan" SuperAdmin.
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\PhoneOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PhoneVerificationController extends Controller
{
    public function __construct(private readonly PhoneOtpService $otp) {}

    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            return redirect()->route('pending');
        }

        return Inertia::render('Auth/VerifyPhone', [
            'phoneMasked' => $this->mask((string) $user->phone),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        if (! $this->otp->verify($user, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'Kode OTP salah atau kedaluwarsa.']);
        }

        return redirect()->route('pending')->with('success', 'Nomor WhatsApp terverifikasi. Akun menunggu persetujuan.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            return redirect()->route('pending');
        }

        $this->otp->sendOtp($user);

        return back()->with('success', 'Kode OTP baru telah dikirim ke WhatsApp Anda.');
    }

    private function mask(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return strlen($digits) > 4 ? str_repeat('•', strlen($digits) - 4).substr($digits, -4) : $digits;
    }
}
