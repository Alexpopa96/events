<?php

namespace App\Http\Controllers\QuoteRequests;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\County;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Create extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('QuoteRequests/Create', [
            'categories' => Category::where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('position')
                ->get(['id', 'name', 'slug']),
            'counties' => County::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
