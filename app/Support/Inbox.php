<?php

namespace App\Support;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

/**
 * Builds the props for the two-pane message inbox, shared by the client
 * account and the provider area so both sides see the same thread shape.
 */
class Inbox
{
    public static function sideOf(User $user): string
    {
        return $user->providerProfile ? Conversation::SIDE_PROVIDER : Conversation::SIDE_CLIENT;
    }

    public static function unreadCount(User $user): int
    {
        $side = self::sideOf($user);
        $column = "conversations.{$side}_last_read_id";

        return Message::query()
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('messages.sender_id', '!=', $user->id)
            ->when(
                $side === Conversation::SIDE_PROVIDER,
                fn ($q) => $q->where('conversations.provider_profile_id', $user->providerProfile->id),
                fn ($q) => $q->where('conversations.client_id', $user->id),
            )
            ->whereColumn('messages.id', '>', $column)
            ->count();
    }

    /**
     * @return array{conversations: array, active: ?array, listings: array}
     */
    public static function props(User $user, ?Conversation $active = null, ?int $listingFilter = null): array
    {
        $side = self::sideOf($user);

        $query = Conversation::query()
            ->with([
                'listing:id,title,slug',
                'listing.media' => fn ($q) => $q->orderByDesc('is_cover')->orderBy('position')->limit(1),
                'client:id,name',
                'providerProfile:id,company_name,logo_path',
                'latestMessage',
            ])
            ->withUnreadFor($side, $user)
            ->whereNotNull('last_message_at')
            ->orderByDesc('last_message_at');

        $side === Conversation::SIDE_PROVIDER
            ? $query->where('provider_profile_id', $user->providerProfile->id)
            : $query->where('client_id', $user->id);

        $all = $query->get();

        $listings = $all->pluck('listing')->unique('id')->map(fn ($l) => ['id' => $l->id, 'title' => $l->title])->values()->all();

        $rows = $listingFilter ? $all->where('listing_id', $listingFilter) : $all;

        return [
            'conversations' => $rows->map(fn (Conversation $c) => self::summary($c, $user, $side))->values()->all(),
            'active' => $active ? self::detail($active, $user, $side) : null,
            'listings' => $listings,
        ];
    }

    private static function counterpart(Conversation $c, string $side): array
    {
        return $side === Conversation::SIDE_PROVIDER
            ? ['name' => $c->client->name, 'logo_url' => null]
            : ['name' => $c->providerProfile->company_name, 'logo_url' => $c->providerProfile->logoUrl()];
    }

    private static function summary(Conversation $c, User $user, string $side): array
    {
        $cover = $c->listing->media->first();

        return [
            'id' => $c->id,
            'listing' => [
                'id' => $c->listing->id,
                'title' => $c->listing->title,
                'slug' => $c->listing->slug,
                'cover_url' => $cover ? "/storage/{$cover->path}" : null,
            ],
            'counterpart' => self::counterpart($c, $side),
            'last_message' => $c->latestMessage ? [
                'body' => $c->latestMessage->body,
                'mine' => $c->latestMessage->sender_id === $user->id,
            ] : null,
            'last_message_at' => $c->last_message_at?->diffForHumans(short: true),
            'unread' => (int) ($c->unread_count ?? 0),
        ];
    }

    private static function detail(Conversation $c, User $user, string $side): array
    {
        $c->loadMissing(['listing:id,title,slug,status', 'client:id,name', 'providerProfile:id,company_name,logo_path,slug']);

        $counterpartSide = $side === Conversation::SIDE_PROVIDER ? Conversation::SIDE_CLIENT : Conversation::SIDE_PROVIDER;

        return [
            'id' => $c->id,
            'listing' => [
                'id' => $c->listing->id,
                'title' => $c->listing->title,
                'slug' => $c->listing->slug,
                'published' => $c->listing->status === 'published',
            ],
            'counterpart' => self::counterpart($c, $side),
            'messages' => self::messages($c, $user),
            // So the UI can mark "my" messages as seen once the other side's read
            // marker passes them — kept live afterwards by the ConversationRead broadcast.
            'counterpart_last_read_id' => (int) $c->{"{$counterpartSide}_last_read_id"},
        ];
    }

    public static function messages(Conversation $c, User $user): array
    {
        return $c->messages()->get()->map(fn (Message $m) => [
            'id' => $m->id,
            'body' => $m->body,
            'mine' => $m->sender_id === $user->id,
            'time' => $m->created_at->format('H:i'),
            'day' => $m->created_at->translatedFormat('j F Y'),
        ])->all();
    }
}
