<?php
 
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{ClassSessionMaster, CourseMaster, FacultyMaster, VenueMaster, SubjectMaster};
use Illuminate\Support\Facades\Crypt;

class CalendarController extends Controller
{
    public function index()
    {
        $courseMaster = CourseMaster::where('active_inactive', 1)
            ->select('pk', 'course_name')
            ->get();
    
        $facultyMaster = FacultyMaster::where('active_inactive', 1)
            ->select('pk', 'faculty_type', 'full_name')
            ->get();
    
        $subjects = SubjectMaster::where('active_inactive', 1)
            ->select('pk', 'subject_name', 'subject_module_master_pk')
            ->get();
    
        $venueMaster = VenueMaster::where('active_inactive', 1)
            ->select('venue_id', 'venue_name')
            ->get();
    
        $classSessionMaster = ClassSessionMaster::where('active_inactive', 1)
            ->select('pk', 'shift_name', 'start_time', 'end_time')
            ->get();
    
        return view('admin.calendar.index', compact(
            'courseMaster',
            'facultyMaster',
            'subjects',
            'venueMaster',
            'classSessionMaster'
        ));
    }
    
    
}
