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






//Registration

Route::middleware(['auth'])->prefix('/registration')->group(function () {
    Route::get('/forms', [FormController::class, 'index'])->name('forms.index');
    Route::get('/forms/create', [FormController::class, 'create'])->name('forms.create');
    Route::post('/forms', [FormController::class, 'store'])->name('forms.store');
    Route::get('/forms/{id}/edit', [FormController::class, 'edit'])->name('forms.edit');
    Route::put('/forms/{id}', [FormController::class, 'update'])->name('forms.update');
    Route::post('/forms/{id}/toggle-visible', [FormController::class, 'toggleVisible'])->name('forms.toggleVisible');

    // Show the form
    Route::get('/forms/new/{formid}', [FormController::class, 'createform'])->name('forms.createnew');

    // Save the form And show fc form
    Route::post('/forms/save/{formid}', [FormController::class, 'saveform'])->name('forms.save');

    Route::get('/forms/{formId}', [FormController::class, 'show'])->name('forms.show');
    // Route::get('/forms/show/{formId?}', [FormController::class, 'show'])->name('forms.show');

    Route::post('/forms/{formId}/submit', [FormController::class, 'submit'])->name('forms.submit');

    // Route::get('/forms/{id}/courselist', [FormController::class, 'courseList'])->name('forms.courseList');
    Route::get('/forms/{id}/pending', [FormController::class, 'pending'])->name('forms.pending');
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
