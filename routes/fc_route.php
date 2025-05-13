<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Registration\FormController;
use App\Http\Controllers\Admin\Registration\ColumnController;
use App\Http\Controllers\Admin\Registration\FormEditController;

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
  
});
