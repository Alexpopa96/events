<?php

use App\Http\Controllers\Administration\Reviews\Approve;
use App\Http\Controllers\Administration\Reviews\Index;
use App\Http\Controllers\Administration\Reviews\Reject;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:moderate reviews'])
    ->prefix('administration/reviews')
    ->as('administration.reviews.')
    ->group(function () {
        Route::get('', Index::class)->name('index');
        Route::post('{review}/approve', Approve::class)->name('approve');
        Route::post('{review}/reject', Reject::class)->name('reject');
    });
