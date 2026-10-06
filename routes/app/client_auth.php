<?php

use App\Http\Controllers\Auth\ClientRegistrationController;
use App\Http\Controllers\Auth\EmailVerificationCodeController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginIdentifyController;
use App\Http\Controllers\Auth\PasswordResetCodeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'guest'])->group(function () {
    Route::get('/register/client', [ClientRegistrationController::class, 'create'])
        ->name('register.client');
    Route::post('/register/client', [ClientRegistrationController::class, 'store'])
        ->name('register.client.store');

    Route::post('/login/identify', LoginIdentifyController::class)
        ->middleware('throttle:10,1')
        ->name('login.identify');

    Route::post('/forgot-password/code', [PasswordResetCodeController::class, 'send'])
        ->middleware('throttle:5,1')
        ->name('password.code.send');
    Route::post('/forgot-password/code/verify', [PasswordResetCodeController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('password.code.verify');

    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])
        ->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
        ->name('auth.google.callback');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/email/verify', [EmailVerificationCodeController::class, 'show'])
        ->name('verification.notice');
    Route::post('/email/verify', [EmailVerificationCodeController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('verification.code.verify');
    Route::post('/email/verify/resend', [EmailVerificationCodeController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.code.resend');
});
