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
});




