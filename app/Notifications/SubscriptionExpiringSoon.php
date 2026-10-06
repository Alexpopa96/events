<?php

namespace App\Notifications;

use App\Models\ProviderSubscription;
use App\Notifications\Concerns\StoresInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringSoon extends Notification implements ShouldQueue
{
    use Queueable, StoresInApp;

    public function __construct(private readonly ProviderSubscription $subscription) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysLeft = now()->diffInDays($this->subscription->ends_at, false);
        $subject = 'Abonamentul tău se reînnoiește curând';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'info',
                'badge' => 'Reînnoire abonament',
                'greeting' => 'Salut, '.$notifiable->name.'!',
                'lines' => [
                    'Planul "'.$this->subscription->plan->name.'" se reînnoiește pe '.$this->subscription->ends_at->format('d.m.Y')
                        .' ('.max(0, $daysLeft).' '.($daysLeft === 1 ? 'zi' : 'zile').' rămase).',
                    'Nu trebuie să faci nimic dacă vrei să continui pe acest plan. Dacă vrei să-l schimbi, o poți face oricând din contul tău.',
                ],
                'actionText' => 'Vezi abonamentul',
                'actionUrl' => route('provider.subscription.index'),
            ]);
    }
}
