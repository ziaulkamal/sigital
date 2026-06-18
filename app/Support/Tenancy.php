<?php

/**
 * app/Support/Tenancy.php
 * Memegang organisasi aktif untuk satu request (P1). Diisi oleh middleware
 * SetCurrentOrganization, dibaca oleh trait BelongsToOrganization (global scope + auto-fill).
 *
 * Didaftarkan sebagai `scoped` di container (AppServiceProvider) agar state-nya
 * di-flush antar-request di lingkungan Octane/RoadRunner.
 */

namespace App\Support;

class Tenancy
{
    private ?int $organizationId = null;

    private bool $superAdmin = false;

    /** Set organisasi aktif. null = tanpa scope (SuperAdmin "Semua" / konteks konsol). */
    public function setOrganizationId(?int $id): void
    {
        $this->organizationId = $id;
    }

    public function organizationId(): ?int
    {
        return $this->organizationId;
    }

    public function setSuperAdmin(bool $value): void
    {
        $this->superAdmin = $value;
    }

    public function isSuperAdmin(): bool
    {
        return $this->superAdmin;
    }

    /** Apakah query/insert perlu difilter ke satu organisasi. */
    public function appliesScope(): bool
    {
        return $this->organizationId !== null;
    }
}
