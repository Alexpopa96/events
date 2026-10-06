<?php

namespace App\Http\Controllers\SavedSearches;

use App\Http\Controllers\Controller;
use App\Support\EventTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Saves the current /anunturi filters as a named alert. A daily job checks
 * each saved search for newly published listings and emails a digest.
 */
class Store extends Controller
{
    private const MAX_PER_USER = 15;

    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->savedSearches()->count() >= self::MAX_PER_USER) {
            return back()->with('error', ['message' => 'Ai atins limita de '.self::MAX_PER_USER.' căutări salvate. Șterge una pentru a adăuga alta.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:100'],
            'event_types' => ['nullable', 'array'],
            'event_types.*' => ['string', Rule::in(EventTypes::values())],
            'county_ids' => ['nullable', 'array'],
            'county_ids.*' => ['integer', 'exists:counties,id'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
            'rating' => ['nullable', 'numeric', 'in:4,4.5'],
            'featured' => ['nullable', 'boolean'],
        ]);

        $name = $data['name'];
        unset($data['name']);

        $user->savedSearches()->create([
            'name' => $name,
            'filters' => $data,
        ]);

        return back()->with('success', ['message' => 'Căutarea a fost salvată. Te anunțăm când apar anunțuri noi.']);
    }
}
