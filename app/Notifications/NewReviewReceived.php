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

class NewReviewReceived extends Notification implements ShouldQueue
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
            ->title('Ai primit o recenzie nouă')
            ->body($this->review->user->name.' a lăsat '.$this->review->rating.' din 5 stele pentru "'.$this->review->listing->title.'"')
            ->data(['url' => route('provider.reviews.index')]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Ai primit o recenzie nouă';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'success',
                'badge' => 'Recenzie nouă',
                'greeting' => 'Salut, '.$notifiable->name.'!',
                'lines' => [
                    $this->review->user->name.' a lăsat '.$this->review->rating.' din 5 stele pentru "'.$this->review->listing->title.'".',
                    'Un răspuns amabil arată viitorilor clienți că îți pasă. Îl poți scrie direct din panoul tău.',
                ],
                'actionText' => 'Răspunde la recenzie',
                'actionUrl' => route('provider.reviews.index'),
            ]);
    }
}
