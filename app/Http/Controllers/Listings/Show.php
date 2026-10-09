<?php

namespace App\Http\Controllers\Listings;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingEvent;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Support\Seo\Landing;
use App\Support\Seo\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class Show extends Controller
{
    public function __invoke(Request $request, Listing $listing): Response
    {
        abort_unless($listing->status === 'published', HttpResponse::HTTP_NOT_FOUND);

        $listing->load([
            'category:id,name,slug',
            'county:id,name,slug',
            'locality:id,name,slug,county_id',
            'media' => fn ($query) => $query->orderByDesc('is_cover')->orderBy('position'),
            'providerProfile' => fn ($query) => $query->with(['county:id,name', 'locality:id,name']),
            'approvedReviews' => fn ($query) => $query->latest()->take(10)->with('user:id,name'),
        ]);

        $listing->loadCount('approvedReviews');

        $listing->increment('views_count');

        ListingEvent::create([
            'provider_profile_id' => $listing->provider_profile_id,
            'listing_id' => $listing->id,
            'user_id' => $request->user()?->id,
            'type' => 'view',
            'ip_hash' => hash('sha256', $request->ip()),
        ]);

        $user = $request->user();

        $related = Listing::query()
            ->where('status', 'published')
            ->where('category_id', $listing->category_id)
            ->where('id', '!=', $listing->id)
            ->with(['category:id,name,slug', 'providerProfile:id,company_name,slug', 'media' => fn ($query) => $query->where('is_cover', true)])
            ->withAvg('approvedReviews', 'rating')
            ->take(4)
            ->get()
            ->map(fn (Listing $item) => [
                'id' => $item->id,
                'slug' => $item->slug,
                'title' => $item->title,
                'category' => $item->category->name,
                'category_slug' => $item->category->slug,
                'price_from' => $item->price_from,
                'price_type' => $item->price_type,
                'cover_url' => $item->media->first() ? "/storage/{$item->media->first()->path}" : null,
                'rating' => $item->approved_reviews_avg_rating ? round((float) $item->approved_reviews_avg_rating, 1) : null,
                'provider' => ['company_name' => $item->providerProfile->company_name, 'slug' => $item->providerProfile->slug],
            ]);

        return Inertia::render('Listings/Show', [
            'listing' => [
                'id' => $listing->id,
                'slug' => $listing->slug,
                'title' => $listing->title,
                'description' => $listing->description,
                'category' => $listing->category->name,
                'category_slug' => $listing->category->slug,
                'price_from' => $listing->price_from,
                'price_to' => $listing->price_to,
                'price_type' => $listing->price_type,
                'benefits' => $listing->benefits ?? [],
                'county' => $listing->county?->name,
                'locality' => $listing->locality?->name,
                'views_count' => $listing->views_count,
                'is_featured' => $listing->is_featured,
                'media' => $listing->media->map(fn ($media) => [
                    'id' => $media->id,
                    'type' => $media->type,
                    'url' => "/storage/{$media->path}",
                    'is_cover' => $media->is_cover,
                ]),
                'rating' => $listing->approvedReviews->count() ? round($listing->approvedReviews->avg('rating'), 1) : null,
                'reviews_count' => $listing->approved_reviews_count,
                'reviews' => $listing->approvedReviews->map(fn ($review) => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'author' => $review->user->name,
                    'created_at' => $review->created_at->diffForHumans(),
                    'provider_reply' => $review->provider_reply,
                    'provider_replied_at' => $review->provider_replied_at?->diffForHumans(),
                ]),
                'provider' => [
                    'company_name' => $listing->providerProfile->company_name,
                    'slug' => $listing->providerProfile->slug,
                    'logo_url' => $listing->providerProfile->logoUrl(),
                    'description' => $listing->providerProfile->description,
                    'phone' => $listing->providerProfile->phone,
                    'whatsapp' => $listing->providerProfile->whatsapp,
                    'email' => $listing->providerProfile->email,
                    'website' => $listing->providerProfile->website,
                    'county' => $listing->providerProfile->county?->name,
                    'locality' => $listing->providerProfile->locality?->name,
                    'rating' => $listing->providerProfile->averageRating() ?: null,
                    'is_verified' => $listing->providerProfile->isAnafVerified(),
                    'response_time_label' => $listing->providerProfile->responseTimeLabel(),
                ],
            ],
            'related' => $related,
            'canMessage' => $user ? $listing->providerProfile->user_id !== $user->id : true,
            'reviewState' => $this->reviewState($listing, $user),
            'unavailableDates' => $this->unavailableDates($listing->providerProfile),
            'isFavorited' => $user ? $user->favorites()->where('listing_id', $listing->id)->exists() : false,
            'seo' => $this->seo($listing)->toArray(),
        ]);
    }

    private function seo(Listing $listing): Seo
    {
        $provider = $listing->providerProfile;
        $place = $listing->locality?->name ?? $listing->county?->displayName();
        $cover = $listing->media->firstWhere('type', '!=', 'video');
        $rating = $listing->approvedReviews->avg('rating');
        $priced = $listing->price_type !== 'on_request' && $listing->price_from > 0;

        $description = $listing->description
            ?: "{$listing->category->name}".($place ? " în {$place}" : '').": {$listing->title}. Vezi prețuri, portofoliu și recenzii, apoi cere o ofertă gratuită.";

        $crumbs = [...(new Landing($listing->category, $listing->county))->breadcrumbs()];
        $crumbs[] = ['name' => $listing->title, 'url' => route('listings.show', $listing->slug)];

        return Seo::make(
            $listing->title.' — '.$listing->category->name.($place ? " {$place}" : ''),
            $description,
            route('listings.show', $listing->slug),
        )
            ->image($cover ? "/storage/{$cover->path}" : $provider->logoUrl())
            ->breadcrumbs($crumbs)
            ->jsonLd(array_filter([
                '@type' => 'Service',
                'name' => $listing->title,
                'serviceType' => $listing->category->name,
                'description' => Str::limit(strip_tags((string) $listing->description), 500),
                'url' => route('listings.show', $listing->slug),
                'image' => $cover ? Seo::absolute("/storage/{$cover->path}") : null,
                'areaServed' => $listing->county ? ['@type' => 'AdministrativeArea', 'name' => ucfirst($listing->county->regionLabel())] : null,
                'provider' => [
                    '@type' => 'LocalBusiness',
                    'name' => $provider->company_name,
                    'url' => route('providers.show', $provider->slug),
                ],
                'offers' => $priced ? [
                    '@type' => 'Offer',
                    'priceCurrency' => 'RON',
                    'price' => (float) $listing->price_from,
                    'availability' => 'https://schema.org/InStock',
                ] : null,
                'aggregateRating' => Seo::aggregateRating($rating, (int) $listing->approved_reviews_count),
            ]));
    }

    /**
     * Dates the provider is already booked on, for the next 6 months.
     *
     * @return array<int, string>
     */
    private function unavailableDates(ProviderProfile $providerProfile): array
    {
        return $providerProfile->availabilityBlocks()
            ->whereBetween('date', [now()->toDateString(), now()->addMonths(6)->toDateString()])
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($date) => $date->toDateString())
            ->all();
    }

    /**
     * What the review form should show this visitor: a form, their pending/finished review, or nothing.
     *
     * @return array{can_review: bool, my_review: array{rating: int, comment: ?string, status: string}|null}
     */
    private function reviewState(Listing $listing, ?User $user): array
    {
        if (! $user || ! $user->can('submit review')) {
            return ['can_review' => false, 'my_review' => null];
        }

        $mine = $listing->reviews()->where('user_id', $user->id)->first();

        return [
            'can_review' => ! $mine && $listing->canBeReviewedBy($user),
            'my_review' => $mine ? ['rating' => $mine->rating, 'comment' => $mine->comment, 'status' => $mine->status] : null,
        ];
    }
}
