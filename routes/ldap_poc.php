<?php

use App\Http\Controllers\Admin\LdapPocController;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────────
// LDAP user-creation POC — isolated test, NOT part of FC Registration.
// Controller returns 404 unless LDAP_POC_ENABLED=true, and 403 for anyone
// who is not Super Admin. Writes are throttled.
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth'])->prefix('admin/ldap-poc')->name('admin.ldap-poc.')->group(function () {
    Route::get('/', [LdapPocController::class, 'index'])->name('index');

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/diagnose', [LdapPocController::class, 'diagnose'])->name('diagnose');
        Route::post('/create', [LdapPocController::class, 'create'])->name('create');
        Route::post('/delete', [LdapPocController::class, 'destroy'])->name('delete');
    });
});
