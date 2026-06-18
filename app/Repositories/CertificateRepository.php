<?php

/**
 * app/Repositories/CertificateRepository.php
 * Query arsip & pencarian sertifikat (nama/acara/nomor/rentang tanggal) — FR-18.
 */

namespace App\Repositories;

use App\Models\Certificate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CertificateRepository
{
    /**
     * Pencarian arsip dengan filter opsional & pagination.
     *
     * @param  array{q?:?string,event_id?:?int,status?:?string,from?:?string,to?:?string}  $filters
     */
    public function search(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return Certificate::query()
            ->with(['registration.person', 'registration.event'])
            ->when($filters['q'] ?? null, function ($query, $q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nomor_unik', 'like', "%{$q}%")
                        ->orWhereHas('registration.person', fn ($p) => $p->where('nama', 'like', "%{$q}%"))
                        ->orWhereHas('registration.event', fn ($e) => $e->where('nama', 'like', "%{$q}%"));
                });
            })
            ->when($filters['event_id'] ?? null, fn ($query, $id) => $query->whereHas('registration', fn ($r) => $r->where('event_id', $id)))
            ->when($filters['status'] ?? null, fn ($query, $s) => $query->where('status', $s))
            ->when($filters['from'] ?? null, fn ($query, $d) => $query->whereDate('issued_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($query, $d) => $query->whereDate('issued_at', '<=', $d))
            ->orderByDesc('issued_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
