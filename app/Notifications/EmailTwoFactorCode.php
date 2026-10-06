<?php

namespace App\Notifications;

use App\Support\EmailTwoFactor;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailTwoFactorCode extends Notification
{
    public function __construct(
        private readonly string $code,
        private readonly int $validMinutes,
        private readonly string $scope,
        private readonly string $name,
        private readonly ?string $detail = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $badge, $line, $note] = match ($this->scope) {
            EmailTwoFactor::SCOPE_LOGIN => [
                'Codul tău de autentificare',
                'Autentificare în 2 pași',
                'Cineva se conectează la contul tău. Introdu codul de mai jos ca să termini autentificarea.',
                ['title' => 'Nu tu te conectezi?', 'text' => 'Ignoră acest email și schimbă-ți parola cât mai repede — cineva îți știe parola, dar fără cod nu poate intra.'],
            ],
            EmailTwoFactor::SCOPE_DISABLE => [
                'Cod pentru dezactivarea autentificării în 2 pași',
                'Dezactivare 2 pași',
                'Ai cerut dezactivarea autentificării în 2 pași. Introdu codul de mai jos ca să confirmi.',
                ['title' => 'Nu ai cerut asta?', 'text' => 'Ignoră acest email — autentificarea în 2 pași rămâne activă.'],
            ],
            EmailTwoFactor::SCOPE_EMAIL_CHANGE => [
                'Codul tău pentru schimbarea adresei de email',
                'Schimbare email',
                'Ai cerut schimbarea adresei de email a contului'.($this->detail ? " în {$this->detail}" : '').'. Introdu codul de mai jos ca să confirmi.',
                ['title' => 'Nu ai cerut asta?', 'text' => 'Ignoră acest email — adresa contului rămâne neschimbată. Dacă nu tu ai inițiat schimbarea, schimbă-ți parola.'],
            ],
            default => [
                'Codul tău pentru activarea autentificării în 2 pași',
                'Activare 2 pași',
                'Ai cerut activarea autentificării în 2 pași. Introdu codul de mai jos ca să confirmi că această adresă îți aparține.',
                ['title' => 'Nu ai cerut asta?', 'text' => 'Poți ignora acest email — nu se schimbă nimic la contul tău.'],
            ],
        };

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'success',
                'badge' => $badge,
                'greeting' => 'Salut, '.$this->name.'!',
                'preheader' => "Codul tău este {$this->code} și este valabil {$this->validMinutes} minute.",
                'lines' => [$line],
                'code' => $this->code,
                'validMinutes' => $this->validMinutes,
                'footnotes' => [
                    $note,
                    'Din motive de securitate, nu împărtăși codul cu nimeni. Echipa EventHub nu ți-l va cere niciodată.',
                ],
            ]);
    }
}
