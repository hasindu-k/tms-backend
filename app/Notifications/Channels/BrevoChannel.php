<?php

namespace App\Notifications\Channels;

use App\Services\BrevoEmailService;
use App\Notifications\Contracts\BrevoNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class BrevoChannel
{
    public function __construct(
        protected BrevoEmailService $brevo
    ) {}

    public function send($notifiable, Notification $notification): void
    {
        Log::info('BrevoChannel send method called for notifiable ID: ' . $notifiable->id);
        if (!$notification instanceof BrevoNotification) {
            return;
        }

        $data = $notification->toBrevo($notifiable);

        $this->brevo->send(
            $data['to'],
            $data['subject'],
            $data['html']
        );
    }
}
