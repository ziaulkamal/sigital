<?php

/**
 * app/Models/Signatory.php
 * Penanda tangan beserta spesimen TTD; relasi M:N ke acara.
 */

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// created_by sengaja tidak fillable (server-controlled): diisi di SignatoryService (P3).
#[Fillable(['nama', 'jabatan', 'gambar_ttd', 'qr_srikandi_path', 'bsre_cert_id', 'is_active'])]
class Signatory extends Model
{
    use BelongsToOrganization;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Pembuat penanda tangan (P3 — signatory per-user). */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_signatory')
            ->withPivot('urutan')
            ->withTimestamps();
    }
}
