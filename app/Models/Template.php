<?php

/**
 * app/Models/Template.php
 * Template sertifikat: layout/view + posisi field (perancang visual = v2).
 */

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// uploaded_by server-controlled (diisi TemplateService) → tidak fillable.
#[Fillable(['nama', 'slug', 'deskripsi', 'background_path', 'background_mime', 'view', 'posisi_field', 'canvas_width', 'canvas_height', 'fonts', 'thumbnail_path', 'is_active', 'is_global'])]
class Template extends Model
{
    use BelongsToOrganization;

    protected function casts(): array
    {
        return [
            'posisi_field' => 'array',
            'fonts' => 'array',
            'is_active' => 'boolean',
            'is_global' => 'boolean',
        ];
    }

    /** Template memakai perancang visual baru bila layout punya elements. */
    public function hasVisualLayout(): bool
    {
        $layout = $this->posisi_field;

        return is_array($layout) && ! empty($layout['elements'] ?? null);
    }

    /** Template global (organization_id null) terlihat oleh semua organisasi. */
    public function organizationScopeIncludesGlobal(): bool
    {
        return true;
    }

    /** Pengunggah template (P6). */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
