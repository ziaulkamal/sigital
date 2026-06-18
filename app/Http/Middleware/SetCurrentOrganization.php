<?php

/**
 * app/Http/Middleware/SetCurrentOrganization.php
 * Menetapkan organisasi aktif per-request (P1):
 *  - User ber-organisasi  → scope ke organisasinya + team spatie = organization_id.
 *  - SuperAdmin (org null) → ikuti switcher di sesi (null = "Semua"), team spatie = null.
 *
 * Dijalankan sebelum HandleInertiaRequests & middleware permission rute.
 */

namespace App\Http\Middleware;

use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentOrganization
{
    public function __construct(private readonly Tenancy $tenancy) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            if ($user->isSuperAdmin()) {
                $this->tenancy->setSuperAdmin(true);
                // Switcher: organisasi terpilih di sesi, null = lihat semua.
                $selected = $request->session()->get('current_organization_id');
                $this->tenancy->setOrganizationId($selected !== null ? (int) $selected : null);
                setPermissionsTeamId(null);
            } else {
                $this->tenancy->setOrganizationId($user->organization_id);
                setPermissionsTeamId($user->organization_id);
            }
        }

        return $next($request);
    }
}
