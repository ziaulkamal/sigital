<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $registrar = app(PermissionRegistrar::class);

        // ── Organisasi contoh (P1): satu dinas, satu komunitas. ──────────────
        $diskominfo = Organization::firstOrCreate(
            ['kode' => 'DISKOMINFO'],
            ['nama' => 'Dinas Komunikasi dan Informatika', 'type' => Organization::TYPE_DINAS]
        );

        $relawantik = Organization::firstOrCreate(
            ['kode' => 'RELAWANTIK'],
            ['nama' => 'Relawan TIK', 'type' => Organization::TYPE_KOMUNITAS]
        );

        // ── SuperAdmin: global, tanpa organisasi. ────────────────────────────
        // Tidak di-assign peran via pivot (spatie teams mewajibkan team_id NOT NULL).
        // Otorisasinya diberikan lewat Gate::before (lihat AppServiceProvider).
        User::firstOrCreate(
            ['email' => 'superadmin@sigital.test'],
            ['name' => 'Super Administrator', 'password' => 'password', 'organization_id' => null]
        );

        // ── Data lama MVP di-assign ke organisasi default (DISKOMINFO). ──────
        $registrar->setPermissionsTeamId($diskominfo->id);

        $admin = User::firstOrCreate(
            ['email' => 'admin@sigital.test'],
            ['name' => 'Administrator', 'password' => 'password']
        );
        $admin->forceFill(['organization_id' => $diskominfo->id])->save();
        $admin->syncRoles('Admin');

        $operator = User::firstOrCreate(
            ['email' => 'operator@sigital.test'],
            ['name' => 'Operator', 'password' => 'password']
        );
        $operator->forceFill(['organization_id' => $diskominfo->id])->save();
        $operator->syncRoles('Operator');

        // ── Operator komunitas (demonstrasi segmen RelawanTIK). ──────────────
        $registrar->setPermissionsTeamId($relawantik->id);
        $relawan = User::firstOrCreate(
            ['email' => 'relawan@sigital.test'],
            ['name' => 'Operator RelawanTIK', 'password' => 'password']
        );
        $relawan->forceFill(['organization_id' => $relawantik->id])->save();
        $relawan->syncRoles('Operator');

        // Template default bersifat GLOBAL (organization_id null) → dipakai semua organisasi.
        Template::firstOrCreate(
            ['slug' => 'default'],
            [
                'nama' => 'Template Default',
                'deskripsi' => 'Template sertifikat bawaan untuk MVP.',
                'view' => 'certificates.default',
                'is_active' => true,
                'is_global' => true,
                'organization_id' => null,
            ]
        );
    }
}
