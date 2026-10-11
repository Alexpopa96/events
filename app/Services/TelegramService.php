<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramService
{
    public function sendMessage(string $text, ?string $chatId = null): void
    {
        $token = config('services.telegram.bot_token');

        $response = Http::timeout(10)
            ->retry(1, 500)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId ?? config('services.telegram.chat_id'),
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

        if (! $response->successful() || ! $response->json('ok')) {
            throw new RuntimeException("Telegram sendMessage failed ({$response->status()}): {$response->body()}");
        }
    }
}
