<?php

/**
 * app/Notifications/EventJoinRequested.php
 * Notifikasi ke pemilik acara saat ada user minta bergabung (P7/K10).
 */

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Notifications\Notification;

class EventJoinRequested extends Notification
{
    public function __construct(private readonly Event $event, private readonly string $requesterName) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Permintaan gabung acara',
            'message' => "{$this->requesterName} ingin bergabung ke \"{$this->event->nama}\".",
            'url' => "/events/{$this->event->id}",
            'icon' => 'user-plus',
        ];
    }
}
