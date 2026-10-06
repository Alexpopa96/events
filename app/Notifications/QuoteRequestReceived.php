<?php

namespace App\Notifications;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteRequestReceived extends Notification implements ShouldQueue
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
        $subject = 'Am primit cererea ta';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'info',
                'badge' => 'Cerere trimisă',
                'greeting' => 'Salut, '.$this->quoteRequest->name.'!',
                'lines' => [
                    'Ai trimis cererea de ofertă "'.$this->quoteRequest->title.'". Echipa noastră o va verifica în cel mai scurt timp.',
                    'Îți vom trimite un email imediat ce cererea este aprobată și devine vizibilă furnizorilor.',
                ],
                'actionText' => 'Vezi cererile mele',
                'actionUrl' => route('quote-requests.index'),
            ]);
    }
}
