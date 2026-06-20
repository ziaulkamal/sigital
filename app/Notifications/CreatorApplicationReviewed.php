<?php

/**
 * app/Notifications/CreatorApplicationReviewed.php
 * Notifikasi ke user saat pendaftaran Creator-nya disetujui / ditolak SuperAdmin.
 */

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CreatorApplicationReviewed extends Notification
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
        return [
            'title' => $this->approved ? 'Pendaftaran Creator disetujui' : 'Pendaftaran Creator ditolak',
            'message' => $this->approved
                ? 'Selamat! Anda kini Marketplace Creator. Lengkapi rekening untuk mengaktifkan pencairan.'
                : "Pendaftaran Creator Anda ditolak. Alasan: {$this->reason}",
            'url' => route('marketplace.creator', absolute: false),
            'icon' => $this->approved ? 'check' : 'x',
        ];
    }
}
