<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogFeedController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/feed.rss', BlogFeedController::class)->name('blog.feed');
Route::get('/blog/stats', [StatsController::class, 'blog'])->name('stats.blog');

Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/blog/{slug}/stats', [StatsController::class, 'post'])->name('stats.post');

Route::delete('/blog/{blog}', [BlogController::class, 'destroy'])
    ->middleware('auth')
    ->name('blog.destroy');
