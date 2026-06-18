<?php

/**
 * app/Services/AuditLogger.php
 * Titik tunggal penulisan jejak audit append-only (FR-19).
 */

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Catat satu aksi audit.
     *
     * @param  array<string,mixed>  $detail
     */
    public function log(string $aksi, ?Model $entitas = null, array $detail = [], ?int $actorId = null): AuditLog
    {
        return AuditLog::create([
            'actor_id' => $actorId ?? Auth::id(),
            'aksi' => $aksi,
            'entitas' => $entitas ? class_basename($entitas) : null,
            'entitas_id' => $entitas?->getKey(),
            'detail' => $detail ?: null,
            'ip' => Request::ip(),
        ]);
    }
}
