<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogFeedController;
use Illuminate\Support\Facades\Route;

Route::get('/blog/feed.rss', BlogFeedController::class)->name('blog.feed');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::delete('/blog/{blog}', [BlogController::class, 'destroy'])
    ->middleware('auth')
    ->name('blog.destroy');
