<?php

/**
 * app/Services/BanService.php
 * Pemblokiran (ban) & pembukaan blokir (unban) akun oleh SuperAdmin.
 * Ban: setel status 'suspended' + simpan alasan/jejak → ditolak saat login dengan alasan.
 * Unban: kembalikan status 'approved' & hapus jejak ban. Semua tercatat di AuditLog.
 */

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class BanService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function ban(User $user, User $actor, string $reason): void
    {
        DB::transaction(function () use ($user, $actor, $reason): void {
            $user->forceFill([
                'status' => User::STATUS_SUSPENDED,
                'banned_reason' => $reason,
                'banned_at' => now(),
                'banned_by' => $actor->id,
            ])->save();

            // Cabut sesi & token aktif agar pemblokiran berlaku seketika.
            $user->tokens()->delete();

            $this->audit->log('user.banned', $user, ['reason' => $reason], $actor->id);
        });
    }

    public function unban(User $user, User $actor): void
    {
        DB::transaction(function () use ($user, $actor): void {
            $user->forceFill([
                'status' => User::STATUS_APPROVED,
                'banned_reason' => null,
                'banned_at' => null,
                'banned_by' => null,
            ])->save();

            $this->audit->log('user.unbanned', $user, [], $actor->id);
        });
    }
}
