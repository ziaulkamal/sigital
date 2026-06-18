<?php

/**
 * app/Notifications/AccountDecision.php
 * Notifikasi ke user atas keputusan persetujuan akun (approved/rejected) oleh SuperAdmin.
 */

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class AccountDecision extends Notification
{
    public function __construct(
        private readonly bool $approved,
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
        return $this->approved
            ? [
                'title' => 'Akun disetujui',
                'message' => 'Akun Anda telah disetujui. Selamat menggunakan aplikasi.',
                'url' => '/dashboard',
                'icon' => 'check',
            ]
            : [
                'title' => 'Pendaftaran ditolak',
                'message' => 'Pendaftaran Anda belum disetujui.'.($this->reason ? " Alasan: {$this->reason}" : ''),
                'url' => null,
                'icon' => 'x',
            ];
    }
}
