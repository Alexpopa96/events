<?php

namespace App\Notifications;

use App\Models\ProviderProfile;
use App\Notifications\Concerns\StoresInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewProviderPendingApproval extends Notification implements ShouldQueue
{
    use Queueable, StoresInApp;

    public function __construct(private readonly ProviderProfile $profile)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Furnizor nou în așteptare de aprobare';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'info',
                'badge' => 'Firmă nouă',
                'greeting' => 'Salut, '.$notifiable->name.'!',
                'lines' => [
                    'Firma "'.$this->profile->company_name.'" (CUI '.$this->profile->cui.') s-a înregistrat și așteaptă validare.',
                    'Poți aproba sau respinge cererea din panoul de administrare.',
                ],
                'actionText' => 'Vezi furnizorii în așteptare',
                'actionUrl' => route('administration.providers.index'),
            ]);
    }
}
