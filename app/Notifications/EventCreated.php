<?php

/**
 * app/Notifications/EventCreated.php
 * Notifikasi ke SuperAdmin saat user membuat acara baru.
 */

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Notifications\Notification;

class EventCreated extends Notification
{
    public function __construct(private readonly Event $event, private readonly string $creatorName) {}

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Acara baru dibuat',
            'message' => "{$this->creatorName} membuat acara \"{$this->event->nama}\".",
            'url' => "/events/{$this->event->id}",
            'icon' => 'calendar',
        ];
    }
}
