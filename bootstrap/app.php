<?php

use App\Http\Middleware\EnsureApproved;
use App\Http\Middleware\EnsureProfileComplete;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetCurrentOrganization;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Di belakang reverse proxy TLS (Nginx/Caddy/Traefik): percayai header
        // X-Forwarded-* agar Laravel tahu request asli https (cegah Mixed Content aset).
        $middleware->trustProxies(at: '*');

        // Tenancy (P1) lebih dulu agar scoping & share Inertia sudah tahu organisasi aktif.
        $middleware->web(append: [
            SetCurrentOrganization::class,
            HandleInertiaRequests::class,
        ]);

        // Alias middleware peran/izin (spatie) untuk web & API.
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'approved' => EnsureApproved::class,
            'profile.complete' => EnsureProfileComplete::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
