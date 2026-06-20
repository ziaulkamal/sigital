<?php

/**
 * app/Notifications/TopupReviewed.php
 * Notifikasi ke user saat permintaan topup-nya disetujui / ditolak SuperAdmin.
 */

namespace App\Notifications;

use App\Models\TopupRequest;
use Illuminate\Notifications\Notification;

class TopupReviewed extends Notification
{
    public function __construct(private readonly TopupRequest $request) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        $approved = $this->request->status === TopupRequest::STATUS_APPROVED;

        return [
            'title' => $approved ? 'Topup disetujui' : 'Topup ditolak',
            'message' => $approved
                ? "Topup {$this->request->amount_credit} credit Anda disetujui dan saldo telah ditambahkan."
                : "Topup {$this->request->amount_credit} credit Anda ditolak. Alasan: {$this->request->reject_reason}",
            'url' => route('credits.index', absolute: false),
            'icon' => $approved ? 'check' : 'x',
        ];
    }
}
