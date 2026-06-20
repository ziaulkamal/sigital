<?php

/**
 * app/Notifications/BankAccountReviewed.php
 * Notifikasi ke Creator saat rekening pencairannya diverifikasi / ditolak SuperAdmin.
 */

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class BankAccountReviewed extends Notification
{
    public function __construct(
        private readonly bool $verified,
        private readonly ?string $reason = null,
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
            'title' => $this->verified ? 'Rekening terverifikasi' : 'Rekening ditolak',
            'message' => $this->verified
                ? 'Rekening pencairan Anda terverifikasi. Anda kini dapat publish template & mengajukan pencairan.'
                : "Rekening pencairan Anda ditolak. Alasan: {$this->reason}",
            'url' => route('marketplace.creator', absolute: false),
            'icon' => $this->verified ? 'check' : 'x',
        ];
    }
}
