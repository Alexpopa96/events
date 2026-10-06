<?php

namespace App\Support\Offers;

use App\Models\Listing;
use App\Models\ProviderProfile;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Validation and shaping of the fields a provider fills in when making or editing an offer.
 */
class OfferData
{
    /**
     * @return array{listing_id: int, price: int, valid_until: string, includes: array<int, string>, message: ?string}
     */
    public static function validate(Request $request, QuoteRequest $lead, ProviderProfile $profile): array
    {
        $rules = [
            // The offer hangs off one of the provider's published listings in the requested category.
            'listing_id' => ['required', 'integer', Rule::exists('listings', 'id')
                ->where('provider_profile_id', $profile->id)
                ->where('status', 'published')
                ->where('category_id', $lead->category_id)],
            'price' => ['required', 'integer', 'min:1', 'max:10000000'],
            'valid_until' => ['required', 'date', 'after_or_equal:today'],
            'includes' => ['nullable', 'array', 'max:10'],
            'includes.*' => ['string', 'max:100'],
            'message' => ['nullable', 'string', 'max:1500'],
        ];

        // A quote that outlives the event it is for makes no sense.
        if ($lead->event_date && $lead->event_date->gte(today())) {
            $rules['valid_until'][] = 'before_or_equal:'.$lead->event_date->toDateString();
        }

        $data = $request->validate($rules, [
            'listing_id.required' => 'Alege anunțul pe care se bazează oferta.',
            'listing_id.exists' => 'Alege unul dintre anunțurile tale publicate din această categorie.',
            'valid_until.before_or_equal' => 'Oferta nu poate fi valabilă după data evenimentului.',
        ]);

        $data['includes'] = collect($data['includes'] ?? [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $data['message'] = filled($data['message'] ?? null) ? trim($data['message']) : null;

        return $data;
    }

    /**
     * Published listings the provider can base an offer on for this request.
     *
     * @return Collection<int, Listing>
     */
    public static function listingsFor(ProviderProfile $profile, QuoteRequest $lead)
    {
        return $profile->listings()
            ->where('status', 'published')
            ->where('category_id', $lead->category_id)
            ->get(['id', 'title']);
    }
}
