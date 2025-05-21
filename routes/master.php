<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    LocationController
};
use App\Http\Controllers\Admin\Master\{
    FacultyTypeMasterController, 
    MDODutyTypeController, 
    CourseGroupTypeController,
    ClassSessionMasterController,
    FacultyExpertiseMasterController
};

Route::prefix('master')->name('master.')->middleware('auth')->group(function () {

    // country route
    Route::prefix('country')->name('country.')->controller(LocationController::class)->group(function () {
        Route::get('/', 'countryIndex')->name('index');
        Route::get('/create', 'countryCreate')->name('create');
        Route::post('/store', 'countryStore')->name('store');
        Route::get('/edit/{id}', 'countryEdit')->name('edit');
        Route::PUT('/update/{id}', 'countryUpdate')->name('update');
        Route::delete('/delete/{id}', 'countryDelete')->name('delete');
    });

    // state
    Route::prefix('state')->name('state.')->controller(LocationController::class)->group(function () {
        Route::get('/', 'stateIndex')->name('index');
        Route::get('/create', 'stateCreate')->name('create');
        Route::post('/store', 'stateStore')->name('store');
        Route::get('/edit/{id}', 'stateEdit')->name('edit');
        Route::post('/update/{id}', 'stateUpdate')->name('update');
        Route::delete('/delete/{id}', 'stateDelete')->name('delete');
    });

    // district route
    Route::prefix('district')->name('district.')->controller(LocationController::class)->group(function () {

        Route::get('/', 'districtIndex')->name('index');
        Route::get('/create', 'districtCreate')->name('create');
        Route::post('/store', 'districtStore')->name('store');
        Route::get('/edit/{id}', 'districtEdit')->name('edit');
        Route::post('/update/{id}', 'districtUpdate')->name('update');
        Route::delete('/delete/{id}', 'districtDelete')->name('delete');
    });

    // city route
    Route::prefix('city')->name('city.')->controller(LocationController::class)->group(function () {
        // Route::get('/', function() {

        Route::get('/', 'cityIndex')->name('index');
        Route::get('/create', 'cityCreate')->name('create');
        Route::post('/store', 'cityStore')->name('store');
        Route::get('/edit/{id}', 'cityEdit')->name('edit');
        Route::post('/update/{id}', 'cityUpdate')->name('update');
        Route::delete('/delete/{id}', 'cityDelete')->name('delete');
    });

    // Faulty Expertise Master Routes
    Route::prefix('faculty-expertise')->name('faculty.expertise.')->controller(FacultyExpertiseMasterController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });

    // Class Session Master Routes
    Route::prefix('class-session')->name('class.session.')->controller(ClassSessionMasterController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });

    // Course Group Type Master Routes
    Route::prefix('course-group-type')->name('course.group.type.')->controller(CourseGroupTypeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create',  'create')->name('create');
        Route::post('/store',  'store')->name('store');
        Route::get('/edit/{id}',  'edit')->name('edit');
        Route::delete('/delete/{id}',  'delete')->name('delete');
    });

    Route::prefix('mdo')->name('mdo_duty_type.')->controller(MDODutyTypeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });

    // Faculty Type Master Routes
    Route::prefix('faculty-type-master')->name('faculty.type.master.')->controller(FacultyTypeMasterController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });
});