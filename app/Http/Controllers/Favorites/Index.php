<?php

namespace App\Http\Controllers\Favorites;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ProviderProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $listings = $user->favorites()
            ->with([
                'listing.category:id,name,slug',
                'listing.providerProfile:id,company_name,slug',
                'listing.media' => fn ($query) => $query->where('is_cover', true),
            ])
            ->latest()
            ->get()
            ->filter(fn ($favorite) => $favorite->listing !== null)
            ->map(fn ($favorite) => [
                'id' => $favorite->listing->id,
                'slug' => $favorite->listing->slug,
                'title' => $favorite->listing->title,
                'category' => $favorite->listing->category->name,
                'category_slug' => $favorite->listing->category->slug,
                'price_from' => $favorite->listing->price_from,
                'price_type' => $favorite->listing->price_type,
                'cover_url' => $favorite->listing->media->first() ? "/storage/{$favorite->listing->media->first()->path}" : null,
                'provider' => ['company_name' => $favorite->listing->providerProfile->company_name, 'slug' => $favorite->listing->providerProfile->slug],
            ])
            ->values();

        $favoriteProviderIds = $user->providerFavorites()->latest()->pluck('provider_profile_id');

        $providers = ProviderProfile::query()
            ->whereIn('id', $favoriteProviderIds)
            ->with([
                'county:id,name',
                'locality:id,name',
                'listings' => fn ($query) => $query->where('status', 'published')->with('category:id,name,slug'),
            ])
            ->withAvg(['reviews as reviews_avg_rating' => fn ($query) => $query->where('status', 'approved')], 'rating')
            ->withCount(['reviews as reviews_count' => fn ($query) => $query->where('status', 'approved')])
            ->withMin(['listings as min_price' => fn ($query) => $query->where('status', 'published')->where('price_type', '!=', 'on_request')->whereNotNull('price_from')], 'price_from')
            ->addSelect(['has_featured_listing' => Listing::selectRaw('count(*) > 0')
                ->whereColumn('listings.provider_profile_id', 'provider_profiles.id')
                ->where('status', 'published')
                ->where('is_featured', true),
            ])
            ->get()
            ->sortBy(fn ($provider) => $favoriteProviderIds->search($provider->id))
            ->values()
            ->map(function (ProviderProfile $provider) {
                $categories = $provider->listings
                    ->pluck('category')
                    ->filter()
                    ->unique('id')
                    ->values()
                    ->take(3)
                    ->map(fn ($category) => ['name' => $category->name, 'slug' => $category->slug]);

                return [
                    'id' => $provider->id,
                    'slug' => $provider->slug,
                    'company_name' => $provider->company_name,
                    'logo_url' => $provider->logoUrl(),
                    'cover_url' => $provider->coverUrl(),
                    'county' => $provider->county?->name,
                    'locality' => $provider->locality?->name,
                    'category' => $categories->first()['name'] ?? null,
                    'category_slug' => $categories->first()['slug'] ?? null,
                    'price_from' => $provider->min_price,
                    'price_type' => $provider->min_price !== null ? 'starting_from' : 'on_request',
                    'is_featured' => (bool) $provider->has_featured_listing,
                    'rating' => $provider->reviews_avg_rating ? round((float) $provider->reviews_avg_rating, 1) : null,
                    'reviews_count' => $provider->reviews_count ?? 0,
                    'phone' => $provider->phone,
                    'whatsapp' => $provider->whatsapp,
                    'email' => $provider->email,
                ];
            });

        return Inertia::render('Favorites/Index', [
            'favoriteListings' => $listings,
            'favoriteProviders' => $providers,
        ]);
    }
}
