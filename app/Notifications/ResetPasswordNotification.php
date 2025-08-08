<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = 60;
    public function toMail($notifiable)
    {
        $resetUrl = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->view('emails.password_reset', [
                'user' => $notifiable,
                'resetUrl' => $resetUrl
            ])
            ->subject('Reset Your Password');
    }

    protected function resetUrl($notifiable)
    {
        return env('FRONTEND_URL', 'http://localhost:5173') . '/password-reset/' . $this->token;
    }
}
