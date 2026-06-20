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
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\ParticipantImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SignatoryController;
use App\Http\Controllers\SwitchOrganizationController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TopupController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Tamu (guest)
|--------------------------------------------------------------------------
*/
// Sajikan berkas font terkurasi (.ttf) untuk @font-face perancang template.
// Hanya nama family dalam whitelist config/fonts → cegah path traversal.
Route::get('/fonts/{family}/{weight?}', [TemplateController::class, 'font'])
    ->where('weight', 'regular|bold')
    ->name('fonts.file');

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

    // --- Pengguna terdaftar (Admin: instansi sendiri · SuperAdmin: semua) ---
    Route::middleware('permission:manage-users')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        // Ban/unban: hanya SuperAdmin (ditegakkan di controller via abort_unless).
        Route::post('users/{user}/ban', [UserController::class, 'ban'])->name('users.ban');
        Route::post('users/{user}/unban', [UserController::class, 'unban'])->name('users.unban');
        // Reset password user lain (SuperAdmin: semua · Admin: instansinya sendiri — guard di controller).
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');
        // Monetisasi (SuperAdmin only — guard di controller): peran, paket Enterprise, sesuaikan credit.
        Route::post('users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::post('users/{user}/plan', [UserController::class, 'setPlan'])->name('users.plan');
        Route::post('users/{user}/credit', [UserController::class, 'adjustCredit'])->name('users.credit');
    });

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
        // Perancang visual (WYSIWYG) — sebelum wildcard {template} agar tak bentrok.
        Route::get('templates/{template}/editor', [TemplateController::class, 'editor'])->name('templates.editor');
        Route::post('templates/{template}/layout', [TemplateController::class, 'saveLayout'])->name('templates.layout');
        Route::post('templates/{template}/preview', [TemplateController::class, 'preview'])->name('templates.preview');
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
        // Regenerate (terapkan template terbaru; nomor & QR tetap) — satuan & se-acara.
        Route::post('certificates/{certificate}/regenerate', [CertificateController::class, 'regenerate'])->name('certificates.regenerate');
        Route::post('events/{event}/regenerate', [CertificateController::class, 'regenerateEvent'])->name('certificates.regenerateEvent');
        Route::get('batch/{batchId}/status', [CertificateController::class, 'batchStatus'])->name('certificates.batchStatus');
    });

    // --- Arsip & distribusi ---
    Route::middleware('permission:view-archive')->group(function () {
        Route::get('certificates', [ArchiveController::class, 'index'])->name('certificates.index');
        Route::get('certificates/{certificate}/download', [ArchiveController::class, 'download'])->name('certificates.download');
        Route::get('certificates/{certificate}/view', [ArchiveController::class, 'view'])->name('certificates.view');
    });
    Route::post('certificates/{certificate}/email', [ArchiveController::class, 'email'])
        ->middleware('permission:distribute-certificates')->name('certificates.email');
    Route::post('certificates/{certificate}/revoke', [ArchiveController::class, 'revoke'])
        ->middleware('permission:issue-certificates')->name('certificates.revoke');
    // Pulihkan sertifikat dicabut — otorisasi SuperAdmin dicek di controller.
    Route::post('certificates/{certificate}/restore', [ArchiveController::class, 'restore'])
        ->middleware('permission:issue-certificates')->name('certificates.restore');

    // --- Log audit (Admin) ---
    Route::middleware('permission:export-audit')->group(function () {
        Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
        Route::get('audit/export', [AuditController::class, 'export'])->name('audit.export');
    });

    // --- Credit & topup (semua user terautentikasi) ---
    Route::get('credits', [TopupController::class, 'index'])->name('credits.index');
    Route::post('credits/topup', [TopupController::class, 'store'])->name('credits.topup');
    // Verifikasi topup (SuperAdmin only — guard di controller).
    Route::get('credits/requests', [TopupController::class, 'requests'])->name('credits.requests');
    Route::get('credits/topup/{topup}/proof', [TopupController::class, 'proof'])->name('credits.topup.proof');
    Route::post('credits/topup/{topup}/approve', [TopupController::class, 'approve'])->name('credits.topup.approve');
    Route::post('credits/topup/{topup}/reject', [TopupController::class, 'reject'])->name('credits.topup.reject');

    // --- Marketplace template (Bagian 6) ---
    Route::get('marketplace', [MarketplaceController::class, 'browse'])->name('marketplace.browse');
    Route::post('marketplace/{template}/purchase', [MarketplaceController::class, 'purchase'])->name('marketplace.purchase');
    // Publikasi template milik sendiri (cek pemilik + Creator di controller).
    Route::post('marketplace/{template}/publish', [MarketplaceController::class, 'publish'])->name('marketplace.publish');
    Route::post('marketplace/{template}/unpublish', [MarketplaceController::class, 'unpublish'])->name('marketplace.unpublish');
    // Pendaftaran Creator (KTP + identitas + S&K) & rekening pencairan.
    Route::post('marketplace/apply', [MarketplaceController::class, 'applyCreator'])->name('marketplace.apply');
    Route::post('marketplace/bank', [MarketplaceController::class, 'storeBank'])->name('marketplace.bank');
    // Dashboard & pencairan Creator.
    Route::get('marketplace/creator', [MarketplaceController::class, 'creator'])->name('marketplace.creator');
    Route::post('marketplace/withdrawals', [MarketplaceController::class, 'requestWithdrawal'])->name('marketplace.withdrawals.request');
    // SuperAdmin: dashboard & verifikasi pendaftaran/rekening/pencairan (guard di controller).
    Route::get('marketplace/admin', [MarketplaceController::class, 'adminDashboard'])->name('marketplace.admin');
    Route::get('marketplace/creators/{user}/ktp', [MarketplaceController::class, 'ktp'])->name('marketplace.creators.ktp');
    Route::post('marketplace/creators/{user}/approve', [MarketplaceController::class, 'approveCreator'])->name('marketplace.creators.approve');
    Route::post('marketplace/creators/{user}/reject', [MarketplaceController::class, 'rejectCreator'])->name('marketplace.creators.reject');
    Route::post('marketplace/creators/{user}/bank/verify', [MarketplaceController::class, 'verifyBank'])->name('marketplace.creators.bank.verify');
    Route::post('marketplace/creators/{user}/bank/reject', [MarketplaceController::class, 'rejectBank'])->name('marketplace.creators.bank.reject');
    Route::post('marketplace/withdrawals/{withdrawal}/schedule', [MarketplaceController::class, 'scheduleWithdrawal'])->name('marketplace.withdrawals.schedule');
    Route::post('marketplace/withdrawals/{withdrawal}/approve', [MarketplaceController::class, 'approveWithdrawal'])->name('marketplace.withdrawals.approve');
    Route::post('marketplace/withdrawals/{withdrawal}/reject', [MarketplaceController::class, 'rejectWithdrawal'])->name('marketplace.withdrawals.reject');
    Route::post('marketplace/withdrawals/{withdrawal}/paid', [MarketplaceController::class, 'markPaid'])->name('marketplace.withdrawals.paid');

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

// Pendaftaran Marketplace Creator — landing publik (tamu diarahkan daftar/masuk;
// user login melihat form aplikasi). Submit form tetap di POST marketplace/apply (auth).
Route::get('/creator/register', [MarketplaceController::class, 'register'])->name('creator.register');

/*
|--------------------------------------------------------------------------
| Publik — Verifikasi sertifikat (halaman Inertia, render via QR)
|--------------------------------------------------------------------------
| Data keaslian diambil dari API stateless; di sini hanya shell halaman.
*/
Route::get('/verify/{token}', fn (string $token) => Inertia::render('Verify', ['token' => $token]))
    ->name('verify');
