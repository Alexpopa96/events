<?php

use App\Http\Controllers\Auth\AnafLookupController;
use App\Http\Controllers\LocalitiesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'throttle:10,1'])->post('/register/anaf-lookup', AnafLookupController::class)
    ->name('register.anaf-lookup');

Route::middleware(['web', 'throttle:60,1'])->get('/localitati', LocalitiesController::class)
    ->name('localities.index');
