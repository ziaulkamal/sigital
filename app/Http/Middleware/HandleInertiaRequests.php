<?php

/**
 * app/Http/Middleware/HandleInertiaRequests.php
 * Berbagi state global ke setiap halaman Inertia (user terautentikasi, peran, flash).
 */

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $organization = $user && ! $isSuperAdmin ? $user->organization : null;

        // SuperAdmin tak punya peran pivot (Gate::before) → sisipkan peran sintetis
        // agar penyaringan menu berbasis `roles` (BaseLayout) tetap berfungsi.
        $roles = $user ? $user->getRoleNames()->all() : [];
        if ($isSuperAdmin) {
            $roles[] = 'SuperAdmin';
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $roles,
                    'status' => $user->status,
                    'is_super_admin' => $isSuperAdmin,
                    'organization' => $organization ? [
                        'id' => $organization->id,
                        'nama' => $organization->nama,
                        'kode' => $organization->kode,
                        'type' => $organization->type,
                    ] : null,
                ] : null,
            ],
            // Switcher organisasi — hanya dibagikan untuk SuperAdmin.
            'tenancy' => $isSuperAdmin ? [
                'is_super_admin' => true,
                'current_organization_id' => $request->session()->get('current_organization_id'),
                'organizations' => Organization::orderBy('nama')->get(['id', 'nama', 'kode', 'type']),
                'pending_approvals' => User::where('status', User::STATUS_PENDING)->count(),
            ] : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'importPreview' => fn () => $request->session()->get('importPreview'),
                'batchId' => fn () => $request->session()->get('batchId'),
            ],
        ];
    }
}
