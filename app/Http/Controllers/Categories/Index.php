<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ProviderProfile;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class Index extends Controller
{
    public function __invoke(): Response
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->withCount(['listings as listings_count' => fn ($query) => $query->where('status', 'published')])
            ->orderBy('position')
            ->get(['id', 'name', 'slug', 'description', 'created_at'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'listingsCount' => $category->listings_count,
                'isNew' => $category->created_at?->greaterThan(Carbon::now()->subDays(30)) ?? false,
            ]);

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'stats' => [
                'providers' => ProviderProfile::where('status', 'active')->count(),
            ],
        ]);
    }
}
