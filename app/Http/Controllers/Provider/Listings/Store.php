<?php

namespace App\Http\Controllers\Provider\Listings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class Store extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $profile = $request->user()->providerProfile;

        $plan = $profile->activePlan();
        $activeListings = $profile->listings()->where('status', '!=', 'archived')->count();

        if ($plan?->max_listings !== null && $activeListings >= $plan->max_listings) {
            return back()->with('error', [
                'message' => "Ai atins limita de {$plan->max_listings} ".Str::plural('anunț', $plan->max_listings)." activ".($plan->max_listings > 1 ? 'e' : '')." din planul \"{$plan->name}\". Arhivează un anunț existent sau treci la un plan superior.",
            ]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'price_type' => ['required', Rule::in(['fixed', 'starting_from', 'per_hour', 'on_request'])],
            'price_from' => ['nullable', 'numeric', 'min:0'],
            'price_to' => ['nullable', 'numeric', 'min:0', 'gte:price_from'],
            'benefits' => ['nullable', 'array'],
            'benefits.*' => ['string', 'max:120'],
            'county_id' => ['nullable', 'integer', 'exists:counties,id'],
            'locality_id' => ['nullable', 'integer', 'exists:localities,id'],
        ]);

        $data['benefits'] = collect($data['benefits'] ?? [])
            ->map(fn ($benefit) => trim($benefit))
            ->filter()
            ->values()
            ->all();

        $slug = Str::slug($data['title']).'-'.Str::lower(Str::random(5));

        if (! empty($data['description'])) {
            $data['description'] = clean($data['description'], 'listing_description');
        }

        $listing = $profile->listings()->create([
            ...$data,
            'slug' => $slug,
            'status' => 'draft',
        ]);

        return redirect()
            ->route('provider.listings.edit', $listing)
            ->with('success', ['message' => 'Anunțul a fost creat ca ciornă.']);
    }
}
