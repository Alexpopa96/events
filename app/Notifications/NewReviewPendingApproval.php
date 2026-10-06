<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReviewPendingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Review $review)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Recenzie nouă în așteptare de aprobare';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'info',
                'badge' => 'Recenzie nouă',
                'greeting' => 'Recenzie nouă de moderat',
                'lines' => [
                    $this->review->user->name.' a lăsat '.$this->review->rating.' din 5 stele pentru "'.$this->review->listing->title.'".',
                    'Recenzia nu este publică până nu o aprobi.',
                ],
                'actionText' => 'Moderează recenzia',
                'actionUrl' => route('administration.reviews.index', ['status' => 'pending']),
            ]);
    }
}
