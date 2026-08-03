<?php

namespace App\Notifications;

use App\Models\ProviderProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProviderRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ProviderProfile $profile,
        private readonly ?string $reason = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Cererea ta de înregistrare nu a putut fi aprobată';

        $lines = [
            'Ne pare rău, dar cererea de înregistrare pentru firma "'.$this->profile->company_name.'" nu a putut fi validată.',
        ];

        if ($this->reason) {
            $lines[] = 'Motiv: '.$this->reason;
        }

        $lines[] = 'Dacă crezi că este o eroare, te rugăm să ne contactezi pentru mai multe detalii.';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'danger',
                'badge' => 'Cerere respinsă',
                'greeting' => 'Salut, '.$notifiable->name.'!',
                'lines' => $lines,
            ]);
    }
}
