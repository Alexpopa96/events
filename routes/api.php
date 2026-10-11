<?php

use App\Http\Controllers\Weather\WeatherReadingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('weather')->group(function () {
    Route::get('/', [WeatherReadingController::class, 'index'])->name('api.weather.index');
    Route::post('/sync', [WeatherReadingController::class, 'sync'])->name('api.weather.sync');
});
