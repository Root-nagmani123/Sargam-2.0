<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\OTNoticeMemoService;

class OTNoticeMemoViewController extends Controller
{
    protected $otNoticeMemoService;

    public function __construct(OTNoticeMemoService $otNoticeMemoService)
    {
        $this->otNoticeMemoService = $otNoticeMemoService;
    }

    public function index(Request $request)
    {
        // Check if user is logged in
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to access this page.');
        }

        $user = Auth::user();
        
        // Get logged-in student from user_credentials
        $userCredential = $this->otNoticeMemoService->getLoggedInStudent($user->pk);
        
        if (!$userCredential) {
            return redirect()->back()->with('error', 'Access denied. This page is only for students.');
        }
        
        $studentMasterPk = $userCredential->user_id;
        
        if (!$studentMasterPk) {
            return redirect()->back()->with('error', 'Student record not found.');
        }
        
        // Match with student_master table
        $student = $this->otNoticeMemoService->getStudentMaster($studentMasterPk);
        
        if (!$student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }
        
        // Get all notices and memos
        $recordsData = $this->otNoticeMemoService->getAllRecords($studentMasterPk);
        
        // Prepare data for view
        $studentData = $this->otNoticeMemoService->prepareStudentData($student, $userCredential, $recordsData);
        
        return view('admin.ot_notice_memo_view.index', compact('studentData'));
    }
}

