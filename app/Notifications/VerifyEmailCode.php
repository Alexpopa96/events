<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailCode extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly int $validForMinutes,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Codul tău de verificare: '.$this->code;

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.verification-code', [
                'subject' => $subject,
                'name' => $notifiable->name,
                'code' => $this->code,
                'validForMinutes' => $this->validForMinutes,
            ]);
    }
}
