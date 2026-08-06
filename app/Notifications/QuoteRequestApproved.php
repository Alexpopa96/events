<?php

namespace App\Notifications;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteRequestApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly QuoteRequest $quoteRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Cererea ta a fost aprobată';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'success',
                'badge' => 'Cerere aprobată',
                'greeting' => 'Vești bune, '.$notifiable->name.'!',
                'lines' => [
                    'Cererea ta "'.$this->quoteRequest->title.'" a fost aprobată și este acum vizibilă furnizorilor.',
                    'Vei primi oferte direct de la furnizorii interesați, pe email sau telefon.',
                ],
                'actionText' => 'Vezi cererile mele',
                'actionUrl' => route('quote-requests.index'),
            ]);
    }
}
