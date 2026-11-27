<?php

use Illuminate\Support\Facades\Route;
use Modules\GuestTaClaim\Http\Controllers\ApprovalController;
use Modules\GuestTaClaim\Http\Controllers\AttachmentController;
use Modules\GuestTaClaim\Http\Controllers\ClaimController;
use Modules\GuestTaClaim\Http\Controllers\DashboardController;
use Modules\GuestTaClaim\Http\Controllers\ExportController;
use Modules\GuestTaClaim\Http\Controllers\PaymentController;
use Modules\GuestTaClaim\Http\Controllers\PermissionController;
use Modules\GuestTaClaim\Http\Controllers\ReportController;


Route::group(['middleware' => ['web', 'auth'], 'prefix' => 'guest-ta-claim', 'as' => 'guest-ta-claim.'], function () {

        Route::get('/', DashboardController::class)->name('dashboard');

        // TA & Training
        // Route::middleware('gtc.can:claim.create')->group(function () {
            Route::get('claims/create', [ClaimController::class, 'create'])->name('claims.create');
            Route::post('claims/prepare', [ClaimController::class, 'prepare'])->name('claims.prepare');
            Route::get('claims/form', [ClaimController::class, 'form'])->name('claims.form');
            Route::post('claims', [ClaimController::class, 'store'])->name('claims.store');
            Route::get('claims/{claim}/edit', [ClaimController::class, 'edit'])->name('claims.edit');
            Route::put('claims/{claim}', [ClaimController::class, 'update'])->name('claims.update');
            Route::delete('claims/{claim}', [ClaimController::class, 'destroy'])->name('claims.destroy');
            Route::post('claims/{claim}/submit-bill', [ClaimController::class, 'submitBill'])->name('claims.submit-bill');
        // });  

        Route::post('claims/{claim}/forward', [ClaimController::class, 'forward'])->name('claims.forward');
        // Route::post('claims/{claim}/forward', [ClaimController::class, 'forward'])->middleware('gtc.can:claim.forward')->name('claims.forward');
        Route::get('claims', [ClaimController::class, 'index'])->name('claims.index');
        Route::get('claims/{claim}/bill', [ClaimController::class, 'bill'])->name('claims.bill');
        Route::get('claims/{claim}', [ClaimController::class, 'show'])->name('claims.show');

        // Course coordinator
        // Route::middleware('gtc.can:cc.approve')->group(function () {
            Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
            Route::post('claims/{claim}/approve', [ApprovalController::class, 'approve'])->name('claims.approve');
            Route::post('claims/{claim}/return', [ApprovalController::class, 'return'])->name('claims.return');
        // });

        // Accounts
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('claims/{claim}/pay', [PaymentController::class, 'pay'])->name('claims.pay');
        // Route::get('payments', [PaymentController::class, 'index'])->middleware('gtc.can:acc.view|acc.pay')->name('payments.index');
        // Route::post('claims/{claim}/pay', [PaymentController::class, 'pay'])->middleware('gtc.can:acc.pay')->name('claims.pay');

        // Reports
        // Route::middleware('gtc.can:claim.report|acc.view|acc.pay')->group(function () {
            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('claims/{claim}/report', [ReportController::class, 'show'])->name('claims.report');
        // });

        Route::get('export/{type}', ExportController::class)
            ->middleware('gtc.can:export')->where('type', 'claims|approvals|payments|reports|timetable|permissions|claim')
            ->name('export');

        Route::get('attachments/{attachment}', AttachmentController::class)->name('attachments.download');

        // Permission mapping
        // Route::middleware('gtc.can:perm.manage')->group(function () {
            Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
            Route::put('permissions/{userId}', [PermissionController::class, 'update'])
                ->whereNumber('userId')->name('permissions.update');
        // });
    });
