<?php

/**
 * app/Http/Controllers/AuditController.php
 * Tampilan & ekspor log audit append-only untuk pemeriksaan (FR-19/20).
 */

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $page = AuditLog::with('actor')
            ->when($request->input('aksi'), fn ($q, $a) => $q->where('aksi', 'like', "%{$a}%"))
            ->when($request->input('entitas'), fn ($q, $e) => $q->where('entitas', $e))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Audit/Index', [
            'filters' => $request->only('aksi', 'entitas'),
            'logs' => [
                'data' => collect($page->items())->map(fn (AuditLog $l) => [
                    'id' => $l->id,
                    'waktu' => $l->created_at?->toDateTimeString(),
                    'aktor' => $l->actor?->name ?? 'Sistem',
                    'aksi' => $l->aksi,
                    'entitas' => $l->entitas ? "{$l->entitas}#{$l->entitas_id}" : '—',
                    'ip' => $l->ip,
                    'detail' => $l->detail,
                ]),
                'links' => $page->linkCollection(),
                'total' => $page->total(),
            ],
        ]);
    }

    /** Ekspor seluruh log audit ke CSV (streamed agar hemat memori). */
    public function export(): StreamedResponse
    {
        $filename = 'audit-log-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'waktu', 'aktor_id', 'aksi', 'entitas', 'entitas_id', 'ip', 'detail']);

            AuditLog::orderBy('id')->chunk(500, function ($logs) use ($out) {
                foreach ($logs as $l) {
                    fputcsv($out, [
                        $l->id, $l->created_at, $l->actor_id, $l->aksi,
                        $l->entitas, $l->entitas_id, $l->ip,
                        $l->detail ? json_encode($l->detail, JSON_UNESCAPED_UNICODE) : '',
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
