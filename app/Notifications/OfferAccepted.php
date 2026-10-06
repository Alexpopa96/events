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

class OfferAccepted extends Notification implements ShouldQueue
{
    use Queueable, StoresInApp;

    public function __construct(private readonly Offer $offer) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', WebPushChannel::class];
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        $request = $this->offer->quoteRequest;

        return (new WebPushMessage)
            ->title('Oferta ta a fost acceptată')
            ->body($request->name.' a acceptat oferta ta pentru "'.$request->title.'"')
            ->data(['url' => route('provider.offers.index')]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Oferta ta a fost acceptată';
        $request = $this->offer->quoteRequest;

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'success',
                'badge' => 'Ofertă acceptată',
                'greeting' => 'Felicitări, '.$notifiable->name.'!',
                'lines' => [
                    $request->name.' a acceptat oferta ta de '.number_format($this->offer->price, 0, ',', '.').' lei pentru "'.$request->title.'".',
                    'Contactează clientul cât mai repede ca să stabiliți detaliile.'.($request->phone ? ' Telefon: '.$request->phone.'.' : ''),
                ],
                'actionText' => 'Vezi oferta',
                'actionUrl' => route('provider.offers.index'),
            ]);
    }
}
