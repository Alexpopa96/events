<?php

namespace App\Http\Controllers\Provider\Listings;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A narrow endpoint just for the price, so it can be edited inline from the
 * listings table without opening the full edit form (and without the status
 * or review implications a full save might carry elsewhere).
 */
class UpdatePrice extends Controller
{
    public function __invoke(Request $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->provider_profile_id === $request->user()->providerProfile?->id, 403);

        $data = $request->validate([
            'price_type' => ['required', Rule::in(['fixed', 'starting_from', 'per_hour', 'on_request'])],
            'price_from' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'price_to' => ['nullable', 'numeric', 'min:0', 'max:1000000', 'gte:price_from'],
        ]);

        if ($data['price_type'] === 'on_request') {
            $data['price_from'] = null;
            $data['price_to'] = null;
        }

        $listing->update($data);

        return back()->with('success', ['message' => 'Prețul a fost actualizat.']);
    }
}
