<?php

/**
 * app/Models/Organization.php
 * Tenant (P1): dinas atau komunitas. `kode` jadi prefix nomor sertifikat.
 * SuperAdmin = user tanpa organization_id (lihat User::isSuperAdmin()).
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nama', 'kode', 'type', 'is_active',
    'requested_by', 'approved_at', 'recommendation_letter_path',
])]
class Organization extends Model
{
    public const TYPE_DINAS = 'dinas';
    public const TYPE_KOMUNITAS = 'komunitas';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function signatories(): HasMany
    {
        return $this->hasMany(Signatory::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
