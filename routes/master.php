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
    FacultyExpertiseMasterController,
    ExemptionCategoryController,
    MemoTypeMasterController,
    DepartmentMasterController,
    DesignationMasterController,
    EmployeeTypeMasterController,
    EmployeeGroupMasterController,
    CasteCategoryMasterController,
    MemoConclusionMasterController,
    HostelBuildingMasterController,
    HostelFloorMasterController,
    HostelRoomMasterController
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
        Route::post('/get-states-by-country', 'getStatesByCountry')->name('get.state.by.country');
        Route::post('/get-districts-by-state', 'getDistrictsByState')->name('get.district.by.state');
        Route::post('/get-cities-by-district', 'getCitiesByDistrict')->name('get.city.by.district');
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
        Route::post('/get-states', 'getStates')->name('getStates');
        Route::post('/get-districts', 'getDistricts')->name('getDistricts');
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
  Route::prefix('course-group-type')->name('course.group.type.')->controller(CourseGroupTypeController::class)
    ->group(function () {

        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::delete('/delete/{id}', 'delete')->name('delete');

        // Ajax & Datatable
        Route::get('/grouptypeview', 'grouptypeview')->name('grouptypeview');
        Route::post('/updatestatus','updateStatus')->name('updatestatus');
    });

    Route::prefix('mdo')->name('mdo_duty_type.')->controller(MDODutyTypeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/delete', 'delete')->name('delete');
        Route::post('/changeStatus', 'changeStatus')->name('status');
    });

  
    // Faculty Type Master Routes
    Route::prefix('faculty-type-master')->name('faculty.type.master.')->controller(FacultyTypeMasterController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });

 Route::prefix('exemption-category-master')->name('exemption.category.master.')->controller(ExemptionCategoryController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/updatedata', 'updatedata')->name('updatedata');
        Route::delete('/delete/{id}', 'delete')->name('delete');
        Route::get('/getcategory', 'getcategory')->name('getcategory');

    });
    
    Route::prefix('exemption-medical-speciality-master')->name('exemption.medical.speciality.')->controller(ExemptionCategoryController::class)->group(function () {
    Route::get('/', 'medicalSpecialityIndex')->name('index');
    Route::get('/create', 'medicalSpecialityCreate')->name('create');
    Route::post('/store', 'medicalSpecialityStore')->name('store');
    Route::get('/edit/{id}', 'medicalSpecialityEdit')->name('edit');
    Route::delete('/delete/{id}', 'medicalSpecialityDelete')->name('delete');
    Route::get('/exemption_med_spec_mst', 'exemption_med_spec_mst')->name('exemption_med_spec_mst');
    Route::post('/MedSpecExupdate', 'MedSpecExupdate')->name('MedSpecExupdate');
});
Route::prefix('memo-type-master')->name('memo.type.master.')->controller(MemoTypeMasterController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::delete('/delete/{id}', 'delete')->name('delete');
});
Route::prefix('memo-conclusion-master')->name('memo.conclusion.master.')->controller(MemoConclusionMasterController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::delete('/delete/{id}', 'destroy')->name('delete');
});

    // Department Master
    Route::prefix('department-master')->name('department.master.')->controller(DepartmentMasterController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });

    // Designation Master
    Route::prefix('designation')->name('designation.')->controller(DesignationMasterController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });

    // Caste Category
    Route::prefix('caste-category')->name('caste.category.')->controller(CasteCategoryMasterController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });

    // Employee Type Master
    Route::prefix('employee-type')->name('employee.type.')->controller(EmployeeTypeMasterController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
    });

    // Employee Group Master
    Route::prefix('employee-group')->name('employee.group.')->controller(EmployeeGroupMasterController::class)->group(function(){
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
    });

    Route::prefix('exemption-medical-speciality-master')->name('exemption.medical.speciality.')->controller(ExemptionCategoryController::class)->group(function () {
        Route::get('/', 'medicalSpecialityIndex')->name('index');
        Route::get('/create', 'medicalSpecialityCreate')->name('create');
        Route::post('/store', 'medicalSpecialityStore')->name('store');
        Route::get('/edit/{id}', 'medicalSpecialityEdit')->name('edit');
        Route::delete('/delete/{id}', 'medicalSpecialityDelete')->name('delete');
    });
    Route::prefix('memo-type-master')->name('memo.type.master.')->controller(MemoTypeMasterController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit'); 
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });
    Route::prefix('memo-conclusion-master')->name('memo.conclusion.master.')->controller(MemoConclusionMasterController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::delete('/delete/{id}', 'destroy')->name('delete');
    });

    // Hostel Building Master
    Route::prefix('hostel-building-master')->name('hostel.building.')->controller(HostelBuildingMasterController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::get('/export', 'export')->name('export');
        Route::delete('/destroy/{id}', 'destroy')->name('destroy');
        // Route::get('/get-building', 'getBuilding')->name('get.building');
    });

    // Hostel Floor Master
    Route::prefix('hostel-floor-master')->name('hostel.floor.')->controller(HostelFloorMasterController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::get('/export', 'export')->name('export');
    });

    // Hostel Room Master
    Route::prefix('hostel-room-master')->name('hostel.room.')->controller(HostelRoomMasterController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
    });

});