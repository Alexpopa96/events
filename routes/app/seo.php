<?php

use App\Http\Controllers\Seo\Robots;
use App\Http\Controllers\Seo\Sitemap;
use Illuminate\Support\Facades\Route;

Route::get('sitemap.xml', Sitemap::class)->name('sitemap');
Route::get('robots.txt', Robots::class)->name('robots');
