<?php

/**
 * app/Services/EventMembershipService.php
 * Kolaborasi acara (P7/K10): minta gabung via kode undangan, lalu owner/SuperAdmin
 * setujui/tolak. Pencarian acara via kode tunduk global scope organisasi (Q7: hanya
 * dalam org yang sama) sehingga lintas-org otomatis tertolak.
 */

namespace App\Services;

use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;
use App\Notifications\EventJoinRequested;

class EventMembershipService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Buat permintaan gabung (pending) dari kode undangan.
     *
     * @throws \RuntimeException kode tak valid (atau lintas-org), atau sudah jadi anggota.
     */
    public function requestJoinByCode(string $code, User $user): EventMember
    {
        $event = Event::where('join_code', $code)->first();

        if ($event === null) {
            throw new \RuntimeException('Kode undangan tidak valid.');
        }

        $existing = $event->members()->where('user_id', $user->id)->first();
        if ($existing !== null) {
            throw new \RuntimeException(match ($existing->status) {
                EventMember::STATUS_APPROVED => 'Anda sudah menjadi anggota acara ini.',
                EventMember::STATUS_PENDING => 'Permintaan Anda masih menunggu persetujuan.',
                default => 'Permintaan Anda sebelumnya ditolak.',
            });
        }

        $member = EventMember::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'role' => EventMember::ROLE_COLLABORATOR,
            'status' => EventMember::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        $this->audit->log('event.join_requested', $event, ['user_id' => $user->id]);

        // Beri tahu pemilik acara ada permintaan gabung.
        $owner = $event->created_by ? User::find($event->created_by) : null;
        $owner?->notify(new EventJoinRequested($event, $user->name));

        return $member;
    }

    public function approve(EventMember $member, User $approver): EventMember
    {
        $member->update([
            'status' => EventMember::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->audit->log('event.join_approved', $member->event, ['user_id' => $member->user_id]);

        return $member;
    }

    public function reject(EventMember $member, User $approver): EventMember
    {
        $member->update([
            'status' => EventMember::STATUS_REJECTED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->audit->log('event.join_rejected', $member->event, ['user_id' => $member->user_id]);

        return $member;
    }
}
