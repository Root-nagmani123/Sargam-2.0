<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Registration\FormController;
use App\Http\Controllers\Admin\Registration\ColumnController;
use App\Http\Controllers\Admin\Registration\FormEditController;
use App\Http\Controllers\Admin\Registration\FcExemptionMasterController;
use Mews\Captcha\Captcha;
use App\Http\Controllers\Admin\Registration\RegistrationImportController;
use App\Http\Controllers\Admin\Registration\FcRegistrationMasterController;






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
    Route::get('/home', [FormController::class, 'home'])->name('forms.home');

    //home page user
    Route::get('/home', [FormController::class, 'homeUser'])->name('forms.home.user');

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

    //verify user 
    Route::post('/registration/verify', [FcExemptionMasterController::class, 'verify'])->name('registration.verify');

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
    // Route::delete('/fc_masterdelete/{id}', [RegistrationImportController::class, 'fc_masterdestroy'])->name('admin.registration.delete');
});
