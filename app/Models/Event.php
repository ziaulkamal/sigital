<?php

/**
 * app/Models/Event.php
 * Acara: jadwal, lokasi, template, penanda tangan terkait, dan status alur penerbitan.
 */

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'kode', 'jadwal_mulai', 'jadwal_selesai', 'lokasi', 'template_id', 'status'])]
class Event extends Model
{
    use BelongsToOrganization;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SIAP_TERBIT = 'siap_terbit';
    public const STATUS_SELESAI = 'selesai';

    protected function casts(): array
    {
        return [
            'jadwal_mulai' => 'datetime',
            'jadwal_selesai' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function signatories(): BelongsToMany
    {
        return $this->belongsToMany(Signatory::class, 'event_signatory')
            ->withPivot('urutan')
            ->withTimestamps()
            ->orderByPivot('urutan');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /** Acara siap terbit bila punya minimal satu penanda tangan dan satu template (FR-04). */
    public function canIssue(): bool
    {
        return $this->template_id !== null && $this->signatories()->exists();
    }
}
