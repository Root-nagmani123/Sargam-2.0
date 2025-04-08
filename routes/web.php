<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\{
    RoleController,
    PermissionController,
    UserController
};

Route::get('clear-cache', function() {
    Artisan::call('cache:clear');
    return redirect()->back()->with('success', 'Cache cleared successfully');
});
// Authentication Routes
Auth::routes(['verify' => true, 'register' => false]);

// Public Routes
Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])->name('post_login');

// Protected Routes
Route::middleware(['auth'])->group(function () {

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('roles', RoleController::class);
        Route::resource('permissions', PermissionController::class);
        Route::resource('users', UserController::class);
    });

    // Dashboard
    Route::get('/dashboard', function() {
        return view('dashboard');
    })->name('dashboard');
    
    // Route::get('/home', [HomeController::class, 'index'])->name('home');
    
    // Member Routes
    Route::prefix('member')->name('member.')->group(function () {
        Route::get('/', function() {
            return view('admin.member.index');
        })->name('index');
        
        Route::get('/create', function() {
            return view('admin.member.create');
        })->name('create');
        
        Route::get('/edit', function() {
            return view('admin.member.edit');
        })->name('edit');
    });
    
    // Faculty Routes
    Route::prefix('faculty')->name('faculty.')->group(function () {
        Route::get('/', function() {
            return view('admin.faculty.index');
        })->name('index');
        
        Route::get('/create', function() {
            return view('admin.faculty.create');
        })->name('create');
        
        Route::get('/edit', function() {
            return view('admin.faculty.edit');
        })->name('edit');
    });
    
    // Programme Routes
    Route::prefix('programme')->name('programme.')->group(function () {
        Route::get('/', function() {
            return view('admin.programme.index');
        })->name('index');
        
        Route::get('/create', function() {
            return view('admin.programme.create');
        })->name('create');
        
        Route::get('/edit', function() {
            return view('admin.programme.edit');
        })->name('edit');
    });
});




Route::get('/dashboard', function(){
    return view('dashboard');
})->name('dashboard');
Route::get('/member-create', function(){
    return view('admin.member.create');
});
Route::get('/member-edit', function(){
    return view('admin.member.edit');
});
Route::get('/member', function(){
    return view('admin.member.index');
});
Route::get('/faculty-create', function(){
    return view('admin.faculty.create');
});
Route::get('/faculty-edit', function(){
    return view('admin.faculty.edit');
});
Route::get('/faculty', function(){
    return view('admin.faculty.index');
});
Route::get('/programme', function(){
    return view('admin.programme.index');
});
Route::get('/programme-create', function(){
    return view('admin.programme.create');
});
Route::get('/programme-edit', function(){
    return view('admin.programme.edit');
});
Route::get('/batch', function(){
    return view('admin.batch.index');
});
Route::get('/batch-create', function(){
    return view('admin.batch.create');
});
Route::get('/batch-edit', function(){
    return view('admin.batch.edit');
});
Route::get('/subject', function(){
    return view('admin.subject.index');
});
Route::get('/subject-create', function(){
    return view('admin.subject.create');
});
Route::get('/subject-edit', function(){
    return view('admin.subject.edit');
});
Route::get('/stream', function(){
    return view('admin.stream.index');
});
Route::get('/stream-create', function(){
    return view('admin.stream.create');
});
Route::get('/stream-edit', function(){
    return view('admin.stream.edit');
});
Route::get('/country', function(){
    return view('admin.country.index');
});
Route::get('/country-create', function(){
    return view('admin.country.create');
});
Route::get('/country-edit', function(){
    return view('admin.country.edit');
});
Route::get('/curriculum', function(){
    return view('admin.curriculum.index');
});
Route::get('/curriculum-create', function(){
    return view('admin.curriculum.create');
});
Route::get('/curriculum-edit', function(){
    return view('admin.curriculum.edit');
});
Route::get('/mapping', function(){
    return view('admin.mapping.index');
});
Route::get('/mapping-create', function(){
    return view('admin.mapping.create');
});
Route::get('/mapping-edit', function(){
    return view('admin.mapping.edit');
});


