<?php

use App\Http\Controllers\Categories\Index as CategoriesIndex;
use App\Http\Controllers\Categories\Show as CategoriesShow;
use App\Http\Controllers\Favorites\Index as FavoritesIndex;
use App\Http\Controllers\Listings\Index as ListingsIndex;
use App\Http\Controllers\Listings\RequestQuote as ListingsRequestQuote;
use App\Http\Controllers\Listings\Show as ListingsShow;
use App\Http\Controllers\Listings\ToggleFavorite;
use App\Http\Controllers\Listings\TrackEvent;
use App\Http\Controllers\QuoteRequests\Create as QuoteRequestsCreate;
use App\Http\Controllers\QuoteRequests\Index as QuoteRequestsIndex;
use App\Http\Controllers\QuoteRequests\Show as QuoteRequestsShow;
use App\Http\Controllers\QuoteRequests\Store as QuoteRequestsStore;
use App\Http\Controllers\QuoteRequests\Success as QuoteRequestsSuccess;
use App\Http\Controllers\QuoteRequests\Update as QuoteRequestsUpdate;
use App\Http\Controllers\Providers\Index as ProvidersIndex;
use App\Http\Controllers\Providers\Show as ProvidersShow;
use Illuminate\Support\Facades\Route;

Route::get('categorii', CategoriesIndex::class)->name('categories.index');
Route::get('categorii/{category:slug}', CategoriesShow::class)->name('categories.show');

Route::get('furnizori', ProvidersIndex::class)->name('providers.index');
Route::get('furnizori/{providerProfile:slug}', ProvidersShow::class)->name('providers.show');

Route::prefix('anunturi')->as('listings.')->group(function () {
    Route::get('', ListingsIndex::class)->name('index');
    Route::get('{listing:slug}', ListingsShow::class)->name('show');
    Route::post('{listing:slug}/eveniment', TrackEvent::class)->name('event');

    Route::middleware(['auth', 'can:submit quote request'])->group(function () {
        Route::post('{listing:slug}/cere-oferta', ListingsRequestQuote::class)->name('request-quote');
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
});

Route::middleware(['auth', 'can:manage own favorites'])->group(function () {
    Route::get('contul-meu/favorite', FavoritesIndex::class)->name('favorites.index');
});
