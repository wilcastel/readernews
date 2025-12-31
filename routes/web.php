<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [FeedController::class, 'index'])->name('dashboard'); // Mapping /dashboard to our main view
    Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search');
    
    // Notes
    Route::get('/notes', [\App\Http\Controllers\NoteController::class, 'index'])->name('notes.index');
    Route::post('/articles/{article}/notes', [\App\Http\Controllers\NoteController::class, 'store'])->name('notes.store');
    Route::delete('/notes/{note}', [\App\Http\Controllers\NoteController::class, 'destroy'])->name('notes.destroy');
    
    // Content Generation
    Route::post('/generate-content', [\App\Http\Controllers\ContentGenerationController::class, 'generate'])->name('ai.generate');

    Route::get('/saved', [FeedController::class, 'saved'])->name('saved');
    Route::get('/favorites', [FeedController::class, 'favorites'])->name('favorites');
    Route::get('/folder/{folder}', [FeedController::class, 'folder'])->name('folder.show');
    Route::get('/feed/{feed}', [FeedController::class, 'feed'])->name('feed.show');
    Route::post('/feed/{feed}/check', [FeedController::class, 'check'])->name('feed.check');
    Route::post('/feed/{feed}/diagnose', [FeedController::class, 'diagnose'])->name('feed.diagnose');
    
    // YouTube Import
    Route::post('/youtube/import', [\App\Http\Controllers\YouTubeImportController::class, 'store'])->name('youtube.import');
    Route::post('/articles/{article}/summarize', [\App\Http\Controllers\YouTubeImportController::class, 'summarize'])->name('articles.summarize');

    Route::post('/feeds', [FeedController::class, 'store'])->name('feeds.store');
    Route::put('/feeds/{feed}', [FeedController::class, 'update'])->name('feeds.update');
    Route::post('/feeds/refresh-all', [FeedController::class, 'refreshAll'])->name('feeds.refresh-all');
    Route::post('/feeds/{feed}/refresh', [FeedController::class, 'refresh'])->name('feeds.refresh');
    Route::delete('/feeds/{feed}', [FeedController::class, 'destroy'])->name('feeds.destroy');
    Route::post('/feeds/{feed}/mark-all-read', [FeedController::class, 'markAllRead'])->name('feeds.mark-all-read');
    
    // Feed Management
    Route::get('/feeds/manage', [FeedController::class, 'manage'])->name('feeds.manage');
    Route::put('/feeds/{feed}/toggle-mode', [FeedController::class, 'toggleMode'])->name('feeds.toggle-mode');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/fetch-models', [SettingsController::class, 'fetchModels'])->name('settings.fetch-models');
    Route::post('/settings/test-model', [SettingsController::class, 'testModel'])->name('settings.test-model');
    
    Route::get('/tags/{tag:slug}', [TagController::class, 'show'])->name('tags.show');
    Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
    Route::post('/articles/{article}/toggle-tag', [TagController::class, 'toggle'])->name('articles.toggle-tag');
    
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
