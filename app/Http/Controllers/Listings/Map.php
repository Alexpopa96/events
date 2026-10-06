<?php

namespace App\Http\Controllers\Listings;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\County;
use App\Models\Listing;
use App\Support\EventTypes;
use App\Support\Listings\ListingFilters;
use App\Support\Listings\ListingQueryScope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class Map extends Controller
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:100', Rule::exists('categories', 'slug')],
            'event_type' => ['nullable', 'string', Rule::in(EventTypes::values())],
        ]);

        $counts = ListingQueryScope::apply(
            Listing::query()->where('status', 'published')->whereNotNull('county_id'),
            ListingFilters::fromValidated($validated)
        )
            ->selectRaw('county_id, count(*) as total')
            ->groupBy('county_id')
            ->pluck('total', 'county_id');

        $counties = County::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (County $county) => [
                'id' => $county->id,
                'name' => $county->name,
                'count' => (int) ($counts[$county->id] ?? 0),
            ]);

        return Inertia::render('Listings/Map', [
            'counties' => $counties,
            'total' => $counties->sum('count'),
            'filters' => [
                'category' => $validated['category'] ?? null,
                'event_type' => $validated['event_type'] ?? null,
            ],
            'categories' => Category::where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('position')
                ->get(['id', 'name', 'slug']),
            'eventTypes' => EventTypes::options(),
        ]);
    }
}
