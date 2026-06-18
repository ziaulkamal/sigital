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

        $brandLogo = config('sigital.brand.logo');

        return [
            ...parent::share($request),
            // Identitas aplikasi (brand) — menggantikan hardcode di layout/login.
            'app' => [
                'name' => config('sigital.brand.name'),
                'tagline' => config('sigital.brand.tagline'),
                'logo' => $brandLogo ? asset('storage/'.$brandLogo) : null,
            ],
            // Notifikasi in-app untuk user terautentikasi (bel di topbar).
            'notifications' => $user ? [
                'unread' => fn () => $user->unreadNotifications()->count(),
                'items' => fn () => $user->notifications()->latest()->limit(10)->get()
                    ->map(fn ($n) => [
                        'id' => $n->id,
                        'type' => class_basename($n->type),
                        'data' => $n->data,
                        'read' => $n->read_at !== null,
                        'created_at' => $n->created_at?->diffForHumans(),
                    ]),
            ] : null,
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $roles,
                    'status' => $user->status,
                    'is_super_admin' => $isSuperAdmin,
                    'two_factor_enabled' => $user->hasTwoFactorEnabled(),
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
                'signatoryCandidates' => fn () => $request->session()->get('signatoryCandidates'),
                'twoFactor' => fn () => $request->session()->get('twoFactor'),
                'batchId' => fn () => $request->session()->get('batchId'),
            ],
        ];
    }
}
