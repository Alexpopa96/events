<?php

namespace App\Http\Controllers\Provider\Leads;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Offer;
use App\Models\QuoteRequest;
use App\Support\Inbox;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(Request $request): Response
    {
        $profile = $request->user()->providerProfile;
        $categoryIds = $profile->listings()->distinct()->pluck('category_id');

        // Resolved before the conversation summaries below, so opening the chat tab
        // (which marks the thread read) is already reflected in the unread counts.
        $chat = $this->chat($request, $profile, $categoryIds);

        $contactedIds = $profile->events()
            ->where('type', 'quote_request_view')
            ->pluck('quote_request_id');

        // Published listings the provider can start a thread from, grouped by category.
        $listingsByCategory = $profile->listings()
            ->where('status', 'published')
            ->get(['id', 'title', 'category_id'])
            ->groupBy('category_id');

        $open = QuoteRequest::whereIn('category_id', $categoryIds)
            ->where('status', 'open')
            ->with('category')
            ->latest()
            ->get();

        $threads = $this->threadsByClient($request, $profile, $open->pluck('user_id')->filter()->unique());

        $offers = Offer::where('provider_profile_id', $profile->id)
            ->whereIn('quote_request_id', $open->pluck('id'))
            ->get()
            ->keyBy('quote_request_id');

        $leads = $open
            ->map(fn (QuoteRequest $lead) => [
                'id' => $lead->id,
                'thread' => $threads->get($lead->user_id),
                'has_account' => (bool) $lead->user_id,
                'message_listings' => $lead->user_id
                    ? $listingsByCategory->get($lead->category_id, collect())->map(fn ($l) => ['id' => $l->id, 'title' => $l->title])->values()
                    : [],
                'offer_listings' => $listingsByCategory->get($lead->category_id, collect())->map(fn ($l) => ['id' => $l->id, 'title' => $l->title])->values(),
                'offer' => $this->offerFor($offers->get($lead->id)),
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'category' => $lead->category->name,
                'event_date' => optional($lead->event_date)->format('d.m.Y'),
                'event_date_iso' => optional($lead->event_date)->toDateString(),
                'city' => $lead->city,
                'county' => $lead->county,
                'budget_range' => $lead->budget_range,
                'message' => $lead->message,
                'created_at' => $lead->created_at->diffForHumans(),
                // Contacted by phone/email (marked), or by having written to them in the app.
                'contacted' => $contactedIds->contains($lead->id) || ($threads->get($lead->user_id)['replied'] ?? false),
            ]);

        return Inertia::render('Provider/Leads/Index', [
            'leads' => $leads,
            'hasCategories' => $categoryIds->isNotEmpty(),
            // View state restored from the URL, so a refresh lands on the same request/tab/filter.
            'selected' => $request->integer('cerere') ?: null,
            'tab' => in_array($request->query('tab'), ['call', 'email', 'chat', 'offer'], true) ? $request->query('tab') : 'call',
            'filter' => in_array($request->query('filtru'), ['new', 'contacted'], true) ? $request->query('filtru') : 'all',
            'q' => Str::limit(trim((string) $request->query('q')), 100, ''),
            // Only built for the chat tab (?tab=chat&cerere=<lead id>), so plain visits stay cheap.
            'chat' => $chat,
        ]);
    }

    private function offerFor(?Offer $offer): ?array
    {
        if (! $offer) {
            return null;
        }

        return [
            'id' => $offer->id,
            'status' => $offer->effectiveStatus(),
            'price' => $offer->price,
            'includes' => $offer->includes ?? [],
            'message' => $offer->message,
            'listing_id' => $offer->listing_id,
            'valid_until' => $offer->valid_until->toDateString(),
            'editable' => in_array($offer->status, Offer::OPEN_STATUSES, true),
        ];
    }

    /**
     * Per client: unread messages and who spoke last, across the provider's conversations with them.
     * Drives the status badges on the leads list.
     */
    private function threadsByClient(Request $request, $profile, $clientIds)
    {
        if ($clientIds->isEmpty()) {
            return collect();
        }

        return Conversation::with('latestMessage')
            ->withUnreadFor(Conversation::SIDE_PROVIDER, $request->user())
            ->withExists(['messages as replied' => fn ($messages) => $messages->where('messages.sender_id', $request->user()->id)])
            ->where('provider_profile_id', $profile->id)
            ->whereIn('client_id', $clientIds)
            ->whereNotNull('last_message_at')
            ->get()
            ->groupBy('client_id')
            ->map(function ($conversations) use ($request) {
                $latest = $conversations->sortByDesc('last_message_at')->first();

                return [
                    'replied' => $conversations->contains('replied', true),
                    'unread' => (int) $conversations->sum('unread_count'),
                    'last_from' => $latest->latestMessage?->sender_id === $request->user()->id ? 'me' : 'client',
                    'last_at' => $latest->last_message_at->diffForHumans(short: true),
                ];
            });
    }

    /**
     * The provider's latest conversation with the client behind a lead, marked as read.
     */
    private function chat(Request $request, $profile, $categoryIds): ?array
    {
        $leadId = $request->integer('cerere');

        if (! $leadId || $request->query('tab') !== 'chat') {
            return null;
        }

        $lead = QuoteRequest::whereIn('category_id', $categoryIds)
            ->where('status', 'open')
            ->whereNotNull('user_id')
            ->find($leadId);

        if (! $lead) {
            return null;
        }

        $conversation = Conversation::with('listing:id,title')
            ->where('provider_profile_id', $profile->id)
            ->where('client_id', $lead->user_id)
            ->orderByDesc('last_message_at')
            ->first();

        $conversation?->markReadBy(Conversation::SIDE_PROVIDER);

        return [
            'lead_id' => $lead->id,
            'conversation_id' => $conversation?->id,
            'listing' => $conversation ? ['id' => $conversation->listing->id, 'title' => $conversation->listing->title] : null,
            'messages' => $conversation ? Inbox::messages($conversation, $request->user()) : [],
            'counterpart_last_read_id' => (int) ($conversation->client_last_read_id ?? 0),
        ];
    }
}
