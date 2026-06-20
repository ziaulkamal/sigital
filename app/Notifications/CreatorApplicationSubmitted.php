<?php

/**
 * app/Notifications/CreatorApplicationSubmitted.php
 * Notifikasi ke SuperAdmin & Admin instansi saat user mendaftar sebagai Marketplace Creator.
 */

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class CreatorApplicationSubmitted extends Notification
{
    public function __construct(private readonly User $applicant) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Pendaftaran Marketplace Creator',
            'message' => "{$this->applicant->name} mendaftar sebagai Marketplace Creator dan menunggu verifikasi.",
            'url' => route('marketplace.admin', absolute: false),
            'icon' => 'sparkles',
        ];
    }
}
