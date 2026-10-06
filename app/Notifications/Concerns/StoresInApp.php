<?php

namespace App\Notifications\Concerns;

/**
 * Mirrors the mail content of a notification into the in-app (database) channel,
 * so the email and the bell entry can never drift apart.
 */
trait StoresInApp
{
    public function toDatabase(object $notifiable): array
    {
        $data = $this->toMail($notifiable)->viewData;

        return [
            'title' => $data['badge'] ?? $data['subject'],
            'subject' => $data['subject'],
            'body' => $data['lines'][0] ?? null,
            'tone' => $data['tone'] ?? 'info',
            'url' => $data['actionUrl'] ?? null,
        ];
    }
}
