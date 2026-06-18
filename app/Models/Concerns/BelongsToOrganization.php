<?php

/**
 * app/Models/Concerns/BelongsToOrganization.php
 * Trait multi-tenant (P1): global scope memfilter query ke organisasi aktif +
 * auto-fill organization_id saat create. SuperAdmin "Semua" (Tenancy::appliesScope()=false)
 * melihat lintas-organisasi.
 */

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder): void {
            $tenancy = app(Tenancy::class);

            if (! $tenancy->appliesScope()) {
                return; // SuperAdmin "Semua" / konsol → tanpa filter
            }

            $model = $builder->getModel();
            $column = $model->qualifyColumn('organization_id');
            $orgId = $tenancy->organizationId();

            if ($model->organizationScopeIncludesGlobal()) {
                // Sertakan juga record bersama (organization_id null), mis. template global.
                $builder->where(function (Builder $query) use ($column, $orgId): void {
                    $query->where($column, $orgId)->orWhereNull($column);
                });
            } else {
                $builder->where($column, $orgId);
            }
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('organization_id') !== null) {
                return; // sudah diisi eksplisit (mis. denormalisasi dari event)
            }

            $orgId = app(Tenancy::class)->organizationId();
            if ($orgId !== null) {
                $model->setAttribute('organization_id', $orgId);
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Entitas yang punya varian "global" (organization_id null) yang terlihat oleh
     * semua organisasi override → true (mis. Template). Default: terisolasi penuh.
     */
    public function organizationScopeIncludesGlobal(): bool
    {
        return false;
    }

    /** Query tanpa scope organisasi — dipakai hati-hati (mis. verifikasi publik, job lintas-org). */
    public static function withoutOrganizationScope(): Builder
    {
        return static::withoutGlobalScope('organization');
    }
}
