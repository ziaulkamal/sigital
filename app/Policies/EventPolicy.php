<?php

/**
 * app/Policies/EventPolicy.php
 * Otorisasi objek acara (P7/§5): cegah IDOR lintas-akses via ID langsung.
 * SuperAdmin lolos lewat Gate::before (AppServiceProvider). Selain itu: akses HANYA
 * untuk owner atau collaborator yang sudah di-approve. Kolaborasi hanya dalam org sama (Q7),
 * jadi global scope organisasi + cek keanggotaan cukup.
 */

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /** Lihat & kelola detail acara (peserta, penerbitan, distribusi sesuai izin peran). */
    public function view(User $user, Event $event): bool
    {
        return $event->hasApprovedMember($user->id);
    }

    public function update(User $user, Event $event): bool
    {
        return $event->hasApprovedMember($user->id);
    }

    public function delete(User $user, Event $event): bool
    {
        // Hanya pemilik yang boleh menghapus acara (collaborator tidak).
        return $event->created_by === $user->id;
    }

    /** Menyetujui/menolak permintaan gabung — hanya pemilik acara (SuperAdmin via before). */
    public function manageMembers(User $user, Event $event): bool
    {
        return $event->created_by === $user->id;
    }
}
