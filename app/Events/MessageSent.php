<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast the instant a message is sent (not queued — a few seconds' delay
 * would defeat the point of "real-time" chat). Goes out on two kinds of
 * channel: the conversation itself (for whoever has the thread open) and
 * each participant's personal channel (so their inbox list/unread badge
 * updates live even when they're not looking at this specific thread).
 */
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Message $message) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $conversation = $this->message->conversation;

        return [
            new PrivateChannel('conversation.'.$conversation->id),
            new PrivateChannel('App.Models.User.'.$conversation->client_id),
            new PrivateChannel('App.Models.User.'.$conversation->providerProfile->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'body' => $this->message->body,
            'time' => $this->message->created_at->format('H:i'),
            'day' => $this->message->created_at->translatedFormat('j F Y'),
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
