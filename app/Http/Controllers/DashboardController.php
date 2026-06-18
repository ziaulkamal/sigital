<?php

/**
 * app/Http/Controllers/DashboardController.php
 * Ringkasan operasional nyata, ter-scope organisasi (SuperAdmin lihat semua/terpilih).
 */

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Signatory;
use App\Models\Template;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $isSuper = $user->isSuperAdmin();

        // Statistik utama (model ter-scope organisasi via trait).
        $stats = [
            ['key' => 'events', 'label' => 'Acara', 'value' => Event::count(), 'icon' => 'calendar', 'color' => '#6366f1'],
            ['key' => 'certificates', 'label' => 'Sertifikat Terbit', 'value' => Certificate::where('status', Certificate::STATUS_ISSUED)->count(), 'icon' => 'award', 'color' => '#10b981'],
            ['key' => 'signatories', 'label' => 'Penanda Tangan', 'value' => Signatory::count(), 'icon' => 'pen', 'color' => '#f59e0b'],
            ['key' => 'templates', 'label' => 'Template', 'value' => Template::count(), 'icon' => 'template', 'color' => '#8b5cf6'],
        ];

        // SuperAdmin: ganti dengan metrik tata kelola lintas-organisasi.
        if ($isSuper) {
            $stats = [
                ['key' => 'organizations', 'label' => 'Organisasi', 'value' => Organization::count(), 'icon' => 'building', 'color' => '#6366f1'],
                ['key' => 'pending', 'label' => 'Menunggu Persetujuan', 'value' => User::where('status', User::STATUS_PENDING)->count(), 'icon' => 'user-check', 'color' => '#f59e0b'],
                ['key' => 'events', 'label' => 'Total Acara', 'value' => Event::count(), 'icon' => 'calendar', 'color' => '#10b981'],
                ['key' => 'certificates', 'label' => 'Total Sertifikat', 'value' => Certificate::where('status', Certificate::STATUS_ISSUED)->count(), 'icon' => 'award', 'color' => '#8b5cf6'],
            ];
        }

        $recentEvents = Event::orderByDesc('id')->limit(5)->get()->map(fn ($e) => [
            'id' => $e->id,
            'nama' => $e->nama,
            'jadwal_mulai' => $e->jadwal_mulai?->translatedFormat('d M Y'),
            'status' => $e->status,
        ]);

        $recentActivity = AuditLog::with('actor')->orderByDesc('id')->limit(6)->get()->map(fn ($l) => [
            'id' => $l->id,
            'aktor' => $l->actor?->name ?? 'Sistem',
            'aksi' => $l->aksi,
            'waktu' => $l->created_at?->diffForHumans(),
        ]);

        return Inertia::render('Dashboard', [
            'greetingName' => $user->name,
            'stats' => $stats,
            'recentEvents' => $recentEvents,
            'recentActivity' => $recentActivity,
        ]);
    }
}
