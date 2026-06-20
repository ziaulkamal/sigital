<?php

/**
 * app/Services/CreditService.php
 * Titik tunggal seluruh mutasi saldo credit per-user. Setiap mutasi:
 *   - dibungkus DB::transaction + lockForUpdate pada baris user (cegah race),
 *   - menulis satu baris ledger credit_transactions (append-only),
 *   - mencatat audit via AuditLogger.
 *
 * Saldo TIDAK boleh minus: consume yang melebihi saldo melempar
 * InsufficientCreditException; adjust negatif di-clamp ke 0.
 */

namespace App\Services;

use App\Exceptions\InsufficientCreditException;
use App\Models\CreditTransaction;
use App\Models\PlatformCreditLedger;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreditService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function balance(User $user): int
    {
        return (int) $user->credit_balance;
    }

    public function hasEnough(User $user, int $amount): bool
    {
        return $this->balance($user) >= $amount;
    }

    /**
     * User bebas-credit: Enterprise aktif (2FA on) atau SuperAdmin.
     * Saat exempt, konsumsi credit dilewati.
     */
    public function isCreditExempt(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isEnterpriseActive();
    }

    /**
     * Kurangi saldo untuk pemakaian (buat acara/template). Tidak mengubah saldo
     * bila user exempt — pemanggil sebaiknya cek isCreditExempt lebih dulu agar
     * dapat mencatat audit 'credit.exempt'; namun dipanggil saat exempt = no-op aman.
     *
     * @throws InsufficientCreditException bila saldo kurang.
     */
    public function consume(User $user, int $amount, ?Model $reference, string $desc, string $action = ''): void
    {
        // Tolak jumlah non-positif: cegah "consume negatif" yang justru menambah saldo.
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Jumlah konsumsi credit harus positif.');
        }

        DB::transaction(function () use ($user, $amount, $reference, $desc, $action) {
            $fresh = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($fresh->credit_balance < $amount) {
                // Penanda upaya gagal — dicatat oleh InsufficientCreditException::report()
                // SETELAH transaksi unwind (agar tak ikut ter-rollback). Lihat exception.
                throw new InsufficientCreditException($amount, (int) $fresh->credit_balance, $action, $user->id, $reference);
            }

            $after = $fresh->credit_balance - $amount;
            $fresh->forceFill(['credit_balance' => $after])->save();
            $user->credit_balance = $after; // sinkron instance pemanggil

            $this->writeLedger($fresh, CreditTransaction::TYPE_CONSUME, -$amount, $after, $reference, $desc);
            $this->audit->log('credit.consumed', $reference, [
                'amount' => $amount,
                'balance_after' => $after,
            ], $fresh->id);
        });
    }

    /**
     * Tambah saldo (topup/grant/royalti). Tipe ledger dapat di-override
     * (mis. template_royalty pada marketplace).
     */
    public function grant(User $user, int $amount, string $desc, ?int $actorId = null, string $type = CreditTransaction::TYPE_GRANT, ?Model $reference = null): void
    {
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($user, $amount, $desc, $actorId, $type, $reference) {
            $fresh = User::query()->lockForUpdate()->findOrFail($user->id);
            $after = $fresh->credit_balance + $amount;
            $fresh->forceFill(['credit_balance' => $after])->save();
            $user->credit_balance = $after;

            $this->writeLedger($fresh, $type, $amount, $after, $reference, $desc, $actorId);
            $this->audit->log('credit.granted', $reference, [
                'amount' => $amount,
                'balance_after' => $after,
                'type' => $type,
            ], $actorId);
        });
    }

    /**
     * Penyesuaian manual dua arah oleh SuperAdmin. delta boleh +/- (≠0).
     * Negatif di-clamp ke 0 (saldo tak minus); amount aktual yang terpotong dicatat.
     */
    public function adjust(User $user, int $delta, string $reason, int $actorId): void
    {
        if ($delta === 0) {
            return;
        }

        DB::transaction(function () use ($user, $delta, $reason, $actorId) {
            $fresh = User::query()->lockForUpdate()->findOrFail($user->id);

            $after = max(0, $fresh->credit_balance + $delta);
            $applied = $after - $fresh->credit_balance; // delta aktual (clamp)
            $fresh->forceFill(['credit_balance' => $after])->save();
            $user->credit_balance = $after;

            $type = $applied >= 0 ? CreditTransaction::TYPE_GRANT : CreditTransaction::TYPE_ADJUST;
            $this->writeLedger($fresh, $type, $applied, $after, null, "Penyesuaian SuperAdmin: {$reason}", $actorId);
            $this->audit->log('credit.adjusted', $fresh, [
                'delta' => $delta,
                'applied' => $applied,
                'balance_after' => $after,
                'reason' => $reason,
            ], $actorId);
        });
    }

    /**
     * Catat pendapatan platform (terpisah dari saldo user). Dipakai marketplace
     * (share platform). Tidak menyentuh saldo user mana pun.
     */
    public function recordPlatformRevenue(int $amount, ?Model $source, string $desc): PlatformCreditLedger
    {
        return PlatformCreditLedger::create([
            'source_type' => $source ? $source->getMorphClass() : null,
            'source_id' => $source?->getKey(),
            'credit_amount' => $amount,
            'description' => $desc,
        ]);
    }

    /** Tulis baris ledger. Dipanggil selalu di dalam transaksi yang sudah lock. */
    private function writeLedger(User $user, string $type, int $amount, int $balanceAfter, ?Model $reference, string $desc, ?int $actorId = null): CreditTransaction
    {
        return CreditTransaction::create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'reference_type' => $reference ? $reference->getMorphClass() : null,
            'reference_id' => $reference?->getKey(),
            'description' => $desc,
            'created_by' => $actorId,
        ]);
    }
}
