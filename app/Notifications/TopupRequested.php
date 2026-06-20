<?php

/**
 * app/Notifications/TopupRequested.php
 * Notifikasi ke SuperAdmin saat user mengajukan topup credit.
 */

namespace App\Notifications;

use App\Models\TopupRequest;
use Illuminate\Notifications\Notification;

class TopupRequested extends Notification
{
    public function __construct(
        private readonly TopupRequest $request,
        private readonly string $userName,
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
            'title' => 'Permintaan topup credit',
            'message' => "{$this->userName} mengajukan topup {$this->request->amount_credit} credit (Rp".number_format($this->request->amount_rupiah, 0, ',', '.').').',
            'url' => route('credits.requests', absolute: false),
            'icon' => 'wallet',
        ];
    }
}
