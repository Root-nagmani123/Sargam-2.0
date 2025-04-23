<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\{
    RoleController,
    PermissionController,
    UserController,
    MemberController
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
        //      Route::get('/', function() {
        //     return view('admin.country.index');
        // })->name('index');
       
        
        // Route::get('/create', function() {
        //     return view('admin.country.create');
        // })->name('create');
        
        // Route::get('/edit', function() {
        //     return view('admin.country.edit');
        // })->name('edit');

    Route::get('/', [LocationController::class, 'countryIndex'])->name('index');
    Route::get('/create', [LocationController::class, 'countryCreate'])->name('create');
    Route::post('/store', [LocationController::class, 'countryStore'])->name('store');
    Route::get('/edit/{id}', [LocationController::class, 'countryEdit'])->name('edit');
    Route::PUT('/update/{id}', [LocationController::class, 'countryUpdate'])->name('update');
    Route::delete('/delete/{id}', [LocationController::class, 'countryDelete'])->name('delete');
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
      
        Route::get('/', [LocationController::class, 'stateIndex'])->name('index');
        Route::get('/create', [LocationController::class, 'stateCreate'])->name('create');
        Route::post('/store', [LocationController::class, 'stateStore'])->name('store');
        Route::get('/edit/{id}', [LocationController::class, 'stateEdit'])->name('edit');
        Route::post('/update/{id}', [LocationController::class, 'stateUpdate'])->name('update');
        Route::delete('/delete/{id}', [LocationController::class, 'stateDelete'])->name('delete');
    });

    
// district route
    Route::prefix('district')->name('district.')->group(function () {
        
    Route::get('/', [LocationController::class, 'districtIndex'])->name('index');
    Route::get('/create', [LocationController::class, 'districtCreate'])->name('create');
    Route::post('/store', [LocationController::class, 'districtStore'])->name('store');
    Route::get('/edit/{id}', [LocationController::class, 'districtEdit'])->name('edit');
    Route::post('/update/{id}', [LocationController::class, 'districtUpdate'])->name('update');
    Route::delete('/delete/{id}', [LocationController::class, 'districtDelete'])->name('delete');
    });

// city route
    Route::prefix('city')->name('city.')->group(function () {
        // Route::get('/', function() {
       
    Route::get('/', [LocationController::class, 'cityIndex'])->name('index');
    Route::get('/create', [LocationController::class, 'cityCreate'])->name('create');
    Route::post('/store', [LocationController::class, 'cityStore'])->name('store');
    Route::get('/edit/{id}', [LocationController::class, 'cityEdit'])->name('edit');
    Route::post('/update/{id}', [LocationController::class, 'cityUpdate'])->name('update');
    Route::delete('/delete/{id}', [LocationController::class, 'cityDelete'])->name('delete');
    });
    
    // state route

Route::prefix('state')->name('state.')->group(function () {
    
    Route::get('/', [LocationController::class, 'stateIndex'])->name('index');
    Route::get('/create', [LocationController::class, 'stateCreate'])->name('create');
    Route::post('/store', [LocationController::class, 'stateStore'])->name('store');
    Route::get('/edit/{id}', [LocationController::class, 'stateEdit'])->name('edit');
    Route::post('/update/{id}', [LocationController::class, 'stateUpdate'])->name('update');
    Route::delete('/delete/{id}', [LocationController::class, 'stateDelete'])->name('delete');
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
       
          // City route
         
                  // section route
                  Route::prefix('section')->name('section.')->group(function () {
                    Route::get('/', function() {
                        return view('admin.section.index');
                    })->name('index');
                });
              
});