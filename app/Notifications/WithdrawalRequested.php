<?php

/**
 * app/Notifications/WithdrawalRequested.php
 * Notifikasi ke SuperAdmin & Admin instansi saat Creator mengajukan pencairan royalti.
 */

namespace App\Notifications;

use App\Models\MarketplaceWithdrawal;
use Illuminate\Notifications\Notification;

class WithdrawalRequested extends Notification
{
    public function __construct(
        private readonly MarketplaceWithdrawal $withdrawal,
        private readonly string $creatorName,
    ) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Permintaan pencairan royalti',
            'message' => "{$this->creatorName} mengajukan pencairan {$this->withdrawal->credit_paid} credit (Rp".number_format($this->withdrawal->rupiah_paid, 0, ',', '.').').',
            'url' => route('marketplace.admin', absolute: false),
            'icon' => 'wallet',
        ];
    }
}
