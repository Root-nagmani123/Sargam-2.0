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


