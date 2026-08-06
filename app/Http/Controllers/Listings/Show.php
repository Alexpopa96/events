<?php

namespace App\Http\Controllers\Listings;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingEvent;
use Illuminate\Http\Request;
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
            'county:id,name',
            'locality:id,name',
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
                ],
            ],
            'related' => $related,
            'isFavorited' => $user ? $user->favorites()->where('listing_id', $listing->id)->exists() : false,
        ]);
    }
}
