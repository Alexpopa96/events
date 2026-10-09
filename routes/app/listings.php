<?php

use App\Http\Controllers\Categories\Events\Show as EventCategoriesShow;
use App\Http\Controllers\Categories\Index as CategoriesIndex;
use App\Http\Controllers\Categories\Show as CategoriesShow;
use App\Http\Controllers\Favorites\Index as FavoritesIndex;
use App\Http\Controllers\Listings\Index as ListingsIndex;
use App\Http\Controllers\Listings\Map as ListingsMap;
use App\Http\Controllers\Listings\RequestQuote as ListingsRequestQuote;
use App\Http\Controllers\Listings\Reviews\Store as ListingsReviewsStore;
use App\Http\Controllers\Listings\Show as ListingsShow;
use App\Http\Controllers\Listings\ToggleFavorite;
use App\Http\Controllers\Listings\TrackEvent;
use App\Http\Controllers\Messages\Index as MessagesIndex;
use App\Http\Controllers\Messages\Open as MessagesOpen;
use App\Http\Controllers\Messages\Start as MessagesStart;
use App\Http\Controllers\Messages\Store as MessagesStore;
use App\Http\Controllers\Notifications\Index as NotificationsIndex;
use App\Http\Controllers\Notifications\Read as NotificationsRead;
use App\Http\Controllers\Notifications\ReadAll as NotificationsReadAll;
use App\Http\Controllers\Providers\Index as ProvidersIndex;
use App\Http\Controllers\Providers\Show as ProvidersShow;
use App\Http\Controllers\Providers\ToggleFavorite as ProvidersToggleFavorite;
use App\Http\Controllers\QuoteRequests\Browse as QuoteRequestsBrowse;
use App\Http\Controllers\QuoteRequests\Create as QuoteRequestsCreate;
use App\Http\Controllers\QuoteRequests\Index as QuoteRequestsIndex;
use App\Http\Controllers\QuoteRequests\Offers\Accept as OffersAccept;
use App\Http\Controllers\QuoteRequests\Offers\Decline as OffersDecline;
use App\Http\Controllers\QuoteRequests\Show as QuoteRequestsShow;
use App\Http\Controllers\QuoteRequests\Store as QuoteRequestsStore;
use App\Http\Controllers\QuoteRequests\Success as QuoteRequestsSuccess;
use App\Http\Controllers\QuoteRequests\Update as QuoteRequestsUpdate;
use App\Http\Controllers\SavedSearches\Destroy as SavedSearchesDestroy;
use App\Http\Controllers\SavedSearches\Index as SavedSearchesIndex;
use App\Http\Controllers\SavedSearches\Store as SavedSearchesStore;
use App\Http\Controllers\Subscriptions\Index as SubscriptionsIndex;
use App\Support\EventTypes;
use Illuminate\Support\Facades\Route;

Route::get('categorii', CategoriesIndex::class)->name('categories.index');
Route::get('categorii/{category:slug}', CategoriesShow::class)->name('categories.show');
Route::get('categorii/{category:slug}/{county:slug}', CategoriesShow::class)->withoutScopedBindings()->name('categories.county');
Route::get('categorii/{category:slug}/{county:slug}/{localitySlug}', CategoriesShow::class)->withoutScopedBindings()->name('categories.locality');

// Event type landing pages: /nunta/fotograf, /nunta/fotograf/cluj, /nunta/fotograf/cluj/cluj-napoca
Route::prefix('{eventType}')
    ->whereIn('eventType', EventTypes::landingValues())
    ->as('events.categories.')
    ->withoutScopedBindings()
    ->group(function () {
        Route::get('{category:slug}', EventCategoriesShow::class)->name('show');
        Route::get('{category:slug}/{county:slug}', EventCategoriesShow::class)->name('county');
        Route::get('{category:slug}/{county:slug}/{localitySlug}', EventCategoriesShow::class)->name('locality');
    });

Route::get('abonamente', SubscriptionsIndex::class)->name('subscriptions.index');
Route::get('cereri-de-oferta', QuoteRequestsBrowse::class)->name('quote-requests.browse');

Route::get('furnizori', ProvidersIndex::class)->name('providers.index');
Route::get('furnizori/{providerProfile:slug}', ProvidersShow::class)->name('providers.show');

Route::middleware(['auth', 'can:manage own favorites'])->group(function () {
    Route::post('furnizori/{providerProfile:slug}/favorite', ProvidersToggleFavorite::class)->name('providers.favorite');
});

Route::prefix('anunturi')->as('listings.')->group(function () {
    Route::get('', ListingsIndex::class)->name('index');
    Route::get('harta', ListingsMap::class)->name('map');
    Route::get('{listing:slug}', ListingsShow::class)->name('show');
    Route::post('{listing:slug}/eveniment', TrackEvent::class)->name('event');

    Route::middleware(['auth', 'can:submit quote request'])->group(function () {
        Route::post('{listing:slug}/cere-oferta', ListingsRequestQuote::class)->name('request-quote');
        Route::get('{listing:slug}/mesaje', MessagesOpen::class)->name('messages.open');
        Route::post('{listing:slug}/mesaje', MessagesStart::class)->middleware('throttle:20,1')->name('messages.start');
    });

    Route::middleware(['auth', 'can:submit review'])->group(function () {
        Route::post('{listing:slug}/recenzii', ListingsReviewsStore::class)->middleware('throttle:10,1')->name('reviews.store');
    });

    Route::middleware(['auth', 'can:manage own favorites'])->group(function () {
        Route::post('{listing:slug}/favorite', ToggleFavorite::class)->name('favorite');
    });
});

Route::middleware(['auth', 'can:submit quote request'])->group(function () {
    Route::prefix('cere-oferta')->as('quote-requests.')->group(function () {
        Route::get('', QuoteRequestsCreate::class)->name('create');
        Route::post('', QuoteRequestsStore::class)->name('store');
        Route::get('{quoteRequest}/succes', QuoteRequestsSuccess::class)->name('success');
    });

    Route::get('contul-meu/cererile-mele', QuoteRequestsIndex::class)->name('quote-requests.index');
    Route::get('contul-meu/cererile-mele/{quoteRequest}', QuoteRequestsShow::class)->name('quote-requests.show');
    Route::put('contul-meu/cererile-mele/{quoteRequest}', QuoteRequestsUpdate::class)->name('quote-requests.update');
    Route::post('contul-meu/oferte/{offer}/accepta', OffersAccept::class)->name('offers.accept');
    Route::post('contul-meu/oferte/{offer}/refuza', OffersDecline::class)->name('offers.decline');
});

Route::middleware(['auth', 'can:manage own favorites'])->group(function () {
    Route::get('contul-meu/favorite', FavoritesIndex::class)->name('favorites.index');
});

Route::middleware(['auth', 'can:save search'])->prefix('contul-meu/cautari-salvate')->as('saved-searches.')->group(function () {
    Route::get('', SavedSearchesIndex::class)->name('index');
    Route::post('', SavedSearchesStore::class)->middleware('throttle:20,1')->name('store');
    Route::delete('{savedSearch}', SavedSearchesDestroy::class)->name('destroy');
});

Route::middleware(['auth', 'can:submit quote request'])->prefix('contul-meu/mesaje')->as('messages.')->group(function () {
    Route::get('{conversation?}', MessagesIndex::class)->name('index');
    Route::post('{conversation}', MessagesStore::class)->middleware('throttle:60,1')->name('store');
});

Route::middleware('auth')->prefix('contul-meu/notificari')->as('notifications.')->group(function () {
    Route::get('', NotificationsIndex::class)->name('index');
    Route::post('citite', NotificationsReadAll::class)->name('read-all');
    Route::post('{notification}', NotificationsRead::class)->name('read');
});
