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
    CourseRepositoryController,
};
use App\Http\Controllers\Dashboard\Calendar1Controller;
use App\Http\Controllers\Admin\MemoNoticeController;
use App\Http\Controllers\Admin\Master\DisciplineMasterController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\IssueManagement\{
    IssueManagementController,
    IssueCategoryController,
    IssueSubCategoryController
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
    Route::get('/dashboard/students', [UserController::class, 'studentList'])->name('admin.dashboard.students');
    Route::get('/dashboard/students/{id}/detail', [UserController::class, 'studentDetail'])->name('admin.dashboard.students.detail');


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
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
    });

    // Vehicle Pass Approval Routes
    Route::prefix('security/vehicle-pass-approval')->name('admin.security.vehicle_pass_approval.')->controller(\App\Http\Controllers\Admin\Security\VehiclePassApprovalController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/all', 'allApplications')->name('all');
        Route::get('/show/{id}', 'show')->name('show');
        Route::post('/approve/{id}', 'approve')->name('approve');
        Route::post('/reject/{id}', 'reject')->name('reject');
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
            Route::post('/memo/get-data', 'getMemoData')->name('get_memo_data');
            Route::post('/memo/get-generated-data', 'getGeneratedMemoData')->name('get_generated_memo_data');
            Route::get('/export-pdf', 'exportPdf')->name('export_pdf');
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
        Route::resource('mealmappings', \App\Http\Controllers\Mess\MealMappingController::class)->only(['index', 'create', 'store']);
        Route::resource('permissionsettings', \App\Http\Controllers\Mess\PermissionSettingController::class)->only(['index', 'create', 'store']);
        Route::resource('storeallocations', \App\Http\Controllers\Mess\StoreAllocationController::class)->only(['index', 'create', 'store']);
        
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
        Route::resource('number-configs', \App\Http\Controllers\Mess\NumberConfigController::class);
        
        // Purchase Order Management
        Route::resource('purchaseorders', \App\Http\Controllers\Mess\PurchaseOrderController::class)->except(['edit', 'update', 'destroy']);
        Route::get('purchaseorders/{id}/edit', [\App\Http\Controllers\Mess\PurchaseOrderController::class, 'edit'])->name('purchaseorders.edit');
        Route::put('purchaseorders/{id}', [\App\Http\Controllers\Mess\PurchaseOrderController::class, 'update'])->name('purchaseorders.update');
        Route::delete('purchaseorders/{id}', [\App\Http\Controllers\Mess\PurchaseOrderController::class, 'destroy'])->name('purchaseorders.destroy');
        Route::post('purchaseorders/{id}/approve', [\App\Http\Controllers\Mess\PurchaseOrderController::class, 'approve'])->name('purchaseorders.approve');
        Route::post('purchaseorders/{id}/reject', [\App\Http\Controllers\Mess\PurchaseOrderController::class, 'reject'])->name('purchaseorders.reject');
        
        // Material Management (formerly Kitchen Issue)
        Route::resource('material-management', \App\Http\Controllers\Mess\KitchenIssueController::class);
        Route::post('material-management/{id}/send-for-approval', [\App\Http\Controllers\Mess\KitchenIssueController::class, 'sendForApproval'])->name('material-management.send-for-approval');
        Route::get('material-management/records/ajax', [\App\Http\Controllers\Mess\KitchenIssueController::class, 'getKitchenIssueRecords'])->name('material-management.records');
        Route::get('material-management/reports/bill', [\App\Http\Controllers\Mess\KitchenIssueController::class, 'billReport'])->name('material-management.bill-report');
        
        // Material Management Approval
        Route::prefix('material-management-approvals')->name('material-management-approvals.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Mess\KitchenIssueApprovalController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Mess\KitchenIssueApprovalController::class, 'show'])->name('show');
            Route::post('/{id}/approve', [\App\Http\Controllers\Mess\KitchenIssueApprovalController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [\App\Http\Controllers\Mess\KitchenIssueApprovalController::class, 'reject'])->name('reject');
        });
        
        // NEW: Billing & Finance
        Route::resource('monthly-bills', \App\Http\Controllers\Mess\MonthlyBillController::class);
        Route::post('monthly-bills/generate', [\App\Http\Controllers\Mess\MonthlyBillController::class, 'generateBills'])->name('monthly-bills.generate');
        Route::resource('finance-bookings', \App\Http\Controllers\Mess\FinanceBookingController::class);
        Route::post('finance-bookings/{id}/approve', [\App\Http\Controllers\Mess\FinanceBookingController::class, 'approve'])->name('finance-bookings.approve');
        Route::post('finance-bookings/{id}/reject', [\App\Http\Controllers\Mess\FinanceBookingController::class, 'reject'])->name('finance-bookings.reject');
        
        // NEW: Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('items-list', [\App\Http\Controllers\Mess\ReportController::class, 'itemsList'])->name('items-list');
            Route::get('mess-summary', [\App\Http\Controllers\Mess\ReportController::class, 'messSummary'])->name('mess-summary');
            Route::get('category-material', [\App\Http\Controllers\Mess\ReportController::class, 'categoryMaterial'])->name('category-material');
            Route::get('pending-orders', [\App\Http\Controllers\Mess\ReportController::class, 'pendingOrders'])->name('pending-orders');
            Route::get('payment-overdue', [\App\Http\Controllers\Mess\ReportController::class, 'paymentOverdue'])->name('payment-overdue');
            Route::get('approved-inbound', [\App\Http\Controllers\Mess\ReportController::class, 'approvedInbound'])->name('approved-inbound');
            Route::get('invoice-bill', [\App\Http\Controllers\Mess\ReportController::class, 'invoiceBill'])->name('invoice-bill');
            Route::get('purchase-orders', [\App\Http\Controllers\Mess\ReportController::class, 'purchaseOrdersReport'])->name('purchase-orders');
            Route::get('ot-not-taking-food', [\App\Http\Controllers\Mess\ReportController::class, 'otNotTakingFood'])->name('ot-not-taking-food');
            Route::get('sale-counter', [\App\Http\Controllers\Mess\ReportController::class, 'saleCounterReport'])->name('sale-counter');
            Route::get('store-due', [\App\Http\Controllers\Mess\ReportController::class, 'storeDue'])->name('store-due');
            Route::get('mess-bill', [\App\Http\Controllers\Mess\ReportController::class, 'messBillReport'])->name('mess-bill');
            Route::get('mess-invoice', [\App\Http\Controllers\Mess\ReportController::class, 'messInvoiceReport'])->name('mess-invoice');
            Route::get('stock-purchase-details', [\App\Http\Controllers\Mess\ReportController::class, 'stockPurchaseDetails'])->name('stock-purchase-details');
            Route::get('client-invoice', [\App\Http\Controllers\Mess\ReportController::class, 'clientInvoice'])->name('client-invoice');
            Route::get('stock-issue-detail', [\App\Http\Controllers\Mess\ReportController::class, 'stockIssueDetail'])->name('stock-issue-detail');
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
    Route::prefix('admin/setup/caste-category')->name('admin.setup.caste_category.')->controller(CasteCategoryController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'delete')->name('delete');
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
    Route::get('/sessions', [DashboardController::class, 'sessions'])->name('admin.dashboard.sessions');

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
    Route::get('issue-management/centcom', [IssueManagementController::class, 'centcom'])->name('issue-management.centcom');
    Route::get('issue-management/create', [IssueManagementController::class, 'create'])->name('issue-management.create');
    Route::post('issue-management', [IssueManagementController::class, 'store'])->name('issue-management.store');
    Route::get('issue-management/{id}', [IssueManagementController::class, 'show'])->name('issue-management.show');
    Route::get('issue-management/{id}/edit', [IssueManagementController::class, 'edit'])->name('issue-management.edit');
    Route::put('issue-management/{id}', [IssueManagementController::class, 'update'])->name('issue-management.update');
    
    // AJAX Routes
    Route::get('issue-management/sub-categories/{categoryId}', [IssueManagementController::class, 'getSubCategories'])->name('issue-management.sub-categories');
    Route::post('issue-management/{id}/feedback', [IssueManagementController::class, 'addFeedback'])->name('issue-management.add-feedback');
    
    // Category Management
    Route::get('issue-categories', [IssueCategoryController::class, 'index'])->name('issue-categories.index');
    Route::post('issue-categories', [IssueCategoryController::class, 'store'])->name('issue-categories.store');
    Route::put('issue-categories/{id}', [IssueCategoryController::class, 'update'])->name('issue-categories.update');
    Route::delete('issue-categories/{id}', [IssueCategoryController::class, 'destroy'])->name('issue-categories.destroy');
    
    // Sub-Category Management
    Route::get('issue-sub-categories', [IssueSubCategoryController::class, 'index'])->name('issue-sub-categories.index');
    Route::post('issue-sub-categories', [IssueSubCategoryController::class, 'store'])->name('issue-sub-categories.store');
    Route::put('issue-sub-categories/{id}', [IssueSubCategoryController::class, 'update'])->name('issue-sub-categories.update');
    Route::delete('issue-sub-categories/{id}', [IssueSubCategoryController::class, 'destroy'])->name('issue-sub-categories.destroy');
});

