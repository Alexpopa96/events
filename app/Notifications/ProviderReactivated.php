<?php

namespace App\Notifications;

use App\Models\ProviderProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProviderReactivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ProviderProfile $profile)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Contul tău de furnizor a fost reactivat';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'success',
                'badge' => 'Cont reactivat',
                'greeting' => 'Vești bune, '.$notifiable->name.'!',
                'lines' => [
                    'Contul firmei "'.$this->profile->company_name.'" a fost reactivat și este din nou vizibil public.',
                    'Poți publica anunțuri și primi cereri de ofertă de la clienți.',
                ],
                'actionText' => 'Accesează dashboard-ul',
                'actionUrl' => route('provider.dashboard'),
            ]);
    }
}
