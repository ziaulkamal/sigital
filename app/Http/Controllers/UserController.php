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
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
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
        ]);
    }
}
