<?php

######################################
// DEVELOPER INFO 
// => MANJEET CHNAD (शैतान 💀)
// => manjeetchand01@gmail.com
// => +91999729452
// => 23 July 2026
// => Everthing comes with a Cost 😉
######################################

use Faker\Provider\Lorem; 
use Illuminate\Support\Facades\Route;
use Modules\Protocol\Http\Controllers\ApprovalController;
use Modules\Protocol\Http\Controllers\HistoryLogController;
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
    // Route::middleware('can:protocol.my_requests')->group(function () {
        Route::get('/', [ProtocolRequestController::class, 'dashboard'])->name('dashboard');
        Route::get('/new', [ProtocolRequestController::class, 'create'])->name('requests.create');
        Route::get('/my-requests', [ProtocolRequestController::class, 'myRequests'])->name('requests.my');
        Route::get('/requests/{protocolRequest}', [ProtocolRequestController::class, 'show'])->name('requests.show');
        Route::post('/requests', [ProtocolRequestController::class, 'storeCombined'])->name('requests.store');
    // });

    /*
    |--------------------------------------------------------------------------
    | Protocol Staff routes
    |--------------------------------------------------------------------------
    */
    // Route::middleware('any.permission:protocol.all_booking,protocol.ticket_booking,protocol.vehicle_booking,protocol.guest_house_booking')->group(function () {
        Route::get('/all-requests', [ApprovalController::class, 'allRequests'])->name('requests.all');
        Route::group(['prefix' => 'approval','as' => 'approval.',], function () {
            Route::get('/queue', [ApprovalController::class, 'queue'])->name('queue');
            Route::get('/queue/{protocolRequest}', [ApprovalController::class, 'review'])->name('review');
            Route::post('/queue/{protocolRequest}/decide', [ApprovalController::class, 'decide'])->name('decide');
        });
    // });

    /*
    |--------------------------------------------------------------------------
    | Protocol Admin routes
    |--------------------------------------------------------------------------
    */
    Route::resource('/driver-master', DriverMasterController::class)->names('driver-master')->middleware('can:protocol.driver_master');
    Route::middleware('can:protocol.vehicle_master')->group(function () {
        Route::get('vehicle-master/{id}/edit', [VehicleMasterController::class, 'edit'])->name('vehicle-master.edit');
        Route::put('vehicle-master/{id}', [VehicleMasterController::class, 'update'])->name('vehicle-master.update');
        Route::delete('vehicle-master/{id}', [VehicleMasterController::class, 'destroy'])->name('vehicle-master.destroy');
        Route::resource('vehicle-master', VehicleMasterController::class)->names('vehicle-master');
        Route::resource('vehicle-categories', VehicleCategoryController::class)->names('vehicle-category');
        Route::resource('vehicle-types', VehicleTypesController::class)->names('vehicle-types');
    });
    Route::middleware('can:protocol.permissions')->group(function(){
        Route::get('/permissions', [PermissionController::class, 'permissions'])->name('permissions');
        Route::get('/permissions/employee/{employee}', [PermissionController::class, 'show']);
        Route::post('/permissions/employee/{employee}', [PermissionController::class, 'update']);
    });

    /*
    |--------------------------------------------------------------------------
    | Manager / recommended-staff routes
    |--------------------------------------------------------------------------
    */
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


