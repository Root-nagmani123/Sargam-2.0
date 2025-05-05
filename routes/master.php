<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{FacultyExpertiseMasterController, LocationController, ClassSessionMasterController};

Route::prefix('master')->name('master.')->group(function () {
    
    // country route
    Route::prefix('country')->name('country.')->group(function () {    
        Route::get('/', [LocationController::class, 'countryIndex'])->name('index');
        Route::get('/create', [LocationController::class, 'countryCreate'])->name('create');
        Route::post('/store', [LocationController::class, 'countryStore'])->name('store');
        Route::get('/edit/{id}', [LocationController::class, 'countryEdit'])->name('edit');
        Route::PUT('/update/{id}', [LocationController::class, 'countryUpdate'])->name('update');
        Route::delete('/delete/{id}', [LocationController::class, 'countryDelete'])->name('delete');
    });

    // state
    Route::prefix('state')->name('state.')->group(function () {
        Route::get('/', [LocationController::class, 'stateIndex'])->name('index');
        Route::get('/create', [LocationController::class, 'stateCreate'])->name('create');
        Route::post('/store', [LocationController::class, 'stateStore'])->name('store');
        Route::get('/edit/{id}', [LocationController::class, 'stateEdit'])->name('edit');
        Route::post('/update/{id}', [LocationController::class, 'stateUpdate'])->name('update');
        Route::delete('/delete/{id}', [LocationController::class, 'stateDelete'])->name('delete');
    });

    // district route
    Route::prefix('district')->name('district.')->group(function () {

        Route::get('/', [LocationController::class, 'districtIndex'])->name('index');
        Route::get('/create', [LocationController::class, 'districtCreate'])->name('create');
        Route::post('/store', [LocationController::class, 'districtStore'])->name('store');
        Route::get('/edit/{id}', [LocationController::class, 'districtEdit'])->name('edit');
        Route::post('/update/{id}', [LocationController::class, 'districtUpdate'])->name('update');
        Route::delete('/delete/{id}', [LocationController::class, 'districtDelete'])->name('delete');
    });

    // city route
    Route::prefix('city')->name('city.')->group(function () {
        // Route::get('/', function() {

        Route::get('/', [LocationController::class, 'cityIndex'])->name('index');
        Route::get('/create', [LocationController::class, 'cityCreate'])->name('create');
        Route::post('/store', [LocationController::class, 'cityStore'])->name('store');
        Route::get('/edit/{id}', [LocationController::class, 'cityEdit'])->name('edit');
        Route::post('/update/{id}', [LocationController::class, 'cityUpdate'])->name('update');
        Route::delete('/delete/{id}', [LocationController::class, 'cityDelete'])->name('delete');
    });

    // Faulty Expertise Master Routes
    Route::prefix('faculty-expertise')->name('faculty.expertise.')->group(function () {
        Route::get('/', [FacultyExpertiseMasterController::class, 'index'])->name('index');
        Route::get('/create', [FacultyExpertiseMasterController::class, 'create'])->name('create');
        Route::post('/store', [FacultyExpertiseMasterController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [FacultyExpertiseMasterController::class, 'edit'])->name('edit');
        Route::delete('/delete/{id}', [FacultyExpertiseMasterController::class, 'delete'])->name('delete');
    });
    
    // Class Session Master Routes
    Route::prefix('class-session')->name('class.session.')->group(function () {
        Route::get('/', [ClassSessionMasterController::class, 'index'])->name('index');
        Route::get('/create', [ClassSessionMasterController::class, 'create'])->name('create');
        Route::post('/store', [ClassSessionMasterController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [ClassSessionMasterController::class, 'edit'])->name('edit');
        Route::delete('/delete/{id}', [ClassSessionMasterController::class, 'delete'])->name('delete');
    });
});