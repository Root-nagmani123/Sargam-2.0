<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    EstateCampusController,
    EstateAreaController,
    EstateBlockController,
    EstateUnitTypeController,
    EstateUnitController,
    EstateElectricSlabController,
    EstatePossessionController,
    EstateMeterReadingController,
    EstateBillingController
};

/*
|--------------------------------------------------------------------------
| Estate Management Routes
|--------------------------------------------------------------------------
|
| Here are all routes related to Estate Management Module
|
*/

Route::prefix('estate')->name('estate.')->middleware(['auth'])->group(function () {
    
    // Campus Management
    Route::resource('campus', EstateCampusController::class);
    
    // Area Management
    Route::resource('area', EstateAreaController::class);
    Route::get('area/campus/{campusId}', [EstateAreaController::class, 'getAreasByCampus'])
        ->name('area.by-campus');
    
    // Block Management
    Route::resource('block', EstateBlockController::class);
    
    // Unit Type Management
    Route::resource('unit-type', EstateUnitTypeController::class);
    
    // Unit Management
    Route::resource('unit', EstateUnitController::class);
    
    // Electric Slab Management
    Route::resource('electric-slab', EstateElectricSlabController::class);
    
    // Possession Management
    Route::resource('possession', EstatePossessionController::class);
    Route::post('possession/{id}/vacate', [EstatePossessionController::class, 'vacate'])
        ->name('possession.vacate');
    Route::get('possession/{id}/meter-reading', [EstatePossessionController::class, 'meterReading'])
        ->name('possession.meter-reading');
    
    // Meter Reading Management
    Route::post('meter-reading', [EstateMeterReadingController::class, 'store'])
        ->name('meter-reading.store');
    Route::delete('meter-reading/{id}', [EstateMeterReadingController::class, 'destroy'])
        ->name('meter-reading.destroy');
    
    // Billing Management
    Route::resource('billing', EstateBillingController::class)->except(['edit', 'update']);
    Route::get('billing/{id}/payment', [EstateBillingController::class, 'payment'])
        ->name('billing.payment');
    Route::post('billing/{id}/payment', [EstateBillingController::class, 'storePayment'])
        ->name('billing.payment.store');
    
});

