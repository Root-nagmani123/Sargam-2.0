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
    NoticeNotificationController,
    MedicalExceptionFacultyViewController,
    MedicalExceptionOTViewController,
    OTMDOEscrotExemptionController,
    FacultyMDOEscortExceptionViewController,
    OTNoticeMemoViewController,
    FacultyNoticeMemoViewController,
    NotificationController,
    MemoDisciplineController,
    DashboardController,
    DashboardStatisticsController,
    ParticipantHistoryController,
    CourseRepositoryController,
    EmployeeIDCardRequestController,
    DuplicateIDCardRequestController,
    FamilyIDCardRequestController,
    WhosWhoController,
    EstateController,
};
use App\Http\Controllers\Dashboard\Calendar1Controller;
use App\Http\Controllers\Admin\MemoNoticeController;
use App\Http\Controllers\Admin\Master\DisciplineMasterController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\IssueManagement\{
    IssueManagementController,
    IssueCategoryController,
    IssueSubCategoryController,
    IssuePriorityController,
    IssueEscalationMatrixController
};
use App\Http\Controllers\Admin\Estate\{
    EstateCampusController,
    EstateElectricSlabController,
    UnitTypeController,
    UnitSubTypeController,
    EstateBlockController,
    PayScaleController,
    EligibilityCriteriaController
};

Route::get('clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('optimize:clear');
    return redirect()->back()->with('success', 'Cache cleared successfully');
});

/**
 * Full optimize flow: clear → config cache → route cache → optimize
 * Step-by-step proper order. Use after deployment or when caches need refresh.
 * GET /admin/system/optimize (auth required)
 */
Route::get('admin/system/optimize', function () {
    $steps = [];
    $run = function ($command, $name) use (&$steps) {
        try {
            \Illuminate\Support\Facades\Artisan::call($command);
            $output = trim(\Illuminate\Support\Facades\Artisan::output());
            $steps[] = ['command' => $command, 'name' => $name, 'ok' => true, 'output' => $output ?: 'OK'];
        } catch (\Throwable $e) {
            $steps[] = ['command' => $command, 'name' => $name, 'ok' => false, 'output' => $e->getMessage()];
        }
    };

    // Step 1: Clear everything (clean slate)
    $run('config:clear', 'Config cache clear');
    $run('cache:clear', 'Application cache clear');
    $run('view:clear', 'View cache clear');
    $run('route:clear', 'Route cache clear');

    // Step 2: Cache config & routes
    $run('config:cache', 'Config cache');
    $run('route:cache', 'Route cache');

    // Step 3: View cache (if available) and optimize
    try {
        \Illuminate\Support\Facades\Artisan::call('view:cache');
        $steps[] = ['command' => 'view:cache', 'name' => 'View cache', 'ok' => true, 'output' => trim(\Illuminate\Support\Facades\Artisan::output()) ?: 'OK'];
    } catch (\Throwable $e) {
        $steps[] = ['command' => 'view:cache', 'name' => 'View cache', 'ok' => false, 'output' => $e->getMessage()];
    }
    $run('optimize', 'Optimize (autoload + bootstrap)');

    $allOk = collect($steps)->every(fn ($s) => $s['ok']);
    if (request()->wantsJson()) {
        return response()->json(['success' => $allOk, 'steps' => $steps]);
    }
    return response()->view('admin.system.optimize-result', compact('steps', 'allOk'));
})->middleware('auth')->name('admin.system.optimize');
// Authentication Routes
Auth::routes(['verify' => true, 'register' => false]);

// Public Routes
Route::get('/', [LoginController::class, 'showLoginForm'])->name('home');
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
    Route::get('/breadcrumb-showcase', fn () => view('admin.breadcrumb-showcase'))->name('admin.breadcrumb-showcase');
    Route::get('/dashboard/students', [UserController::class, 'studentList'])->name('admin.dashboard.students');
    Route::get('/dashboard/students/{id}/detail', [UserController::class, 'studentDetail'])->name('admin.dashboard.students.detail');
    Route::get('/dashboard/students/{id}/history', [ParticipantHistoryController::class, 'show'])->name('admin.dashboard.students.history');


    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

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
        Route::delete('delete/{id}', 'destroy')->name('destroy');
    });

    // Faculty Routes
    Route::prefix('faculty')->name('faculty.')->controller(FacultyController::class)->group(function () {

        Route::get('/',  'index')->name('index');
        Route::get('create',  'create')->name('create');
        Route::post('store',  'store')->name('store');
        Route::get('edit/{id}',  'edit')->name('edit');
        Route::post('update',  'update')->name('update');
        Route::get('show/{id}',  'show')->name('show');
        Route::delete('delete/{id}',  'destroy')->name('destroy');
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
        Route::delete('delete/{id}', 'destroy')->name('destroy');
        Route::get('get-courses-by-status', 'getCoursesByStatus')->name('get.courses.by.status');
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
     Route::post('admin/stream/toggle-status', [StreamController::class, 'toggleStatus'])
    ->name('admin.stream.toggleStatus');
    // Section (placeholder: redirects to Stream until Section CRUD is implemented)
    Route::get('section', function () {
        return redirect()->route('stream.index');
    })->name('section.index');
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

        Route::resource('notice', NoticeNotificationController::class)
            ->except(['show'])
            ->parameters(['notice' => 'encId']);

        Route::get('/notice/get-courses', [NoticeNotificationController::class, 'getCourses'])
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
        Route::post('/event-update/{id}', [CalendarController::class, 'update_event'])->name('calendar.event.update');
        Route::get('/get-group-types', [CalendarController::class, 'getGroupTypes'])->name('get.group.types');

        Route::delete('/event-delete/{id}', [CalendarController::class, 'delete_event'])->name('calendar.event.delete');

        Route::get('/get-week', [CalendarController::class, 'weeklyTimetable'])->name('getWeek');
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


    // Group Mapping Routes
    Route::prefix('group-mapping')->name('group.mapping.')->controller(GroupMappingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('edit/{id}', 'edit')->name('edit');
        Route::post('store', 'store')->name('store');
        Route::post('import-group-mapping', 'importGroupMapping')->name('import');
        Route::post('get-group-names-by-type', 'getGroupNamesByType')->name('get.group.names.by.type');
        Route::post('add-single-student', 'addSingleStudent')->name('add.single.student');
        Route::post('student-list', 'studentList')->name('student.list');
        Route::post('student-update', 'updateStudent')->name('student.update');
        Route::delete('student-delete', 'deleteStudent')->name('student.delete');
        Route::post('send-message', 'sendMessage')->name('send.message');
        Route::get('export-student-list/{id?}', 'exportStudentList')->name('export.student.list');
        Route::delete('delete/{id}', 'delete')->name('delete');
       // Route::post('get-courses-by-status', 'getCoursesByStatus')->name('get.courses.by.status');

     Route::post('get-courses-by-status', 'getCoursesByStatus')
    ->name('get.courses.by.status');





    });

    //feedback route
    Route::prefix('feedback')->name('feedback.')->group(function () {
        Route::get('/', [CalendarController::class, 'feedbackList'])->name('get.feedbackList');
        Route::get('/event-feedback/{id}', [CalendarController::class, 'getEventFeedback']);
        Route::get('/student-feedback', [CalendarController::class, 'studentFeedback'])->name('get.studentFeedback');
        Route::post('/submit-feedback', [CalendarController::class, 'submitFeedback'])->name('submit.feedback');
    });
    // MDO/Escort Exemption Routes
    Route::prefix('mdo-escrot-exemption')->name('mdo-escrot-exemption.')->controller(MDOEscrotExemptionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::delete('/destroy/{id}', 'destroy')->name('destroy');
        Route::post('/update', 'update')->name('update');
        Route::post('get-student-list-according-to-course', 'getStudentListAccordingToCourse')->name('get.student.list.according.to.course');
    });

    // ============================================
    // Security Management Routes (Vehicle & Visitor Pass)
    // ============================================

    // Vehicle Type Master Routes
    Route::prefix('security/vehicle-type')->name('admin.security.vehicle_type.')->controller(\App\Http\Controllers\Admin\Security\VehicleTypeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
        Route::post('/toggle-status/{id}', 'toggleStatus')->name('toggle.status');
    });

    // Vehicle Pass Configuration Routes
    Route::prefix('security/vehicle-pass-config')->name('admin.security.vehicle_pass_config.')->controller(\App\Http\Controllers\Admin\Security\VehiclePassConfigController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::put('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
        Route::post('/toggle-status/{id}', 'toggleStatus')->name('toggle.status');
    });

    // Vehicle Pass Application Routes
    Route::prefix('security/vehicle-pass')->name('admin.security.vehicle_pass.')->controller(\App\Http\Controllers\Admin\Security\VehiclePassController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/export', 'export')->name('export');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });

    // Duplicate Vehicle Pass Application Routes
    Route::prefix('security/duplicate-vehicle-pass')->name('admin.security.duplicate_vehicle_pass.')->controller(\App\Http\Controllers\Admin\Security\DuplicateVehiclePassController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'destroy')->name('delete');
        Route::get('/api/vehicle-details', 'getVehicleDetails')->name('api.vehicle_details');
    });

    // Vehicle Pass Approval Routes
    Route::prefix('security/vehicle-pass-approval')->name('admin.security.vehicle_pass_approval.')->controller(\App\Http\Controllers\Admin\Security\VehiclePassApprovalController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/all', 'allApplications')->name('all');
        Route::get('/show/{id}', 'show')->name('show');
        
        Route::post('/approve/{id}', 'approve')->name('approve');
        Route::post('/reject/{id}', 'reject')->name('reject');
    });

    // Employee ID Card Approval Routes (Approval I & II)
    Route::prefix('security/employee-idcard-approval')->name('admin.security.employee_idcard_approval.')->controller(\App\Http\Controllers\Admin\Security\EmployeeIDCardApprovalController::class)->group(function () {
        Route::get('/approval1', 'approval1')->name('approval1');
        Route::get('/approval2', 'approval2')->name('approval2');
        Route::get('/all', 'all')->name('all');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/export', 'export')->name('export');
        Route::post('/approve1/{id}', 'approve1')->name('approve1');
        Route::post('/approve2/{id}', 'approve2')->name('approve2');
        Route::post('/reject1/{id}', 'reject1')->name('reject1');
        Route::post('/reject2/{id}', 'reject2')->name('reject2');
    });

    // Family ID Card Approval Routes
    Route::prefix('security/family-idcard-approval')->name('admin.security.family_idcard_approval.')->controller(\App\Http\Controllers\Admin\Security\FamilyIDCardApprovalController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/all', 'all')->name('all');
        Route::get('/show/{id}', 'show')->name('show');
        Route::post('/approve/{id}', 'approve')->name('approve');
        Route::post('/reject/{id}', 'reject')->name('reject');
        Route::post('/approve-group/{id}', 'approveGroup')->name('approve_group');
        Route::post('/reject-group/{id}', 'rejectGroup')->name('reject_group');
    });

    // Visitor/Gate Pass Routes
    Route::prefix('security/visitor-pass')->name('admin.security.visitor_pass.')->controller(\App\Http\Controllers\Admin\Security\VisitorPassController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
        Route::post('/checkout/{id}', 'checkOut')->name('checkout');
    });

    // ============================================
    // End Security Management Routes
    // ============================================

    // Attendance Routes
    Route::prefix('attendance')->name('attendance.')->controller(AttendanceController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/get-attendance-list', 'getAttendanceList')->name('get.attendance.list');
        Route::get('/create', 'create')->name('create');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::get('/mark/{group_pk}/{course_pk}/{timetable_pk}', 'markAttendanceView')->name('mark');
        Route::post('/save', 'save')->name('save');
        Route::get('/export/{group_pk}/{course_pk}/{timetable_pk}', 'export')->name('export');

        Route::get('/user_attendance', 'index')->name('user_attendance.index');
        Route::get('/student_mark/{group_pk}/{course_pk}/{timetable_pk}', 'markAttendanceView')->name('student_mark');
        // Route::get('/student_mark/{group_pk}/{course_pk}/{timetable_pk}/{student_pk}', 'OTmarkAttendanceView')->name('OT.student_mark.student');
        Route::get('/student_mark/{group_pk}/{course_pk}/{timetable_pk}/{student_pk}', 'OTmarkAttendanceView')->name('OT.student_mark.student');
    });

    Route::get('/getstudentmarks', [AttendanceController::class, 'OTmarkAttendanceData'])->name('ot.student.attendance.data');
    Route::prefix('student-medical-exemption')->name('student.medical.exemption.')->controller(StudentMedicalExemptionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/get-students-by-course', 'getStudentsByCourse')->name('getStudentsByCourse');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::get('/export', 'export')->name('export');

        Route::delete('/delete/{id}', 'delete')->name('delete');
    });

    // Medical Exception Views
    Route::get('/medical-exception-faculty-view', [MedicalExceptionFacultyViewController::class, 'index'])->name('medical.exception.faculty.view');

    Route::get('/medical-exception-ot-view', [MedicalExceptionOTViewController::class, 'index'])->name('medical.exception.ot.view');

    // OT MDO/Escort Exception View
    Route::get('/ot-mdo-escrot-exemption-view', [OTMDOEscrotExemptionController::class, 'index'])->name('ot.mdo.escrot.exemption.view');

    // Faculty MDO/Escort Exception View
    Route::get('/faculty-mdo-escort-exception-view', [FacultyMDOEscortExceptionViewController::class, 'index'])->name('faculty.mdo.escort.exception.view');

    // OT Notice / Memo View
    Route::get('/ot-notice-memo-view', [OTNoticeMemoViewController::class, 'index'])->name('ot.notice.memo.view');

    // Faculty Notice / Memo View
    Route::get('/faculty-notice-memo-view', [FacultyNoticeMemoViewController::class, 'index'])->name('faculty.notice.memo.view');

    Route::prefix('admin/course-memo-decision')
        ->name('course.memo.decision.')
        ->controller(CourseMemoDecisionMappController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::get('/edit/{id}', 'edit')->name('edit');
            Route::post('/update', 'update')->name('update');
            Route::delete('/delete/{id}', 'destroy')->name('delete');
        });
    Route::prefix('admin/memo-notice-management')
        ->name('memo.notice.management.')
        ->controller(CourseAttendanceNoticeMapController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/conversation/{id}/{type}', 'conversation')->name('conversation');
            Route::get('/get_conversation_model/{id}/{type}/{user_type}', 'get_conversation_model')->name('get_conversation_model');
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
            Route::post('/memo/get-data', [CourseAttendanceNoticeMapController::class, 'getMemoData'])->name('get_memo_data');
            Route::post('/memo/get-generated-data', [CourseAttendanceNoticeMapController::class, 'getGeneratedMemoData'])->name('get_generated_memo_data');
            Route::get('/export-pdf', 'exportPdf')->name('export_pdf');

            Route::post('admin/memo-notice-management/filter', 'filter')->name('filter');
            Route::get('admin/memo-notice-management/filter', 'clear_filter')->name('clear_filter');

        });



    Route::get('/send_notice', [CourseAttendanceNoticeMapController::class, 'send_only_notice'])->name('send.notice.management.index');
    Route::get('/attendance_send_notice/{group_pk}/{course_pk}/{timetable_pk}', [CourseAttendanceNoticeMapController::class, 'view_all_notice_list'])->name('attendance.send_notice');
    Route::post('/notice_direct_save', [CourseAttendanceNoticeMapController::class, 'notice_direct_save'])->name('notice.direct.save');


    Route::prefix('admin/discipline')->name('master.discipline.')->group(function () {
        Route::get('/', [DisciplineMasterController::class, 'index'])->name('index');
        Route::get('create', [DisciplineMasterController::class, 'create'])->name('create');
        Route::get('edit/{id}', [DisciplineMasterController::class, 'edit'])->name('edit');
        Route::post('store', [DisciplineMasterController::class, 'store'])->name('store');
        Route::delete('delete/{id}', [DisciplineMasterController::class, 'destroy'])->name('delete');
    });
    Route::prefix('memo/discipline')->name('memo.discipline.')->group(function () {
        Route::get('/', [MemoDisciplineController::class, 'index'])->name('index');
        Route::get('create', [MemoDisciplineController::class, 'create'])->name('create');
        Route::get('edit/{id}', [MemoDisciplineController::class, 'edit'])->name('edit');
        Route::post('/discipline_generate_memo_store', [MemoDisciplineController::class, 'discipline_generate_memo_store'])->name('discipline_generate_memo_store');

        Route::get('/get-student-by-course', [MemoDisciplineController::class, 'getStudentByCourse'])->name('getStudentByCourse');
        Route::get('/getMarkDeduction', [MemoDisciplineController::class, 'getMarkDeduction'])->name('getMarkDeduction');

        Route::post('/send-memo', [MemoDisciplineController::class, 'sendMemo'])->name('sendMemo');
        Route::post('/close-memo', [MemoDisciplineController::class, 'closeMemo'])->name('closeMemo');
        Route::get('/get_conversation_model/{memoId}/{type}', [MemoDisciplineController::class, 'getConversationModel'])->name('get_conversation_model');

        Route::post('/memo-discipline-conversation-store', [MemoDisciplineController::class, 'memoDisciplineConversationStore'])->name('conversation.store');


        Route::get('/memo-discipline-show/{id}', [MemoDisciplineController::class, 'memo_show'])->name('memo.show');
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
        Route::post('import', 'processImport')->name('process.import');
    });

    Route::prefix('hostel-building-floor-room-map')->name('hostel.building.floor.room.map.')->controller(HostelBuildingFloorRoomMappingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::get('/export', 'export')->name('export');
        Route::post('/update-comment', 'updateComment')->name('update.comment');
    });

    // Mess Management
    Route::prefix('admin/mess')->name('admin.mess.')->group(function () {
        // Master Data
        Route::resource('events', \App\Http\Controllers\Mess\EventController::class)->only(['index', 'create', 'store']);
        Route::resource('inventories', \App\Http\Controllers\Mess\InventoryController::class)->only(['index', 'create', 'store']);
        Route::resource('vendors', \App\Http\Controllers\Mess\VendorController::class)->except(['show']);
        Route::resource('invoices', \App\Http\Controllers\Mess\InvoiceController::class)->only(['index', 'create', 'store']);
        Route::resource('itemcategories', \App\Http\Controllers\Mess\ItemCategoryController::class)->except(['show']);
        Route::resource('itemsubcategories', \App\Http\Controllers\Mess\ItemSubcategoryController::class)->except(['show']);
        Route::resource('storeallocations', \App\Http\Controllers\Mess\StoreAllocationController::class)->only(['index', 'store']);
        Route::get('storeallocations/{id}/edit', [\App\Http\Controllers\Mess\StoreAllocationController::class, 'edit'])->name('storeallocations.edit');
        Route::put('storeallocations/{id}', [\App\Http\Controllers\Mess\StoreAllocationController::class, 'update'])->name('storeallocations.update');
        Route::delete('storeallocations/{id}', [\App\Http\Controllers\Mess\StoreAllocationController::class, 'destroy'])->name('storeallocations.destroy');

        // Store Management
        Route::resource('stores', \App\Http\Controllers\Mess\StoreController::class)->except(['show']);

        Route::resource('sub-stores', \App\Http\Controllers\Mess\SubStoreController::class)->except(['show']);

        // NEW: Setup - Configuration Modules
        Route::resource('vendor-item-mappings', \App\Http\Controllers\Mess\VendorItemMappingController::class);
        Route::resource('menu-rate-lists', \App\Http\Controllers\Mess\MenuRateListController::class);
        Route::resource('sale-counters', \App\Http\Controllers\Mess\SaleCounterController::class);
        Route::resource('sale-counter-mappings', \App\Http\Controllers\Mess\SaleCounterMappingController::class);
        Route::resource('credit-limits', \App\Http\Controllers\Mess\CreditLimitController::class);
        Route::resource('client-types', \App\Http\Controllers\Mess\ClientTypeController::class)->except(['show']);
        Route::post('meal-rate-master/{id}/toggle-status', [\App\Http\Controllers\Mess\MealRateMasterController::class, 'toggleStatus'])->name('meal-rate-master.toggle-status');
        Route::resource('meal-rate-master', \App\Http\Controllers\Mess\MealRateMasterController::class)->except(['show']);
        Route::resource('number-configs', \App\Http\Controllers\Mess\NumberConfigController::class);

        // Purchase Order Management
        Route::resource('purchaseorders', \App\Http\Controllers\Mess\PurchaseOrderController::class)->except(['edit', 'update', 'destroy']);
        Route::get('purchaseorders/{id}/edit', [\App\Http\Controllers\Mess\PurchaseOrderController::class, 'edit'])->name('purchaseorders.edit');
        Route::put('purchaseorders/{id}', [\App\Http\Controllers\Mess\PurchaseOrderController::class, 'update'])->name('purchaseorders.update');
        Route::delete('purchaseorders/{id}', [\App\Http\Controllers\Mess\PurchaseOrderController::class, 'destroy'])->name('purchaseorders.destroy');
        Route::post('purchaseorders/{id}/approve', [\App\Http\Controllers\Mess\PurchaseOrderController::class, 'approve'])->name('purchaseorders.approve');
        Route::post('purchaseorders/{id}/reject', [\App\Http\Controllers\Mess\PurchaseOrderController::class, 'reject'])->name('purchaseorders.reject');
        Route::get('purchaseorders/vendor/{vendorId}/items', [\App\Http\Controllers\Mess\PurchaseOrderController::class, 'getVendorItems'])->name('purchaseorders.vendor.items');

        // Material Management (formerly Kitchen Issue)
        Route::get('material-management/students-by-course/{course_pk}', [\App\Http\Controllers\Mess\KitchenIssueController::class, 'getStudentsByCourse'])->name('material-management.students-by-course');
        Route::get('material-management/store/{storeIdentifier}/items', [\App\Http\Controllers\Mess\KitchenIssueController::class, 'getStoreItems'])->name('material-management.store.items');
        Route::resource('material-management', \App\Http\Controllers\Mess\KitchenIssueController::class);
        Route::get('material-management/{id}/return', [\App\Http\Controllers\Mess\KitchenIssueController::class, 'returnData'])->name('material-management.return');
        Route::put('material-management/{id}/return', [\App\Http\Controllers\Mess\KitchenIssueController::class, 'updateReturn'])->name('material-management.update-return');
        Route::post('material-management/{id}/send-for-approval', [\App\Http\Controllers\Mess\KitchenIssueController::class, 'sendForApproval'])->name('material-management.send-for-approval');
        Route::get('material-management/records/ajax', [\App\Http\Controllers\Mess\KitchenIssueController::class, 'getKitchenIssueRecords'])->name('material-management.records');
        Route::get('material-management/reports/bill', [\App\Http\Controllers\Mess\KitchenIssueController::class, 'billReport'])->name('material-management.bill-report');

        // Selling Voucher with Date Range (standalone module - design like Selling Voucher, data separate)
        Route::get('selling-voucher-date-range/students-by-course/{course_pk}', [\App\Http\Controllers\Mess\SellingVoucherDateRangeController::class, 'getStudentsByCourse'])->name('selling-voucher-date-range.students-by-course');
        Route::get('selling-voucher-date-range/store/{storeIdentifier}/items', [\App\Http\Controllers\Mess\SellingVoucherDateRangeController::class, 'getStoreItems'])->name('selling-voucher-date-range.store.items');
        Route::resource('selling-voucher-date-range', \App\Http\Controllers\Mess\SellingVoucherDateRangeController::class);
        Route::get('selling-voucher-date-range/{id}/return', [\App\Http\Controllers\Mess\SellingVoucherDateRangeController::class, 'returnData'])->name('selling-voucher-date-range.return');
        Route::put('selling-voucher-date-range/{id}/return', [\App\Http\Controllers\Mess\SellingVoucherDateRangeController::class, 'updateReturn'])->name('selling-voucher-date-range.update-return');

        // Material Management Approval
        Route::prefix('material-management-approvals')->name('material-management-approvals.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Mess\KitchenIssueApprovalController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Mess\KitchenIssueApprovalController::class, 'show'])->name('show');
            Route::post('/{id}/approve', [\App\Http\Controllers\Mess\KitchenIssueApprovalController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [\App\Http\Controllers\Mess\KitchenIssueApprovalController::class, 'reject'])->name('reject');
        });

        // NEW: Billing & Finance
        Route::get('process-mess-bills-employee', [\App\Http\Controllers\Mess\ProcessMessBillsEmployeeController::class, 'index'])->name('process-mess-bills-employee.index');
        Route::get('process-mess-bills-employee/modal-data', [\App\Http\Controllers\Mess\ProcessMessBillsEmployeeController::class, 'modalData'])->name('process-mess-bills-employee.modal-data');
        Route::get('process-mess-bills-employee/{id}/payment-details', [\App\Http\Controllers\Mess\ProcessMessBillsEmployeeController::class, 'paymentDetails'])->name('process-mess-bills-employee.payment-details');
        Route::post('process-mess-bills-employee/{id}/generate-invoice', [\App\Http\Controllers\Mess\ProcessMessBillsEmployeeController::class, 'generateInvoice'])->name('process-mess-bills-employee.generate-invoice');
        Route::post('process-mess-bills-employee/{id}/generate-payment', [\App\Http\Controllers\Mess\ProcessMessBillsEmployeeController::class, 'generatePayment'])->name('process-mess-bills-employee.generate-payment');
        Route::get('process-mess-bills-employee/{id}/print-receipt', [\App\Http\Controllers\Mess\ProcessMessBillsEmployeeController::class, 'printReceipt'])->name('process-mess-bills-employee.print-receipt');
        Route::get('process-mess-bills-employee/export', [\App\Http\Controllers\Mess\ProcessMessBillsEmployeeController::class, 'export'])->name('process-mess-bills-employee.export');
        Route::resource('monthly-bills', \App\Http\Controllers\Mess\MonthlyBillController::class);
        Route::post('monthly-bills/generate', [\App\Http\Controllers\Mess\MonthlyBillController::class, 'generateBills'])->name('monthly-bills.generate');
        Route::resource('finance-bookings', \App\Http\Controllers\Mess\FinanceBookingController::class);
        Route::post('finance-bookings/{id}/approve', [\App\Http\Controllers\Mess\FinanceBookingController::class, 'approve'])->name('finance-bookings.approve');
        Route::post('finance-bookings/{id}/reject', [\App\Http\Controllers\Mess\FinanceBookingController::class, 'reject'])->name('finance-bookings.reject');

        // NEW: Mess RBAC - Permission Management
        // IMPORTANT: Custom routes MUST come BEFORE resource route
        Route::get('permissions/users-by-role', [\App\Http\Controllers\Mess\MessPermissionController::class, 'getUsersByRole'])->name('permissions.getUsersByRole');
        Route::get('permissions/check/{action}', [\App\Http\Controllers\Mess\MessPermissionController::class, 'checkPermission'])->name('permissions.check');
        Route::resource('permissions', \App\Http\Controllers\Mess\MessPermissionController::class);

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('stock-purchase-details', [\App\Http\Controllers\Mess\ReportController::class, 'stockPurchaseDetails'])->name('stock-purchase-details');
            Route::get('stock-purchase-details/export', [\App\Http\Controllers\Mess\ReportController::class, 'stockPurchaseDetailsExcel'])->name('stock-purchase-details.excel');
            Route::get('stock-summary', [\App\Http\Controllers\Mess\ReportController::class, 'stockSummary'])->name('stock-summary');
            Route::get('stock-summary/export', [\App\Http\Controllers\Mess\ReportController::class, 'stockSummaryExcel'])->name('stock-summary.excel');
            Route::get('category-wise-print-slip', [\App\Http\Controllers\Mess\ReportController::class, 'categoryWisePrintSlip'])->name('category-wise-print-slip');
            Route::get('category-wise-print-slip/export', [\App\Http\Controllers\Mess\ReportController::class, 'categoryWisePrintSlipExcel'])->name('category-wise-print-slip.excel');
            Route::get('stock-balance-till-date', [\App\Http\Controllers\Mess\ReportController::class, 'stockBalanceTillDate'])->name('stock-balance-till-date');
            Route::get('stock-balance-till-date/export', [\App\Http\Controllers\Mess\ReportController::class, 'stockBalanceTillDateExcel'])->name('stock-balance-till-date.excel');
            Route::get('selling-voucher-print-slip', [\App\Http\Controllers\Mess\ReportController::class, 'sellingVoucherPrintSlip'])->name('selling-voucher-print-slip');
            Route::get('selling-voucher-print-slip/export', [\App\Http\Controllers\Mess\ReportController::class, 'sellingVoucherPrintSlipExcel'])->name('selling-voucher-print-slip.excel');
            Route::get('purchase-sale-quantity', [\App\Http\Controllers\Mess\ReportController::class, 'purchaseSaleQuantityReport'])->name('purchase-sale-quantity');
            Route::get('purchase-sale-quantity/export', [\App\Http\Controllers\Mess\ReportController::class, 'purchaseSaleQuantityExcel'])->name('purchase-sale-quantity.excel');
        });
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
// Route::get('/admin/memo-notice', function () {
//     return view('admin.courseAttendanceNoticeMap.memo_notice'); // or any other view you want to show
// })->name('admin.courseAttendanceNoticeMap.memo_notice');

// routes/web.php (admin section)

Route::prefix('admin')->group(function () {
    Route::get('/memo-notice', [MemoNoticeController::class, 'index'])->name('admin.memo-notice.index');
    Route::get('/memo-notice/create', [MemoNoticeController::class, 'create'])->name('admin.memo-notice.create');
    Route::post('/memo-notice', [MemoNoticeController::class, 'store'])->name('admin.memo-notice.store');
    Route::get('/memo-notice/preview', [MemoNoticeController::class, 'preview'])->name('admin.memo-notice.preview');
    Route::get('/memo-notice/{pk}/edit', [MemoNoticeController::class, 'edit'])->name('admin.memo-notice.edit');
    Route::post('/memo-notice/{pk}', [MemoNoticeController::class, 'update'])->name('admin.memo-notice.update');
    Route::delete('/memo-notice/{pk}', [MemoNoticeController::class, 'destroy'])->name('admin.memo-notice.destroy');
    Route::post('/memo-notice/upload-pdf', [MemoNoticeController::class, 'uploadPdf'])->name('admin.memo-notice.upload-pdf');
    Route::post('/memo-notice/{pk}/status/{status}', [MemoNoticeController::class, 'changeStatus'])->name('admin.memo-notice.status');
});


// setup route

use App\Http\Controllers\Admin\Setup\EmployeeTypeController;
use App\Http\Controllers\Admin\Setup\EmployeeGroupController;
use App\Http\Controllers\Admin\Setup\DepartmentMasterSetupController;
use App\Http\Controllers\Admin\Setup\DesignationMasterSetupController;
use App\Http\Controllers\Admin\Setup\CasteCategoryController;

// Setup -> Employee Type (moved to controller with modal CRUD)
Route::middleware(['auth'])->group(function () {
    Route::prefix('admin/setup/employee-type')->name('admin.setup.employee_type.')->controller(EmployeeTypeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });
    Route::prefix('admin/setup/employee-group')->name('admin.setup.employee_group.')->controller(EmployeeGroupController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });
    Route::prefix('admin/setup/department-master')->name('admin.setup.department_master.')->controller(DepartmentMasterSetupController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });
    Route::prefix('admin/setup/designation-master')->name('admin.setup.designation_master.')->controller(DesignationMasterSetupController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });
    Route::prefix('admin/setup/caste-category')->name('admin.setup.caste_category.')->controller(CasteCategoryController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });

    // Employee ID Card Request Routes
    Route::prefix('admin/employee-idcard')->name('admin.employee_idcard.')->controller(EmployeeIDCardRequestController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/export', 'export')->name('export');
        Route::get('/create', 'create')->name('create');
        Route::get('/sub-types', 'subTypes')->name('subTypes');
        Route::get('/me', 'me')->name('me');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::put('/update/{id}', 'update')->name('update');
        Route::patch('/amend-dup-ext/{id}', 'amendDuplicationExtension')->name('amendDuplicationExtension');
        Route::delete('/delete/{id}', 'destroy')->name('destroy');
        Route::post('/restore/{id}', 'restore')->name('restore');
        Route::delete('/force-delete/{id}', 'forceDelete')->name('forceDelete');
    });

    // Duplicate ID Card Request Routes
    Route::prefix('admin/duplicate-idcard')->name('admin.duplicate_idcard.')->controller(DuplicateIDCardRequestController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::post('/store', 'store')->name('store');
        Route::post('/{id}/update', 'update')->name('update');
        Route::get('/lookup/by-card-number', 'lookupByCardNumber')->name('lookup');
    });

    // Family ID Card Request Routes
    Route::prefix('admin/family-idcard')->name('admin.family_idcard.')->controller(FamilyIDCardRequestController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/export', 'export')->name('export');
        Route::get('/members/{id}', 'members')->name('members');
        Route::post('/duplicate/{id}', 'duplicateRequest')->name('duplicate');
        Route::get('/show/{familyIDCardRequest}', 'show')->name('show');
        Route::get('/edit/{familyIDCardRequest}', 'edit')->name('edit');
        Route::put('/update/{familyIDCardRequest}', 'update')->name('update');
        Route::delete('/delete/{familyIDCardRequest}', 'destroy')->name('destroy');
        Route::post('/restore/{id}', 'restore')->name('restore');
        Route::delete('/force-delete/{id}', 'forceDelete')->name('forceDelete');
    });

    Route::prefix('admin/setup/member')->name('admin.setup.member.')->controller(MemberController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });


    /// Faculty Dashboard Route
    Route::get('/faculty_dashboard', function () {
        return view('faculty.dashboard');
    })->name('faculty.dashboard');

    // Notification Routes
    Route::prefix('admin/notifications')->name('admin.notifications.')->controller(NotificationController::class)->group(function () {
        // Mark as read and redirect (new method with redirect)
        Route::post('/mark-read-redirect/{id}', 'markAsReadAndRedirect')->name('mark-read-redirect');

        // Mark as read (legacy method for backward compatibility)
        Route::post('/mark-read/{id}', 'markAsRead')->name('mark-read');

        // Mark all as read
        Route::post('/mark-all-read', 'markAllAsRead')->name('mark-all-read');
    });

    //change password work here
    Route::get('/change_password', [UserController::class, 'change_password'])->name('admin.password.change_password');

    Route::post('/submit_change_password', [UserController::class, 'submit_change_password'])->name('admin.password.submit_change_password');


    // Report walal route

    Route::get('/faculty_view', function () {
        return view('admin.feedback.faculty_view');
    })->name('admin.feedback.faculty_view.page');

    Route::get('/feedback_details', function () {
        return view('admin.feedback.feedback_details');
    })->name('admin.feedback.feedback_details.page');

    //  dashboard page route

    //   Route::get('/active-course', function () { DashboardController:}})->name('admin.dashboard.active_course');
    Route::get('/active-course', [DashboardController::class, 'active_course'])->name('admin.dashboard.active_course');
    Route::get('/incoming-course', [DashboardController::class, 'incoming_course'])->name('admin.dashboard.incoming_course');
    Route::get('/guest-faculty', [DashboardController::class, 'guest_faculty'])->name('admin.dashboard.guest_faculty');
    Route::get('/inhouse-faculty', [DashboardController::class, 'inhouse_faculty'])->name('admin.dashboard.inhouse_faculty');

    // Who's Who Routes
    Route::get('/faculty/whos-who', [WhosWhoController::class, 'index'])->name('admin.faculty.whos-who');
    Route::get('/faculty/whos-who/courses', [WhosWhoController::class, 'getCourses'])->name('admin.faculty.whos-who.courses');
    Route::get('/faculty/whos-who/cadres', [WhosWhoController::class, 'getCadres'])->name('admin.faculty.whos-who.cadres');
    Route::get('/faculty/whos-who/counsellor-groups', [WhosWhoController::class, 'getCounsellorGroups'])->name('admin.faculty.whos-who.counsellor-groups');
    Route::get('/faculty/whos-who/students', [WhosWhoController::class, 'getStudents'])->name('admin.faculty.whos-who.students');
    Route::get('/faculty/whos-who/static-info', [WhosWhoController::class, 'getStaticInfo'])->name('admin.faculty.whos-who.static-info');
    Route::get('/sessions', [DashboardController::class, 'sessions'])->name('admin.dashboard.sessions');

    // Participant / Dashboard Statistics (charts data)
    Route::get('/dashboard-statistics/charts', [DashboardStatisticsController::class, 'charts'])->name('admin.dashboard-statistics.charts');
    Route::post('/dashboard-statistics/save-from-course', [DashboardStatisticsController::class, 'saveSnapshotFromCourse'])->name('admin.dashboard-statistics.save-from-course');
    Route::post('/dashboard-statistics/{dashboard_statistic}/set-default', [DashboardStatisticsController::class, 'setDefault'])->name('admin.dashboard-statistics.set-default');
    Route::resource('dashboard-statistics', DashboardStatisticsController::class)->names('admin.dashboard-statistics')->parameters(['dashboard_statistics' => 'dashboard_statistic']);

    Route::get('/upcoming-events', function () {
        return view('admin.dashboard.upcoming_events');
    })->name('admin.dashboard.upcoming_events');

    //    Route::get('/guest-faculty', function () {
    //      return view('admin.dashboard.guest_faculty');
    //  })->name('admin.dashboard.guest_faculty');

    //    Route::get('/inhouse-faculty', function () {
    //      return view('admin.dashboard.inhouse_faculty');
    //  })->name('admin.dashboard.inhouse_faculty');
    // });
    //course repository AJAX routes (MUST be before resource route)
    Route::get('course-repository/courses', [CourseRepositoryController::class, 'getCourses'])->name('course-repository.courses');
    Route::get('course-repository/subjects/{coursePk}', [CourseRepositoryController::class, 'getSubjectsByCourse'])->name('course-repository.subjects');
    Route::get('course-repository/topics/{subjectPk}', [CourseRepositoryController::class, 'getTopicsBySubject'])->name('course-repository.topics');
    Route::get('course-repository/session-dates', [CourseRepositoryController::class, 'getSessionDateByTopic'])->name('course-repository.session-dates');
    Route::get('course-repository/authors-by-topic', [CourseRepositoryController::class, 'getAuthorsByTopic'])->name('course-repository.authors-by-topic');
    Route::get('course-repository/groups', [CourseRepositoryController::class, 'getGroupsByCourse'])->name('course-repository.groups');
    Route::get('course-repository/timetables', [CourseRepositoryController::class, 'getTimetablesByGroup'])->name('course-repository.timetables');

    // Custom routes for document operations
    Route::post('course-repository/{pk}/upload-document', [CourseRepositoryController::class, 'uploadDocument'])->name('course-repository.upload-document');
    Route::post('course-repository/document/{pk}/update', [CourseRepositoryController::class, 'updateDocument'])->name('course-repository.document.update');
    Route::delete('course-repository/document/{pk}', [CourseRepositoryController::class, 'deleteDocument'])->name('course-repository.document.delete');
    Route::get('course-repository/document/{pk}/download', [CourseRepositoryController::class, 'downloadDocument'])->name('course-repository.document.download');

    // Search route
    Route::get('course-repository-search', [CourseRepositoryController::class, 'search'])->name('course-repository.search');

    // AJAX endpoints for course repository
    Route::get('course-repository/ministries-by-sector', [CourseRepositoryController::class, 'getMynostriesBySector'])->name('course-repository.ministries-by-sector');

    //course repository resource routes (MUST be after AJAX routes)
    Route::resource('course-repository', CourseRepositoryController::class, [
    'parameters' => ['course-repository' => 'pk']
]);

// upload document route




// User view routes
Route::get('/course-repository-user', [CourseRepositoryController::class, 'userIndex'])->name('admin.course-repository.user.index');
Route::get('/course-repository-user/foundation-course', [CourseRepositoryController::class, 'foundationCourse'])->name('admin.course-repository.user.foundation-course');
Route::get('/course-repository-user/foundation-course/{courseCode}', [CourseRepositoryController::class, 'foundationCourseDetail'])->name('admin.course-repository.user.foundation-course.detail');
Route::get('/course-repository-user/foundation-course/{courseCode}/class-material-subject-wise', [CourseRepositoryController::class, 'classMaterialSubjectWise'])->name('admin.course-repository.user.class-material-subject-wise');
Route::get('/course-repository-user/foundation-course/{courseCode}/class-material-week-wise', [CourseRepositoryController::class, 'classMaterialWeekWise'])->name('admin.course-repository.user.class-material-week-wise');
Route::get('/course-repository-user/foundation-course/{courseCode}/week/{weekNumber}', [CourseRepositoryController::class, 'weekDetail'])->name('admin.course-repository.user.week-detail');
Route::get('/course-repository-user/document/{documentId}/details', [CourseRepositoryController::class, 'documentDetails'])->name('admin.course-repository.user.document-details');
Route::get('/course-repository-user/document/{documentId}/view', [CourseRepositoryController::class, 'documentView'])->name('admin.course-repository.user.document-view');
Route::get('/course-repository-user/document/{documentId}/video', [CourseRepositoryController::class, 'documentVideo'])->name('admin.course-repository.user.document-video');
Route::get('/course-repository-user/filter-data', [CourseRepositoryController::class, 'filterData'])->name('admin.course-repository.user.filter-data');
Route::get('/course-repository-user/{pk}', [CourseRepositoryController::class, 'userShow'])->name('admin.course-repository.user.show');

    // Feedback Database Routes
    Route::prefix('faculty')->group(function () {
        Route::get('/database', [FeedbackController::class, 'database'])->name('admin.feedback.database');
        Route::get('/database/data', [FeedbackController::class, 'getDatabaseData'])->name('admin.feedback.database.data');
        Route::get('/database/topics', [FeedbackController::class, 'getTopicsForCourse'])->name('admin.feedback.database.topics');
        Route::get('/database/export', [FeedbackController::class, 'exportDatabase'])->name('admin.feedback.database.export');
    });
    Route::get('/feedback_average', [FeedbackController::class, 'showFacultyAverage'])->name('feedback.average');
    Route::post('/faculty_view', [FeedbackController::class, 'facultyView'])->name('admin.feedback.faculty_view');
    Route::get('/faculty_view/suggestions', [FeedbackController::class, 'getFacultySuggestions'])->name('feedback.faculty_suggestions');
    Route::post('/faculty_view/export', [FeedbackController::class, 'exportFacultyFeedback'])->name('admin.feedback.faculty_view.export');
    Route::get('/feedback_details', [FeedbackController::class, 'feedbackDetails'])->name('admin.feedback.feedback_details');
    Route::post('/feedback_details/export', [FeedbackController::class, 'exportFeedbackDetails'])->name('admin.feedback.feedback_details.export');
});

    Route::get('/student-faculty-feedback', [CalendarController::class, 'studentFacultyFeedback'])->name('feedback.get.studentFacultyFeedback');
    Route::get('/admin/feedback/pending-students', [FeedbackController::class, 'pendingStudents'])->name('admin.feedback.pending.students');
// Change export routes to POST
    Route::post('/admin/feedback/pending-students/export/pdf', [FeedbackController::class, 'exportPendingStudentsPDF'])
    ->name('admin.feedback.export.pdf');

    Route::post('/admin/feedback/pending-students/export/excel', [FeedbackController::class, 'exportPendingStudentsExcel'])
    ->name('admin.feedback.export.excel');

// ============================================
// Issue Management Module Routes (CENTCOM)
// ============================================
    Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {

    // Issue Management - Main Routes
    Route::get('issue-management', [IssueManagementController::class, 'index'])->name('issue-management.index');
    Route::get('issue-management/export/excel', [IssueManagementController::class, 'exportExcel'])->name('issue-management.export.excel');
    Route::get('issue-management/export/pdf', [IssueManagementController::class, 'exportPdf'])->name('issue-management.export.pdf');
    Route::get('issue-management/centcom', [IssueManagementController::class, 'centcom'])->name('issue-management.centcom');
    Route::get('issue-management/create', [IssueManagementController::class, 'create'])->name('issue-management.create');
    Route::post('issue-management', [IssueManagementController::class, 'store'])->name('issue-management.store');

    // AJAX Routes (must come BEFORE parameterized routes like {id})
    Route::get('issue-management/sub-categories/{categoryId}', [IssueManagementController::class, 'getSubCategories'])->name('issue-management.sub-categories');
    Route::get('issue-management/nodal-employees/{categoryId}', [IssueManagementController::class, 'getNodalEmployees'])->name('issue-management.nodal-employees');
    Route::get('issue-management/buildings', [IssueManagementController::class, 'getBuildings'])->name('issue-management.buildings');
    Route::get('issue-management/floors', [IssueManagementController::class, 'getFloors'])->name('issue-management.floors');
    Route::get('issue-management/rooms', [IssueManagementController::class, 'getRooms'])->name('issue-management.rooms');

    // Parameterized Routes (must come AFTER specific routes)
    Route::get('issue-management/{id}', [IssueManagementController::class, 'show'])->name('issue-management.show');
    Route::get('issue-management/{id}/edit', [IssueManagementController::class, 'edit'])->name('issue-management.edit');
    Route::put('issue-management/{id}', [IssueManagementController::class, 'update'])->name('issue-management.update');
    Route::put('issue-management/{id}/status', [IssueManagementController::class, 'status_update'])->name('issue-management.status_update');

    // AJAX Routes
    Route::get('issue-management/sub-categories/{categoryId}', [IssueManagementController::class, 'getSubCategories'])->name('issue-management.sub-categories');
    Route::post('issue-management/{id}/feedback', [IssueManagementController::class, 'addFeedback'])->name('issue-management.add-feedback');

    // Category Management
    Route::get('issue-categories', [IssueCategoryController::class, 'index'])->name('issue-categories.index');
    Route::post('issue-categories', [IssueCategoryController::class, 'store'])->name('issue-categories.store');
    Route::put('issue-categories/{id}', [IssueCategoryController::class, 'update'])->name('issue-categories.update');
    Route::delete('issue-categories/{id}', [IssueCategoryController::class, 'destroy'])->name('issue-categories.destroy');    // Sub-Category Management
    Route::get('issue-sub-categories', [IssueSubCategoryController::class, 'index'])->name('issue-sub-categories.index');
    Route::post('issue-sub-categories', [IssueSubCategoryController::class, 'store'])->name('issue-sub-categories.store');
    Route::put('issue-sub-categories/{id}', [IssueSubCategoryController::class, 'update'])->name('issue-sub-categories.update');
    Route::delete('issue-sub-categories/{id}', [IssueSubCategoryController::class, 'destroy'])->name('issue-sub-categories.destroy');

    // Priority Management
    Route::get('issue-priorities', [IssuePriorityController::class, 'index'])->name('issue-priorities.index');
    Route::post('issue-priorities', [IssuePriorityController::class, 'store'])->name('issue-priorities.store');
    Route::put('issue-priorities/{id}', [IssuePriorityController::class, 'update'])->name('issue-priorities.update');
    Route::delete('issue-priorities/{id}', [IssuePriorityController::class, 'destroy'])->name('issue-priorities.destroy');

    // Escalation Matrix (3-level hierarchy)
    Route::get('issue-escalation-matrix', [IssueEscalationMatrixController::class, 'index'])->name('issue-escalation-matrix.index');
    Route::post('issue-escalation-matrix', [IssueEscalationMatrixController::class, 'store'])->name('issue-escalation-matrix.store');
    Route::put('issue-escalation-matrix/{categoryId}', [IssueEscalationMatrixController::class, 'update'])->name('issue-escalation-matrix.update');

    // Estate Management Routes
    Route::prefix('estate')->name('estate.')->group(function () {
        // Estate Request for Others
        Route::get('request-for-others', [EstateController::class, 'requestForOthers'])->name('request-for-others');

        // Request For Estate (estate_home_request_details + possession)
        Route::get('request-for-estate', [EstateController::class, 'requestForEstate'])->name('request-for-estate');
        Route::get('request-for-estate/employees', [EstateController::class, 'getRequestForEstateEmployees'])->name('request-for-estate.employees');
        Route::get('request-for-estate/employee-details/{pk}', [EstateController::class, 'getRequestForEstateEmployeeDetails'])->name('request-for-estate.employee-details');
        Route::get('request-for-estate/vacant-houses', [EstateController::class, 'getVacantHousesForEstateRequest'])->name('request-for-estate.vacant-houses');
        Route::post('request-for-estate', [EstateController::class, 'storeRequestForEstate'])->name('request-for-estate.store');
        Route::delete('request-for-estate/{id}', [EstateController::class, 'destroyRequestForEstate'])->name('request-for-estate.destroy');

        // Estate Approval Setting & Add Approved Request House
        Route::get('estate-approval-setting', [EstateController::class, 'estateApprovalSetting'])->name('estate-approval-setting');
        Route::get('add-approved-request-house', [EstateController::class, 'addApprovedRequestHouse'])->name('add-approved-request-house');
        Route::post('store-approved-request-house', [EstateController::class, 'storeApprovedRequestHouse'])->name('store-approved-request-house');

        Route::get('add-other-estate-request', [EstateController::class, 'addOtherEstateRequest'])->name('add-other-estate-request');
        Route::post('add-other-estate-request', [EstateController::class, 'storeOtherEstateRequest'])->name('add-other-estate-request.store');
        Route::delete('other-estate-request/{id}', [EstateController::class, 'destroyOtherEstateRequest'])->name('other-estate-request.destroy');

        Route::get('change-request-hac-approved', function () {
            return view('admin.estate.change-request-hac-approved');
        })->name('change-request-hac-approved');


        // Change Requests (HAC Approved)
        Route::get('change-request-hac-approved', [EstateController::class, 'changeRequestHacApproved'])->name('change-request-hac-approved');
        Route::post('change-request/approve/{id}', [EstateController::class, 'approveChangeRequest'])->name('change-request.approve');
        Route::post('change-request/disapprove/{id}', [EstateController::class, 'disapproveChangeRequest'])->name('change-request.disapprove');
        
        Route::get('add-other-estate-request', [EstateController::class, 'addOtherEstateRequest'])->name('add-other-estate-request');
        Route::post('add-other-estate-request', [EstateController::class, 'storeOtherEstateRequest'])->name('add-other-estate-request.store');
        Route::delete('other-estate-request/{id}', [EstateController::class, 'destroyOtherEstateRequest'])->name('other-estate-request.destroy');

        // Estate Possession
        Route::get('possession-for-others', [EstateController::class, 'possessionForOthers'])->name('possession-for-others');
        Route::delete('possession/{id}', [EstateController::class, 'destroyPossession'])->name('possession-delete');

        Route::get('possession-view', [EstateController::class, 'possessionView'])->name('possession-view');
        Route::post('possession-view/store', [EstateController::class, 'storePossession'])->name('possession-view.store');
        Route::get('possession/blocks', [EstateController::class, 'getPossessionBlocks'])->name('possession.blocks');
        Route::get('possession/unit-sub-types', [EstateController::class, 'getPossessionUnitSubTypes'])->name('possession.unit-sub-types');
        Route::get('possession/houses', [EstateController::class, 'getPossessionHouses'])->name('possession.houses');

        // Update Meter
        Route::get('update-meter-reading', [EstateController::class, 'updateMeterReading'])->name('update-meter-reading');
        Route::get('list-meter-reading', [EstateController::class, 'listMeterReading'])->name('list-meter-reading');
        Route::get('list-meter-reading/data', [EstateController::class, 'getListMeterReadingData'])->name('list-meter-reading.data');
        Route::get('update-meter-reading/list', [EstateController::class, 'getMeterReadingList'])->name('update-meter-reading.list');
        Route::get('update-meter-reading/meter-reading-dates', [EstateController::class, 'getMeterReadingDates'])->name('update-meter-reading.meter-reading-dates');
        Route::get('update-meter-reading/blocks', [EstateController::class, 'getMeterReadingBlocks'])->name('update-meter-reading.blocks');
        Route::get('update-meter-reading/unit-sub-types', [EstateController::class, 'getMeterReadingUnitSubTypes'])->name('update-meter-reading.unit-sub-types');
        Route::post('update-meter-reading/store', [EstateController::class, 'storeMeterReadings'])->name('update-meter-reading.store');
        Route::get('update-meter-reading-of-other', [EstateController::class, 'updateMeterReadingOfOther'])->name('update-meter-reading-of-other');
        Route::get('update-meter-reading-of-other/list', [EstateController::class, 'getMeterReadingListOther'])->name('update-meter-reading-of-other.list');
        Route::get('update-meter-reading-of-other/meter-reading-dates', [EstateController::class, 'getMeterReadingDatesOther'])->name('update-meter-reading-of-other.meter-reading-dates');
        Route::get('update-meter-reading-of-other/blocks', [EstateController::class, 'getMeterReadingBlocksOther'])->name('update-meter-reading-of-other.blocks');
        Route::get('update-meter-reading-of-other/unit-sub-types', [EstateController::class, 'getMeterReadingUnitSubTypesOther'])->name('update-meter-reading-of-other.unit-sub-types');
        Route::post('update-meter-reading-of-other/store', [EstateController::class, 'storeMeterReadingsOther'])->name('update-meter-reading-of-other.store');
        Route::get('update-meter-no', function () {
            return view('admin.estate.update_meter_no');
        })->name('update-meter-no');

        // Generate Estate Bill / Estate Bill Summary
        Route::get('generate-estate-bill', [EstateController::class, 'generateEstateBill'])->name('generate-estate-bill');

        // Return House
        Route::get('return-house', function () {
            return view('admin.estate.return_house');
        })->name('return-house');

        // Define House
        Route::get('define-house', [EstateController::class, 'defineHouse'])->name('define-house');
        Route::post('define-house', [EstateController::class, 'storeDefineHouse'])->name('define-house.store');
        Route::get('define-house/data', [EstateController::class, 'getDefineHouseData'])->name('define-house.data');
        Route::get('define-house/blocks', [EstateController::class, 'getDefineHouseBlocks'])->name('define-house.blocks');
        Route::get('define-house/{id}', [EstateController::class, 'showDefineHouse'])->name('define-house.show');
        Route::put('define-house/{id}', [EstateController::class, 'updateDefineHouse'])->name('define-house.update');
        Route::delete('define-house/{id}', [EstateController::class, 'destroyDefineHouse'])->name('define-house.destroy');

        // Define Electric Slab
        Route::get('define-electric-slab', [EstateElectricSlabController::class, 'index'])->name('define-electric-slab.index');
        Route::get('define-electric-slab/create', [EstateElectricSlabController::class, 'create'])->name('define-electric-slab.create');
        Route::post('define-electric-slab', [EstateElectricSlabController::class, 'store'])->name('define-electric-slab.store');
        Route::get('define-electric-slab/{id}/edit', [EstateElectricSlabController::class, 'edit'])->name('define-electric-slab.edit');
        Route::put('define-electric-slab/{id}', [EstateElectricSlabController::class, 'update'])->name('define-electric-slab.update');
        Route::delete('define-electric-slab/{id}', [EstateElectricSlabController::class, 'destroy'])->name('define-electric-slab.destroy');

        // Define Estate/Campus
        Route::get('define-campus', [EstateCampusController::class, 'index'])->name('define-campus.index');
        Route::get('define-campus/create', [EstateCampusController::class, 'create'])->name('define-campus.create');
        Route::post('define-campus', [EstateCampusController::class, 'store'])->name('define-campus.store');
        Route::get('define-campus/{id}/edit', [EstateCampusController::class, 'edit'])->name('define-campus.edit');
        Route::put('define-campus/{id}', [EstateCampusController::class, 'update'])->name('define-campus.update');
        Route::delete('define-campus/{id}', [EstateCampusController::class, 'destroy'])->name('define-campus.destroy');

        // Define Unit Type
        Route::get('define-unit-type', [UnitTypeController::class, 'index'])->name('define-unit-type.index');
        Route::get('define-unit-type/create', [UnitTypeController::class, 'create'])->name('define-unit-type.create');
        Route::post('define-unit-type', [UnitTypeController::class, 'store'])->name('define-unit-type.store');
        Route::get('define-unit-type/{id}/edit', [UnitTypeController::class, 'edit'])->name('define-unit-type.edit');
        Route::put('define-unit-type/{id}', [UnitTypeController::class, 'update'])->name('define-unit-type.update');
        Route::delete('define-unit-type/{id}', [UnitTypeController::class, 'destroy'])->name('define-unit-type.destroy');

        // Define Unit Sub Type
        Route::get('define-unit-sub-type', [UnitSubTypeController::class, 'index'])->name('define-unit-sub-type.index');
        Route::get('define-unit-sub-type/create', [UnitSubTypeController::class, 'create'])->name('define-unit-sub-type.create');
        Route::post('define-unit-sub-type', [UnitSubTypeController::class, 'store'])->name('define-unit-sub-type.store');
        Route::get('define-unit-sub-type/{id}/edit', [UnitSubTypeController::class, 'edit'])->name('define-unit-sub-type.edit');
        Route::put('define-unit-sub-type/{id}', [UnitSubTypeController::class, 'update'])->name('define-unit-sub-type.update');
        Route::delete('define-unit-sub-type/{id}', [UnitSubTypeController::class, 'destroy'])->name('define-unit-sub-type.destroy');

        // Define Block/Building
        Route::get('define-block-building', [EstateBlockController::class, 'index'])->name('define-block-building.index');
        Route::get('define-block-building/create', [EstateBlockController::class, 'create'])->name('define-block-building.create');
        Route::post('define-block-building', [EstateBlockController::class, 'store'])->name('define-block-building.store');
        Route::get('define-block-building/{id}/edit', [EstateBlockController::class, 'edit'])->name('define-block-building.edit');
        Route::put('define-block-building/{id}', [EstateBlockController::class, 'update'])->name('define-block-building.update');
        Route::delete('define-block-building/{id}', [EstateBlockController::class, 'destroy'])->name('define-block-building.destroy');

        // Define Pay Scale (for eligibility)
        Route::get('define-pay-scale', [PayScaleController::class, 'index'])->name('define-pay-scale.index');
        Route::get('define-pay-scale/create', [PayScaleController::class, 'create'])->name('define-pay-scale.create');
        Route::post('define-pay-scale', [PayScaleController::class, 'store'])->name('define-pay-scale.store');
        Route::get('define-pay-scale/{id}/edit', [PayScaleController::class, 'edit'])->name('define-pay-scale.edit');
        Route::put('define-pay-scale/{id}', [PayScaleController::class, 'update'])->name('define-pay-scale.update');
        Route::delete('define-pay-scale/{id}', [PayScaleController::class, 'destroy'])->name('define-pay-scale.destroy');

        // Eligibility - Criteria
        Route::get('eligibility-criteria', [EligibilityCriteriaController::class, 'index'])->name('eligibility-criteria.index');
        Route::get('eligibility-criteria/create', [EligibilityCriteriaController::class, 'create'])->name('eligibility-criteria.create');
        Route::post('eligibility-criteria', [EligibilityCriteriaController::class, 'store'])->name('eligibility-criteria.store');
        Route::get('eligibility-criteria/{id}/edit', [EligibilityCriteriaController::class, 'edit'])->name('eligibility-criteria.edit');
        Route::put('eligibility-criteria/{id}', [EligibilityCriteriaController::class, 'update'])->name('eligibility-criteria.update');
        Route::delete('eligibility-criteria/{id}', [EligibilityCriteriaController::class, 'destroy'])->name('eligibility-criteria.destroy');

        // Estate Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('pending-meter-reading/data', [EstateController::class, 'getPendingMeterReadingData'])->name('pending-meter-reading.data');
            Route::get('pending-meter-reading', [EstateController::class, 'pendingMeterReading'])->name('pending-meter-reading');
            
            Route::get('house-status/data', [EstateController::class, 'getHouseStatusData'])->name('house-status.data');
            Route::get('house-status', [EstateController::class, 'houseStatus'])->name('house-status');
            
            Route::get('pending-meter-reading', function () {
                return view('admin.estate.pending_meter_reading');
            })->name('pending-meter-reading');

            Route::get('house-status', function () {
                return view('admin.estate.house_status');
            })->name('house-status');

            Route::get('bill-report-grid', function () {
                return view('admin.estate.estate_bill_report_grid');
            })->name('bill-report-grid');

            Route::get('bill-report-print', [EstateController::class, 'estateBillReportPrint'])->name('bill-report-print');
            
            Route::get('bill-report-print', [EstateController::class, 'estateBillReportPrint'])->name('bill-report-print');
            Route::get('bill-report-print-all', [EstateController::class, 'estateBillReportPrintAll'])->name('bill-report-print-all');
            Route::get('bill-report-print-all-pdf', [EstateController::class, 'estateBillReportPrintAllPdf'])->name('bill-report-print-all-pdf');

            Route::get('migration-report', [EstateController::class, 'estateMigrationReport'])->name('migration-report');
            Route::get('migration-report/filter-options', [EstateController::class, 'getEstateMigrationReportFilterOptions'])->name('migration-report.filter-options');
        });
    });
});

Route::get('/view-logs', [App\Http\Controllers\LogController::class, 'index'])
    ->middleware('auth');
