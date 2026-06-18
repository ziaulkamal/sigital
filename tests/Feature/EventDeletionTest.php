<?php

/**
 * tests/Feature/EventDeletionTest.php
 * Acara hanya boleh dihapus oleh Admin & SuperAdmin. Operator/owner non-Admin: dilarang.
 */

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventMember;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EventDeletionTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->org = Organization::create(['nama' => 'Dinas A', 'kode' => 'DINASA', 'type' => 'dinas', 'is_active' => true]);
    }

    private function userWithRole(string $role, string $email): User
    {
        $user = User::create(['name' => $role, 'email' => $email, 'password' => 'password']);
        $user->forceFill([
            'organization_id' => $this->org->id,
            'status' => User::STATUS_APPROVED,
            'nik' => fake()->unique()->numerify('################'),
            'phone' => '628'.fake()->numerify('#########'),
        ])->save();
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
        $user->assignRole($role);

        return $user;
    }

    private function eventOwnedBy(User $owner): Event
    {
        app(Tenancy::class)->setOrganizationId($this->org->id);
        $event = Event::create(['nama' => 'Acara', 'jadwal_mulai' => now()]);
        $event->forceFill(['created_by' => $owner->id])->save();
        EventMember::create([
            'event_id' => $event->id, 'user_id' => $owner->id,
            'role' => EventMember::ROLE_OWNER, 'status' => EventMember::STATUS_APPROVED, 'approved_at' => now(),
        ]);
        app(Tenancy::class)->setOrganizationId(null);

        return $event;
    }

    public function test_operator_owner_cannot_delete_event(): void
    {
        $operator = $this->userWithRole('Operator', 'op@test.local');
        $event = $this->eventOwnedBy($operator);

        $this->actingAs($operator)->delete("/events/{$event->id}")->assertForbidden();
        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }

    public function test_admin_can_delete_event(): void
    {
        $operator = $this->userWithRole('Operator', 'op@test.local');
        $admin = $this->userWithRole('Admin', 'admin@test.local');
        $event = $this->eventOwnedBy($operator);

        $this->actingAs($admin)->delete("/events/{$event->id}")->assertRedirect('/events');
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_super_admin_can_delete_event(): void
    {
        $operator = $this->userWithRole('Operator', 'op@test.local');
        $super = User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);
        $event = $this->eventOwnedBy($operator);

        $this->actingAs($super)->delete("/events/{$event->id}")->assertRedirect('/events');
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }
}
