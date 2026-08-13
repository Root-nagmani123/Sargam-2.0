<?php

use App\Http\Controllers\FC\{
    DescriptiveDataReportController,
    FcActivityController,
    FcActivityDepartmentController,
    FcActivityHomeController,
    FcActivityMasterManageController,
    FcActivityMedicalController,
    FcActivityReportController,
    FcActivityStatusController,
    FcAdminSmsController,
    FcTravelArrivalSlotController,
    RegistrationStep1Controller,
    RegistrationStep2Controller,
    RegistrationStep3Controller,
    BankDetailsController,
    DocumentUploadController,
    FcJoiningSampleDocumentController,
    FcJoiningDocumentFormController,
    RegistrationStatusController,
    FormBuilderController,
    FormManagementController,
    GenericFormController,
    ReportController,
    TravelPlanController,
    TravelPlanReportController,
    StepReportController,
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

    // Step field editor (opened from Form Management → Edit form → Fields)
    Route::prefix('form-builder')->name('form-builder.')->group(function () {
        // The field editor is reached ONLY from the Actions column on Form Management, so the
        // page AND every endpoint it posts to carry the same flag as the button.
        //
        // Guarding the page alone was not enough: hiding a button and 403-ing the screen still
        // left PUT /form-builder/fields/{id} open, so DevTools — or curl — could rename, retype
        // or reorder a question trainees are answering, without ever loading the editor. A lock
        // that only holds while you use the UI is not a lock.
        Route::get('/steps/{step}',           [FormBuilderController::class, 'editStep'])->middleware('fc.builder.action:form_step_actions_enabled')->name('step');
        Route::put('/steps/{step}',           [FormBuilderController::class, 'updateStep'])->middleware('fc.builder.action:form_step_actions_enabled')->name('step.update');
        Route::get('/steps/{step}/preview',   [FormBuilderController::class, 'preview'])->name('preview');

        Route::post('/steps/{step}/fields',   [FormBuilderController::class, 'storeField'])->middleware('fc.builder.action:form_step_actions_enabled')->name('field.store');
        Route::put('/fields/{field}',         [FormBuilderController::class, 'updateField'])->middleware('fc.builder.action:form_step_actions_enabled')->name('field.update');
        Route::delete('/fields/{field}',      [FormBuilderController::class, 'deleteField'])->middleware('fc.builder.delete')->name('field.delete');
        Route::post('/fields/reorder',        [FormBuilderController::class, 'reorderFields'])->middleware('fc.builder.action:form_step_actions_enabled')->name('field.reorder');
        // Renames a section heading across every field of the step in one edit
        Route::post('/steps/{step}/rename-section', [FormBuilderController::class, 'renameSection'])->middleware('fc.builder.action:form_step_actions_enabled')->name('section.rename');

        Route::post('/steps/{step}/groups',   [FormBuilderController::class, 'storeGroup'])->middleware('fc.builder.action:form_step_actions_enabled')->name('group.store');
        Route::put('/groups/{group}',         [FormBuilderController::class, 'updateGroup'])->middleware('fc.builder.action:form_step_actions_enabled')->name('group.update');
        Route::delete('/groups/{group}',      [FormBuilderController::class, 'deleteGroup'])->middleware('fc.builder.delete')->name('group.delete');

        Route::post('/groups/{group}/fields', [FormBuilderController::class, 'storeGroupField'])->middleware('fc.builder.action:form_step_actions_enabled')->name('group-field.store');
        Route::put('/group-fields/{field}',   [FormBuilderController::class, 'updateGroupField'])->middleware('fc.builder.action:form_step_actions_enabled')->name('group-field.update');
        Route::delete('/group-fields/{field}',[FormBuilderController::class, 'deleteGroupField'])->middleware('fc.builder.delete')->name('group-field.delete');
        Route::post('/group-fields/reorder',  [FormBuilderController::class, 'reorderGroupFields'])->middleware('fc.builder.action:form_step_actions_enabled')->name('group-field.reorder');

        Route::post('/doc-masters',           [FormBuilderController::class, 'storeDocMaster'])->middleware('fc.builder.action:form_step_actions_enabled')->name('doc-master.store');
        Route::put('/doc-masters/{doc}',      [FormBuilderController::class, 'updateDocMaster'])->middleware('fc.builder.action:form_step_actions_enabled')->name('doc-master.update');
        Route::delete('/doc-masters/{doc}',   [FormBuilderController::class, 'deleteDocMaster'])->middleware('fc.builder.delete')->name('doc-master.delete');
        Route::post('/doc-masters/reorder',   [FormBuilderController::class, 'reorderDocMasters'])->middleware('fc.builder.action:form_step_actions_enabled')->name('doc-master.reorder');
    });

    // ── Sample Document Master (downloadable blank forms per joining document) ──
    Route::prefix('sample-documents')->name('sample-documents.')->group(function () {
        Route::get('/',             [FcJoiningSampleDocumentController::class, 'index'])->name('index');
        Route::post('/',            [FcJoiningSampleDocumentController::class, 'store'])->name('store');
        Route::put('/{sample}',     [FcJoiningSampleDocumentController::class, 'update'])->name('update');
        Route::delete('/{sample}',  [FcJoiningSampleDocumentController::class, 'destroy'])->name('destroy');
    });

    // ── FC SMS bulk send (B1 / B2 only; no recipient picker) ──
    Route::prefix('sms')->middleware(['fc.reg.admin'])->name('sms.')->group(function () {
        Route::get('/',  [FcAdminSmsController::class, 'index'])->name('index');
        Route::get('/recipients', [FcAdminSmsController::class, 'recipients'])->name('recipients');
        Route::post('/', [FcAdminSmsController::class, 'send'])->name('send');
    });

    // NOTE: the fc-reg/admin/joining/* attendance routes were removed — their
    // controller (FcJoiningAttendanceController) no longer exists, so every one of
    // them 500'd on resolution and broke `php artisan route:list`. Nothing in the
    // app or the sidebar menus linked to them.

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
        Route::get('/list',                    [FormManagementController::class, 'ajaxList'])->name('list');
        Route::get('/filter/courses',          [FormManagementController::class, 'filterCourses'])->name('filter.courses');
        Route::get('/create',                  [FormManagementController::class, 'create'])->name('create');
        Route::post('/',                       [FormManagementController::class, 'store'])->name('store');
        Route::get('/{form}/edit',             [FormManagementController::class, 'edit'])->name('edit');
        Route::put('/{form}',                  [FormManagementController::class, 'update'])->name('update');
        Route::delete('/{form}',               [FormManagementController::class, 'destroy'])->middleware('fc.builder.delete')->name('destroy');

        // API: get columns for a table
        Route::get('/api/table-columns',       [FormManagementController::class, 'getTableColumns'])->name('api.table-columns');

        // Step CRUD within a form.
        //
        // step.store / step.reorder are all-or-nothing, so they carry their flag as
        // route middleware. update / step.update stay open on purpose — the safe
        // fields on those forms (name, description, icon) must remain editable
        // during a live intake, so their locks are per-FIELD inside the controller.
        Route::post('/{form}/steps',           [FormManagementController::class, 'storeStep'])->middleware('fc.builder.action:form_step_add_enabled')->name('step.store');
        // NOT gated on form_step_actions_enabled, even though its button is hidden by it:
        // every field in the Edit Step modal already has its own per-field lock, and blanket-
        // refusing this endpoint would take the always-safe step NAME down with it — which is
        // the one thing the per-field design deliberately keeps editable during a live intake.
        Route::put('/steps/{step}',            [FormManagementController::class, 'updateStep'])->name('step.update');
        Route::delete('/steps/{step}',         [FormManagementController::class, 'deleteStep'])->middleware('fc.builder.delete')->name('step.delete');
        Route::post('/steps/reorder',          [FormManagementController::class, 'reorderSteps'])->middleware('fc.builder.action:form_step_reorder_enabled')->name('step.reorder');
    });

    // ── FC Post-Arrival Activities ───────────────────────────────────────
    Route::prefix('activities')->name('activities.')->group(function () {
        Route::get('/', [FcActivityHomeController::class, 'index'])->name('index');
        Route::get('/data', [FcActivityHomeController::class, 'dataTable'])->name('data');

        Route::post('/', [FcActivityController::class, 'store'])->name('store');

        Route::prefix('ajax')->name('ajax.')->group(function () {
            Route::get('/courses', [FcActivityHomeController::class, 'ajaxCourses'])->name('courses');
            Route::get('/ots', [FcActivityHomeController::class, 'ajaxOts'])->name('ots');
            Route::get('/ot-name', [FcActivityHomeController::class, 'ajaxOtName'])->name('ot-name');
            Route::get('/house', [FcActivityHomeController::class, 'ajaxHouse'])->name('house');
            Route::get('/departments', [FcActivityHomeController::class, 'ajaxDepartments'])->name('departments');
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

    // Fillable joining-document forms (fill online → generates a PDF into the doc slot)
    Route::get('/{form}/step/{step}/fill/{field}',  [FcJoiningDocumentFormController::class, 'show'])->name('doc-form');
    Route::post('/{form}/step/{step}/fill/{field}', [FcJoiningDocumentFormController::class, 'save'])->name('doc-form.save');

    // Trainee downloads their OWN descriptive roll once the form is complete. No username
    // in the path on purpose — the controller resolves the trainee from Auth::id().
    Route::get('/{form}/descriptive-roll/pdf', [ReportController::class, 'myDescriptiveRollPdf'])->name('descriptive-roll.pdf');
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
// Descriptive Data upload passthrough (photo / signature).
//
// ─── ACCESS DECISION — reviewed and accepted, 2026-08-06 (PR #282, finding H-01) ───────────
// This route is registered OUTSIDE the auth group DELIBERATELY. It serves trainee photographs
// and specimen signatures to anyone holding the link, without a login.
//
// Why that is the accepted position, not an oversight:
//   • It REPLACES links to public files under public/storage, which the web server already
//     served to anyone who knew the path, with no Laravel auth in the loop. Exposure is
//     therefore unchanged; the token version is strictly harder to abuse.
//   • The token is an encrypted stored path, so the URL leaks neither the storage layout nor
//     the internal user id, cannot be enumerated, and fails closed if tampered with.
//   • The requirement is that an exported workbook mailed to a colleague keeps working for a
//     recipient who is not a Sargam user. Gating this route breaks exactly that.
//
// Residual risk, accepted: a forwarded export hands the images to whoever receives it.
// To require a login instead, move this line inside the group below — and expect emailed
// exports to stop resolving for anyone not signed in.
// ──────────────────────────────────────────────────────────────────────────────────────────
Route::get('/admin/reports/descriptive-data/file', [DescriptiveDataReportController::class, 'file'])
    ->name('admin.reports.descriptive-data.file');

Route::middleware(['auth'])->prefix('admin/reports')->name('admin.reports.')->group(function () {

    // Main overview table of all registered students
    Route::get('/',              [ReportController::class, 'overview'])->name('overview');

    // Individual student full profile
    Route::get('/student/{username}', [ReportController::class, 'studentDetail'])->name('student');
    Route::get('/student/{username}/pdf', [ReportController::class, 'studentDetailPdf'])->name('student.pdf');
    // Print view — the PDF template served as HTML so browser print matches the PDF exactly
    Route::get('/student/{username}/print', [ReportController::class, 'studentDetailPrint'])->name('student.print');
    // Standalone per-student document verification page
    Route::get('/student/{username}/documents', [ReportController::class, 'studentDocuments'])->name('student.documents');
    Route::post('/student/{username}/documents/{documentMasterId}/verify', [ReportController::class, 'updateStudentDocumentVerification'])
        ->name('student.documents.verify');
    Route::post('/student/{userId}/form-documents/{formFieldId}/verify', [ReportController::class, 'updateDynamicFormDocumentVerification'])
        ->name('student.form-documents.verify');

    // Form-specific dynamic report (works for any form, any number of steps)
    Route::get('/form/{form}',            [ReportController::class, 'formOverview'])->name('form');
    Route::get('/form/{form}/export',     [ReportController::class, 'formExportCsv'])->name('form.export');
    Route::get('/form/{form}/export-pdf-zip', [ReportController::class, 'formExportPdfZip'])->name('form.export.pdf-zip');

    // Health Risk Factors — course-wise report + exports
    Route::get('/health-risk', [ReportController::class, 'healthRiskReport'])->name('health-risk');
    Route::get('/health-risk/print', [ReportController::class, 'healthRiskPrint'])->name('health-risk.print');
    Route::get('/health-risk/export-pdf', [ReportController::class, 'healthRiskExportPdf'])->name('health-risk.export.pdf');
    Route::get('/health-risk/export-excel', [ReportController::class, 'healthRiskExportExcel'])->name('health-risk.export.excel');

    // Descriptive Roll (first 2 steps) — course-wise, per-student PDF + bulk ZIP
    Route::get('/descriptive-roll',                        [ReportController::class, 'firstTwoStepsIndex'])->name('descriptive-roll');
    Route::get('/descriptive-roll/zip',                    [ReportController::class, 'firstTwoStepsZip'])->name('descriptive-roll.zip');
    Route::get('/descriptive-roll/student/{username}/pdf', [ReportController::class, 'firstTwoStepsStudentPdf'])->name('descriptive-roll.student.pdf');

    // Descriptive Data — the Descriptive Roll fields as a filterable table + Excel/PDF export.
    // Columns are resolved per course from the form definition (FcDescriptiveDataFieldResolver).
    // match(get|post): GET renders the page; the DataTable POSTs its draw request. DataTables
    // sends 6 parameters per column, and this report has ~99 columns — as a GET that is a ~25 KB
    // query string, which the web server rejects with 414 URI Too Long. POST puts it in the body.
    Route::match(['get', 'post'], '/descriptive-data', [DescriptiveDataReportController::class, 'index'])->name('descriptive-data');
    // Column + filter metadata, so switching course rebuilds the table without a page load.
    Route::get('/descriptive-data/columns',      [DescriptiveDataReportController::class, 'columns'])->name('descriptive-data.columns');
    Route::get('/descriptive-data/export-excel', [DescriptiveDataReportController::class, 'exportExcel'])->name('descriptive-data.export.excel');
    Route::get('/descriptive-data/export-pdf',   [DescriptiveDataReportController::class, 'exportPdf'])->name('descriptive-data.export.pdf');
    // CSV streams from a cursor — the no-row-limit path for large courses.
    Route::get('/descriptive-data/export-csv',   [DescriptiveDataReportController::class, 'exportCsv'])->name('descriptive-data.export.csv');
    // fc.reg.admin, unlike its siblings: this one endpoint returns EVERY trainee photograph on a
    // course in a single file. The rest of the report is a screen an admin reads; this is a bulk
    // PII extract, so it does not inherit the group's auth-only gate. Super Admin passes, as does
    // anyone holding `bulk_smsemail`; a trainee does not. Widening the gate to the whole report
    // group is a separate decision — see PR #283 review M-1 / #282 M-3.
    Route::get('/descriptive-data/export-photos', [DescriptiveDataReportController::class, 'exportPhotos'])
        ->middleware('fc.reg.admin')
        ->name('descriptive-data.export.photos');

    // Step reports — one FC registration step, one row per trainee, with Excel/PDF export.
    // All served by StepReportController; the report is pinned per URL with ->defaults() rather
    // than exposed as a path segment, so each keeps a readable address of its own and an unknown
    // key cannot be probed. Adding another is one entry here plus one FcStepReport subclass.
    //
    // match(get|post) for the same reason as descriptive-data: the page renders on GET and the
    // DataTable POSTs its draw request.
    foreach ([
        'vision-statement' => 'Vision Statement',
        'special-assistant' => 'Special Assistant',
        // 'bank-report', not 'bank-details': /admin/reports/bank-details already belongs to the
        // older ReportController::bankDetails() screen, which overview.blade.php and
        // form-overview.blade.php both link to. Registering the same URI here would shadow it.
        'bank-report' => 'Bank Details',
        'pre-medical-history' => 'Pre-Medical History',
    ] as $stepReportKey => $stepReportLabel) {
        // NOTE — the group's `auth` only, by decision. A role gate was applied here and then
        // deliberately removed: no role or user currently holds the `bulk_smsemail` permission
        // that fc.reg.admin accepts, so gating these would have left them Super-Admin-only and
        // locked out the people who use them.
        //
        // The exposure this leaves open is real and recorded: `auth` is not an admin gate in
        // this application — Authenticate.php:96 hydrates an FC trainee's roster session into
        // Auth — so a logged-in trainee can reach these screens and their sheet exports. Same
        // gate the sibling descriptive-data report already carries. Revisit when a reporting
        // permission exists to gate on.
        Route::match(['get', 'post'], '/'.$stepReportKey, [StepReportController::class, 'index'])
            ->defaults('report', $stepReportKey)->name($stepReportKey);
        Route::get('/'.$stepReportKey.'/export-excel', [StepReportController::class, 'exportExcel'])
            ->defaults('report', $stepReportKey)->name($stepReportKey.'.export.excel');
        Route::get('/'.$stepReportKey.'/export-pdf', [StepReportController::class, 'exportPdf'])
            ->defaults('report', $stepReportKey)->name($stepReportKey.'.export.pdf');

        // fc.reg.admin, unlike its siblings: this endpoint returns EVERY uploaded document on a
        // course in a single file. The rest of the report is a screen an admin reads; this is a
        // bulk extract of trainee-supplied documents, so it gets the same gate as the photo
        // archive on descriptive-data rather than the group's auth-only one. 404s on a report
        // that has no upload columns.
        Route::get('/'.$stepReportKey.'/export-documents', [StepReportController::class, 'exportDocuments'])
            ->defaults('report', $stepReportKey)
            ->middleware('fc.reg.admin')
            ->name($stepReportKey.'.export.documents');
    }

    // Serves the step reports' uploads — AUTHENTICATED, unlike descriptive-data/file.
    //
    // That older route has no auth at all, deliberately, so an emailed workbook keeps resolving
    // for a recipient who is not a Sargam user — a trade accepted for photographs and
    // signatures. These reports' uploads are Aadhaar cards, PAN cards, cancelled cheques and
    // medical documents, which should not be readable by an anonymous URL holder, so they get
    // their own endpoint inside the auth group. No role gate, matching the screens above.
    // Consequence, deliberate: document links inside a forwarded export require a login.
    Route::get('/step-file', [StepReportController::class, 'file'])
        ->name('step-file');

    // Aggregated reports
    Route::get('/by-service',   [ReportController::class, 'byService'])->name('service');
    Route::get('/by-state',     [ReportController::class, 'byState'])->name('state');
    Route::get('/documents',            [ReportController::class, 'documents'])->name('documents');
    Route::get('/documents/export-zip', [ReportController::class, 'documentsExportZip'])->name('documents.export');
    // Course-wise Document Verification report (student list → per-student verify page)
    Route::get('/document-verification', [ReportController::class, 'documentVerificationIndex'])->name('document-verification');
    Route::get('/bank-details', [ReportController::class, 'bankDetails'])->name('bank');

    // CSV exports
    Route::get('/export/{type}', [ReportController::class, 'exportCsv'])->name('export')
         ->where('type','overview|service|state|bank');
});
