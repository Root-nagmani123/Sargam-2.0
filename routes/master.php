<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\FacultyExpertiseMasterController;

Route::prefix('master')->name('master.')->group(function () {
    
    // Faulty Expertise Master Routes
    Route::prefix('faculty-expertise')->name('faculty.expertise.')->group(function () {
        Route::get('/', [FacultyExpertiseMasterController::class, 'index'])->name('index');
        Route::get('/create', [FacultyExpertiseMasterController::class, 'create'])->name('create');
        Route::post('/store', [FacultyExpertiseMasterController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [FacultyExpertiseMasterController::class, 'edit'])->name('edit');
        Route::delete('/delete/{id}', [FacultyExpertiseMasterController::class, 'delete'])->name('delete');
    });
    
});