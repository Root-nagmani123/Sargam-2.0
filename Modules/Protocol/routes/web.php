<?php

use Illuminate\Support\Facades\Route;
use Modules\Protocol\Http\Controllers\ApprovalController;
use Modules\Protocol\Http\Controllers\ManagerApprovalController;
use Modules\Protocol\Http\Controllers\ProtocolRequestController;
use Modules\Protocol\Http\Controllers\Admin\{PermissionController,VehicleMasterController,DriverMasterController,VehicleTypesController,VehicleCategoryController};
use Modules\Protocol\Http\Controllers\CommonController;


Route::group(['middleware' => ['web', 'auth'], 'prefix' => 'protocol', 'as' => 'protocol.'], function () {

    /*
    |--------------------------------------------------------------------------
    | Employee routes
    |--------------------------------------------------------------------------
    */  
    Route::middleware('can:protocol.my_requests')->group(function () {
        Route::get('/', [ProtocolRequestController::class, 'dashboard'])->name('dashboard');
        Route::get('/new', [ProtocolRequestController::class, 'create'])->name('requests.create');
        Route::get('/my-requests', [ProtocolRequestController::class, 'myRequests'])->name('requests.my');
        Route::get('/requests/{protocolRequest}', [ProtocolRequestController::class, 'show'])->name('requests.show');
        Route::get('/requests/{protocolRequest}/edit', [ProtocolRequestController::class, 'edit'])->name('requests.edit');
        Route::post('/requests', [ProtocolRequestController::class, 'storeCombined'])->name('requests.store');
        Route::put('/requests/{protocolRequest}', [ProtocolRequestController::class, 'update'])->name('requests.update');
    });

    /*
    |--------------------------------------------------------------------------
    | Protocol Staff / Admin routes
    |--------------------------------------------------------------------------
    */

    // Requests routes
    Route::middleware('can:protocol.protocol_requests')->group(function () {
        Route::get('/all-requests', [ApprovalController::class, 'allRequests'])->name('requests.all');
        Route::group(['prefix' => 'approval','as' => 'approval.',], function () {
            Route::get('/queue', [ApprovalController::class, 'queue'])->name('queue');
            Route::get('/queue/{protocolRequest}', [ApprovalController::class, 'review'])->name('review');
            Route::post('/queue/{protocolRequest}/decide', [ApprovalController::class, 'decide'])->name('decide');
        });
    });

    // Driver Master routes
    Route::middleware('can:protocol.driver_master')->group(function () {
        Route::resource('/driver-master', DriverMasterController::class)->except('show')->names('driver-master');
    });

    Route::middleware('can:protocol.recommended_booking')->group(function () {
        Route::get('/recommended_booking', [ApprovalController::class, 'recommendedRequests'])->name('recommended-booking');
    });

    // Vehicle Master routes
    Route::middleware('can:protocol.vehicle_master')->group(function () {
        Route::get('vehicle-master/{id}/edit', [VehicleMasterController::class, 'edit'])->name('vehicle-master.edit');
        Route::put('vehicle-master/{id}', [VehicleMasterController::class, 'update'])->name('vehicle-master.update');
        Route::delete('vehicle-master/{id}', [VehicleMasterController::class, 'destroy'])->name('vehicle-master.destroy');
        // edit/update/destroy are the explicit routes above
        Route::resource('vehicle-master', VehicleMasterController::class)->only(['index', 'create', 'store'])->names('vehicle-master');
        // Hidden until the DBA creates vehicle_categories / vehicle_types (neither exists on staging or local; the screens 500).
        // Re-enable these two lines once the tables are in place.
        // Route::resource('vehicle-categories', VehicleCategoryController::class)->only(['index', 'store', 'update', 'destroy'])->names('vehicle-category');
        // Route::resource('vehicle-types', VehicleTypesController::class)->only(['index', 'store', 'update', 'destroy'])->names('vehicle-types');
    });

    // Permission routes
    Route::middleware('can:protocol.permissions')->group(function(){
        Route::get('/permissions', [PermissionController::class, 'permissions'])->name('permissions');
        Route::get('/permissions/employee/{employee}', [PermissionController::class, 'show']);
        Route::post('/permissions/employee/{employee}', [PermissionController::class, 'update']);
    });

    // Manager routes
    Route::group(['prefix' => 'manager', 'as' => 'manager.'], function () {
        Route::get('/queue', [ManagerApprovalController::class, 'queue'])->name('queue');
        Route::get('/queue/{protocolRequest}', [ManagerApprovalController::class, 'review'])->name('review');
        Route::post('/queue/{protocolRequest}/decide', [ManagerApprovalController::class, 'decide'])->name('decide');
    });

    /*
    |--------------------------------------------------------------------------
    | Country,State And City list routes
    |--------------------------------------------------------------------------
    */
    Route::group(['prefix' => 'protocol'],function(){
        Route::get('/countries',[CommonController::class, 'getCountries'])->name('countries');
        Route::get('/states/{country_id}',[CommonController::class, 'getStates'])->name('states');
        Route::get('/cities/{state_id}',[CommonController::class, 'getCities'])->name('cities');
    });

});


