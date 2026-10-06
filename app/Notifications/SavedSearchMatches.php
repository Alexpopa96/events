<?php

namespace App\Notifications;

use App\Models\Listing;
use App\Models\SavedSearch;
use App\Notifications\Concerns\StoresInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class SavedSearchMatches extends Notification implements ShouldQueue
{
    use Queueable, StoresInApp;

    /** @param Collection<int, Listing> $listings */
    public function __construct(private readonly SavedSearch $search, private readonly Collection $listings) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->listings->count();
        $subject = $count === 1
            ? 'Un anunț nou pentru căutarea ta „'.$this->search->name.'”'
            : "{$count} anunțuri noi pentru căutarea ta „{$this->search->name}”";

        $lines = [
            $count === 1
                ? 'A apărut un anunț nou care se potrivește căutării tale salvate.'
                : "Au apărut {$count} anunțuri noi care se potrivesc căutării tale salvate.",
        ];

        foreach ($this->listings->take(5) as $listing) {
            $lines[] = '• '.$listing->title.' — '.$listing->providerProfile->company_name;
        }

        if ($count > 5) {
            $lines[] = '...și încă '.($count - 5).' '.($count - 5 === 1 ? 'anunț' : 'anunțuri').'.';
        }

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $subject,
                'tone' => 'success',
                'badge' => 'Căutare salvată',
                'greeting' => 'Salut, '.$notifiable->name.'!',
                'lines' => $lines,
                'actionText' => 'Vezi anunțurile',
                'actionUrl' => route('listings.index', $this->search->queryParams()),
            ]);
    }
}
