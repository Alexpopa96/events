<?php

use App\Http\Controllers\PushSubscriptions\Destroy;
use App\Http\Controllers\PushSubscriptions\Store;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:20,1'])->prefix('push-subscriptions')->as('push-subscriptions.')->group(function () {
    Route::post('', Store::class)->name('store');
    Route::delete('', Destroy::class)->name('destroy');
});
