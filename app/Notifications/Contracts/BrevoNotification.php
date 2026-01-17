<?php

namespace App\Notifications\Contracts;

interface BrevoNotification
{
    /**
     * Return Brevo email payload.
     */
    public function toBrevo(object $notifiable): array;
}
