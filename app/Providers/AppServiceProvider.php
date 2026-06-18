<?php

namespace App\Providers;

use App\Support\Tenancy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Satu instance Tenancy per-request (di-flush antar-request pada Octane).
        $this->app->scoped(Tenancy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // SuperAdmin (organization_id null) = superuser lintas-organisasi: lolos semua
        // pemeriksaan izin (canAny dipakai PermissionMiddleware). null = lanjut cek normal.
        Gate::before(fn ($user) => $user->isSuperAdmin() ? true : null);
    }
}
