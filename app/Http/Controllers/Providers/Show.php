<?php

namespace App\Http\Controllers\Providers;

use App\Http\Controllers\Controller;
use App\Models\ListingMedia;
use App\Models\ProviderProfile;
use App\Models\Review;
use App\Support\Seo\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class Show extends Controller
{
    public function __invoke(Request $request, ProviderProfile $providerProfile): Response
    {
        abort_unless($providerProfile->status === 'active', HttpResponse::HTTP_NOT_FOUND);

        $providerProfile->load([
            'county:id,name,slug',
            'locality:id,name',
            'listings' => fn ($query) => $query->where('status', 'published')
                ->with(['category:id,name,slug', 'media' => fn ($query) => $query->where('is_cover', true)->orWhere('position', 0)])
                ->withAvg('approvedReviews', 'rating')
                ->withCount('approvedReviews')
                ->orderByDesc('is_featured')
                ->orderByDesc('published_at'),
        ]);

        $reviews = Review::query()
            ->where('provider_profile_id', $providerProfile->id)
            ->where('status', 'approved')
            ->with(['user:id,name', 'listing:id,title,slug'])
            ->latest()
            ->take(20)
            ->get();

        // Photos from every published listing, for the hero mosaic and the gallery tab.
        $gallery = ListingMedia::query()
            ->whereIn('listing_id', $providerProfile->listings->pluck('id'))
            ->with('listing:id,title,slug')
            ->orderByDesc('is_cover')
            ->orderBy('position')
            ->take(40)
            ->get();

        $reviewsCount = $providerProfile->reviews()->where('status', 'approved')->count();

        $categories = $providerProfile->listings
            ->pluck('category')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($category) => ['id' => $category->id, 'name' => $category->name, 'slug' => $category->slug]);

        return Inertia::render('Providers/Show', [
            'provider' => [
                'id' => $providerProfile->id,
                'slug' => $providerProfile->slug,
                'company_name' => $providerProfile->company_name,
                'description' => $providerProfile->description,
                'logo_url' => $providerProfile->logoUrl(),
                'cover_url' => $providerProfile->coverUrl(),
                'phone' => $providerProfile->phone,
                'whatsapp' => $providerProfile->whatsapp,
                'email' => $providerProfile->email,
                'website' => $providerProfile->website,
                'address' => $providerProfile->address,
                'county' => $providerProfile->county?->name,
                'locality' => $providerProfile->locality?->name,
                'social_links' => $providerProfile->social_links ?? [],
                'categories' => $categories,
                'rating' => $providerProfile->averageRating() ?: null,
                'reviews_count' => $reviewsCount,
                'listings_count' => $providerProfile->listings->count(),
                'member_since' => $providerProfile->approved_at?->format('Y'),
                'is_featured' => $providerProfile->listings->contains('is_featured', true),
                'is_verified' => $providerProfile->isAnafVerified(),
                'response_time_label' => $providerProfile->responseTimeLabel(),
                'is_favorited' => $request->user()
                    ? $request->user()->providerFavorites()->where('provider_profile_id', $providerProfile->id)->exists()
                    : false,
            ],
            'unavailableDates' => $providerProfile->availabilityBlocks()
                ->whereBetween('date', [now()->toDateString(), now()->addMonths(6)->toDateString()])
                ->orderBy('date')
                ->pluck('date')
                ->map(fn ($date) => $date->toDateString())
                ->all(),
            'listings' => $providerProfile->listings->map(fn ($listing) => [
                'id' => $listing->id,
                'slug' => $listing->slug,
                'title' => $listing->title,
                'description' => $listing->description,
                'category' => $listing->category?->name,
                'category_slug' => $listing->category?->slug,
                'price_from' => $listing->price_from,
                'price_to' => $listing->price_to,
                'price_type' => $listing->price_type,
                'is_featured' => $listing->is_featured,
                'views_count' => $listing->views_count,
                'cover_url' => $listing->media->first() ? "/storage/{$listing->media->first()->path}" : null,
                'rating' => $listing->approved_reviews_avg_rating ? round((float) $listing->approved_reviews_avg_rating, 1) : null,
                'reviews_count' => $listing->approved_reviews_count ?? 0,
            ]),
            'seo' => $this->seo($providerProfile, $reviews, $reviewsCount, $categories->pluck('name')->all())->toArray(),
            'gallery' => $gallery->map(fn (ListingMedia $media) => [
                'id' => $media->id,
                'type' => $media->type,
                'url' => "/storage/{$media->path}",
                'listing_title' => $media->listing?->title,
                'listing_slug' => $media->listing?->slug,
            ]),
            'reviews' => $reviews->map(fn (Review $review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'author' => $review->user->name,
                'listing_title' => $review->listing?->title,
                'provider_reply' => $review->provider_reply,
                'provider_replied_at' => $review->provider_replied_at?->diffForHumans(),
                'created_at' => $review->created_at->diffForHumans(),
            ]),
        ]);
    }

    private function seo(ProviderProfile $provider, $reviews, int $reviewsCount, array $categoryNames): Seo
    {
        $place = $provider->locality?->name ?? $provider->county?->displayName();
        $what = $categoryNames ? implode(', ', array_slice($categoryNames, 0, 3)) : 'Furnizor evenimente';
        $url = route('providers.show', $provider->slug);

        $description = $provider->description
            ?: "{$provider->company_name} — {$what}".($place ? " în {$place}" : '').'. Vezi portofoliul, prețurile și recenziile clienților, apoi cere o ofertă gratuită.';

        return Seo::make("{$provider->company_name} — {$what}".($place ? " {$place}" : ''), $description, $url)
            ->type('profile')
            ->image($provider->coverUrl() ?? $provider->logoUrl())
            ->breadcrumbs([
                ['name' => 'Acasă', 'url' => route('home')],
                ['name' => 'Furnizori', 'url' => route('providers.index')],
                ['name' => $provider->company_name, 'url' => $url],
            ])
            ->jsonLd(array_filter([
                '@type' => 'LocalBusiness',
                '@id' => "{$url}#business",
                'name' => $provider->company_name,
                'description' => Str::limit(strip_tags((string) $provider->description), 500) ?: null,
                'url' => $url,
                'image' => ($image = $provider->coverUrl() ?? $provider->logoUrl()) ? Seo::absolute($image) : null,
                'logo' => $provider->logoUrl() ? Seo::absolute($provider->logoUrl()) : null,
                'telephone' => $provider->phone,
                'sameAs' => array_values(array_filter(array_merge([$provider->website], array_values($provider->social_links ?? [])), fn ($link) => is_string($link) && Str::startsWith($link, 'http'))) ?: null,
                'address' => $place ? array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => $provider->address,
                    'addressLocality' => $provider->locality?->name,
                    'addressRegion' => $provider->county?->displayName(),
                    'addressCountry' => 'RO',
                ]) : null,
                'aggregateRating' => Seo::aggregateRating($provider->averageRating(), $reviewsCount),
                'review' => $reviews->take(5)->map(fn (Review $review) => array_filter([
                    '@type' => 'Review',
                    'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $review->rating, 'bestRating' => 5],
                    'author' => ['@type' => 'Person', 'name' => $review->user->name],
                    'reviewBody' => $review->comment,
                    'datePublished' => $review->created_at->toDateString(),
                ]))->values()->all() ?: null,
            ]));
    }
}
