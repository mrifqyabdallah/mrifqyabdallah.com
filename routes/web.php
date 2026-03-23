<?php

use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/opcache', [StatsController::class, 'opcache'])->name('opcache');

require __DIR__.'/settings.php';
require __DIR__.'/blog.php';
