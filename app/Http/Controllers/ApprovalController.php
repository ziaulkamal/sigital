<?php

/**
 * app/Http/Controllers/ApprovalController.php
 * Panel Persetujuan SuperAdmin (P2): daftar pending, approve/reject, unduh surat rekomendasi.
 * Otorisasi: permission `approve-users` (SuperAdmin lolos via Gate::before).
 */

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApprovalController extends Controller
{
    public function __construct(private readonly ApprovalService $service) {}

    public function index(): Response
    {
        $pending = User::with('organization')
            ->where('status', User::STATUS_PENDING)
            ->orderBy('created_at')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'requested_role' => $u->requested_role,
                'created_at' => $u->created_at?->toDateTimeString(),
                'organization' => $u->organization ? [
                    'id' => $u->organization->id,
                    'nama' => $u->organization->nama,
                    'kode' => $u->organization->kode,
                    'type' => $u->organization->type,
                    'is_proposed' => ! $u->organization->is_active, // diajukan saat registrasi
                    'has_recommendation' => (bool) $u->organization->recommendation_letter_path,
                ] : null,
            ]);

        return Inertia::render('Approvals/Index', ['pending' => $pending]);
    }

    public function approve(User $user): RedirectResponse
    {
        $this->service->approve($user, request()->user());

        return back()->with('success', "Akun {$user->name} disetujui.");
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->service->reject($user, $request->user(), $data['reason'] ?? null);

        return back()->with('success', "Pendaftaran {$user->name} ditolak.");
    }

    /** Unduh/pratinjau surat rekomendasi organisasi yang diajukan user (SuperAdmin). */
    public function recommendation(User $user): StreamedResponse
    {
        $path = $user->organization?->recommendation_letter_path;

        abort_if(! $path || ! Storage::exists($path), 404, 'Surat rekomendasi tidak ditemukan.');

        return Storage::response($path);
    }
}
