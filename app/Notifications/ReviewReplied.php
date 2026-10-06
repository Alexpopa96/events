<?php

namespace App\Notifications;

use App\Models\Review;
use App\Notifications\Concerns\StoresInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ReviewReplied extends Notification implements ShouldQueue
{
    use Queueable, StoresInApp;

    public function __construct(private readonly Review $review) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', WebPushChannel::class];
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->review->providerProfile->company_name.' ți-a răspuns la recenzie')
            ->body('Pentru anunțul "'.$this->review->listing->title.'"')
            ->data(['url' => route('listings.show', $this->review->listing->slug).'#recenzii']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->review->providerProfile->company_name.' ți-a răspuns la recenzie';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'info',
                'badge' => 'Răspuns la recenzie',
                'greeting' => 'Salut, '.$notifiable->name.'!',
                'lines' => [
                    $this->review->providerProfile->company_name.' a răspuns la recenzia ta pentru "'.$this->review->listing->title.'".',
                ],
                'actionText' => 'Vezi răspunsul',
                'actionUrl' => route('listings.show', $this->review->listing->slug).'#recenzii',
            ]);
    }
}
