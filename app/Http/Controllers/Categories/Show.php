<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\County;
use App\Support\Listings\CategoryPage;
use App\Support\Seo\Landing;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * /categorii/fotograf, /categorii/fotograf/cluj, /categorii/fotograf/cluj/cluj-napoca
 */
class Show extends Controller
{
    public function __invoke(Request $request, CategoryPage $page, Category $category, ?County $county = null, ?string $localitySlug = null): Response
    {
        return $page->render($request, Landing::resolve($category, $county, $localitySlug));
    }
}
