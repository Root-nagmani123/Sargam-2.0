<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\{
    RoleController,
    PermissionController,
    UserController,
    MemberController
};

Route::get('clear-cache', function() {
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
    Route::get('/dashboard', function() {
        return view('dashboard');
    })->name('dashboard');
    
    // Route::get('/home', [HomeController::class, 'index'])->name('home');
    
    // Member Routes
    Route::prefix('member')->name('member.')->group(function () {
        Route::get('/', [MemberController::class, 'index'])->name('index');
        Route::get('create', [MemberController::class, 'create'])->name('create');
        Route::get('edit', [MemberController::class, 'edit'])->name('edit');
        // Route::get('load-step', [MemberController::class, 'loadStep'])->name('load-step');
        // Route::post('store', [MemberController::class, 'store'])->name('store');
        // Route::get('step1', [MemberController::class, 'step1'])->name('step1');
        // Route::post('step1', [MemberController::class, 'step1Store'])->name('step1Store');
        // Route::get('step2', [MemberController::class, 'step2'])->name('step2'); 
        // Route::get('step3', [MemberController::class, 'step3'])->name('step3');
        // Route::get('step4', [MemberController::class, 'step4'])->name('step4');
        // Route::get('step5', [MemberController::class, 'step5'])->name('step5');
        
        
        
        Route::get('/step/{step}', [MemberController::class, 'loadStep'])->name('load-step');
        Route::post('/validate-step/{step}', [MemberController::class, 'validateStep']);
Route::post('/admin/member/store', [MemberController::class, 'store'])->name('store');


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

    
// district route
    Route::prefix('district')->name('district.')->group(function () {
        Route::get('/', function() {
            return view('admin.district.index');
        })->name('index');
        
        Route::get('/create', function() {
            return view('admin.district.create');
        })->name('create');
        
        Route::get('/edit', function() {
            return view('admin.district.edit');
        })->name('edit');
    });

// city route
    Route::prefix('city')->name('city.')->group(function () {
        Route::get('/', function() {
            return view('admin.city.index');
        })->name('index');
        
        Route::get('/create', function() {
            return view('admin.city.create');
        })->name('create');
        
        Route::get('/edit', function() {
            return view('admin.city.edit');
        })->name('edit');
    });
    
    // state route

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
    // calendar
    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/', function() {
            return view('admin.calendar.index');
        })->name('index');
        
        Route::get('/create', function() {
            return view('admin.calendar.create');
        })->name('create');
        
        Route::get('/edit', function() {
            return view('admin.calendar.edit');
        })->name('edit');
    });

        // Area of Expertise
        Route::prefix('expertise')->name('expertise.')->group(function () {
            Route::get('/', function() {
                return view('admin.expertise.index');
            })->name('index');
            
            Route::get('/create', function() {
                return view('admin.expertise.create');
            })->name('create');
            
            Route::get('/edit', function() {
                return view('admin.expertise.edit');
            })->name('edit');
        });
          // District route
          Route::prefix('district')->name('district.')->group(function () {
            Route::get('/', function() {
                return view('admin.district.index');
            })->name('index');
            
            Route::get('/create', function() {
                return view('admin.district.create');
            })->name('create');
            
            Route::get('/edit', function() {
                return view('admin.district.edit');
            })->name('edit');
        });
          // City route
          Route::prefix('city')->name('city.')->group(function () {
            Route::get('/', function() {
                return view('admin.city.index');
            })->name('index');
            
            Route::get('/create', function() {
                return view('admin.city.create');
            })->name('create');
            
            Route::get('/edit', function() {
                return view('admin.city.edit');
            })->name('edit');
        });
});