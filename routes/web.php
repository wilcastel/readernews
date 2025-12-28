<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\FeedController;

Route::get('/', [FeedController::class, 'index'])->name('home');
Route::get('/saved', [FeedController::class, 'saved'])->name('saved');
Route::get('/favorites', [FeedController::class, 'favorites'])->name('favorites');
Route::get('/folder/{folder}', [FeedController::class, 'folder'])->name('folder.show');
Route::get('/feed/{feed}', [FeedController::class, 'feed'])->name('feed.show');

Route::post('/feeds', [FeedController::class, 'store'])->name('feeds.store');
Route::put('/feeds/{feed}', [FeedController::class, 'update'])->name('feeds.update');
Route::delete('/feeds/{feed}', [FeedController::class, 'destroy'])->name('feeds.destroy');
Route::get('/articles/{article}', [\App\Http\Controllers\ArticleController::class, 'show'])->name('articles.show');
Route::post('/articles/{article}/fetch', [\App\Http\Controllers\ArticleController::class, 'fetchContent'])->name('articles.fetch');
Route::post('/articles/{article}/toggle-saved', [\App\Http\Controllers\ArticleController::class, 'toggleSaved'])->name('articles.toggle-saved');
Route::post('/articles/{article}/toggle-favorite', [\App\Http\Controllers\ArticleController::class, 'toggleFavorite'])->name('articles.toggle-favorite');
Route::post('/articles/{article}/mark-read', [\App\Http\Controllers\ArticleController::class, 'markRead'])->name('articles.mark-read');
Route::post('/folders', [\App\Http\Controllers\FolderController::class, 'store'])->name('folders.store');
Route::delete('/folders/{folder}', [\App\Http\Controllers\FolderController::class, 'destroy'])->name('folders.destroy');
