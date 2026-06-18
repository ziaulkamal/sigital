<?php

/**
 * app/Services/EventService.php
 * Logika acara: pembuatan, penetapan penanda tangan & template, transisi status (FR-03/04/05).
 */

namespace App\Services;

use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;
use App\Notifications\EventCreated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class EventService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** @param array<string,mixed> $data */
    public function create(array $data): Event
    {
        $signatoryIds = $data['signatory_ids'] ?? [];
        unset($data['signatory_ids']);

        $event = new Event($data);
        $event->created_by = Auth::id(); // pemilik (P7)
        $event->join_code = $this->uniqueJoinCode();
        $event->save();

        // Pembuat otomatis menjadi owner ber-status approved (K10).
        if ($event->created_by !== null) {
            EventMember::create([
                'event_id' => $event->id,
                'user_id' => $event->created_by,
                'role' => EventMember::ROLE_OWNER,
                'status' => EventMember::STATUS_APPROVED,
                'approved_at' => now(),
            ]);
        }

        $this->syncSignatories($event, $signatoryIds);

        $this->audit->log('event.created', $event, ['nama' => $event->nama]);

        // Beri tahu SuperAdmin acara baru dibuat oleh user.
        $creatorName = Auth::user()?->name ?? 'Pengguna';
        Notification::send(User::whereNull('organization_id')->get(), new EventCreated($event, $creatorName));

        return $event;
    }

    /** Kode undangan acak yang unik (Q8 — owner membagikannya untuk join). */
    private function uniqueJoinCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Event::withoutOrganizationScope()->where('join_code', $code)->exists());

        return $code;
    }

    /** @param array<string,mixed> $data */
    public function update(Event $event, array $data): Event
    {
        $signatoryIds = $data['signatory_ids'] ?? null;
        unset($data['signatory_ids']);

        $event->update($data);
        if ($signatoryIds !== null) {
            $this->syncSignatories($event, $signatoryIds);
        }

        $this->audit->log('event.updated', $event, ['nama' => $event->nama]);

        return $event;
    }

    /** Tetapkan urutan penanda tangan sesuai posisi pada array. */
    public function syncSignatories(Event $event, array $signatoryIds): void
    {
        $payload = [];
        foreach (array_values($signatoryIds) as $i => $id) {
            $payload[$id] = ['urutan' => $i + 1];
        }
        $event->signatories()->sync($payload);
    }

    /** Ubah status; cegah "siap_terbit" bila belum punya template + penanda tangan (FR-04). */
    public function changeStatus(Event $event, string $status): Event
    {
        if ($status === Event::STATUS_SIAP_TERBIT && ! $event->canIssue()) {
            throw new \RuntimeException('Acara wajib punya minimal satu template dan satu penanda tangan sebelum siap terbit.');
        }

        $event->update(['status' => $status]);
        $this->audit->log('event.status_changed', $event, ['status' => $status]);

        return $event;
    }
}
