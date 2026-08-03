<?php

use App\Http\Controllers\Administration\Providers\AnafLookup;
use App\Http\Controllers\Administration\Providers\Approve;
use App\Http\Controllers\Administration\Providers\Index;
use App\Http\Controllers\Administration\Providers\Reactivate;
use App\Http\Controllers\Administration\Providers\Reject;
use App\Http\Controllers\Administration\Providers\Show;
use App\Http\Controllers\Administration\Providers\Suspend;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:moderate providers'])
    ->prefix('administration/providers')
    ->as('administration.providers.')
    ->group(function () {
        Route::get('', Index::class)->name('index');
        Route::get('{provider}', Show::class)->name('show');
        Route::post('{provider}/approve', Approve::class)->name('approve');
        Route::post('{provider}/reject', Reject::class)->name('reject');
        Route::post('{provider}/suspend', Suspend::class)->name('suspend');
        Route::post('{provider}/reactivate', Reactivate::class)->name('reactivate');
        Route::post('{provider}/anaf-lookup', AnafLookup::class)->name('anaf-lookup');
    });
