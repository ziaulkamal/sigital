<?php

/**
 * app/Models/Template.php
 * Template sertifikat: layout/view + posisi field (perancang visual = v2).
 */

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'slug', 'deskripsi', 'background_path', 'view', 'posisi_field', 'is_active', 'is_global'])]
class Template extends Model
{
    use BelongsToOrganization;

    protected function casts(): array
    {
        return [
            'posisi_field' => 'array',
            'is_active' => 'boolean',
            'is_global' => 'boolean',
        ];
    }

    /** Template global (organization_id null) terlihat oleh semua organisasi. */
    public function organizationScopeIncludesGlobal(): bool
    {
        return true;
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
