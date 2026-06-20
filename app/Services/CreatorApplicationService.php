<?php

/**
 * app/Services/CreatorApplicationService.php
 * Pendaftaran Marketplace Creator (verifikasi SuperAdmin) + rekening pencairan.
 *
 * Alur: apply (pending) → approve/reject; setelah approved → saveBank (pending) →
 * verifyBank/rejectBank. Fitur creator (publish/pencairan) baru terbuka saat
 * creator approved DAN bank verified (User::canUseCreatorFeatures).
 *
 * Semua mutasi terpusat di sini + audit (pola MarketplaceWithdrawalService).
 * KTP disimpan di disk PRIVAT ('local') — data sensitif.
 */

namespace App\Services;

use App\Models\User;
use App\Notifications\BankAccountReviewed;
use App\Notifications\BankAccountSubmitted;
use App\Notifications\CreatorApplicationReviewed;
use App\Notifications\CreatorApplicationSubmitted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class CreatorApplicationService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * User mendaftar sebagai Creator. KTP wajib (disimpan privat).
     *
     * @param  array{full_name:string,address:string}  $data
     */
    public function apply(User $user, array $data, UploadedFile $ktp): void
    {
        if ($user->isMarketplaceCreator()) {
            throw new RuntimeException('Akun Anda sudah menjadi Marketplace Creator.');
        }
        if ($user->creatorApplicationPending()) {
            throw new RuntimeException('Pendaftaran Anda sedang menunggu verifikasi.');
        }

        $ktpPath = $ktp->store('creator-ktp', 'local'); // disk privat

        $user->forceFill([
            'creator_status' => 'pending',
            'creator_full_name' => $data['full_name'],
            'creator_address' => $data['address'],
            'creator_ktp_path' => $ktpPath,
            'creator_terms_accepted_at' => now(),
            'creator_applied_at' => now(),
            'creator_reviewed_at' => null,
            'creator_reviewed_by' => null,
            'creator_reject_reason' => null,
        ])->save();

        $this->audit->log('marketplace.creator_applied', $user, ['full_name' => $data['full_name']], $user->id);
        Notification::send($this->reviewers($user), new CreatorApplicationSubmitted($user));
    }

    public function approve(User $user, User $actor): void
    {
        $this->assertPendingApplication($user);

        $user->forceFill([
            'creator_status' => 'approved',
            'marketplace_enabled' => true,
            'marketplace_joined_at' => now(),
            'creator_reviewed_at' => now(),
            'creator_reviewed_by' => $actor->id,
            'creator_reject_reason' => null,
        ])->save();

        $this->audit->log('marketplace.creator_approved', $user, [], $actor->id);
        $user->notify(new CreatorApplicationReviewed(approved: true));
    }

    public function reject(User $user, string $reason, User $actor): void
    {
        $this->assertPendingApplication($user);

        $user->forceFill([
            'creator_status' => 'rejected',
            'marketplace_enabled' => false,
            'creator_reviewed_at' => now(),
            'creator_reviewed_by' => $actor->id,
            'creator_reject_reason' => $reason,
        ])->save();

        $this->audit->log('marketplace.creator_rejected', $user, ['reason' => $reason], $actor->id);
        $user->notify(new CreatorApplicationReviewed(approved: false, reason: $reason));
    }

    /**
     * Creator menyimpan/mengubah rekening. Setiap perubahan → status pending
     * (wajib verifikasi ulang; cegah ganti rekening diam-diam setelah lolos).
     *
     * @param  array{bank_name:string,bank_account_no:string,bank_account_holder:string}  $data
     */
    public function saveBank(User $user, array $data): void
    {
        if (! $user->isMarketplaceCreator()) {
            throw new RuntimeException('Lengkapi pendaftaran Creator dahulu.');
        }

        $user->forceFill([
            'bank_name' => $data['bank_name'],
            'bank_account_no' => $data['bank_account_no'],
            'bank_account_holder' => $data['bank_account_holder'],
            'bank_status' => 'pending',
            'bank_reviewed_at' => null,
            'bank_reviewed_by' => null,
            'bank_reject_reason' => null,
        ])->save();

        $this->audit->log('marketplace.bank_submitted', $user, [], $user->id);
        Notification::send($this->superAdmins(), new BankAccountSubmitted($user));
    }

    public function verifyBank(User $user, User $actor): void
    {
        $this->assertPendingBank($user);

        $user->forceFill([
            'bank_status' => 'verified',
            'bank_reviewed_at' => now(),
            'bank_reviewed_by' => $actor->id,
            'bank_reject_reason' => null,
        ])->save();

        $this->audit->log('marketplace.bank_verified', $user, [], $actor->id);
        $user->notify(new BankAccountReviewed(verified: true));
    }

    public function rejectBank(User $user, string $reason, User $actor): void
    {
        $this->assertPendingBank($user);

        $user->forceFill([
            'bank_status' => 'rejected',
            'bank_reviewed_at' => now(),
            'bank_reviewed_by' => $actor->id,
            'bank_reject_reason' => $reason,
        ])->save();

        $this->audit->log('marketplace.bank_rejected', $user, ['reason' => $reason], $actor->id);
        $user->notify(new BankAccountReviewed(verified: false, reason: $reason));
    }

    private function assertPendingApplication(User $user): void
    {
        if (! $user->creatorApplicationPending()) {
            throw new RuntimeException('Pendaftaran ini sudah diproses.');
        }
    }

    private function assertPendingBank(User $user): void
    {
        if ($user->bank_status !== 'pending') {
            throw new RuntimeException('Rekening ini sudah diproses.');
        }
    }

    /** SuperAdmin global. */
    private function superAdmins()
    {
        return User::whereNull('organization_id')->get();
    }

    /** SuperAdmin + Admin instansi pemohon (pola WithdrawalRequested). */
    private function reviewers(User $applicant)
    {
        $orgAdmins = $applicant->organization_id === null
            ? collect()
            : User::where('organization_id', $applicant->organization_id)
                ->where('id', '!=', $applicant->id)
                ->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))
                ->get();

        return $this->superAdmins()->merge($orgAdmins)->unique('id');
    }
}
