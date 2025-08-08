<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $task;
    protected $comment;

    public function __construct($task, $comment)
    {
        $this->task = $task;
        $this->comment = $comment;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Comment Added')
            ->view('emails.comment_added', [
                'task' => $this->task,
                'comment' => $this->comment,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}
