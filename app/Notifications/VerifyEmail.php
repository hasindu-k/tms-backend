<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class VerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = 60;

    /**
     * Get the verification notification mail message for the given user.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    protected function verificationUrl($notifiable)
    {
        // Default expiration time (60 minutes)
        $expiration = Carbon::now()->addMinutes(60);

        // Generate the default backend signed route for verification
        $temporarySignedUrl = URL::temporarySignedRoute(
            'verification.verify', // This is the route for verification (don't change it)
            $expiration, // Expiration time for the signed route
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );

        // Replace backend URL with frontend URL
        $frontendUrl = env('APP_FRONTEND_URL', 'http://localhost:5173') . '/email/verify/' . $notifiable->getKey() . '/' . sha1($notifiable->getEmailForVerification());

        // Append query params (expires and signature) from the generated backend URL to the frontend URL
        $queryParams = parse_url($temporarySignedUrl, PHP_URL_QUERY);

        return $frontendUrl . '?' . $queryParams;
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        // Render the custom Blade email view
        return (new MailMessage)
            ->view('emails.verify_email', [
                'user' => $notifiable,
                'verificationUrl' => $verificationUrl
            ])
            ->subject('Verify Your Email Address');
    }
}
