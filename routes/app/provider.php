<?php

use App\Http\Controllers\Provider\DashboardController;
use App\Http\Controllers\Provider\Leads\Index as LeadsIndex;
use App\Http\Controllers\Provider\Leads\MarkContacted;
use App\Http\Controllers\Provider\Listings\Create as ListingsCreate;
use App\Http\Controllers\Provider\Listings\Destroy as ListingsDestroy;
use App\Http\Controllers\Provider\Listings\Edit as ListingsEdit;
use App\Http\Controllers\Provider\Listings\Index as ListingsIndex;
use App\Http\Controllers\Provider\Listings\Media\Destroy as ListingsMediaDestroy;
use App\Http\Controllers\Provider\Listings\Media\Reorder as ListingsMediaReorder;
use App\Http\Controllers\Provider\Listings\Media\SetCover as ListingsMediaSetCover;
use App\Http\Controllers\Provider\Listings\Media\Store as ListingsMediaStore;
use App\Http\Controllers\Provider\Listings\Store as ListingsStore;
use App\Http\Controllers\Provider\Listings\Update as ListingsUpdate;
use App\Http\Controllers\Provider\PendingApproval;
use App\Http\Controllers\Provider\Profile\Edit as ProfileEdit;
use App\Http\Controllers\Provider\Profile\Update as ProfileUpdate;
use App\Http\Controllers\Provider\Subscription\Index as SubscriptionIndex;
use App\Http\Controllers\Provider\Subscription\Update as SubscriptionUpdate;
use App\Http\Middleware\EnsureProviderIsApproved;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:view provider dashboard'])
    ->prefix('furnizor')
    ->as('provider.')
    ->group(function () {
        Route::get('in-asteptare', PendingApproval::class)->name('pending');

        Route::middleware(EnsureProviderIsApproved::class)->group(function () {
            Route::get('dashboard', DashboardController::class)->name('dashboard');

            Route::prefix('anunturi')->as('listings.')->group(function () {
                Route::get('', ListingsIndex::class)->name('index');
                Route::get('creeaza', ListingsCreate::class)->name('create');
                Route::post('', ListingsStore::class)->name('store');
                Route::get('{listing}/editeaza', ListingsEdit::class)->name('edit');
                Route::put('{listing}', ListingsUpdate::class)->name('update');
                Route::delete('{listing}', ListingsDestroy::class)->name('destroy');

                Route::prefix('{listing}/media')->as('media.')->group(function () {
                    Route::post('', ListingsMediaStore::class)->name('store');
                    Route::patch('reorder', ListingsMediaReorder::class)->name('reorder');
                    Route::patch('{media}/cover', ListingsMediaSetCover::class)->name('cover');
                    Route::delete('{media}', ListingsMediaDestroy::class)->name('destroy');
                });
            });

            Route::prefix('profil')->as('profile.')->group(function () {
                Route::get('', ProfileEdit::class)->name('edit');
                Route::post('', ProfileUpdate::class)->name('update');
            });

            Route::prefix('abonament')->as('subscription.')->group(function () {
                Route::get('', SubscriptionIndex::class)->name('index');
                Route::put('', SubscriptionUpdate::class)->name('update');
            });

            Route::prefix('cereri-oferta')->as('leads.')->group(function () {
                Route::get('', LeadsIndex::class)->name('index');
                Route::post('{lead}/contactat', MarkContacted::class)->name('contacted');
            });
        });
    });
