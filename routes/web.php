<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [FeedController::class, 'index'])->name('dashboard'); // Mapping /dashboard to our main view
    Route::get('/saved', [FeedController::class, 'saved'])->name('saved');
    Route::get('/favorites', [FeedController::class, 'favorites'])->name('favorites');
    Route::get('/folder/{folder}', [FeedController::class, 'folder'])->name('folder.show');
    Route::get('/feed/{feed}', [FeedController::class, 'feed'])->name('feed.show');

    Route::post('/feeds', [FeedController::class, 'store'])->name('feeds.store');
    Route::put('/feeds/{feed}', [FeedController::class, 'update'])->name('feeds.update');
    Route::delete('/feeds/{feed}', [FeedController::class, 'destroy'])->name('feeds.destroy');
    
    Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
    Route::post('/articles/{article}/fetch', [ArticleController::class, 'fetchContent'])->name('articles.fetch');
    Route::post('/articles/{article}/toggle-saved', [ArticleController::class, 'toggleSaved'])->name('articles.toggle-saved');
    Route::post('/articles/{article}/toggle-favorite', [ArticleController::class, 'toggleFavorite'])->name('articles.toggle-favorite');
    Route::post('/articles/{article}/mark-read', [ArticleController::class, 'markRead'])->name('articles.mark-read');
    
    Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
