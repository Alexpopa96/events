<?php

namespace App\Notifications;

use App\Models\ProviderProfile;
use App\Notifications\Concerns\StoresInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProviderRegistrationReceived extends Notification implements ShouldQueue
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
        $subject = 'Am primit cererea ta de înregistrare';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'success',
                'badge' => 'Cerere primită',
                'greeting' => 'Salut, '.$notifiable->name.'!',
                'lines' => [
                    'Cererea de înregistrare pentru firma "'.$this->profile->company_name.'" a fost trimisă cu succes.',
                    'Contul tău este în curs de validare de către echipa noastră. Vei primi un email imediat ce firma ta este aprobată.',
                    'Îți mulțumim pentru răbdare!',
                ],
            ]);
    }
}
