<?php

namespace App\Http\Controllers\Categories\Events;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\County;
use App\Support\Listings\CategoryPage;
use App\Support\Seo\Landing;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * /nunta/fotograf, /nunta/fotograf/cluj, /nunta/fotograf/cluj/cluj-napoca
 */
class Show extends Controller
{
    public function __invoke(Request $request, CategoryPage $page, string $eventType, Category $category, ?County $county = null, ?string $localitySlug = null): Response
    {
        return $page->render($request, Landing::resolve($category, $county, $localitySlug, $eventType));
    }
}
