<?php

use App\Http\Controllers\LinkController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| The short-code routes use a strict {6} regex so the catch-all redirect
| route at the bottom never swallows real paths like /shorten, /stats/...,
| or /r/...
|
*/

Route::get('/', [LinkController::class, 'create'])
    ->name('links.create');

Route::post('/shorten', [LinkController::class, 'store'])
    ->middleware('throttle:create-link')
    ->name('links.store');

Route::get('/r/{short_code}', [LinkController::class, 'show'])
    ->where('short_code', '[A-Za-z0-9]{6}')
    ->name('links.show');

Route::get('/stats/{short_code}', [StatsController::class, 'show'])
    ->where('short_code', '[A-Za-z0-9]{6}')
    ->name('links.stats');

Route::get('/{short_code}', RedirectController::class)
    ->middleware('log.clicks')
    ->where('short_code', '[A-Za-z0-9]{6}')
    ->name('links.redirect');
