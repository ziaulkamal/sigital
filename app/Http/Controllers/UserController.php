<?php

/**
 * app/Http/Controllers/UserController.php
 * Daftar pengguna terdaftar beserta instansi & status. Visibilitas:
 *  - SuperAdmin: seluruh pengguna lintas-instansi.
 *  - Admin (org): hanya pengguna pada instansinya sendiri.
 * Otorisasi via permission `manage-users` (SuperAdmin lolos Gate::before; Admin punya izin ini).
 */

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly BanService $banService) {}

    public function index(Request $request): Response
    {
        $actor = $request->user();

        $query = User::query()->with(['organization', 'roles']);

        // Admin org hanya melihat pengguna pada instansinya; SuperAdmin melihat semua.
        if (! $actor->isSuperAdmin()) {
            $query->where('organization_id', $actor->organization_id);
        }

        $users = $query->orderByDesc('created_at')->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'nik' => $u->nik,
            'phone' => $u->phone,
            'status' => $u->status,
            'requested_role' => $u->requested_role,
            'roles' => $u->getRoleNames()->all(),
            'is_banned' => $u->isBanned(),
            'banned_reason' => $u->banned_reason,
            'banned_at' => $u->banned_at?->toDateTimeString(),
            'created_at' => $u->created_at?->toDateTimeString(),
            'organization' => $u->organization ? [
                'id' => $u->organization->id,
                'nama' => $u->organization->nama,
                'kode' => $u->organization->kode,
                'type' => $u->organization->type,
            ] : null,
        ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'isSuperAdmin' => $actor->isSuperAdmin(),
            'currentUserId' => $actor->id,
        ]);
    }

    /** Blokir akun (SuperAdmin). Wajib menyebut alasan → ditampilkan ke user saat login. */
    public function ban(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->isSuperAdmin(), 403);
        abort_if($user->isSuperAdmin() || $user->id === $actor->id, 403, 'Akun ini tidak dapat diblokir.');

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [], ['reason' => 'alasan']);

        $this->banService->ban($user, $actor, $data['reason']);

        return back()->with('success', "Akun {$user->name} diblokir.");
    }

    /** Buka blokir akun (SuperAdmin) → akun aktif kembali. */
    public function unban(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->isSuperAdmin(), 403);

        $this->banService->unban($user, $actor);

        return back()->with('success', "Blokir akun {$user->name} dibuka.");
    }
}
