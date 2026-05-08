<?php

use App\Http\Controllers\FC\{
    FcActivityController,
    FcActivityDepartmentController,
    FcActivityHomeController,
    FcActivityMasterManageController,
    FcActivityMedicalController,
    FcActivityReportController,
    FcActivityStatusController,
    FcTravelArrivalSlotController,
    RegistrationStep1Controller,
    RegistrationStep2Controller,
    RegistrationStep3Controller,
    BankDetailsController,
    DocumentUploadController,
    RegistrationStatusController,
    FcJoiningAttendanceController,
    FormBuilderController,
    FormManagementController,
    GenericFormController,
    ReportController,
    TravelPlanController,
    TravelPlanReportController,
};
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────────
// FC OFFICER TRAINEE ROUTES (uses existing auth - user_credentials & roles)
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth'])->prefix('fc-reg')->name('fc-reg.')->group(function () {

    Route::get('/dashboard', [RegistrationStep1Controller::class, 'dashboard'])->name('dashboard');

    Route::prefix('registration')->name('registration.')->group(function () {

        Route::get('/step1',  [RegistrationStep1Controller::class, 'showStep1'])->name('step1');
        Route::post('/step1', [RegistrationStep1Controller::class, 'saveStep1'])->name('step1.save');

        Route::get('/step2',  [RegistrationStep2Controller::class, 'showStep2'])->name('step2');
        Route::post('/step2', [RegistrationStep2Controller::class, 'saveStep2'])->name('step2.save');

        Route::get('/step3',  [RegistrationStep3Controller::class, 'showStep3'])->name('step3');
        Route::post('/step3/qualifications',   [RegistrationStep3Controller::class, 'saveQualifications'])->name('step3.qualifications');
        Route::post('/step3/higher-education', [RegistrationStep3Controller::class, 'saveHigherEducation'])->name('step3.higher-education');
        Route::post('/step3/employment',       [RegistrationStep3Controller::class, 'saveEmployment'])->name('step3.employment');
        Route::post('/step3/spouse',           [RegistrationStep3Controller::class, 'saveSpouse'])->name('step3.spouse');
        Route::post('/step3/languages',        [RegistrationStep3Controller::class, 'saveLanguages'])->name('step3.languages');
        Route::post('/step3/hobbies',          [RegistrationStep3Controller::class, 'saveHobbies'])->name('step3.hobbies');
        Route::post('/step3/distinctions',     [RegistrationStep3Controller::class, 'saveDistinctions'])->name('step3.distinctions');
        Route::post('/step3/sports',           [RegistrationStep3Controller::class, 'saveSports'])->name('step3.sports');
        Route::post('/step3/pre-medical-history', [RegistrationStep3Controller::class, 'savePreMedicalHistory'])->name('step3.pre-medical-history');
        Route::post('/step3/module',           [RegistrationStep3Controller::class, 'saveModuleChoice'])->name('step3.module');
        Route::post('/step3/group/{group}',    [RegistrationStep3Controller::class, 'saveGroup'])->name('step3.save-group');

        Route::get('/bank',  [BankDetailsController::class, 'show'])->name('bank');
        Route::post('/bank', [BankDetailsController::class, 'save'])->name('bank.save');

        Route::get('/travel',         [TravelPlanController::class, 'show'])->name('travel');
        Route::post('/travel',        [TravelPlanController::class, 'save'])->name('travel.save');
        Route::post('/travel/submit', [TravelPlanController::class, 'submit'])->name('travel.submit');

        Route::get('/documents',               [DocumentUploadController::class, 'show'])->name('documents');
        Route::post('/documents/{id}/upload',  [DocumentUploadController::class, 'upload'])->name('documents.upload');
        Route::delete('/documents/{id}/delete',[DocumentUploadController::class, 'delete'])->name('documents.delete');
        Route::post('/documents/final-submit', [DocumentUploadController::class, 'finalSubmit'])->name('documents.submit');

        Route::get('/status',   [RegistrationStatusController::class, 'show'])->name('status');
        Route::post('/confirm', [RegistrationStatusController::class, 'confirm'])->name('confirm');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// FC REG ADMIN ROUTES
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth'])->prefix('fc-reg/admin')->name('fc-reg.admin.')->group(function () {

    // ── Form Builder ─────────────────────────────────────────────────
    Route::prefix('form-builder')->name('form-builder.')->group(function () {
        Route::get('/',                       [FormBuilderController::class, 'index'])->name('index');
        Route::get('/steps/{step}',           [FormBuilderController::class, 'editStep'])->name('step');
        Route::put('/steps/{step}',           [FormBuilderController::class, 'updateStep'])->name('step.update');
        Route::get('/steps/{step}/preview',   [FormBuilderController::class, 'preview'])->name('preview');

        // Field CRUD
        Route::post('/steps/{step}/fields',   [FormBuilderController::class, 'storeField'])->name('field.store');
        Route::put('/fields/{field}',         [FormBuilderController::class, 'updateField'])->name('field.update');
        Route::delete('/fields/{field}',      [FormBuilderController::class, 'deleteField'])->name('field.delete');
        Route::post('/fields/reorder',        [FormBuilderController::class, 'reorderFields'])->name('field.reorder');

        // Group CRUD
        Route::post('/steps/{step}/groups',   [FormBuilderController::class, 'storeGroup'])->name('group.store');
        Route::put('/groups/{group}',         [FormBuilderController::class, 'updateGroup'])->name('group.update');
        Route::delete('/groups/{group}',      [FormBuilderController::class, 'deleteGroup'])->name('group.delete');

        // Group Field CRUD
        Route::post('/groups/{group}/fields', [FormBuilderController::class, 'storeGroupField'])->name('group-field.store');
        Route::put('/group-fields/{field}',   [FormBuilderController::class, 'updateGroupField'])->name('group-field.update');
        Route::delete('/group-fields/{field}',[FormBuilderController::class, 'deleteGroupField'])->name('group-field.delete');
        Route::post('/group-fields/reorder',  [FormBuilderController::class, 'reorderGroupFields'])->name('group-field.reorder');

        // Document Master CRUD (documents step)
        Route::post('/doc-masters',           [FormBuilderController::class, 'storeDocMaster'])->name('doc-master.store');
        Route::put('/doc-masters/{doc}',      [FormBuilderController::class, 'updateDocMaster'])->name('doc-master.update');
        Route::delete('/doc-masters/{doc}',   [FormBuilderController::class, 'deleteDocMaster'])->name('doc-master.delete');
        Route::post('/doc-masters/reorder',   [FormBuilderController::class, 'reorderDocMasters'])->name('doc-master.reorder');
    });

    Route::prefix('joining')->name('joining.')->group(function () {
        Route::get('/attendance/{hostel}',       [FcJoiningAttendanceController::class, 'showHostelList'])->name('hostel');
        Route::post('/attendance/{hostel}',      [FcJoiningAttendanceController::class, 'markAttendance'])->name('mark');
        Route::post('/attendance/{hostel}/bulk', [FcJoiningAttendanceController::class, 'bulkMark'])->name('bulk');
        Route::get('/medical/{username}',        [FcJoiningAttendanceController::class, 'showMedicalForm'])->name('medical');
        Route::post('/medical/{username}',       [FcJoiningAttendanceController::class, 'saveMedicalDetails'])->name('medical.save');
    });

    // ── Form Management (Create / Edit / Delete forms) ───────────────
    // ── Post-arrival setup (coordinators: departments + activity master CRUD)
    Route::prefix('activity-setup')->middleware(['fc.activity.coordinator'])->name('activity-setup.')->group(function () {
        Route::get('departments/data', [FcActivityDepartmentController::class, 'dataTable'])->name('departments.data');
        Route::get('masters/data', [FcActivityMasterManageController::class, 'dataTable'])->name('masters.data');
        Route::resource('departments', FcActivityDepartmentController::class)->except(['show', 'create', 'edit']);
        Route::resource('masters', FcActivityMasterManageController::class)->except(['show', 'create', 'edit']);
    });

    Route::prefix('forms')->name('forms.')->group(function () {
        Route::get('/',                        [FormManagementController::class, 'index'])->name('index');
        Route::get('/create',                  [FormManagementController::class, 'create'])->name('create');
        Route::post('/',                       [FormManagementController::class, 'store'])->name('store');
        Route::get('/{form}/edit',             [FormManagementController::class, 'edit'])->name('edit');
        Route::put('/{form}',                  [FormManagementController::class, 'update'])->name('update');
        Route::delete('/{form}',               [FormManagementController::class, 'destroy'])->name('destroy');

        // API: get columns for a table
        Route::get('/api/table-columns',       [FormManagementController::class, 'getTableColumns'])->name('api.table-columns');

        // Step CRUD within a form
        Route::post('/{form}/steps',           [FormManagementController::class, 'storeStep'])->name('step.store');
        Route::put('/steps/{step}',            [FormManagementController::class, 'updateStep'])->name('step.update');
        Route::delete('/steps/{step}',         [FormManagementController::class, 'deleteStep'])->name('step.delete');
        Route::post('/steps/reorder',          [FormManagementController::class, 'reorderSteps'])->name('step.reorder');
    });

    // ── FC Post-Arrival Activities ───────────────────────────────────────
    Route::prefix('activities')->name('activities.')->group(function () {
        Route::get('/', [FcActivityHomeController::class, 'index'])->name('index');

        Route::post('/', [FcActivityController::class, 'store'])->name('store');

        Route::prefix('ajax')->name('ajax.')->group(function () {
            Route::get('/courses', [FcActivityHomeController::class, 'ajaxCourses'])->name('courses');
            Route::get('/ots', [FcActivityHomeController::class, 'ajaxOts'])->name('ots');
            Route::get('/ot-name', [FcActivityHomeController::class, 'ajaxOtName'])->name('ot-name');
            Route::get('/house', [FcActivityHomeController::class, 'ajaxHouse'])->name('house');
            Route::get('/activities', [FcActivityHomeController::class, 'ajaxActivities'])->name('activities');
        });

        Route::prefix('status')->name('status.')->group(function () {
            Route::get('/', [FcActivityStatusController::class, 'picker'])->name('index');
            Route::get('/grid/{deptCode}', [FcActivityStatusController::class, 'departmentGrid'])->name('grid');
            Route::get('/matrix', [FcActivityStatusController::class, 'matrix'])
                ->middleware(['fc.activity.matrix'])
                ->name('matrix');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/summary', [FcActivityReportController::class, 'summary'])->name('summary');
            Route::get('/by-activity/{menuid}', [FcActivityReportController::class, 'byActivity'])->name('by-activity');
            Route::get('/not-joined', [FcActivityReportController::class, 'notJoined'])->name('not-joined');
            Route::get('/service-wise', [FcActivityReportController::class, 'serviceWise'])->name('service-wise');
        });

        Route::prefix('medical')->name('medical.')->group(function () {
            Route::get('/', [FcActivityMedicalController::class, 'index'])->name('index');
            Route::get('/data', [FcActivityMedicalController::class, 'dataTable'])->name('data');
            Route::get('/export/print', [FcActivityMedicalController::class, 'exportPrint'])->name('export.print');
            Route::get('/export/pdf', [FcActivityMedicalController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/export/excel', [FcActivityMedicalController::class, 'exportExcel'])->name('export.excel');
            Route::post('/consultation', [FcActivityMedicalController::class, 'updateConsultation'])->name('consultation');
            Route::get('/pre-history', [FcActivityMedicalController::class, 'preHistoryPreview'])->name('pre-history');
            Route::get('/report', [FcActivityMedicalController::class, 'show'])->name('show');
            Route::post('/upload', [FcActivityMedicalController::class, 'upload'])->name('upload');
        });

        Route::put('/{activityId}', [FcActivityController::class, 'update'])->name('update');
        Route::delete('/{activityId}', [FcActivityController::class, 'destroy'])->name('destroy');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// GENERIC FORM ROUTES (any logged-in user filling a dynamic form)
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth'])->prefix('fc-reg/forms')->name('fc-reg.forms.')->group(function () {
    Route::get('/{form}',                    [GenericFormController::class, 'formDashboard'])->name('dashboard');
    Route::get('/{form}/step/{step}',        [GenericFormController::class, 'showStep'])->name('step');
    Route::post('/{form}/step/{step}',       [GenericFormController::class, 'saveStep'])->name('step.save');
    Route::post('/{form}/group/{group}',     [GenericFormController::class, 'saveGroup'])->name('group.save');
});

// ── FC Travel plans (admin) ────────────────────────────────────
Route::middleware(['auth'])->prefix('admin/travel')->name('admin.travel.')->group(function () {
    Route::get('/',                    [TravelPlanReportController::class, 'index'])->name('index');
    Route::get('/student/{username}',[TravelPlanReportController::class, 'show'])->name('show');
    Route::get('/student/{username}/edit', [TravelPlanReportController::class, 'edit'])->name('edit');
    Route::put('/student/{username}', [TravelPlanReportController::class, 'update'])->name('update');
    Route::get('/export/joining',   [TravelPlanReportController::class, 'exportJoiningReport'])->name('export.joining');
    Route::prefix('slots')->name('slots.')->group(function () {
        Route::get('/',              [FcTravelArrivalSlotController::class, 'index'])->name('index');
        Route::post('/',              [FcTravelArrivalSlotController::class, 'store'])->name('store');
        Route::put('/{slot}',         [FcTravelArrivalSlotController::class, 'update'])->name('update');
        Route::delete('/{slot}',     [FcTravelArrivalSlotController::class, 'destroy'])->name('destroy');
    });
});

// ── Report Routes ─────────────────────────────────────────────
Route::middleware(['auth'])->prefix('admin/reports')->name('admin.reports.')->group(function () {

    // Main overview table of all registered students
    Route::get('/',              [ReportController::class, 'overview'])->name('overview');

    // Individual student full profile
    Route::get('/student/{username}', [ReportController::class, 'studentDetail'])->name('student');
    Route::get('/student/{username}/pdf', [ReportController::class, 'studentDetailPdf'])->name('student.pdf');
    Route::post('/student/{username}/documents/{documentMasterId}/verify', [ReportController::class, 'updateStudentDocumentVerification'])
        ->name('student.documents.verify');

    // Aggregated reports
    Route::get('/by-service',   [ReportController::class, 'byService'])->name('service');
    Route::get('/by-state',     [ReportController::class, 'byState'])->name('state');
    Route::get('/documents',    [ReportController::class, 'documents'])->name('documents');
    Route::get('/bank-details', [ReportController::class, 'bankDetails'])->name('bank');

    // CSV exports
    Route::get('/export/{type}', [ReportController::class, 'exportCsv'])->name('export')
         ->where('type','overview|service|state|bank');
});
