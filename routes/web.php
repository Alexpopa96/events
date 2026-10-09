<?php

use App\Http\Controllers\Impersonate;
use App\Models\Category;
use App\Models\County;
use App\Models\Listing;
use App\Models\ProviderProfile;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Support\DashboardMetrics;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

if (! function_exists('dashboardProps')) {
function dashboardProps(): array
{
    $user = auth()->user();
    $metrics = new DashboardMetrics((int) request('range', 30));

    $canUsers = $user->can('view users');
    $canProviders = $user->can('moderate providers');
    $canQuotes = $user->can('moderate quote requests');

    $kpis = [];
    $growth = [];

    if ($canUsers) {
        $kpis[] = ['key' => 'users', 'label' => 'Utilizatori', 'total' => User::count(), 'href' => '/administration/users']
            + $metrics->trend(User::class);
        $growth['users'] = $metrics->dailyCounts(User::class);
    }

    if ($canProviders) {
        $kpis[] = ['key' => 'providers', 'label' => 'Furnizori activi', 'total' => ProviderProfile::where('status', 'active')->count(), 'href' => '/administration/providers']
            + $metrics->trend(ProviderProfile::class);
        $growth['providers'] = $metrics->dailyCounts(ProviderProfile::class);
    }

    if ($canQuotes) {
        $kpis[] = ['key' => 'quote_requests', 'label' => 'Cereri de ofertă', 'total' => QuoteRequest::count(), 'href' => '/administration/quote-requests']
            + $metrics->trend(QuoteRequest::class);
        $growth['quote_requests'] = $metrics->dailyCounts(QuoteRequest::class);
    }

    if ($user->can('view dashboard')) {
        $kpis[] = ['key' => 'listings', 'label' => 'Anunțuri publicate', 'total' => Listing::where('status', 'published')->count(), 'href' => null]
            + $metrics->trend(Listing::class, fn ($query) => $query->where('status', 'published'), 'published_at');
    }

    $attention = [];

    if ($canProviders) {
        $attention[] = ['label' => 'Furnizori de aprobat', 'value' => ProviderProfile::where('status', 'pending')->count(), 'href' => '/administration/providers'];
    }

    if ($canQuotes) {
        $attention[] = ['label' => 'Cereri de moderat', 'value' => QuoteRequest::where('status', 'pending_review')->count(), 'href' => '/administration/quote-requests'];
    }

    return [
        'range' => $metrics->range(),
        'kpis' => $kpis,
        'attention' => $attention,
        'growth' => $growth ? $metrics->growth($growth) : null,
        'providerStatus' => $canProviders
            ? $metrics->statusBreakdown(ProviderProfile::class, ['active' => 'Activi', 'pending' => 'În așteptare', 'suspended' => 'Suspendați', 'rejected' => 'Respinși'])
            : [],
        'quoteStatus' => $canQuotes
            ? $metrics->statusBreakdown(QuoteRequest::class, ['open' => 'Deschise', 'pending_review' => 'De moderat', 'closed' => 'Închise', 'rejected' => 'Respinse'])
            : [],
        'topCategories' => $canQuotes ? $metrics->topCategories() : [],
        'revenue' => $user->can('view dashboard') ? $metrics->revenue() : null,
        'activity' => $metrics->activity(12, $canProviders, $canQuotes, $canUsers),
        'pendingProviders' => $canProviders
            ? ProviderProfile::where('status', 'pending')
                ->latest()
                ->take(5)
                ->with('user:id,name,email')
                ->get(['id', 'company_name', 'user_id', 'created_at'])
            : [],
    ];
}
}

if (! function_exists('homepageProps')) {
function homepageProps(): array
{
    $listings = Listing::query()
        ->where('status', 'published')
        ->with([
            'category:id,name,slug',
            'providerProfile:id,company_name,slug,logo_path',
            'county:id,name',
            'locality:id,name',
            'media' => fn ($query) => $query->where('is_cover', true)->orWhere('position', 0),
        ])
        ->withAvg('approvedReviews', 'rating')
        ->withCount('approvedReviews')
        ->orderByDesc('is_featured')
        ->orderByDesc('published_at')
        ->take(4)
        ->get()
        ->map(fn (Listing $listing) => [
            'id' => $listing->id,
            'slug' => $listing->slug,
            'title' => $listing->title,
            'category' => $listing->category->name,
            'category_slug' => $listing->category->slug,
            'price_from' => $listing->price_from,
            'price_to' => $listing->price_to,
            'price_type' => $listing->price_type,
            'is_featured' => $listing->is_featured,
            'county' => $listing->county?->name,
            'locality' => $listing->locality?->name,
            'cover_url' => $listing->media->first() ? "/storage/{$listing->media->first()->path}" : null,
            'rating' => $listing->approved_reviews_avg_rating ? round((float) $listing->approved_reviews_avg_rating, 1) : null,
            'reviews_count' => $listing->approved_reviews_count,
            'provider' => [
                'company_name' => $listing->providerProfile->company_name,
                'slug' => $listing->providerProfile->slug,
                'logo_url' => $listing->providerProfile->logoUrl(),
            ],
        ]);

    $user = auth()->user();

    return [
        'categories' => Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->withCount(['listings as listings_count' => fn ($query) => $query->where('status', 'published')])
            ->orderBy('position')
            ->get(['id', 'name', 'slug']),
        'counties' => County::orderBy('name')->get(['id', 'name']),
        'listings' => $listings,
        'favoriteListingIds' => $user ? $user->favorites()->pluck('listing_id') : [],
        'stats' => [
            'providers' => ProviderProfile::where('status', 'active')->count(),
            'categories' => Category::where('is_active', true)->count(),
            'quoteRequests' => QuoteRequest::count(),
        ],
        'quoteRequests' => QuoteRequest::where('status', 'open')
            ->with(['category:id,name'])
            ->latest()
            ->take(1)
            ->get()
            ->map(fn (QuoteRequest $quoteRequest) => [
                'id' => $quoteRequest->id,
                'message' => $quoteRequest->message,
                'event_type' => $quoteRequest->event_type,
                'category' => $quoteRequest->category?->name,
                'county' => $quoteRequest->county,
                'budget_range' => $quoteRequest->budget_range,
            ]),
    ];
}
}

Route::get('/', function () {
    $user = auth()->user();

    if ($user?->hasRole('furnizor')) {
        return redirect()->route('provider.dashboard');
    }

    if ($user?->hasRole('client')) {
        return Inertia::render('Client/Dashboard', homepageProps());
    }

    if ($user?->can('view dashboard')) {
        return Inertia::render('Dashboard', dashboardProps());
    }

    return Inertia::render('Welcome', homepageProps());
})->name('home');

Route::get('/dashboard', function () {
    return redirect('/');
})->middleware(['web', 'auth', 'can:view dashboard'])->name('dashboard');

Route::post('/impersonate/{id}', [Impersonate::class, 'impersonate'])->middleware('auth');
Route::post('/exitimpersonate', [Impersonate::class, 'exitImpersonate'])->middleware('auth');

//Route::middleware([
//    'auth:sanctum',
//    config('jetstream.auth_session'),
//    'verified',
//])->group(function () {
//    Route::get('/dashboard', function () {
//        return Inertia::render('Dashboard');
//    })->name('dashboard');
//});

require __DIR__.'/app/permissions.php';
require __DIR__.'/app/users.php';
require __DIR__.'/app/roles.php';
require __DIR__.'/app/provider.php';
require __DIR__.'/app/onboarding.php';
require __DIR__.'/app/administration-providers.php';
require __DIR__.'/app/administration-quote-requests.php';
require __DIR__.'/app/administration-reviews.php';
require __DIR__.'/app/client_auth.php';
require __DIR__.'/app/two_factor.php';
require __DIR__.'/app/listings.php';
require __DIR__.'/app/push-subscriptions.php';
require __DIR__.'/app/search.php';
