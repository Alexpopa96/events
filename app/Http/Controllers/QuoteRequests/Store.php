<?php

namespace App\Http\Controllers\QuoteRequests;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Locality;
use App\Models\QuoteRequest;
use App\Notifications\NewQuoteRequestPendingApproval;
use App\Notifications\QuoteRequestPackageReceived;
use App\Notifications\QuoteRequestReceived;
use App\Rules\RomanianPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class Store extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // A client can ask for several services at once for the same event
            // (e.g. photographer + DJ + venue), submitted as one shared request.
            'category_ids' => ['required', 'array', 'min:1', 'max:5'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            'event_type' => ['nullable', 'string', 'max:50'],
            'event_date' => ['nullable', 'date', 'after:today'],
            'county_id' => ['nullable', 'integer', 'exists:counties,id'],
            'locality_id' => ['nullable', 'integer', 'exists:localities,id'],
            'guest_count' => ['nullable', 'string', 'max:50'],
            'budget_range' => ['nullable', 'string', 'max:50'],
            'preferences' => ['nullable', 'array'],
            'preferences.*' => ['string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30', new RomanianPhone],
            'contact_method' => ['nullable', 'string', 'max:30'],
            'platform_only' => ['nullable', 'boolean'],
        ]);

        $categoryIds = $data['category_ids'];
        unset($data['category_ids']);

        $shared = [
            ...$data,
            'user_id' => $request->user()->id,
            'county' => ($data['county_id'] ?? null) ? County::find($data['county_id'])?->name : null,
            'city' => ($data['locality_id'] ?? null) ? Locality::find($data['locality_id'])?->name : null,
            'status' => 'pending_review',
            // Only a real package (more than one category) gets a group token — a
            // lone request has nothing to group with, so it stays null as before.
            'group_token' => count($categoryIds) > 1 ? Str::uuid()->toString() : null,
        ];

        $created = collect($categoryIds)->map(
            fn ($categoryId) => QuoteRequest::create([...$shared, 'category_id' => $categoryId])
        );

        // Reloaded as a proper Eloquent collection (the one above is a plain Support
        // collection, since it was built from an array of ids) so ->load() works below.
        $quoteRequests = QuoteRequest::whereIn('id', $created->pluck('id'))->with('category')->get();

        $quoteRequests->each(
            fn (QuoteRequest $quoteRequest) => Notification::route('mail', config('mail.support_address'))
                ->notify(new NewQuoteRequestPendingApproval($quoteRequest))
        );

        if ($quoteRequests->count() > 1) {
            Notification::route('mail', [$shared['email'] => $shared['name']])
                ->notify(new QuoteRequestPackageReceived($quoteRequests));
        } else {
            Notification::route('mail', [$shared['email'] => $shared['name']])
                ->notify(new QuoteRequestReceived($quoteRequests->first()));
        }

        $first = $quoteRequests->first();

        return redirect()->route('quote-requests.success', $first)
            ->with('success', ['message' => $quoteRequests->count() > 1
                ? 'Cererile tale au fost trimise spre aprobare.'
                : 'Cererea ta a fost trimisă spre aprobare.'])
            ->with('quote_request_success_id', $first->id);
    }
}
