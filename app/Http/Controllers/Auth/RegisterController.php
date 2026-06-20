<?php

/**
 * app/Http/Controllers/Auth/RegisterController.php
 * Registrasi mandiri panitia (P2/K9). User memilih organisasi yang ada atau mengajukan
 * organisasi baru; akun berstatus `pending` hingga di-approve SuperAdmin.
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\UserRegistered;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            // Organisasi aktif yang bisa diikuti (bergabung).
            'organizations' => Organization::where('is_active', true)
                ->orderBy('nama')->get(['id', 'nama', 'kode', 'type']),
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($request, $data): User {
            $newOrg = $data['org_mode'] === 'new';

            if ($newOrg) {
                // Surat rekomendasi (wajib dinas) disimpan ke disk privat.
                $letterPath = $request->hasFile('recommendation_letter')
                    ? $request->file('recommendation_letter')->store('recommendations')
                    : null;

                $organization = Organization::create([
                    'nama' => $data['org_nama'],
                    'kode' => $data['org_kode'],
                    'type' => $data['org_type'],
                    'is_active' => false, // aktif setelah di-approve SuperAdmin
                    'recommendation_letter_path' => $letterPath,
                ]);

                // Pengaju organisasi baru menjadi calon Admin organisasinya.
                $requestedRole = 'Admin';
            } else {
                $organization = Organization::findOrFail($data['organization_id']);
                $requestedRole = 'Operator';
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            // Kolom server-controlled (bukan mass-assignment).
            // NIK & nomor HP belum diisi di sini — dilengkapi setelah akun di-approve.
            // Saldo awal (signup_grant): cukup membuat satu acara, selebihnya perlu topup.
            $user->forceFill([
                'organization_id' => $organization->id,
                'status' => User::STATUS_PENDING,
                'requested_role' => $requestedRole,
                'credit_balance' => (int) config('sigital.credit.signup_grant', 60),
            ])->save();

            if ($newOrg) {
                $organization->forceFill(['requested_by' => $user->id])->save();
                $this->audit->log('organization.requested', $organization, [
                    'kode' => $organization->kode, 'type' => $organization->type,
                ], $user->id);
            }

            $this->audit->log('user.registered', $user, [
                'organization_id' => $organization->id,
                'requested_role' => $requestedRole,
            ], $user->id);

            return $user;
        });

        // Beri tahu seluruh SuperAdmin ada pendaftar baru menunggu persetujuan.
        // Kegagalan notifikasi TIDAK boleh menggagalkan registrasi (akun sudah tersimpan).
        try {
            Notification::send(User::whereNull('organization_id')->get(), new UserRegistered($user));
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi UserRegistered', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        // Login agar bisa melihat halaman "menunggu persetujuan"; akses aplikasi diblokir EnsureApproved.
        Auth::login($user);

        return redirect()->route('pending')->with('success', 'Pendaftaran berhasil. Akun Anda menunggu persetujuan SuperAdmin.');
    }
}
