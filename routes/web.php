<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\StreamController;
use App\Http\Controllers\Admin\SubjectModuleController;
use App\Http\Controllers\Admin\SubjectMasterController;
use App\Http\Controllers\Admin\{
    RoleController,
    PermissionController,
    UserController,
    MemberController,
    CourseController,
    FacultyController
};

Route::get('clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('optimize:clear');
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
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Member Routes
    Route::prefix('member')->name('member.')->group(function () {
        Route::get('/', [MemberController::class, 'index'])->name('index');
        Route::get('create', [MemberController::class, 'create'])->name('create');
        Route::get('edit', [MemberController::class, 'edit'])->name('edit');
        Route::get('/step/{step}', [MemberController::class, 'loadStep'])->name('load-step');
        Route::post('/validate-step/{step}', [MemberController::class, 'validateStep']);
        Route::post('/store', [MemberController::class, 'store'])->name('store');
    });

    // Faculty Routes
    Route::prefix('faculty')->name('faculty.')->group(function () {

        Route::get('/', [FacultyController::class, 'index'])->name('index');
        Route::get('create', [FacultyController::class, 'create'])->name('create');
        Route::post('store', [FacultyController::class, 'store'])->name('store');
        Route::get('edit/{id}', [FacultyController::class, 'edit'])->name('edit');
        Route::post('update', [FacultyController::class, 'update'])->name('update');
       
    });

    // Programme Routes
    Route::prefix('programme')->name('programme.')->controller(CourseController::class)->group(function () {

        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::get('edit/{id}', 'edit')->name('edit');
        Route::post('store', 'store')->name('store');
    });

    // batch route
    Route::prefix('batch')->name('batch.')->group(function () {
        Route::get('/', function () {
            return view('admin.batch.index');
        })->name('index');

        Route::get('/create', function () {
            return view('admin.batch.create');
        })->name('create');

        Route::get('/edit', function () {
            return view('admin.batch.edit');
        })->name('edit');
    });

        Route::resource('subject', SubjectMasterController::class);
   
    // subject route
   

    Route::resource('stream', StreamController::class);

        Route::resource('subject-module', SubjectModuleController::class);
  
    
    Route::post('/admin/toggle-status', [UserController::class, 'toggleStatus'])->name('admin.toggleStatus');


    

    // curriculum route
    Route::prefix('curriculum')->name('curriculum.')->group(function () {
        Route::get('/', function () {
            return view('admin.curriculum.index');
        })->name('index');

        Route::get('/create', function () {
            return view('admin.curriculum.create');
        })->name('create');

        Route::get('/edit', function () {
            return view('admin.curriculum.edit');
        })->name('edit');
    });

    // mapping routes

    Route::prefix('mapping')->name('mapping.')->group(function () {
        Route::get('/', function () {
            return view('admin.mapping.index');
        })->name('index');

        Route::get('/create', function () {
            return view('admin.mapping.create');
        })->name('create');

        Route::get('/edit', function () {
            return view('admin.mapping.edit');
        })->name('edit');
    });
    
    // calendar
    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/', function () {
            return view('admin.calendar.index');
        })->name('index');

        Route::get('/create', function () {
            return view('admin.calendar.create');
        })->name('create');

        Route::get('/edit', function () {
            return view('admin.calendar.edit');
        })->name('edit');
    });

    // Area of Expertise
    Route::prefix('expertise')->name('expertise.')->group(function () {
        Route::get('/', function () {
            return view('admin.expertise.index');
        })->name('index');

        Route::get('/create', function () {
            return view('admin.expertise.create');
        })->name('create');

        Route::get('/edit', function () {
            return view('admin.expertise.edit');
        })->name('edit');
    });

    // City route

    // section route
    Route::prefix('section')->name('section.')->group(function () {
        Route::get('/', function () {
            return view('admin.section.index');
        })->name('index');
    });


    
});
