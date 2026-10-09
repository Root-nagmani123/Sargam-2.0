<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api\KnowledgeFeedController;
use App\Http\Middleware\EnsureKnowledgeFeedKey;


Route::name('api.')->group(function() {
    
    Route::get('get-building', [ApiController::class, 'getBuilding'])->name('get.buildings');

    // ज्ञानकोश pull feed — see App\Http\Controllers\Api\KnowledgeFeedController.
    Route::middleware(EnsureKnowledgeFeedKey::class)
        ->prefix('knowledge')
        ->name('knowledge.')
        ->group(function () {
            Route::get('notices', [KnowledgeFeedController::class, 'index'])->name('notices');
            Route::get('notices/{notice}/file', [KnowledgeFeedController::class, 'file'])
                ->whereNumber('notice')
                ->name('notices.file');
        });
});