<?php

use App\Http\Controllers\Impersonate;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ProviderProfile;
use App\Models\QuoteRequest;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

function dashboardProps(): array
{
    $user = auth()->user();

    $stats = [];

    if ($user->can('view users')) {
        $stats[] = ['label' => 'Utilizatori', 'value' => User::count(), 'href' => '/administration/users'];
    }

    if ($user->can('moderate providers')) {
        $stats[] = ['label' => 'Furnizori activi', 'value' => ProviderProfile::where('status', 'active')->count(), 'href' => '/administration/providers'];
        $stats[] = ['label' => 'Furnizori în așteptare', 'value' => ProviderProfile::where('status', 'pending')->count(), 'href' => '/administration/providers'];
    }

    if ($user->can('view dashboard')) {
        $stats[] = ['label' => 'Anunțuri publicate', 'value' => Listing::where('status', 'published')->count(), 'href' => null];
        $stats[] = ['label' => 'Cereri de ofertă deschise', 'value' => QuoteRequest::where('status', 'open')->count(), 'href' => null];
    }

    return [
        'stats' => $stats,
        'pendingProviders' => $user->can('moderate providers')
            ? ProviderProfile::where('status', 'pending')
                ->latest()
                ->take(5)
                ->with('user:id,name,email')
                ->get(['id', 'company_name', 'user_id', 'created_at'])
            : [],
    ];
}

Route::get('/', function () {
    $user = auth()->user();

    if ($user?->hasRole('furnizor')) {
        return redirect()->route('provider.dashboard');
    }

    if ($user?->can('view dashboard')) {
        return Inertia::render('Dashboard', dashboardProps());
    }

    return Inertia::render('Welcome', [
        'categories' => Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('position')
            ->get(['id', 'name', 'slug']),
        'plans' => SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get(['name', 'slug', 'description', 'price', 'currency', 'billing_period', 'max_listings', 'max_photos_per_listing', 'max_videos_per_listing', 'allows_featured_placement', 'features']),
        'stats' => [
            'providers' => ProviderProfile::where('status', 'active')->count(),
            'categories' => Category::where('is_active', true)->count(),
        ],
    ]);
})->name('home');

Route::get('/dashboard', function () {
    if (auth()->user()?->hasRole('furnizor')) {
        return redirect()->route('provider.dashboard');
    }

    return Inertia::render('Dashboard', dashboardProps());
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
require __DIR__.'/app/client_auth.php';
