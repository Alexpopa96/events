<?php

namespace App\Http\Controllers\Providers;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class Show extends Controller
{
    public function __invoke(Request $request, ProviderProfile $providerProfile): Response
    {
        abort_unless($providerProfile->status === 'active', HttpResponse::HTTP_NOT_FOUND);

        $providerProfile->load([
            'county:id,name',
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
                'is_favorited' => $request->user()
                    ? $request->user()->providerFavorites()->where('provider_profile_id', $providerProfile->id)->exists()
                    : false,
            ],
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
            'reviews' => $reviews->map(fn (Review $review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'author' => $review->user->name,
                'listing_title' => $review->listing?->title,
                'created_at' => $review->created_at->diffForHumans(),
            ]),
        ]);
    }
}
