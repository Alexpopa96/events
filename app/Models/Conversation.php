<?php

namespace App\Models;

use App\Events\ConversationRead;
use App\Events\MessageSent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    public const SIDE_CLIENT = 'client';

    public const SIDE_PROVIDER = 'provider';

    protected $fillable = [
        'listing_id',
        'client_id',
        'provider_profile_id',
        'last_message_at',
        'client_last_read_id',
        'provider_last_read_id',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Which side of the thread this user is on, or null when they are not a participant.
     */
    public function sideFor(User $user): ?string
    {
        if ($this->client_id === $user->id) {
            return self::SIDE_CLIENT;
        }

        if ($user->providerProfile && $this->provider_profile_id === $user->providerProfile->id) {
            return self::SIDE_PROVIDER;
        }

        return null;
    }

    public function markReadBy(string $side): void
    {
        $lastReadId = (int) $this->messages()->max('id');

        $this->forceFill(["{$side}_last_read_id" => $lastReadId])->save();

        if ($lastReadId > 0) {
            broadcast(new ConversationRead($this, $side, $lastReadId))->toOthers();
        }
    }

    /**
     * Record a new message and mark the thread as read up to it for the sender.
     */
    public function addMessage(User $sender, string $body, string $side): Message
    {
        $message = $this->messages()->create(['sender_id' => $sender->id, 'body' => trim($body)]);

        $this->forceFill(['last_message_at' => now(), "{$side}_last_read_id" => $message->id])->save();

        broadcast(new MessageSent($message->setRelation('conversation', $this->loadMissing('providerProfile'))))->toOthers();

        return $message;
    }

    /**
     * Adds `unread_count`: messages from the other side newer than this side's read marker.
     */
    public function scopeWithUnreadFor($query, string $side, User $user)
    {
        return $query->withCount(['messages as unread_count' => fn ($messages) => $messages
            ->where('messages.sender_id', '!=', $user->id)
            ->whereColumn('messages.id', '>', "conversations.{$side}_last_read_id"),
        ]);
    }
}
