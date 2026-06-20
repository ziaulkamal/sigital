<?php

/**
 * app/Services/MarketplaceWithdrawalService.php
 * Pencairan royalti Creator (Bagian 6.7/6.8). Saat diajukan, credit langsung
 * dipotong dari saldo (ditahan); biaya admin dipisah sebagai pendapatan platform.
 * credit_paid = credit_requested - admin_fee → dasar nilai rupiah.
 *
 * Bila ditolak, credit dikembalikan penuh ke Creator (refund).
 */

namespace App\Services;

use App\Exceptions\InsufficientCreditException;
use App\Models\CreditTransaction;
use App\Models\MarketplaceWithdrawal;
use App\Models\User;
use App\Notifications\WithdrawalRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class MarketplaceWithdrawalService
{
    public function __construct(
        private readonly CreditService $credit,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Creator mengajukan pencairan. Credit langsung dipotong (ditahan).
     *
     * @throws InsufficientCreditException bila saldo kurang.
     */
    public function requestWithdrawal(User $user, int $creditRequested): MarketplaceWithdrawal
    {
        $fee = (int) config('sigital.credit.withdraw_fee');
        $min = (int) config('sigital.credit.withdraw_min');
        $rupiahPerCredit = (int) config('sigital.credit.rupiah_per_credit');

        if (! $user->canUseCreatorFeatures()) {
            throw new RuntimeException('Lengkapi & verifikasi rekening pencairan dahulu.');
        }
        if ($creditRequested < $min) {
            throw new RuntimeException("Minimal pencairan {$min} credit.");
        }
        if ($creditRequested <= $fee) {
            throw new RuntimeException('Jumlah pencairan harus melebihi biaya admin.');
        }

        return DB::transaction(function () use ($user, $creditRequested, $fee, $rupiahPerCredit) {
            $creditPaid = $creditRequested - $fee;

            $withdrawal = MarketplaceWithdrawal::create([
                'user_id' => $user->id,
                'credit_requested' => $creditRequested,
                'admin_fee_credit' => $fee,
                'credit_paid' => $creditPaid,
                'rupiah_paid' => $creditPaid * $rupiahPerCredit,
                'status' => MarketplaceWithdrawal::STATUS_PENDING,
                'requested_at' => now(),
            ]);

            // Tahan credit: potong seluruh jumlah yang diajukan (termasuk fee).
            $this->credit->consume($user, $creditRequested, $withdrawal, 'Pencairan royalti #'.$withdrawal->id, 'mencairkan royalti');
            // Biaya admin → pendapatan platform.
            $this->credit->recordPlatformRevenue($fee, $withdrawal, 'Biaya admin pencairan #'.$withdrawal->id);

            $this->audit->log('marketplace.withdraw_requested', $withdrawal, [
                'credit_requested' => $creditRequested, 'credit_paid' => $creditPaid,
            ], $user->id);

            $this->notifyReviewers($withdrawal, $user);

            return $withdrawal;
        });
    }

    /** Beri tahu SuperAdmin (global) & Admin instansi pemohon tentang pengajuan pencairan. */
    private function notifyReviewers(MarketplaceWithdrawal $withdrawal, User $creator): void
    {
        $superAdmins = User::whereNull('organization_id')->get();

        // Admin pada instansi yang sama dengan pemohon.
        $orgAdmins = $creator->organization_id === null
            ? collect()
            : User::where('organization_id', $creator->organization_id)
                ->where('id', '!=', $creator->id)
                ->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))
                ->get();

        $recipients = $superAdmins->merge($orgAdmins)->unique('id');

        Notification::send($recipients, new WithdrawalRequested($withdrawal, $creator->name));
    }

    /** SuperAdmin menjadwalkan tanggal pembayaran. */
    public function scheduleWithdrawal(MarketplaceWithdrawal $w, \DateTimeInterface $date, User $actor): MarketplaceWithdrawal
    {
        $this->assertOpen($w);
        $w->forceFill([
            'status' => MarketplaceWithdrawal::STATUS_SCHEDULED,
            'scheduled_payout_date' => $date,
        ])->save();
        $this->audit->log('marketplace.withdraw_scheduled', $w, ['date' => $date->format('Y-m-d')], $actor->id);

        return $w;
    }

    public function approveWithdrawal(MarketplaceWithdrawal $w, User $actor): MarketplaceWithdrawal
    {
        $this->assertOpen($w);
        $w->forceFill([
            'status' => MarketplaceWithdrawal::STATUS_APPROVED,
            'processed_by' => $actor->id,
            'processed_at' => now(),
        ])->save();
        $this->audit->log('marketplace.withdraw_approved', $w, [], $actor->id);

        return $w;
    }

    /** Tolak → kembalikan seluruh credit yang ditahan ke Creator (refund). */
    public function rejectWithdrawal(MarketplaceWithdrawal $w, string $reason, User $actor): MarketplaceWithdrawal
    {
        $this->assertOpen($w);

        DB::transaction(function () use ($w, $reason, $actor) {
            $this->credit->grant(
                $w->user,
                $w->credit_requested,
                'Pengembalian pencairan #'.$w->id.': '.$reason,
                $actor->id,
                CreditTransaction::TYPE_REFUND,
                $w,
            );
            $w->forceFill([
                'status' => MarketplaceWithdrawal::STATUS_REJECTED,
                'processed_by' => $actor->id,
                'processed_at' => now(),
                'notes' => $reason,
            ])->save();
            $this->audit->log('marketplace.withdraw_rejected', $w, ['reason' => $reason], $actor->id);
        });

        return $w;
    }

    public function markAsPaid(MarketplaceWithdrawal $w, User $actor): MarketplaceWithdrawal
    {
        if ($w->status === MarketplaceWithdrawal::STATUS_PAID) {
            throw new RuntimeException('Pencairan ini sudah dibayar.');
        }
        if ($w->status === MarketplaceWithdrawal::STATUS_REJECTED) {
            throw new RuntimeException('Pencairan ini ditolak.');
        }

        $w->forceFill([
            'status' => MarketplaceWithdrawal::STATUS_PAID,
            'processed_by' => $actor->id,
            'processed_at' => now(),
        ])->save();
        $this->audit->log('marketplace.withdraw_paid', $w, ['rupiah' => $w->rupiah_paid], $actor->id);

        return $w;
    }

    private function assertOpen(MarketplaceWithdrawal $w): void
    {
        if (! $w->isOpen()) {
            throw new RuntimeException('Status pencairan tidak memungkinkan aksi ini.');
        }
    }
}
