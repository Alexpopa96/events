<?php

namespace App\Notifications;

use App\Models\ProviderProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProviderApproved extends Notification implements ShouldQueue
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
        $subject = 'Firma ta a fost aprobată';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'success',
                'badge' => 'Cont aprobat',
                'greeting' => 'Vești bune, '.$notifiable->name.'!',
                'lines' => [
                    'Firma "'.$this->profile->company_name.'" a fost validată și contul tău de furnizor este acum activ.',
                    'Poți publica anunțuri și primi cereri de ofertă de la clienți.',
                ],
                'actionText' => 'Accesează dashboard-ul',
                'actionUrl' => route('provider.dashboard'),
            ]);
    }
}
