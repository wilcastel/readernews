<?php

use Illuminate\Support\Facades\Route;
use Wilcastel\AiManager\Http\Controllers\AiConfigController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/ai-manager', [AiConfigController::class, 'index'])->name('ai-manager.index');
    Route::post('/ai-manager', [AiConfigController::class, 'store'])->name('ai-manager.store');
    Route::put('/ai-manager/{id}', [AiConfigController::class, 'update'])->name('ai-manager.update');
    Route::delete('/ai-manager/{id}', [AiConfigController::class, 'destroy'])->name('ai-manager.destroy');
    Route::post('/ai-manager/{id}/toggle', [AiConfigController::class, 'toggle'])->name('ai-manager.toggle');
});
