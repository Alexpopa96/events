<?php

namespace App\Http\Controllers\Favorites;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(Request $request): Response
    {
        $favorites = $request->user()
            ->favorites()
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

        return Inertia::render('Favorites/Index', [
            'favorites' => $favorites,
        ]);
    }
}
