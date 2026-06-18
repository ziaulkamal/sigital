<?php

/**
 * routes/web.php
 * Permukaan WEB (Inertia, auth sesi) untuk UI internal Admin/Operator.
 * Verifikasi publik & integrasi ada di routes/api.php (stateless, ber-versi).
 */

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\CompleteProfileController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventMemberController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\ParticipantImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SignatoryController;
use App\Http\Controllers\SwitchOrganizationController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Tamu (guest)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/forgot-password', fn () => Inertia::render('Auth/ForgotPassword'))->name('password.request');
});

/*
|--------------------------------------------------------------------------
| Tantangan 2FA (P5) — kredensial sudah benar, menunggu kode (bukan guest/auth penuh)
|--------------------------------------------------------------------------
*/
Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.login');
Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store']);

/*
|--------------------------------------------------------------------------
| Terautentikasi (Admin/Operator)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Verifikasi OTP WhatsApp (anti akun palsu) — sebelum menunggu persetujuan.
    Route::get('/verify-phone', [PhoneVerificationController::class, 'create'])->name('verify-phone');
    Route::post('/verify-phone', [PhoneVerificationController::class, 'store']);
    Route::post('/verify-phone/resend', [PhoneVerificationController::class, 'resend'])->name('verify-phone.resend');

    // Halaman tunggu approval — di luar gate 'approved' agar user pending bisa melihatnya (P2).
    Route::get('/pending', fn () => Inertia::render('Auth/Pending'))->name('pending');

    // Lengkapi profil wajib (NIK + HP) setelah di-approve — di luar gate 'profile.complete'
    // agar tak terjadi loop redirect. EnsureApproved tetap memastikan akun sudah aktif.
    Route::middleware('approved')->group(function () {
        Route::get('/complete-profile', [CompleteProfileController::class, 'create'])->name('profile.complete');
        Route::post('/complete-profile', [CompleteProfileController::class, 'store']);
    });
});

/*
|--------------------------------------------------------------------------
| Aplikasi (wajib auth + akun sudah di-approve SuperAdmin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'approved', 'profile.complete'])->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Switcher organisasi (SuperAdmin) — "Semua" atau pilih satu organisasi.
    // Otorisasi SuperAdmin ditegakkan di dalam controller (Gate::before, bukan peran pivot).
    Route::post('/switch-organization', SwitchOrganizationController::class)
        ->name('organization.switch');

    // --- Data master (Admin) ---
    Route::middleware('permission:manage-signatories')->group(function () {
        Route::get('signatories', [SignatoryController::class, 'index'])->name('signatories.index');
        Route::post('signatories', [SignatoryController::class, 'store'])->name('signatories.store');
        Route::post('signatories/{signatory}', [SignatoryController::class, 'update'])->name('signatories.update');
        Route::delete('signatories/{signatory}', [SignatoryController::class, 'destroy'])->name('signatories.destroy');
    });

    // --- Template sertifikat (Admin) ---
    Route::middleware('permission:manage-templates')->group(function () {
        Route::get('templates', [TemplateController::class, 'index'])->name('templates.index');
        Route::post('templates', [TemplateController::class, 'store'])->name('templates.store');
        Route::post('templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
        Route::delete('templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
        // Branding organisasi (logo/kop) — K8
        Route::put('settings/branding', [BrandingController::class, 'update'])->name('branding.update');
    });

    // --- Acara (Operator) ---
    Route::middleware('permission:manage-events')->group(function () {
        // Kolaborasi (P7) — daftar sebelum resource agar /events/join tak bentrok dgn {event}.
        Route::post('events/join', [EventMemberController::class, 'join'])->name('events.join');
        Route::post('events/{event}/members/{member}/approve', [EventMemberController::class, 'approve'])->name('events.members.approve');
        Route::post('events/{event}/members/{member}/reject', [EventMemberController::class, 'reject'])->name('events.members.reject');

        Route::resource('events', EventController::class);
    });

    // --- Peserta (Operator) ---
    Route::middleware('permission:manage-participants')->group(function () {
        Route::post('events/{event}/participants', [ParticipantController::class, 'store'])->name('participants.store');
        Route::delete('participants/{registration}', [ParticipantController::class, 'destroy'])->name('participants.destroy');
        Route::post('events/{event}/import/preview', [ParticipantImportController::class, 'preview'])->name('participants.import.preview');
        Route::post('events/{event}/import', [ParticipantImportController::class, 'store'])->name('participants.import.store');
    });

    // --- Penerbitan (Operator) ---
    Route::middleware('permission:issue-certificates')->group(function () {
        Route::post('events/{event}/issue', [CertificateController::class, 'issueBatch'])->name('certificates.issueBatch');
        Route::post('registrations/{registration}/issue', [CertificateController::class, 'issueOne'])->name('certificates.issueOne');
        Route::get('batch/{batchId}/status', [CertificateController::class, 'batchStatus'])->name('certificates.batchStatus');
    });

    // --- Arsip & distribusi ---
    Route::middleware('permission:view-archive')->group(function () {
        Route::get('certificates', [ArchiveController::class, 'index'])->name('certificates.index');
        Route::get('certificates/{certificate}/download', [ArchiveController::class, 'download'])->name('certificates.download');
    });
    Route::post('certificates/{certificate}/email', [ArchiveController::class, 'email'])
        ->middleware('permission:distribute-certificates')->name('certificates.email');
    Route::post('certificates/{certificate}/revoke', [ArchiveController::class, 'revoke'])
        ->middleware('permission:issue-certificates')->name('certificates.revoke');

    // --- Log audit (Admin) ---
    Route::middleware('permission:export-audit')->group(function () {
        Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
        Route::get('audit/export', [AuditController::class, 'export'])->name('audit.export');
    });

    // --- Notifikasi in-app (bel topbar) ---
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // --- Pengaturan akun sendiri (P4 — semua user terautentikasi) ---
    Route::get('/settings', fn () => Inertia::render('Settings'))->name('settings');
    Route::patch('/settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/settings/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    Route::delete('/settings/account', [ProfileController::class, 'deactivate'])->name('account.deactivate');

    // 2FA (P5 — opsional per-user)
    Route::post('/settings/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/settings/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::post('/settings/two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('two-factor.recovery');
    Route::delete('/settings/two-factor', [TwoFactorController::class, 'disable'])->name('two-factor.disable');

    // --- Persetujuan akun (SuperAdmin via Gate::before pada permission approve-users) ---
    Route::middleware('permission:approve-users')->group(function () {
        Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('approvals/{user}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('approvals/{user}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
        Route::get('approvals/{user}/recommendation', [ApprovalController::class, 'recommendation'])->name('approvals.recommendation');
    });
});

/*
|--------------------------------------------------------------------------
| Publik — Halaman legal (dapat diakses sebelum & sesudah login)
|--------------------------------------------------------------------------
| Tanpa middleware guest/auth: tamu maupun pengguna login boleh membuka.
*/
Route::prefix('legal')->name('legal.')->group(function () {
    Route::get('/syarat-ketentuan', fn () => Inertia::render('Legal/Terms'))->name('terms');
    Route::get('/kebijakan-privasi', fn () => Inertia::render('Legal/Privacy'))->name('privacy');
    Route::get('/cookie', fn () => Inertia::render('Legal/Cookie'))->name('cookie');
});

/*
|--------------------------------------------------------------------------
| Publik — Verifikasi sertifikat (halaman Inertia, render via QR)
|--------------------------------------------------------------------------
| Data keaslian diambil dari API stateless; di sini hanya shell halaman.
*/
Route::get('/verify/{token}', fn (string $token) => Inertia::render('Verify', ['token' => $token]))
    ->name('verify');
