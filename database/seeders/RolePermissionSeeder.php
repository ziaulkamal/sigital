<?php

/**
 * database/seeders/RolePermissionSeeder.php
 * Tetapkan peran Admin/Operator beserta izin granular (FR-23/FR-24).
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app()[PermissionRegistrar::class];
        $registrar->forgetCachedPermissions();

        // Teams aktif: peran & izin dibuat sebagai GLOBAL (team_id null), lalu di-assign
        // ke user dalam konteks tim (organisasi) di DatabaseSeeder.
        $registrar->setPermissionsTeamId(null);

        $permissions = [
            // Tata kelola lintas-organisasi (SuperAdmin)
            'manage-organizations', 'approve-users',
            // Data master (Admin org)
            'manage-users', 'manage-signatories', 'manage-templates', 'manage-numbering',
            // Operasional (Operator org)
            'manage-events', 'manage-participants', 'issue-certificates',
            'distribute-certificates', 'view-archive', 'export-audit',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // SuperAdmin: tata kelola lintas-organisasi (peran global, team_id null).
        $superAdmin = Role::findOrCreate('SuperAdmin', 'web');
        $superAdmin->syncPermissions(Permission::all());

        // Operator: seluruh tugas operasional dalam organisasinya.
        $operator = Role::findOrCreate('Operator', 'web');
        $operator->syncPermissions([
            'manage-events', 'manage-participants', 'issue-certificates',
            'distribute-certificates', 'view-archive',
        ]);

        // Admin (org): akses penuh di organisasinya (tanpa tata kelola lintas-org).
        $admin = Role::findOrCreate('Admin', 'web');
        $admin->syncPermissions([
            'manage-users', 'manage-signatories', 'manage-templates', 'manage-numbering',
            'manage-events', 'manage-participants', 'issue-certificates',
            'distribute-certificates', 'view-archive', 'export-audit',
        ]);
    }
}
