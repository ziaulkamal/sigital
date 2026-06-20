<?php

/**
 * app/Services/TemplateMarketplaceService.php
 * Marketplace template (Bagian 6): publish/unpublish + pembelian penggunaan
 * dengan bagi hasil (pembeli -15, pemilik +10, platform +5).
 *
 * Seluruh mutasi credit lewat CreditService; pembelian dibungkus DB::transaction
 * agar ledger pembeli, royalti pemilik, & pendapatan platform konsisten.
 */

namespace App\Services;

use App\Exceptions\InsufficientCreditException;
use App\Models\CreditTransaction;
use App\Models\Template;
use App\Models\TemplateUsageTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TemplateMarketplaceService
{
    public function __construct(
        private readonly CreditService $credit,
        private readonly AuditLogger $audit,
    ) {}

    /** Publikasikan template ke marketplace (hanya pemilik yang Marketplace Creator). */
    public function publishTemplate(Template $template, User $actor): Template
    {
        $template->forceFill([
            'is_marketplace' => true,
            'marketplace_price' => (int) config('sigital.credit.marketplace_price'),
            'published_at' => now(),
        ])->save();

        $this->audit->log('marketplace.template_published', $template, ['nama' => $template->nama], $actor->id);

        return $template;
    }

    public function unpublishTemplate(Template $template, User $actor): Template
    {
        $template->forceFill(['is_marketplace' => false, 'published_at' => null])->save();
        $this->audit->log('marketplace.template_unpublished', $template, ['nama' => $template->nama], $actor->id);

        return $template;
    }

    /**
     * Pembeli memakai template marketplace milik orang lain.
     * Pemilik tidak dikenakan biaya untuk template-nya sendiri (dicegah di sini).
     *
     * @throws InsufficientCreditException bila saldo pembeli kurang.
     */
    public function purchaseTemplate(Template $template, User $buyer): TemplateUsageTransaction
    {
        if (! $template->isPublished()) {
            throw new RuntimeException('Template ini tidak tersedia di marketplace.');
        }

        $ownerId = $template->uploaded_by;
        if ($ownerId === null) {
            throw new RuntimeException('Template marketplace tidak memiliki pemilik.');
        }
        if ($ownerId === $buyer->id) {
            throw new RuntimeException('Anda adalah pemilik template ini — gratis dipakai.');
        }

        $price = (int) config('sigital.credit.marketplace_price');
        $ownerShare = (int) config('sigital.credit.marketplace_owner_share');
        $platformShare = (int) config('sigital.credit.marketplace_platform_share');

        return DB::transaction(function () use ($template, $buyer, $ownerId, $price, $ownerShare, $platformShare) {
            $owner = User::query()->findOrFail($ownerId);

            // 1) Potong pembeli (lempar bila kurang → rollback seluruhnya).
            $this->credit->consume($buyer, $price, $template, 'Pakai template marketplace: '.$template->nama, 'memakai template marketplace');

            // 2) Royalti ke pemilik.
            $this->credit->grant($owner, $ownerShare, 'Royalti template: '.$template->nama, $buyer->id, CreditTransaction::TYPE_TEMPLATE_ROYALTY, $template);

            // 3) Histori penggunaan.
            $usage = TemplateUsageTransaction::create([
                'template_id' => $template->id,
                'owner_user_id' => $ownerId,
                'buyer_user_id' => $buyer->id,
                'price_credit' => $price,
                'owner_credit' => $ownerShare,
                'platform_credit' => $platformShare,
            ]);

            // 4) Pendapatan platform (terpisah dari saldo user).
            $this->credit->recordPlatformRevenue($platformShare, $usage, 'Share marketplace: '.$template->nama);

            $this->audit->log('marketplace.template_purchased', $usage, [
                'template_id' => $template->id, 'price' => $price, 'owner' => $ownerShare, 'platform' => $platformShare,
            ], $buyer->id);
            $this->audit->log('marketplace.royalty_granted', $template, ['amount' => $ownerShare], $buyer->id);

            return $usage;
        });
    }
}
