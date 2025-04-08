<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;

// Authentication Routes
Auth::routes(['verify' => true, 'register' => false]);

// Public Routes
Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])->name('post_login');

// Protected Routes
Route::middleware(['auth'])->group(function () {
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

    // batch route
    Route::prefix('batch')->name('batch.')->group(function () {
        Route::get('/', function() {
            return view('admin.batch.index');
        })->name('index');
        
        Route::get('/create', function() {
            return view('admin.batch.create');
        })->name('create');
        
        Route::get('/edit', function() {
            return view('admin.batch.edit');
        })->name('edit');
    });


    // subject route
    Route::prefix('subject')->name('subject.')->group(function () {
        Route::get('/', function() {
            return view('admin.subject.index');
        })->name('index');
        
        Route::get('/create', function() {
            return view('admin.subject.create');
        })->name('create');
        
        Route::get('/edit', function() {
            return view('admin.subject.edit');
        })->name('edit');
    });

    // stream route
    Route::prefix('stream')->name('stream.')->group(function () {
        Route::get('/', function() {
            return view('admin.stream.index');
        })->name('index');
        
        Route::get('/create', function() {
            return view('admin.stream.create');
        })->name('create');
        
        Route::get('/edit', function() {
            return view('admin.stream.edit');
        })->name('edit');
    });

    // country route
    Route::prefix('country')->name('country.')->group(function () {
        Route::get('/', function() {
            return view('admin.country.index');
        })->name('index');
        
        Route::get('/create', function() {
            return view('admin.country.create');
        })->name('create');
        
        Route::get('/edit', function() {
            return view('admin.country.edit');
        })->name('edit');
    });

    // curriculum route
    Route::prefix('curriculum')->name('curriculum.')->group(function () {
        Route::get('/', function() {
            return view('admin.curriculum.index');
        })->name('index');
        
        Route::get('/create', function() {
            return view('admin.curriculum.create');
        })->name('create');
        
        Route::get('/edit', function() {
            return view('admin.curriculum.edit');
        })->name('edit');
    });

    // mapping routes

    Route::prefix('mapping')->name('mapping.')->group(function () {
        Route::get('/', function() {
            return view('admin.mapping.index');
        })->name('index');
        
        Route::get('/create', function() {
            return view('admin.mapping.create');
        })->name('create');
        
        Route::get('/edit', function() {
            return view('admin.mapping.edit');
        })->name('edit');
    });

// state

    Route::prefix('state')->name('state.')->group(function () {
        Route::get('/', function() {
            return view('admin.state.index');
        })->name('index');
        
        Route::get('/create', function() {
            return view('admin.state.create');
        })->name('create');
        
        Route::get('/edit', function() {
            return view('admin.state.edit');
        })->name('edit');
    });
  
});


