<?php

/**
 * app/Notifications/BankAccountSubmitted.php
 * Notifikasi ke SuperAdmin saat Creator mengirim/mengubah rekening pencairan.
 */

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class BankAccountSubmitted extends Notification
{
    public function __construct(private readonly User $creator) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Rekening pencairan menunggu verifikasi',
            'message' => "{$this->creator->name} mengirim rekening pencairan untuk diverifikasi.",
            'url' => route('marketplace.admin', absolute: false),
            'icon' => 'wallet',
        ];
    }
}
