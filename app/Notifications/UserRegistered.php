<?php

/**
 * app/Notifications/UserRegistered.php
 * Notifikasi ke SuperAdmin saat ada pendaftar baru menunggu persetujuan.
 */

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class UserRegistered extends Notification
{
    public function __construct(private readonly User $registrant) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Pendaftar baru',
            'message' => "{$this->registrant->name} mendaftar dan menunggu persetujuan.",
            'url' => '/approvals',
            'icon' => 'user-plus',
        ];
    }
}
