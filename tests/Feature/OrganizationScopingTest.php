<?php

/**
 * tests/Feature/OrganizationScopingTest.php
 * Acceptance P1: data terisolasi per organisasi; SuperAdmin melihat gabungan;
 * nomor sertifikat memakai kode organisasi.
 */

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrganizationScopingTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;
    private Organization $orgB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->orgA = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas']);
        $this->orgB = Organization::create(['nama' => 'Komunitas B', 'kode' => 'KOMB', 'type' => 'komunitas']);
    }

    private function operatorFor(Organization $org, string $email): User
    {
        // organization_id sengaja tidak fillable di User (server-controlled) → set eksplisit.
        $user = User::create([
            'name' => "Operator {$org->kode}",
            'email' => $email,
            'password' => 'password',
        ]);
        $user->forceFill(['organization_id' => $org->id])->save();

        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $user->assignRole('Operator');

        return $user;
    }

    private function eventIn(Organization $org, string $nama): Event
    {
        app(Tenancy::class)->setOrganizationId($org->id);
        $event = Event::create(['nama' => $nama, 'jadwal_mulai' => now()]);
        app(Tenancy::class)->setOrganizationId(null);

        return $event;
    }

    public function test_operator_only_sees_events_from_own_organization(): void
    {
        $operatorA = $this->operatorFor($this->orgA, 'a@test.local');
        $operatorB = $this->operatorFor($this->orgB, 'b@test.local');

        $this->eventIn($this->orgA, 'Acara Dinas A');
        $this->eventIn($this->orgB, 'Acara Komunitas B');

        $this->actingAs($operatorA)->get('/events')
            ->assertOk()
            ->assertSee('Acara Dinas A')
            ->assertDontSee('Acara Komunitas B');

        $this->actingAs($operatorB)->get('/events')
            ->assertOk()
            ->assertSee('Acara Komunitas B')
            ->assertDontSee('Acara Dinas A');
    }

    public function test_event_auto_fills_organization_from_active_tenant(): void
    {
        $event = $this->eventIn($this->orgA, 'Acara Auto');

        $this->assertSame($this->orgA->id, $event->organization_id);
    }

    public function test_super_admin_sees_all_organizations(): void
    {
        $this->eventIn($this->orgA, 'Acara Dinas A');
        $this->eventIn($this->orgB, 'Acara Komunitas B');

        // SuperAdmin = organization_id null (default) → tanpa scope.
        $superAdmin = User::create([
            'name' => 'Super', 'email' => 'super@test.local', 'password' => 'password',
        ]);

        $this->actingAs($superAdmin)->get('/events')
            ->assertOk()
            ->assertSee('Acara Dinas A')
            ->assertSee('Acara Komunitas B');
    }

    public function test_certificate_number_uses_organization_code(): void
    {
        $event = $this->eventIn($this->orgB, 'Acara Komunitas B');

        $nomor = app(\App\Services\Certificate\CertificateNumberGenerator::class)->generate($event, 1);

        $this->assertStringStartsWith('KOMB/', $nomor);
    }
}
