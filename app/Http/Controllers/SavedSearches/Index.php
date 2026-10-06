<?php

namespace App\Http\Controllers\SavedSearches;

use App\Http\Controllers\Controller;
use App\Models\SavedSearch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(Request $request): Response
    {
        $searches = $request->user()->savedSearches()->latest()->get();

        return Inertia::render('SavedSearches/Index', [
            'savedSearches' => $searches->map(fn (SavedSearch $search) => [
                'id' => $search->id,
                'name' => $search->name,
                'summary' => $this->summarize($search->filters),
                'query' => $search->queryParams(),
                'created_at' => $search->created_at->diffForHumans(),
            ]),
        ]);
    }

    /**
     * A short, human-readable recap of what the search filters for
     * ("Fotografie · Cluj · sub 1.500 lei"), so the list doesn't need to
     * show raw filter keys.
     */
    private function summarize(array $filters): string
    {
        $parts = [];

        if (! empty($filters['q'])) {
            $parts[] = '"'.$filters['q'].'"';
        }

        if (! empty($filters['categories'])) {
            $parts[] = implode(', ', $filters['categories']);
        }

        if (! empty($filters['price_max'])) {
            $parts[] = 'sub '.number_format((float) $filters['price_max'], 0, ',', '.').' lei';
        } elseif (! empty($filters['price_min'])) {
            $parts[] = 'de la '.number_format((float) $filters['price_min'], 0, ',', '.').' lei';
        }

        if (! empty($filters['rating'])) {
            $parts[] = $filters['rating'].'+ stele';
        }

        if (! empty($filters['featured'])) {
            $parts[] = 'doar premium';
        }

        return $parts ? implode(' · ', $parts) : 'Toate anunțurile';
    }
}
