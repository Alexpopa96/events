<?php

use App\Http\Controllers\Auth\EmailChangeController;
use App\Http\Controllers\Auth\EmailTwoFactorChallengeController;
use App\Http\Controllers\Auth\EmailTwoFactorSettingsController;
use Illuminate\Support\Facades\Route;

// Second step of sign-in, before the user is authenticated.
Route::middleware(['web', 'guest'])->group(function () {
    Route::get('/autentificare-2-pasi', [EmailTwoFactorChallengeController::class, 'show'])->name('email-two-factor.challenge');
    Route::post('/autentificare-2-pasi', [EmailTwoFactorChallengeController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('email-two-factor.verify');
    Route::post('/autentificare-2-pasi/retrimite', [EmailTwoFactorChallengeController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('email-two-factor.resend');
});

// Enabling / disabling it from the account settings.
Route::middleware(['web', 'auth'])->prefix('user/email-two-factor')->as('email-two-factor.')->group(function () {
    Route::post('cod', [EmailTwoFactorSettingsController::class, 'send'])->middleware('throttle:5,1')->name('send');
    Route::post('confirmare', [EmailTwoFactorSettingsController::class, 'confirm'])->middleware('throttle:10,1')->name('confirm');
    Route::post('cod-dezactivare', [EmailTwoFactorSettingsController::class, 'sendDisableCode'])->middleware('throttle:5,1')->name('disable-code');
    Route::delete('/', [EmailTwoFactorSettingsController::class, 'disable'])->middleware('throttle:10,1')->name('disable');
});

// Changing the account email: a code goes to the current address first.
Route::middleware(['web', 'auth'])->post('user/email-change/cod', [EmailChangeController::class, 'send'])
    ->middleware('throttle:5,1')
    ->name('email-change.send');
