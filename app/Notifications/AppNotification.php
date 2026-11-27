<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $title,
        private string $message,
        private string $url,
        private array $data = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return array_merge($this->data, [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ]);
    }
}
