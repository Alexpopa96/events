<?php

namespace App\Http\Controllers\Provider\Listings;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class Update extends Controller
{
    public function __invoke(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->provider_profile_id === $request->user()->providerProfile?->id, 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'price_type' => ['required', Rule::in(['fixed', 'starting_from', 'per_hour', 'on_request'])],
            'price_from' => ['nullable', 'numeric', 'min:0'],
            'price_to' => ['nullable', 'numeric', 'min:0', 'gte:price_from'],
            'county_id' => ['nullable', 'integer', 'exists:counties,id'],
            'locality_id' => ['nullable', 'integer', 'exists:localities,id'],
            'action' => ['required', Rule::in(['save', 'submit', 'unpublish'])],
        ]);

        $action = $data['action'];
        unset($data['action']);

        if (! empty($data['description'])) {
            $data['description'] = clean($data['description'], 'listing_description');
        }

        $message = 'Anunțul a fost salvat.';

        if ($action === 'submit') {
            $data['status'] = 'pending_review';
            $data['rejection_reason'] = null;
            $message = 'Anunțul a fost trimis spre verificare.';
        } elseif ($action === 'unpublish') {
            $data['status'] = 'draft';
            $data['published_at'] = null;
            $message = 'Anunțul a fost retras și mutat la ciorne.';
        }

        $listing->update($data);

        return redirect()
            ->route('provider.listings.edit', $listing)
            ->with('success', ['message' => $message]);
    }
}
