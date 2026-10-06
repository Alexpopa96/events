<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCode extends Notification
{
    public function __construct(private readonly string $code, private readonly int $validMinutes)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Codul tău de resetare a parolei';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'success',
                'badge' => 'Resetare parolă',
                'greeting' => 'Salut, '.$notifiable->name.'!',
                'preheader' => "Codul tău este {$this->code} și este valabil {$this->validMinutes} minute.",
                'lines' => ['Ai cerut resetarea parolei. Introdu codul de mai jos în pagina de recuperare pentru a alege o parolă nouă.'],
                'code' => $this->code,
                'validMinutes' => $this->validMinutes,
                'footnotes' => [
                    ['title' => 'Nu ai cerut resetarea?', 'text' => 'Poți ignora acest email — parola ta rămâne neschimbată.'],
                    'Din motive de securitate, nu împărtăși codul cu nimeni. Echipa EventHub nu ți-l va cere niciodată.',
                ],
            ]);
    }
}
