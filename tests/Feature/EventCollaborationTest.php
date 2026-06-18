<?php

/**
 * tests/Feature/EventCollaborationTest.php
 * Acceptance P7 (K10): pembuat acara = owner; user lain minta gabung via kode undangan (Q8);
 * non-member tak bisa akses; setelah owner approve bisa kelola; SuperAdmin selalu akses;
 * kolaborasi hanya dalam organisasi yang sama (Q7).
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

class EventCollaborationTest extends TestCase
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
        $user = User::create(['name' => $email, 'email' => $email, 'password' => 'password']);
        $user->forceFill(['organization_id' => $org->id])->save();
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $user->assignRole('Operator');

        return $user;
    }

    private function superAdmin(): User
    {
        return User::create(['name' => 'Super', 'email' => 'super@test.local', 'password' => 'password']);
    }

    /** Buat acara sebagai $owner (lewat route → owner + join_code di-set service). */
    private function createEventAs(User $owner, string $nama = 'Acara'): Event
    {
        $this->actingAs($owner)->post('/events', ['nama' => $nama, 'jadwal_mulai' => now()->toDateTimeString()])
            ->assertRedirect();

        app(Tenancy::class)->setOrganizationId($owner->organization_id);
        $event = Event::where('nama', $nama)->firstOrFail();
        app(Tenancy::class)->setOrganizationId(null);

        return $event;
    }

    public function test_creator_becomes_owner_with_join_code(): void
    {
        $owner = $this->operatorFor($this->orgA, 'owner@test.local');
        $event = $this->createEventAs($owner);

        $this->assertSame($owner->id, $event->created_by);
        $this->assertNotNull($event->join_code);
        $this->assertDatabaseHas('event_members', [
            'event_id' => $event->id, 'user_id' => $owner->id,
            'role' => EventMember::ROLE_OWNER, 'status' => EventMember::STATUS_APPROVED,
        ]);
    }

    public function test_non_member_cannot_access_event(): void
    {
        $owner = $this->operatorFor($this->orgA, 'owner@test.local');
        $event = $this->createEventAs($owner);
        $other = $this->operatorFor($this->orgA, 'other@test.local');

        $this->actingAs($other)->get("/events/{$event->id}")->assertForbidden();
    }

    public function test_join_by_code_then_owner_approves_grants_access(): void
    {
        $owner = $this->operatorFor($this->orgA, 'owner@test.local');
        $event = $this->createEventAs($owner);
        $collab = $this->operatorFor($this->orgA, 'collab@test.local');

        // Minta gabung via kode → pending → belum bisa akses.
        $this->actingAs($collab)->post('/events/join', ['join_code' => $event->join_code])
            ->assertRedirect();
        $this->assertDatabaseHas('event_members', [
            'event_id' => $event->id, 'user_id' => $collab->id, 'status' => EventMember::STATUS_PENDING,
        ]);
        $this->actingAs($collab)->get("/events/{$event->id}")->assertForbidden();

        // Owner approve → kini bisa akses & muncul di indeks.
        $member = EventMember::where('event_id', $event->id)->where('user_id', $collab->id)->first();
        $this->actingAs($owner)->post("/events/{$event->id}/members/{$member->id}/approve")
            ->assertRedirect();

        $this->actingAs($collab)->get("/events/{$event->id}")->assertOk();
        $this->actingAs($collab)->get('/events')->assertOk()->assertSee($event->nama);
    }

    public function test_non_owner_cannot_approve(): void
    {
        $owner = $this->operatorFor($this->orgA, 'owner@test.local');
        $event = $this->createEventAs($owner);
        $collab = $this->operatorFor($this->orgA, 'collab@test.local');
        $this->actingAs($collab)->post('/events/join', ['join_code' => $event->join_code]);
        $member = EventMember::where('event_id', $event->id)->where('user_id', $collab->id)->first();

        // Operator lain (bukan owner) tak boleh approve.
        $intruder = $this->operatorFor($this->orgA, 'intruder@test.local');
        $this->actingAs($intruder)->post("/events/{$event->id}/members/{$member->id}/approve")
            ->assertForbidden();
    }

    public function test_invalid_code_is_rejected(): void
    {
        $user = $this->operatorFor($this->orgA, 'u@test.local');

        $this->actingAs($user)->post('/events/join', ['join_code' => 'TIDAKADA1'])
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_join_is_scoped_to_same_organization(): void
    {
        // Kode milik acara org A tidak bisa dipakai user org B (Q7).
        $owner = $this->operatorFor($this->orgA, 'owner@test.local');
        $event = $this->createEventAs($owner);
        $outsider = $this->operatorFor($this->orgB, 'outsider@test.local');

        $this->actingAs($outsider)->post('/events/join', ['join_code' => $event->join_code])
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('event_members', ['event_id' => $event->id, 'user_id' => $outsider->id]);
    }

    public function test_super_admin_can_access_any_event(): void
    {
        $owner = $this->operatorFor($this->orgA, 'owner@test.local');
        $event = $this->createEventAs($owner);

        $this->actingAs($this->superAdmin())->get("/events/{$event->id}")->assertOk();
    }
}
