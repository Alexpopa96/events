<?php

namespace App\Notifications;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Sent once for a package submission (several service categories for the same
 * event), instead of one QuoteRequestReceived per category — so the client
 * doesn't get a burst of near-identical emails.
 */
class QuoteRequestPackageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param Collection<int, QuoteRequest> $quoteRequests */
    public function __construct(private readonly Collection $quoteRequests) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $first = $this->quoteRequests->first();
        $subject = 'Am primit cererile tale';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'info',
                'badge' => 'Pachet trimis',
                'greeting' => 'Salut, '.$first->name.'!',
                'lines' => [
                    'Ai trimis cererea "'.$first->title.'" pentru '.$this->quoteRequests->count().' categorii de servicii: '
                        .$this->quoteRequests->pluck('category.name')->filter()->implode(', ').'.',
                    'Echipa noastră le va verifica în cel mai scurt timp. Îți vom trimite un email pentru fiecare, imediat ce devine vizibilă furnizorilor.',
                ],
                'actionText' => 'Vezi cererile mele',
                'actionUrl' => route('quote-requests.index'),
            ]);
    }
}
