<?php

use App\Http\Controllers\Administration\QuoteRequests\Approve;
use App\Http\Controllers\Administration\QuoteRequests\Index;
use App\Http\Controllers\Administration\QuoteRequests\Reject;
use App\Http\Controllers\Administration\QuoteRequests\Show;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:moderate quote requests'])
    ->prefix('administration/quote-requests')
    ->as('administration.quote-requests.')
    ->group(function () {
        Route::get('', Index::class)->name('index');
        Route::get('{quoteRequest}', Show::class)->name('show');
        Route::post('{quoteRequest}/approve', Approve::class)->name('approve');
        Route::post('{quoteRequest}/reject', Reject::class)->name('reject');
    });
