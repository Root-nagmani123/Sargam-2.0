<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Registration\FormController;
use App\Http\Controllers\Admin\Registration\ColumnController;
use App\Http\Controllers\Admin\Registration\FormEditController;
use App\Http\Controllers\Admin\Registration\FcExemptionMasterController;
use Mews\Captcha\Captcha;
use App\Http\Controllers\Admin\Registration\RegistrationImportController;
use App\Http\Controllers\Admin\Registration\FcRegistrationMasterController;
use App\Http\Controllers\Admin\Registration\FrontPageController;
use App\Http\Controllers\Admin\Registration\FcJoiningDocumentController;
use App\Http\Controllers\Admin\Registration\StudentImportController;
use App\Http\Controllers\Admin\Registration\EnrollementController;
use App\Http\Controllers\Admin\PeerEvaluationController;



//Registration

Route::middleware(['auth'])->prefix('/registration')->group(function () {
    Route::get('/forms', [FormController::class, 'index'])->name('forms.index');
    Route::get('/forms/create', [FormController::class, 'create'])->name('forms.create');
    Route::post('/forms', [FormController::class, 'store'])->name('forms.store');
    Route::get('/forms/{id}/edit', [FormController::class, 'edit'])->name('forms.edit');
    Route::put('/forms/{id}', [FormController::class, 'update'])->name('forms.update');
    Route::post('/forms/{id}/toggle-visible', [FormController::class, 'toggleVisible'])->name('forms.toggleVisible');
    // template route
    Route::get('/forms/template-create', [FormController::class, 'template_create'])->name('forms.template.create');
    // template store
    Route::post('/forms/template-store', [FormController::class, 'template_store'])->name('forms.template.store');


    // Show the form
    Route::get('/forms/new/{formid}', [FormController::class, 'createform'])->name('forms.createnew');

    // Save the form And show fc form
    Route::post('/forms/save/{formid}', [FormController::class, 'saveform'])->name('forms.save');

    Route::get('/forms/{formId}', [FormController::class, 'show'])->name('forms.show');
    // Route::get('/forms/show/{formId?}', [FormController::class, 'show'])->name('forms.show');

    Route::post('/forms/{formId}/submit', [FormController::class, 'submit'])->name('forms.submit');

    // Route::get('/forms/{id}/courselist', [FormController::class, 'courseList'])->name('forms.courseList');
    Route::post('/forms/{id}/moveup', [FormController::class, 'moveUp'])->name('forms.moveup');
    Route::post('/forms/{id}/movedown', [FormController::class, 'moveDown'])->name('forms.movedown');

    Route::get('/forms/{form}/courselist', [FormController::class, 'courseList'])->name('forms.courseList');
    Route::get('/forms/{form}/pending', [FormController::class, 'pending'])->name('forms.pending');

    Route::get('/forms/{formid}/user/{uid}/display', [FormController::class, 'display'])->name('forms.display');
    Route::get('/forms/{formid}/user/{uid}/download', [FormController::class, 'downloadPdf'])->name('forms.downloadpdf');

    //Add dynamic column to table
    Route::get('/add-column', [ColumnController::class, 'showForm'])->name('admin.column.form');
    Route::post('/add-column', [ColumnController::class, 'addColumn'])->name('admin.column.add');

    //home page
    // Route::get('/home', [FormController::class, 'home'])->name('forms.home');

    //home page user
    // Route::get('/home', [FormController::class, 'homeUser'])->name('forms.home.user');

    //main page
    Route::get('/main_page', [FormController::class, 'main_page'])->name('forms.main_page');
    //export
    Route::get('/forms/export/{formid}', [FormController::class, 'exportfcformList'])->name('forms.export');

    //Fc_form edit function

    Route::get('/forms/{form_id}/fc-form_edit', [FormEditController::class, 'fc_edit'])->name('forms.fc_edit');
    Route::post('/forms/fc-update', [FormEditController::class, 'fc_update'])->name('forms.fc_update');


    //logo registration
    Route::get('/admin/registration-page/create', [FormEditController::class, 'LogoCreate'])->name('registration-page.create');
    Route::post('/admin/registration-page/store', [FormEditController::class, 'LogoUpdate'])->name('registration-page.store');

    //generate pdf 
    Route::get('/forms/{form_id}/pdf/{user_id}', [FormController::class, 'generatePdf'])
        ->name('forms.pdf');

    //Exemption master page
    Route::get('/exemption', [FormController::class, 'exemption'])->name('forms.exemption');

    //Exemption master create

    Route::get('admin/registration/fc-exemption_master', [FcExemptionMasterController::class, 'index'])->name('admin.fc_exemption.index');
    Route::get('admin/registration/fc-exemption_master/create', [FcExemptionMasterController::class, 'create'])->name('admin.fc_exemption.create');
    Route::post('admin/registration/fc-exemption_master/store', [FcExemptionMasterController::class, 'store'])->name('admin.fc_exemption.store');
    Route::get('admin/registration/fc-exemption_master/edit/{id}', [FcExemptionMasterController::class, 'edit'])->name('admin.fc_exemption.edit');
    Route::post('admin/registration/fc-exemption_master/update/{id}', [FcExemptionMasterController::class, 'update'])->name('admin.fc_exemption.update');
    Route::delete('admin/registration/fc-exemption_master/delete/{id}', [FcExemptionMasterController::class, 'destroy'])->name('admin.fc_exemption.destroy');

    // User exemption store
    Route::get('/exemption-create', [FcExemptionMasterController::class, 'exemptioncreate'])->name('exemption.create');
    Route::post('/exemption-store', [FcExemptionMasterController::class, 'exemptionstore'])->name('exemption.store');

    // user exemption listing 
    Route::get('/exemptions_data', [FcExemptionMasterController::class, 'exemption_list'])->name('exemptions.datalist');



    //exemption datalist export
    Route::get('/admin/exemption-data/export', [FcExemptionMasterController::class, 'exemptionexport'])->name('admin.exemption.export');

    // toggle exemption php
    Route::post('/admin/fc_exemption/{id}/toggle-visible', [FcExemptionMasterController::class, 'toggleVisible'])
        ->name('admin.fc_exemption.toggleVisible');

    //captcha route
    Route::get('captcha', [FcExemptionMasterController::class, 'reloadCaptcha'])->name('reload.captcha');
});

Route::prefix('/registration')->group(function () {
    Route::get('/import', [RegistrationImportController::class, 'showForm'])->name('admin.registration.import.form');
    Route::post('/import/preview', [RegistrationImportController::class, 'preview'])->name('admin.registration.preview');
    Route::post('/import/confirm', [RegistrationImportController::class, 'importConfirmed'])->name('admin.registration.import.confirm');
});

//Fc Registration Master list
Route::prefix('/registration')->group(function () {
    Route::get('/fc_masterlist', [RegistrationImportController::class, 'fc_masterindex'])->name('admin.registration.index');
    Route::get('/fc_masteredit/{id}', [RegistrationImportController::class, 'fc_masteredit'])->name('admin.registration.edit');
    Route::put('/fc_masterupdate/{id}', [RegistrationImportController::class, 'fc_masterupdate'])->name('admin.registration.update');
    Route::delete('/fc_masterdelete/{id}', [RegistrationImportController::class, 'fc_masterdestroy'])->name('admin.registration.delete');
});

//fcmaster export

Route::get('admin/registration/export', [RegistrationImportController::class, 'export'])->name('admin.registration.export');

//fc registor form route
Route::get('/fc-register-form', function () {
    return view('fc.register_form');
})->name('fc.register_form')->middleware('auth');


//front page data route
Route::get('/registration/frontpage', [FrontPageController::class, 'index'])->name('admin.frontpage');
Route::post('/registration/frontpage', [FrontPageController::class, 'storeOrUpdate'])->name('admin.frontpage.save');

//foundation page route
Route::get('/registration/foundation-course', [FrontPageController::class, 'foundationIndex'])->name('frontpage.index');

// Auth verify user 
Route::get('/registration/fc-auth', [FrontPageController::class, 'authindex'])->name('verify.authindex');
Route::post('/registration/verify', [FrontPageController::class, 'verify'])->name('registration.verify');

// Show the create credentials form
Route::get('/fc/create-credentials', [FrontPageController::class, 'credential_index'])->name('credential.registration.create');

// Handle form submission
Route::post('/fc/store-credentials', [FrontPageController::class, 'credential_store'])->name('credential.registration.store');

//login verify user route
Route::get('/fc/login', [FrontPageController::class, 'showLoginForm'])->name('fc.login');
Route::post('/fc/login-verify', [FrontPageController::class, 'verifyLogin'])->name('fc.login.verify');

//path choose route
Route::get('/fc/choose-path', [FrontPageController::class, 'choosePath'])->name('fc.choose.path');

//page path route 
Route::get('/admin/path-page', [FrontPageController::class, 'pathPageForm'])->name('admin.path.page');
Route::post('/admin/path-page', [FrontPageController::class, 'pathPageSave'])->name('admin.path.page.save');

//delete path faq
Route::delete('/fc/faqs/{id}', [FrontPageController::class, 'destroyFaq'])->name('fc.faqs.destroy');

// view all FAQs
Route::get('/fc/all-faqs', [FrontPageController::class, 'allFaqs'])->name('fc.faqs.all');

//exemption category  index
Route::get('/exemption-category', [FrontPageController::class, 'showExemptionCategory'])->name('fc.exemption_category.index');

//exemption category index admin
Route::get('/admin/exemption-category', [FrontPageController::class, 'exemptionIndex'])->name('admin.exemptionIndex');
Route::get('/admin/exemption/create', [FrontPageController::class, 'exemptionCreate'])->name('exemptionCreate');
Route::post('/admin/exemption/store', [FrontPageController::class, 'exemptionStore'])->name('exemptionStore');
Route::get('/admin/exemption/edit/{id}', [FrontPageController::class, 'exemptionEdit'])->name('exemptionEdit');
Route::post('/admin/exemption/update/{id}', [FrontPageController::class, 'exemptionUpdate'])->name('exemptionUpdate');
Route::post('/admin/exemption/notice/update', [FrontPageController::class, 'exemptionUpdateNotice'])->name('exemptionUpdateNotice');

// show exemption application form
Route::get('/fc/exemption-application/{id}', [FrontPageController::class, 'exemptionApplication'])->name('fc.exemption_application');
// apply exemption store 
Route::post('/fc/exemption-apply/{id}', [FrontPageController::class, 'apply_exemptionstore'])->name('fc.exemption.apply');

//thanks page route
Route::get('/fc/thank-you', function () {
    return view('fc.thank_you');
})->name('fc.thank_you');

//forget password page
Route::get('/fc/forget-password', function () {
    return view('fc.forget');
})->name('fc.forget');

//status page
Route::get('/fc/status', function () {
    return view('fc.status');
})->name('fc.status');


//reset passowrd index
Route::get('/fc/forgot-password', [FrontPageController::class, 'showForgotPasswordForm'])->name('fc.password.forgot');


//reset password 
Route::post('/fc/password-reset', [FrontPageController::class, 'resetPassword'])->name('fc.password.reset');

//reset web-auth form verify
Route::post('/fc/verify-web-auth', [FrontPageController::class, 'verifyWebAuth'])->name('fc.verify_web_auth');

//joining document route
Route::get('/admin/fc/joining-documents/{formId}', [FcJoiningDocumentController::class, 'create'])->name('fc.joining.index');
Route::post('/admin/fc/joining-documents/upload', [FcJoiningDocumentController::class, 'store'])->name('fc.joining.upload');

//joining document list
Route::get('/admin/reports/joining-documents/{formId}', [FcJoiningDocumentController::class, 'fc_report_index'])->name('admin.joining-documents.index');
Route::get('/admin/reports/joining-documents/download-all/{userId}', [FcJoiningDocumentController::class, 'downloadAll'])->name('admin.joining-documents.download-all');

//remark
Route::post('/admin/joining-documents/save-remark/{user_id}', [FcJoiningDocumentController::class, 'saveRemark'])->name('admin.joining-documents.save-remark');

//student page status

Route::get('/foundation-course/status', [FrontPageController::class, 'student_status'])->name('foundation.course.status');

//admin migration route
Route::get('/admin/migrate-students', [StudentImportController::class, 'index'])->name('students.index'); // index page
Route::post('/admin/migrate-fc-registration', [StudentImportController::class, 'migrate'])->name('admin.migrate.fc');

// course enrollment route
// routes/web.php
Route::get('/enrollment/create', [EnrollementController::class, 'create'])->name('enrollment.create');
Route::post('/enrollment/store', [EnrollementController::class, 'store'])->name('enrollment.store');
Route::post('/enrollment/filter-students', [EnrollementController::class, 'filterStudents'])->name('enrollment.filterStudents');

// student master course map route
Route::get('/student-courses', [EnrollementController::class, 'studentCourses'])->name('student.courses');

// export student enrollment
Route::get('admin/student-report', [EnrollementController::class, 'StudenEnroll_index'])->name('studentEnroll.report');
Route::get('admin/student-report/export', [EnrollementController::class, 'StudenEnroll_export'])->name('studentEnroll.report.export');

//ajax list enrolled students
// Get enrolled students with filtering
Route::get('/enrollment/enrolled-students', [EnrollementController::class, 'getEnrolledStudents'])
    ->name('enrollment.getEnrolled');

// Export enrolled students
Route::get('/enrollment/export-enrolled', [EnrollementController::class, 'exportEnrolledStudents'])
    ->name('enrollment.exportEnrolled');

// Inactive forms list
Route::get('/forms/inactive', [FormController::class, 'inactive'])->name('forms.inactive');


// Download template 1
Route::get('/download-fctemplate', [RegistrationImportController::class, 'downloadFcRegistrationTemplate'])->name('fc.download.fctemplate');
// Download template 2
Route::get('/download-template', [RegistrationImportController::class, 'downloadTemplate'])->name('fc.download.template');
// Route::post('/upload-excel', [RegistrationImportController::class, 'uploadExcel'])->name('fc.upload.excel');

// Bulk upload preview
Route::post('/registration/bulk/preview', [RegistrationImportController::class, 'previewUpload'])
    ->name('fc.preview.upload');

// Confirm and import
Route::post('/registration/bulk/confirm', [RegistrationImportController::class, 'confirmUpload'])
    ->name('fc.confirm.upload');

// Deactivate filtered records
Route::post('registration/deactivate-filtered', [RegistrationImportController::class, 'deactivateFiltered'])
    ->name('admin.registration.deactivate.filtered');


// Route::prefix('admin/peer')->group(function () {
//     Route::get('/', [PeerEvaluationController::class, 'index'])->name('peer.index');
//     Route::post('/group', [PeerEvaluationController::class, 'storeGroup'])->name('peer.group.store');
//     Route::post('/column', [PeerEvaluationController::class, 'storeColumn'])->name('peer.column.store');
//     Route::post('/toggle/{id}', [PeerEvaluationController::class, 'toggleColumn'])->name('peer.column.toggle');
//     Route::post('/store', [PeerEvaluationController::class, 'storeScore'])->name('peer.store');
// });


// Admin Routes
Route::prefix('admin')->group(function () {
    Route::get('/peer-evaluation', [PeerEvaluationController::class, 'index'])->name('admin.peer.index');
    Route::post('/peer/group/store', [PeerEvaluationController::class, 'storeGroup'])->name('admin.peer.group.store');
    Route::post('/peer/column/store', [PeerEvaluationController::class, 'storeColumn'])->name('admin.peer.column.store');
    Route::post('/peer/toggle/{id}', [PeerEvaluationController::class, 'toggleColumn'])->name('admin.peer.toggle');
    Route::post('/peer/group/delete/{id}', [PeerEvaluationController::class, 'deleteGroup'])->name('admin.peer.group.delete');
    Route::post('/peer/column/delete/{id}', [PeerEvaluationController::class, 'deleteColumn'])->name('admin.peer.column.delete');
});

// User Routes
Route::get('/peer-evaluation', [PeerEvaluationController::class, 'user_index'])->name('peer.index');
Route::post('/peer-evaluation', [PeerEvaluationController::class, 'store'])->name('peer.store');
Route::get('/peer-evaluation/group/{groupId}/members', [PeerEvaluationController::class, 'getGroupMembers'])->name('peer.group.members');


// Group Members Routes
Route::get('/peer/group/{id}/members', [PeerEvaluationController::class, 'showGroupMembers'])->name('admin.peer.group.members');
Route::get('/peer/group/{id}/import', [PeerEvaluationController::class, 'importMembersView'])->name('admin.peer.group.import');
Route::post('/peer/group/{id}/add-members', [PeerEvaluationController::class, 'addMembersToGroup'])->name('admin.peer.group.add-members');
Route::post('/admin/peer/group/{groupId}/remove-member/{memberPk}', [PeerEvaluationController::class, 'removeMemberFromGroup'])->name('admin.peer.group.remove-member');
// Make sure you have this route defined
// Route::post('/admin/peer/group/{groupId}/remove-member/{memberPk}', [PeerEvaluationAdminController::class, 'removeMemberFromGroup'])->name('admin.peer.group.remove-member');

// Excel Import Routes
Route::get('/peer/group/{id}/import', [PeerEvaluationController::class, 'importMembersView'])->name('admin.peer.group.import');
Route::post('/peer/group/{id}/import-excel', [PeerEvaluationController::class, 'importExcel'])->name('admin.peer.group.import-excel');
Route::get('/peer/download-template', [PeerEvaluationController::class, 'PeerDownloadTemplate'])->name('admin.peer.download-template');

// User Routes
// Route::get('/peer-evaluation', [PeerEvaluationController::class, 'index'])->name('peer.index');
// Route::post('/peer-evaluation', [PeerEvaluationController::class, 'store'])->name('peer.store');

// User-facing routes (peer.store is POST /peer-evaluation above; do not duplicate name here)
Route::prefix('peer')->middleware('auth')->group(function () {
    Route::get('my-groups', [PeerEvaluationController::class, 'user_groups'])->name('peer.user_groups');
    Route::get('evaluate/{groupId}', [PeerEvaluationController::class, 'user_evaluation'])->name('peer.user_evaluation');
    Route::post('store', [PeerEvaluationController::class, 'store'])->name('peer.store.submit');
});

// View submissions for a specific peer group
Route::get('admin/peer/group/{group}/submissions', [PeerEvaluationController::class, 'viewSubmissions'])
    ->name('admin.peer.group.submissions');

// Export submissions for a specific peer group
Route::get('admin/peer/export/{groupId}', [PeerEvaluationController::class, 'exportSubmissions'])
    ->name('admin.peer.export');

// web.php
Route::post('admin/peer/group/toggle-form/{id}', [PeerEvaluationController::class, 'toggleForm'])->name('admin.peer.group.toggleForm');

// Reflection Fields
Route::post('/admin/peer/reflection/add', [PeerEvaluationController::class, 'addReflectionField'])->name('admin.peer.reflection.add');
Route::post('/admin/peer/reflection/toggle/{id}', [PeerEvaluationController::class, 'toggleReflectionField'])->name('admin.peer.reflection.toggle');
Route::post('/admin/peer/reflection/delete/{id}', [PeerEvaluationController::class, 'deleteReflectionField'])->name('admin.peer.reflection.delete');

// Additional Admin Routes for Peer Evaluation Groups max marks
Route::post('/admin/peer/groups/add', [PeerEvaluationController::class, 'addGroup'])->name('admin.peer.groups.add');
Route::post('/admin/peer/groups/update-marks', [PeerEvaluationController::class, 'updateMaxMarks'])->name('admin.peer.groups.update-marks');

// // Events and Courses
// Route::post('/admin/peer/event/add', [PeerEvaluationController::class, 'addEvent'])->name('admin.peer.event.add');
// Route::post('/admin/peer/course/add', [PeerEvaluationController::class, 'addCourse'])->name('admin.peer.course.add');
// Route::get('/admin/peer/courses/{eventId}', [PeerEvaluationController::class, 'getCoursesByEvent']);

// // Groups with event/course
// Route::post('/admin/peer/group/add', [PeerEvaluationController::class, 'addGroup'])->name('admin.peer.group.add');
// // Route::post('/admin/peer/group/toggle-form/{id}', [PeerEvaluationController::class, 'toggleFormStatus']);
// // Route::post('/admin/peer/group/delete/{id}', [PeerEvaluationController::class, 'deleteGroup']);

// // Columns with event/course
// Route::post('/admin/peer/column/add', [PeerEvaluationController::class, 'addColumn'])->name('admin.peer.column.add');
// Route::post('/admin/peer/column/toggle/{id}', [PeerEvaluationController::class, 'toggleColumnVisibility']);
// Route::post('/admin/peer/column/delete/{id}', [PeerEvaluationController::class, 'deleteColumn']);

// // Max marks
// Route::post('/admin/peer/groups/update-marks', [PeerEvaluationController::class, 'updateMaxMarks'])->name('admin.peer.groups.update-marks');

// Keep your existing routes for reflection fields, etc.


// Courses
Route::post('/admin/peer/course/add', [PeerEvaluationController::class, 'addCourse'])->name('admin.peer.course.add');

//update course
Route::post('/admin/peer/course/update', [PeerEvaluationController::class, 'updateCourse'])
    ->name('admin.peer.course.update');
	
//delete course
Route::delete('/admin/peer/course/delete/{id}', [PeerEvaluationController::class, 'deleteCourse'])
    ->name('admin.peer.course.delete');



// Events (now belong to courses)
Route::post('/admin/peer/event/add', [PeerEvaluationController::class, 'addEvent'])->name('admin.peer.event.add');
Route::get('/admin/peer/events/{courseId}', [PeerEvaluationController::class, 'getEventsByCourse']); // Changed from getCoursesByEvent

// Groups with course/event (updated hierarchy)
Route::post('/admin/peer/group/add', [PeerEvaluationController::class, 'addGroup'])->name('admin.peer.group.add');
Route::post('/admin/peer/group/toggle-form/{id}', [PeerEvaluationController::class, 'toggleFormStatus']);
Route::post('/admin/peer/group/delete/{id}', [PeerEvaluationController::class, 'deleteGroup']);

// Columns with course/event (updated hierarchy)
Route::post('/admin/peer/column/add', [PeerEvaluationController::class, 'addColumn'])->name('admin.peer.column.add');
Route::post('/admin/peer/column/toggle/{id}', [PeerEvaluationController::class, 'toggleColumnVisibility']);
Route::post('/admin/peer/column/delete/{id}', [PeerEvaluationController::class, 'deleteColumn']);

// Max marks
Route::post('/admin/peer/groups/update-marks', [PeerEvaluationController::class, 'updateMaxMarks'])->name('admin.peer.groups.update-marks');

// Reflection fields (add these new routes)
Route::post('/admin/peer/reflection/add', [PeerEvaluationController::class, 'addReflectionField'])->name('admin.peer.reflection.add');
Route::post('/admin/peer/reflection/toggle/{id}', [PeerEvaluationController::class, 'toggleReflectionField']);
Route::post('/admin/peer/reflection/delete/{id}', [PeerEvaluationController::class, 'deleteReflectionField']);

// Keep your existing routes for backward compatibility if needed
Route::get('/admin/peer/courses/{eventId}', [PeerEvaluationController::class, 'getCoursesByEvent']); // Keep if used elsewhere

// Enrollment edit routes
Route::get('/enrollment/{student}/edit', [EnrollementController::class, 'edit'])->name('enrollment.edit');
Route::post('/enrollment/{student}', [EnrollementController::class, 'update'])->name('enrollment.update');

// Import to OT List
Route::post('/student-enrollment/import-to-ot-list', [EnrollementController::class, 'import'])
    ->name('student.enrollment.import');