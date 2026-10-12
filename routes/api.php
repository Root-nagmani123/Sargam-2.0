<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api\KnowledgeFeedController;
use App\Http\Middleware\EnsureKnowledgeFeedKey;


Route::name('api.')->group(function() {
    
    Route::get('get-building', [ApiController::class, 'getBuilding'])->name('get.buildings');

    // ज्ञानकोश (Gyankosh) pull feed — see App\Http\Controllers\Api\KnowledgeFeedController.
    Route::middleware(EnsureKnowledgeFeedKey::class)
        ->prefix('gyankosh')
        ->name('gyankosh.')
        ->group(function () {
            Route::get('published-notices', [KnowledgeFeedController::class, 'index'])
                ->name('published-notices');
            Route::get('published-notices/{notice}/download', [KnowledgeFeedController::class, 'file'])
                ->whereNumber('notice')
                ->name('published-notices.download');
        });
});