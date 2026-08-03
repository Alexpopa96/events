<?php

use App\Http\Controllers\Auth\ClientRegistrationController;
use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'guest'])->group(function () {
    Route::get('/register/client', [ClientRegistrationController::class, 'create'])
        ->name('register.client');
    Route::post('/register/client', [ClientRegistrationController::class, 'store'])
        ->name('register.client.store');

    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])
        ->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
        ->name('auth.google.callback');
});
