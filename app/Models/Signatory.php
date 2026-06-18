<?php

/**
 * app/Models/Signatory.php
 * Penanda tangan beserta spesimen TTD; relasi M:N ke acara.
 */

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['nama', 'jabatan', 'gambar_ttd', 'bsre_cert_id', 'is_active'])]
class Signatory extends Model
{
    use BelongsToOrganization;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_signatory')
            ->withPivot('urutan')
            ->withTimestamps();
    }
}
