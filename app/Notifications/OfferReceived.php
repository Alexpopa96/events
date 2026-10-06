<?php

namespace App\Notifications;

use App\Models\Offer;
use App\Notifications\Concerns\StoresInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class OfferReceived extends Notification implements ShouldQueue
{
    use Queueable, StoresInApp;

    public function __construct(private readonly Offer $offer, private readonly bool $updated = false) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', WebPushChannel::class];
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        $company = $this->offer->providerProfile->company_name;

        return (new WebPushMessage)
            ->title($this->updated ? "$company și-a actualizat oferta" : 'Ai primit o ofertă nouă')
            ->body("$company: ".number_format($this->offer->price, 0, ',', '.').' lei pentru "'.$this->offer->quoteRequest->title.'"')
            ->data(['url' => route('quote-requests.show', $this->offer->quote_request_id).'#oferte']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $company = $this->offer->providerProfile->company_name;
        $subject = $this->updated ? "$company și-a actualizat oferta" : 'Ai primit o ofertă nouă';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'success',
                'badge' => $this->updated ? 'Ofertă actualizată' : 'Ofertă nouă',
                'greeting' => 'Salut, '.$notifiable->name.'!',
                'lines' => [
                    "$company ți-a trimis o ofertă de ".number_format($this->offer->price, 0, ',', '.').' lei pentru "'.$this->offer->quoteRequest->title.'".',
                    'Oferta este valabilă până la '.$this->offer->valid_until->format('d.m.Y').'. O poți compara cu celelalte și o poți accepta direct din cont.',
                ],
                'actionText' => 'Vezi oferta',
                'actionUrl' => route('quote-requests.show', $this->offer->quote_request_id).'#oferte',
            ]);
    }
}
