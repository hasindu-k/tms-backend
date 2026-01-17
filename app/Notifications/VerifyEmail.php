<?php

namespace App\Notifications;

use App\Notifications\Channels\BrevoChannel;
use App\Notifications\Contracts\BrevoNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Notification implements BrevoNotification
{
    // use Queueable;

    public $tries = 3;
    public $backoff = 60;

    public function via(object $notifiable): array
    {
        return [BrevoChannel::class];
    }

    protected function verificationUrl($notifiable): string
    {
        $expiration = Carbon::now()->addMinutes(
            config('auth.verification.expire', 60)
        );

        $temporarySignedUrl = URL::temporarySignedRoute(
            'verification.verify',
            $expiration,
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        $frontendUrl =
            config('app.frontend_url', 'http://localhost:5173') .
            '/email/verify/' .
            $notifiable->getKey() .
            '/' .
            sha1($notifiable->getEmailForVerification());

        return $frontendUrl . '?' . parse_url($temporarySignedUrl, PHP_URL_QUERY);
    }

    public function toBrevo(object $notifiable): array
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        Log::info('Generated verification URL for user ID: ' . $notifiable->id);
        Log::info('Verification URL: ' . $verificationUrl);

        return [
            'to'      => $notifiable->getEmailForVerification(),
            'subject' => 'Verify Your Email Address',
            'html'    => view('emails.verify_email', [
                'user' => $notifiable,
                'verificationUrl' => $verificationUrl,
            ])->render(),
        ];
    }
}
