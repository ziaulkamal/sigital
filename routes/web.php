<?php

/**
 * routes/web.php
 * Permukaan WEB (Inertia, auth sesi) untuk UI internal Admin/Operator.
 * Verifikasi publik & integrasi ada di routes/api.php (stateless, ber-versi).
 */

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\ParticipantImportController;
use App\Http\Controllers\SignatoryController;
use App\Http\Controllers\SwitchOrganizationController;
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
| Terautentikasi (Admin/Operator)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Halaman tunggu approval — di luar gate 'approved' agar user pending bisa melihatnya (P2).
    Route::get('/pending', fn () => Inertia::render('Auth/Pending'))->name('pending');
});

/*
|--------------------------------------------------------------------------
| Aplikasi (wajib auth + akun sudah di-approve SuperAdmin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'approved'])->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

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

    // --- Acara (Operator) ---
    Route::middleware('permission:manage-events')->group(function () {
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

    Route::get('/settings', fn () => Inertia::render('Settings'))->name('settings');

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
| Publik — Verifikasi sertifikat (halaman Inertia, render via QR)
|--------------------------------------------------------------------------
| Data keaslian diambil dari API stateless; di sini hanya shell halaman.
*/
Route::get('/verify/{token}', fn (string $token) => Inertia::render('Verify', ['token' => $token]))
    ->name('verify');
