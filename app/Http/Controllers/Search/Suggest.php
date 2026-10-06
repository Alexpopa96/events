<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ProviderProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Powers the header's global search dropdown: a handful of top matches across
 * listings, providers and categories for whatever the visitor is typing.
 */
class Suggest extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:80']]);
        $term = $data['q'];

        $listings = Listing::query()
            ->where('status', 'published')
            ->where('title', 'like', "%{$term}%")
            ->with(['category:id,name,slug', 'media' => fn ($query) => $query->where('is_cover', true)])
            ->take(4)
            ->get()
            ->map(fn (Listing $listing) => [
                'type' => 'listing',
                'id' => $listing->id,
                'title' => $listing->title,
                'subtitle' => $listing->category->name,
                'url' => route('listings.show', $listing->slug),
                'image' => $listing->media->first() ? "/storage/{$listing->media->first()->path}" : null,
            ]);

        $providers = ProviderProfile::query()
            ->where('status', 'active')
            ->where('company_name', 'like', "%{$term}%")
            ->take(4)
            ->get()
            ->map(fn (ProviderProfile $provider) => [
                'type' => 'provider',
                'id' => $provider->id,
                'title' => $provider->company_name,
                'subtitle' => 'Furnizor',
                'url' => route('providers.show', $provider->slug),
                'image' => $provider->logoUrl(),
            ]);

        $categories = Category::query()
            ->where('is_active', true)
            ->where('name', 'like', "%{$term}%")
            ->take(3)
            ->get()
            ->map(fn (Category $category) => [
                'type' => 'category',
                'id' => $category->id,
                'title' => $category->name,
                'subtitle' => 'Categorie',
                'url' => route('categories.show', $category->slug),
                'image' => null,
            ]);

        return response()->json([
            'results' => $listings->concat($providers)->concat($categories)->values(),
        ]);
    }
}
