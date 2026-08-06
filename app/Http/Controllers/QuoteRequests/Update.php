<?php

namespace App\Http\Controllers\QuoteRequests;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Locality;
use App\Models\QuoteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Update extends Controller
{
    public function __invoke(Request $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        abort_unless($quoteRequest->user_id === $request->user()->id, 403);
        abort_if($quoteRequest->status === 'closed', 403);

        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
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
            'phone' => ['required', 'string', 'max:30'],
            'contact_method' => ['nullable', 'string', 'max:30'],
            'platform_only' => ['nullable', 'boolean'],
        ]);

        $originalStatus = $quoteRequest->status;

        $quoteRequest->fill([
            ...$data,
            'county' => ($data['county_id'] ?? null) ? County::find($data['county_id'])?->name : null,
            'city' => ($data['locality_id'] ?? null) ? Locality::find($data['locality_id'])?->name : null,
        ]);

        // Category, event type, and location decide which providers see the
        // request — changing any of them means providers who already saw it
        // saw stale targeting, so it goes back through review. Budget,
        // preferences, and contact details don't affect matching and can
        // change freely. A rejected request always goes back to review
        // regardless of what changed, since it already failed once.
        $majorChange = $quoteRequest->isDirty(['category_id', 'event_type', 'county_id', 'locality_id']);
        $needsReview = $originalStatus === 'rejected' || ($originalStatus === 'open' && $majorChange);

        if ($needsReview) {
            $quoteRequest->status = 'pending_review';
            $quoteRequest->rejection_reason = null;
            $quoteRequest->approved_at = null;
            $quoteRequest->rejected_at = null;
        }

        $quoteRequest->save();

        return redirect()->route('quote-requests.show', $quoteRequest)
            ->with('success', ['message' => $needsReview
                ? 'Cererea a fost actualizată și retrimisă spre aprobare.'
                : 'Cererea a fost actualizată.']);
    }
}
