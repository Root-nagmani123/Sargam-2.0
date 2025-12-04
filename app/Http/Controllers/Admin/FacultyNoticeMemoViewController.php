<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\FacultyNoticeMemoService;

class FacultyNoticeMemoViewController extends Controller
{
    protected $facultyNoticeMemoService;

    public function __construct(FacultyNoticeMemoService $facultyNoticeMemoService)
    {
        $this->facultyNoticeMemoService = $facultyNoticeMemoService;
    }

    public function index(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to access this page.');
        }

        $user = Auth::user();
        $currentDate = now()->format('Y-m-d');
        
        // Step 1: Faculty Login Check
        // Check if user_category = 'F' (Faculty)
        $userCredential = $this->facultyNoticeMemoService->getLoggedInFaculty($user->pk);
        
        if (!$userCredential) {
            return redirect()->back()->with('error', 'Access denied. This page is for faculty members only.');
        }
        
        $userId = $userCredential->user_id;
        
        if (!$userId) {
            return redirect()->back()->with('error', 'Faculty record not found.');
        }
        
        // Step 2: Match With Faculty Coordinator
        // Match: course_coordinator_master.Assistant_Coordinator_name = user_id
        $coordinatorCourses = $this->facultyNoticeMemoService->getCoordinatorCourses($userId);
        
        if ($coordinatorCourses->isEmpty()) {
            // Show "No data" message
            return view('admin.faculty_notice_memo_view.index', [
                'isFacultyView' => true,
                'studentData' => [],
                'totalRecords' => 0,
                'hasData' => false
            ]);
        }
        
        // Step 3: Validate Course (course_master)
        // Check: course_master.active = 1, course_master.end_date >= today
        $validCourses = $this->facultyNoticeMemoService->getValidCourses($coordinatorCourses, $currentDate);
        
        if ($validCourses->isEmpty()) {
            // Show "No data" message
            return view('admin.faculty_notice_memo_view.index', [
                'isFacultyView' => true,
                'studentData' => [],
                'totalRecords' => 0,
                'hasData' => false
            ]);
        }
        
        $validCourseIds = $validCourses->pluck('pk')->toArray();
        
        // Step 4 & 5: Get Notice / Memo Records
        // Check notice_memo type and fetch records
        $recordsData = $this->facultyNoticeMemoService->getAllRecords($userId, $validCourseIds);
        
        // Step 6 & 7: Prepare data for view
        $viewData = $this->facultyNoticeMemoService->prepareViewData($recordsData, $validCourses);
        
        return view('admin.faculty_notice_memo_view.index', [
            'isFacultyView' => true,
            'studentData' => $viewData['studentData'],
            'totalRecords' => $viewData['totalRecords'],
            'hasData' => $viewData['hasData']
        ]);
    }
}

