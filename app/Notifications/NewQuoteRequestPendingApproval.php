<?php

namespace App\Notifications;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewQuoteRequestPendingApproval extends Notification implements ShouldQueue
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
        $subject = 'Cerere de ofertă nouă în așteptare de aprobare';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'info',
                'badge' => 'Cerere nouă',
                'greeting' => 'Cerere nouă de aprobat',
                'lines' => [
                    'A fost publicată cererea de ofertă "'.$this->quoteRequest->title.'" de către '.$this->quoteRequest->name.' ('.$this->quoteRequest->email.').',
                    'Cererea așteaptă aprobare și nu este vizibilă furnizorilor până nu o aprobi.',
                ],
                'actionText' => 'Vezi cererea',
                'actionUrl' => route('administration.quote-requests.show', $this->quoteRequest),
            ]);
    }
}
