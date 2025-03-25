<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

Auth::routes(['verify' => true, 'register' => false]);

Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('post_login');

// Route::middleware(['auth', 'verified'])->group(function () {
    
//     // Route::name('admin.')->group(function () { // prefix for route name
        
//     // });
// });
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index']);

Route::get('/dashboard', function(){
    return view('dashboard');
});
