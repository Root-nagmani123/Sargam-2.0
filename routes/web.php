<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\{
    RoleController,
    PermissionController,
    UserController,
    MemberController,
    CourseController,
    FacultyController,
    StreamController,
    SubjectModuleController,
    SubjectMasterController,
    VenueMasterController,
    GroupMappingController,
    CalendarController,
    MDOEscrotExemptionController,
    AttendanceController,
    StudentMedicalExemptionController,
    CourseMemoDecisionMappController,
    CourseAttendanceNoticeMapController,
    HostelBuildingFloorMappingController,
    HostelBuildingFloorRoomMappingController,
    NoticeController
};
use App\Http\Controllers\Dashboard\Calendar1Controller;

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
          Route::get('users/get-roles', [UserController::class, 'getAllRoles'])
        ->name('users.getRoles'); 
       Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
Route::get('roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit');
Route::put('roles/{id}', [RoleController::class, 'update'])->name('roles.update');
Route::delete('roles/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Route::resource('permissions', PermissionController::class);
        Route::resource('users', UserController::class);
Route::get('users/assign-role/{id}', [UserController::class, 'assignRole'])->name('users.assignRole');
Route::post('users/assign-role-save', [UserController::class, 'assignRoleSave'])
    ->name('users.assignRoleSave');
  


    });

    // // Dashboard
    // Route::get('/dashboard', function () {
    //     $year = request('year', now()->year);
    //     $month = request('month', now()->month);
    //     $events = []; // Add your events logic here if needed
    //     return view('admin.dashboard', compact('year', 'month', 'events'));

    // })->name('admin.dashboard');

           Route::get('/dashboard', [UserController::class, 'dashboard'])->name('admin.dashboard');


    Route::get('/calendar', [Calendar1Controller::class, 'index'])->name('calendar.index');

    // Route::get('/home', [HomeController::class, 'index'])->name('home');

	// By Dhananjay
//Route::post('/faculty/check-unique', [FacultyController::class, 'checkUnique'])->name('faculty.checkUnique');


    // Member Routes
    Route::prefix('member')->name('member.')->controller(MemberController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::get('edit/{id}', 'edit')->name('edit');
        Route::get('show/{id}', 'show')->name('show');
        Route::get('/step/{step}', 'loadStep')->name('load-step');
        Route::get('/edit-step/{step}/{id}', 'editStep')->name('edit-step');
        Route::post('/validate-step/{step}', 'validateStep');
        Route::post('/update-validate-step/{step}/{id}', 'updateValidateStep');
        Route::post('/store', 'store')->name('store');
        Route::post('update', 'update')->name('update');
        Route::get('excel-export', 'excelExport')->name('excel.export');
    });

    // Faculty Routes
    Route::prefix('faculty')->name('faculty.')->controller(FacultyController::class)->group(function () {

        Route::get('/',  'index')->name('index');
        Route::get('create',  'create')->name('create');
        Route::post('store',  'store')->name('store');
        Route::get('edit/{id}',  'edit')->name('edit');
        Route::post('update',  'update')->name('update');
        Route::get('show/{id}',  'show')->name('show');
        Route::get('excel-export',  'excelExportFaculty')->name('excel.export');
		Route::post('check-unique', 'checkUnique')->name('checkUnique');
    Route::get('search-first-name', 'searchFirstName')->name('searchFirstName');
    Route::get('check-firstname', 'checkFirstName')->name('checkFirstName');
    Route::get('check-fullname', 'checkFullName')->name('checkFullName');
    Route::get('details/{id}', 'getFacultyDetails')->name('details');
    Route::get('download/{id}', 'downloadPDF')->name('download');

    // Static view
    Route::get('print-blank', function () {
        return view('admin.faculty.blank-print');
    })->name('printBlank');

    });

    // Programme Routes
    Route::prefix('programme')->name('programme.')->controller(CourseController::class)->group(function () {

        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::get('edit/{id}', 'edit')->name('edit');
        Route::get('view/{id}', 'view')->name('view');
        Route::get('show/{id}', 'show')->name('show');
        Route::get('download-pdf/{id}', 'downloadPdf')->name('download.pdf');
        Route::get('debug/{id}', 'debug')->name('debug');
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
    Route::resource('Venue-Master', VenueMasterController::class);


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

    Route::prefix('admin')->name('admin.')->group(function () {
 
Route::resource('notice', NoticeController::class)
     ->except(['show'])
     ->parameters(['notice' => 'encId']);

Route::get('/notice/get-courses', [NoticeController::class, 'getCourses'])
     ->name('notice.getCourses');
Route::post('/summernote/upload', [UserController::class, 'uploadPdf'])->name('summernote.upload');

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
    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/', [CalendarController::class, 'index'])->name('index');
        Route::get('/get-subject-Name', [CalendarController::class, 'getSubjectName'])->name('get.subject.name');
        Route::post('/events', [CalendarController::class, 'store'])->name('event.store');
        Route::get('/full-calendar-details', [CalendarController::class, 'fullCalendarDetails'])->name('event.calendar-details');
        Route::get('/single-calendar-details', [CalendarController::class, 'SingleCalendarDetails'])->name('event.Singlecalendar-details');

        Route::get('/event-edit/{id}', [CalendarController::class, 'event_edit'])->name('calendar.event.show');
        Route::put('/event-update/{id}', [CalendarController::class, 'update_event'])->name('calendar.event.update');
        Route::get('/get-group-types', [CalendarController::class, 'getGroupTypes'])->name('get.group.types');

        Route::delete('/event-delete/{id}', [CalendarController::class, 'delete_event'])->name('calendar.event.delete');
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

    // Group Mapping Routes
    Route::prefix('group-mapping')->name('group.mapping.')->controller(GroupMappingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('edit/{id}', 'edit')->name('edit');
        Route::post('store', 'store')->name('store');
        Route::post('import-group-mapping', 'importGroupMapping')->name('import');
        Route::post('add-single-student', 'addSingleStudent')->name('add.single.student');
        Route::post('student-list', 'studentList')->name('student.list');
        Route::post('student-update', 'updateStudent')->name('student.update');
        Route::delete('student-delete', 'deleteStudent')->name('student.delete');
        Route::post('send-message', 'sendMessage')->name('send.message');
        Route::get('export-student-list/{id?}', 'exportStudentList')->name('export.student.list');
        Route::delete('delete/{id}', 'delete')->name('delete');
    });

    //feedback route
    Route::prefix('feedback')->name('feedback.')->group(function () {
       Route::get('/', [CalendarController::class, 'feedbackList'])->name('get.feedbackList');
      Route::get('/event-feedback/{id}', [CalendarController::class, 'getEventFeedback']);
       Route::get('/student-feedback', [CalendarController::class, 'studentFeedback'])->name('get.studentFeedback');
        Route::post('/submit-feedback', [CalendarController::class, 'submitFeedback'])->name('submit.feedback');
    });
    // MDO Escrot Exemption Routes
    Route::prefix('mdo-escrot-exemption')->name('mdo-escrot-exemption.')->controller(MDOEscrotExemptionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::get('/destroy', 'destroy')->name('destroy');
        Route::post('/update', 'update')->name('update');
        Route::post('get-student-list-according-to-course', 'getStudentListAccordingToCourse')->name('get.student.list.according.to.course');
    });

    // Attendance Routes
    Route::prefix('attendance')->name('attendance.')->controller(AttendanceController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/get-attendance-list', 'getAttendanceList')->name('get.attendance.list');
        Route::get('/create', 'create')->name('create');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::get('/mark/{group_pk}/{course_pk}/{timetable_pk}', 'markAttendanceView')->name('mark');
        Route::post('/save', 'save')->name('save');
    });

   Route::prefix('student-medical-exemption')->name('student.medical.exemption.')->controller(StudentMedicalExemptionController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/get-students-by-course', 'getStudentsByCourse')->name('getStudentsByCourse');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/update/{id}', 'update')->name('update');

    Route::delete('/delete/{id}', 'delete')->name('delete');
});

Route::prefix('admin/course-memo-decision')
    ->name('course.memo.decision.')
    ->controller(CourseMemoDecisionMappController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'destroy')->name('delete');

    });
    Route::prefix('admin/memo-notice-management')
    ->name('memo.notice.management.')
    ->controller(CourseAttendanceNoticeMapController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/conversation/{id}/{type}', 'conversation')->name('conversation');
        Route::get('/get_conversation_model/{id}/{type}', 'get_conversation_model')->name('get_conversation_model');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/Subject-by-course', 'getSubjectByCourse')->name('getSubjectByCourse'); // <-- New AJAX route
        Route::get('/Topic-by-subject', 'getTopicBysubject')->name('getTopicBysubject'); // <-- New AJAX route
        Route::get('/get-timetable-Details-By-topic', 'gettimetableDetailsBytopic')->name('gettimetableDetailsBytopic'); // <-- New AJAX route
        Route::post('/get-student-attendance-by-topic', 'getStudentAttendanceBytopic')->name('getStudentAttendanceBytopic'); // <-- New AJAX route
        Route::post('/store_memo_notice', 'store_memo_notice')->name('store_memo_notice');
        Route::post('/store_memo_status', 'store_memo_status')->name('store_memo_status');
        Route::post('/memo_notice_conversation', 'memo_notice_conversation')->name('memo_notice_conversation');
        Route::post('/memo_notice_conversation_student', 'memo_notice_conversation_student')->name('memo_notice_conversation_student');
        Route::post('/memo_notice_conversation_model', 'memo_notice_conversation_model')->name('memo_notice_conversation_model');
   Route::delete('/notice-delete-Message/{id}/{type}', [CourseAttendanceNoticeMapController::class, 'noticedeleteMessage'])
    ->name('noticedeleteMessage');
//  Route::get('/user_chat', function () {
//     return view('admin.courseAttendanceNoticeMap.chat');
// })->name('admin.courseAttendanceNoticeMap.chat');
        Route::get('/user', 'user')->name('user');
        Route::get('/conversation_student/{id}/{type}', 'conversation_student')->name('conversation_student');
        Route::post('/memo/get-data', 'getMemoData')->name('get_memo_data');

    });
    Route::get('/user/chat', function () {
    return view('admin.courseAttendanceNoticeMap.chat');
})->name('admin.courseAttendanceNoticeMap.chat');

    Route::prefix('hostel-building-map')->name('hostel.building.map.')->controller(HostelBuildingFloorMappingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');

        Route::get('assign-student', 'assignStudent')->name('assign.student');
        Route::post('assign-hostel-student', 'assignHostelToStudent')->name('assign.hostel.to.student');
        Route::get('export', 'export')->name('export');
        Route::get('import', 'import')->name('import');
        Route::post('import', 'import')->name('import');
    });

    Route::prefix('hostel-building-floor-room-map')->name('hostel.building.floor.room.map.')->controller(HostelBuildingFloorRoomMappingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::get('/export', 'export')->name('export');
        Route::post('/update-comment', 'updateComment')->name('update.comment');
    });
});



// //fc front page route
Route::get('/fc-front', function () {
    return view('fc.front_page');
})->name('fc.front');

Route::get('/admin/memo-conversation', function () {
    return view('admin.courseAttendanceNoticeMap.memo_conversation'); // or any other view you want to show
})->name('admin.courseAttendanceNoticeMap.memo_conversation');

//route for admin notice/ memo conversation
Route::get('/admin/memo-notice', function () {
    return view('admin.courseAttendanceNoticeMap.memo_notice'); // or any other view you want to show
})->name('admin.courseAttendanceNoticeMap.memo_notice');


// setup route

use App\Http\Controllers\Admin\Setup\EmployeeTypeController;
use App\Http\Controllers\Admin\Setup\EmployeeGroupController;
use App\Http\Controllers\Admin\Setup\DepartmentMasterSetupController;
use App\Http\Controllers\Admin\Setup\DesignationMasterSetupController;
use App\Http\Controllers\Admin\Setup\CasteCategoryController;

// Setup -> Employee Type (moved to controller with modal CRUD)
Route::middleware(['auth'])->group(function(){
    Route::prefix('admin/setup/employee-type')->name('admin.setup.employee_type.')->controller(EmployeeTypeController::class)->group(function(){
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });
    Route::prefix('admin/setup/employee-group')->name('admin.setup.employee_group.')->controller(EmployeeGroupController::class)->group(function(){
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });
    Route::prefix('admin/setup/department-master')->name('admin.setup.department_master.')->controller(DepartmentMasterSetupController::class)->group(function(){
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });
    Route::prefix('admin/setup/designation-master')->name('admin.setup.designation_master.')->controller(DesignationMasterSetupController::class)->group(function(){
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });
    Route::prefix('admin/setup/caste-category')->name('admin.setup.caste_category.')->controller(CasteCategoryController::class)->group(function(){
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });
    Route::prefix('admin/setup/caste-category')->name('admin.setup.caste_category.')->controller(CasteCategoryController::class)->group(function(){
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });
    Route::prefix('admin/setup/member')->name('admin.setup.member.')->controller(MemberController::class)->group(function(){
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });
});
