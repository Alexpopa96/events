<?php

use App\Http\Controllers\Search\Suggest;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'throttle:60,1'])->get('/cautare/sugestii', Suggest::class)->name('search.suggest');
