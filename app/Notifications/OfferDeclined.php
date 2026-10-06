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

class OfferDeclined extends Notification implements ShouldQueue
{
    use Queueable, StoresInApp;

    /** @param bool $chosenAnother the client accepted a different offer, rather than refusing this one */
    public function __construct(private readonly Offer $offer, private readonly bool $chosenAnother = false) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', WebPushChannel::class];
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        $title = $this->offer->quoteRequest->title;

        return (new WebPushMessage)
            ->title($this->chosenAnother ? 'Clientul a ales altă ofertă' : 'Oferta ta a fost refuzată')
            ->body('Pentru "'.$title.'"')
            ->data(['url' => route('provider.offers.index')]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->chosenAnother ? 'Clientul a ales altă ofertă' : 'Oferta ta a fost refuzată';
        $title = $this->offer->quoteRequest->title;

        $lines = [$this->chosenAnother
            ? 'Pentru "'.$title.'", clientul a ales o altă ofertă. Cererea s-a închis.'
            : 'Clientul a refuzat oferta ta pentru "'.$title.'".'];

        if (! $this->chosenAnother && filled($this->offer->decline_reason)) {
            $lines[] = 'Motiv: '.$this->offer->decline_reason;
        }

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'info',
                'badge' => 'Ofertă refuzată',
                'greeting' => 'Salut, '.$notifiable->name.'!',
                'lines' => $lines,
                'actionText' => 'Vezi ofertele mele',
                'actionUrl' => route('provider.offers.index'),
            ]);
    }
}
