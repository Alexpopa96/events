<?php

namespace App\Notifications;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteRequestRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly QuoteRequest $quoteRequest,
        private readonly ?string $reason = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Cererea ta nu a putut fi aprobată';

        $lines = [
            'Ne pare rău, dar cererea ta "'.$this->quoteRequest->title.'" nu a putut fi validată de echipa noastră.',
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
