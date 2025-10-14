<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;


Route::name('api.')->group(function() {
    
    Route::get('get-building', [ApiController::class, 'getBuilding'])->name('get.buildings');
});