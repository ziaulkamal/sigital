<?php

/**
 * app/Services/ApprovalService.php
 * Fase P2 — keputusan approval SuperAdmin atas registrasi panitia (K4).
 * Approve: aktifkan akun (+ organisasi yang diajukan) & tetapkan peran dalam konteks tim.
 * Reject: tandai akun ditolak. Semua tercatat di AuditLog.
 */

namespace App\Services;

use App\Models\User;
use App\Notifications\AccountDecision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;

class ApprovalService
{
    /** Peran organisasi yang boleh diberikan saat approval. */
    private const ASSIGNABLE_ROLES = ['Admin', 'Operator'];

    public function __construct(private readonly AuditLogger $audit) {}

    public function approve(User $user, User $approver): void
    {
        DB::transaction(function () use ($user, $approver): void {
            $user->forceFill([
                'status' => User::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ])->save();

            $organization = $user->organization;

            // Aktifkan organisasi yang diajukan saat registrasi (bila belum aktif).
            if ($organization && ! $organization->is_active) {
                $organization->forceFill(['is_active' => true, 'approved_at' => now()])->save();
            }

            // Tetapkan peran dalam konteks tim (organisasi) — spatie teams.
            $role = in_array($user->requested_role, self::ASSIGNABLE_ROLES, true)
                ? $user->requested_role
                : 'Operator';

            app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);
            $user->syncRoles($role);

            $this->audit->log('user.approved', $user, [
                'organization_id' => $user->organization_id,
                'role' => $role,
            ], $approver->id);
        });

        $user->notify(new AccountDecision(true));
        $this->notify($user, 'Akun SIGITAL Anda telah disetujui',
            "Halo {$user->name}, akun Anda telah disetujui. Silakan masuk untuk mulai menggunakan SIGITAL.");
    }

    public function reject(User $user, User $approver, ?string $reason = null): void
    {
        $user->forceFill([
            'status' => User::STATUS_REJECTED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ])->save();

        $this->audit->log('user.rejected', $user, [
            'organization_id' => $user->organization_id,
            'reason' => $reason,
        ], $approver->id);

        $user->notify(new AccountDecision(false, $reason));
        $this->notify($user, 'Pendaftaran SIGITAL Anda ditolak',
            "Halo {$user->name}, pendaftaran Anda belum dapat disetujui.".($reason ? " Alasan: {$reason}" : ''));
    }

    /** Kirim notifikasi email (mailer 'log' di dev). Kegagalan email tak membatalkan keputusan. */
    private function notify(User $user, string $subject, string $body): void
    {
        try {
            Mail::raw($body, function ($message) use ($user, $subject): void {
                $message->to($user->email)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim email approval: '.$e->getMessage());
        }
    }
}
