<?php
// app/Http/Controllers/Admin/MemoNoticeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\CalendarEvent;
use App\Models\FacultyMaster;

class DashboardController extends Controller
{
function active_course(Request $request)
{
   $active_courses = DB::table('course_master')
        ->where('active_inactive', 1)
        ->where('start_year', '<', now())
        ->where('end_date', '>=', now())
        ->get();
    return view('admin.dashboard.active_course', compact('active_courses'));
    
}

function incoming_course(Request $request)
{
    $incoming_courses = DB::table('course_master')
        ->where('active_inactive', 1)
        ->where('start_year', '>', now())
        ->orderBy('start_year', 'asc')
        ->get();
    return view('admin.dashboard.incoming_course', compact('incoming_courses'));
}

function guest_faculty()
{
   $guest_faculty = DB::table('faculty_master')
       ->where('faculty_type', 2)
       ->where('active_inactive', 1)
       ->select('pk', 'full_name', 'mobile_no', 'faculty_sector', 'email_id', 'photo_uplode_path')
       ->get();
   
   // Fetch feedback data and session count for each guest faculty
   $guest_faculty = $guest_faculty->map(function ($faculty) {
       // Get feedback grouped by session (timetable_pk)
       $feedbackData = DB::table('topic_feedback as tf')
           ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
           ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
           ->leftJoin('subject_master as sm', 'tt.subject_master_pk', '=', 'sm.pk')
           ->select(
               'tf.timetable_pk',
               'tf.topic_name',
               'cm.course_name',
               'cm.pk as course_id',
               'sm.subject_name',
               'tt.START_DATE as session_date',
               'tt.class_session',
               DB::raw('COUNT(DISTINCT tf.student_master_pk) as participant_count'),
               DB::raw('ROUND(AVG(CAST(tf.content AS DECIMAL(10,2))) * 20, 2) as avg_content_percent'),
               DB::raw('ROUND(AVG(CAST(tf.presentation AS DECIMAL(10,2))) * 20, 2) as avg_presentation_percent'),
               DB::raw('ROUND(AVG(CAST(tf.content AS DECIMAL(10,2))), 2) as avg_content_rating'),
               DB::raw('ROUND(AVG(CAST(tf.presentation AS DECIMAL(10,2))), 2) as avg_presentation_rating'),
               DB::raw('GROUP_CONCAT(DISTINCT CASE WHEN tf.remark IS NOT NULL AND TRIM(tf.remark) != "" THEN tf.remark END SEPARATOR " | ") as remarks')
           )
           ->where('tf.faculty_pk', $faculty->pk)
           ->where('tf.is_submitted', 1)
           ->groupBy('tf.timetable_pk', 'tf.topic_name', 'cm.course_name', 'cm.pk', 'sm.subject_name', 'tt.START_DATE', 'tt.class_session')
           ->orderBy('tt.START_DATE', 'desc')
           ->get();
       
       // Count total sessions for this faculty from timetable
       $sessionCount = DB::table('timetable')
           ->where('active_inactive', 1)
           ->where(function ($query) use ($faculty) {
               $query->whereRaw('JSON_CONTAINS(faculty_master, ?)', ['"'.$faculty->pk.'"'])
                     ->orWhereRaw('FIND_IN_SET(?, faculty_master)', [$faculty->pk])
                     ->orWhere('faculty_master', $faculty->pk);
           })
           ->count();
       
       // Calculate summary statistics
       $totalFeedback = $feedbackData->count();
       $totalParticipants = $feedbackData->sum('participant_count');
       $avgContent = $feedbackData->avg('avg_content_percent') ?? 0;
       $avgPresentation = $feedbackData->avg('avg_presentation_percent') ?? 0;
       
       $faculty->feedback = $feedbackData;
       $faculty->session_count = $sessionCount;
       $faculty->feedback_summary = [
           'total_feedback' => $totalFeedback,
           'total_participants' => $totalParticipants,
           'avg_content' => round($avgContent, 2),
           'avg_presentation' => round($avgPresentation, 2),
       ];
       
       return $faculty;
   });
   
    return view('admin.dashboard.guest_faculty', compact('guest_faculty'));
    
}
function inhouse_faculty()
{
    $inhouse_faculty = DB::table('faculty_master')
        ->where('faculty_type', 1)
        ->where('active_inactive', 1)
        ->select('pk', 'full_name', 'mobile_no', 'faculty_sector', 'email_id', 'photo_uplode_path')
        ->get();

    $inhouse_faculty = $inhouse_faculty->map(function ($faculty) {
        $feedbackData = DB::table('topic_feedback as tf')
            ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
            ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
            ->leftJoin('subject_master as sm', 'tt.subject_master_pk', '=', 'sm.pk')
            ->select(
                'tf.timetable_pk',
                'tf.topic_name',
                'cm.course_name',
                'cm.pk as course_id',
                'sm.subject_name',
                'tt.START_DATE as session_date',
                'tt.class_session',
                DB::raw('COUNT(DISTINCT tf.student_master_pk) as participant_count'),
                DB::raw('ROUND(AVG(CAST(tf.content AS DECIMAL(10,2))) * 20, 2) as avg_content_percent'),
                DB::raw('ROUND(AVG(CAST(tf.presentation AS DECIMAL(10,2))) * 20, 2) as avg_presentation_percent'),
                DB::raw('ROUND(AVG(CAST(tf.content AS DECIMAL(10,2))), 2) as avg_content_rating'),
                DB::raw('ROUND(AVG(CAST(tf.presentation AS DECIMAL(10,2))), 2) as avg_presentation_rating'),
                DB::raw('GROUP_CONCAT(DISTINCT CASE WHEN tf.remark IS NOT NULL AND TRIM(tf.remark) != "" THEN tf.remark END SEPARATOR " | ") as remarks')
            )
            ->where('tf.faculty_pk', $faculty->pk)
            ->where('tf.is_submitted', 1)
            ->groupBy('tf.timetable_pk', 'tf.topic_name', 'cm.course_name', 'cm.pk', 'sm.subject_name', 'tt.START_DATE', 'tt.class_session')
            ->orderBy('tt.START_DATE', 'desc')
            ->get();

        $sessionCount = DB::table('timetable')
            ->where('active_inactive', 1)
            ->where(function ($query) use ($faculty) {
                $query->whereRaw('JSON_CONTAINS(faculty_master, ?)', ['"'.$faculty->pk.'"'])
                      ->orWhereRaw('FIND_IN_SET(?, faculty_master)', [$faculty->pk])
                      ->orWhere('faculty_master', $faculty->pk);
            })
            ->count();

        $totalFeedback = $feedbackData->count();
        $totalParticipants = $feedbackData->sum('participant_count');
        $avgContent = $feedbackData->avg('avg_content_percent') ?? 0;
        $avgPresentation = $feedbackData->avg('avg_presentation_percent') ?? 0;

        $faculty->feedback = $feedbackData;
        $faculty->session_count = $sessionCount;
        $faculty->feedback_summary = [
            'total_feedback' => $totalFeedback,
            'total_participants' => $totalParticipants,
            'avg_content' => round($avgContent, 2),
            'avg_presentation' => round($avgPresentation, 2),
        ];

        return $faculty;
    });

    return view('admin.dashboard.inhouse_faculty', compact('inhouse_faculty'));
}

function sessions(Request $request)
{
    $userId = Auth::user()->user_id;
    $sessions = collect([]);
    
    // Fetch sessions for Internal Faculty or Guest Faculty
    if(hasRole('Internal Faculty') || hasRole('Guest Faculty')){
        // Get faculty_master.pk from user_id
        $faculty = FacultyMaster::where('employee_master_pk', $userId)->first();
        
        if ($faculty) {
            $facultyPk = $faculty->pk;
            
            // Fetch sessions with related data
            $sessions = CalendarEvent::where('active_inactive', 1)
                ->where(function ($query) use ($facultyPk) {
                    $query->whereRaw('JSON_CONTAINS(faculty_master, ?)', ['"'.$facultyPk.'"'])
                          ->orWhereRaw('FIND_IN_SET(?, faculty_master)', [$facultyPk]);
                })
                ->with([
                    'venue',
                    'classSession',
                    'courseGroupTypeMaster'
                ])
                ->orderBy('START_DATE', 'desc')
                ->orderBy('class_session', 'asc')
                ->get();
            
            // Process sessions to include additional details
            $sessions = $sessions->map(function ($session) {
                // Get course name
                $courseName = DB::table('course_master')
                    ->where('pk', $session->course_master_pk)
                    ->value('course_name') ?? 'N/A';
                
                // Get subject name
                $subjectName = DB::table('subject_master')
                    ->where('pk', $session->subject_master_pk)
                    ->value('subject_name') ?? 'N/A';
                
                // Get module name
                $moduleName = DB::table('subject_module_master')
                    ->where('pk', $session->subject_module_master_pk)
                    ->value('module_name') ?? 'N/A';
                
                // Parse faculty names
                $facultyIds = json_decode($session->faculty_master, true);
                if (!is_array($facultyIds)) {
                    $facultyIds = $session->faculty_master ? [$session->faculty_master] : [];
                }
                $facultyNames = DB::table('faculty_master')
                    ->whereIn('pk', $facultyIds)
                    ->pluck('full_name')
                    ->implode(', ') ?: 'N/A';
                
                // Parse group names
                $groupIds = json_decode($session->group_name, true) ?? [];
                $groupNames = DB::table('group_type_master_course_master_map')
                    ->whereIn('pk', $groupIds)
                    ->pluck('group_name')
                    ->implode(', ') ?: 'N/A';
                
                // Get session time
                $sessionTime = $session->class_session;
                if ($session->session_type == 1 && $session->classSession) {
                    $sessionTime = $session->classSession->shift_name . ' (' . 
                                   $session->classSession->start_time . ' - ' . 
                                   $session->classSession->end_time . ')';
                }
                
                return [
                    'pk' => $session->pk,
                    'course_name' => $courseName,
                    'subject_name' => $subjectName,
                    'module_name' => $moduleName,
                    'topic' => $session->subject_topic ?? 'N/A',
                    'faculty_names' => $facultyNames,
                    'group_names' => $groupNames,
                    'venue_name' => $session->venue ? $session->venue->venue_name : 'N/A',
                    'session_time' => $sessionTime,
                    'session_date' => Carbon::parse($session->START_DATE)->format('d M Y'),
                    'start_date' => $session->START_DATE,
                    'end_date' => $session->END_DATE,
                    'full_day' => $session->full_day ?? 0,
                    'feedback_checkbox' => $session->feedback_checkbox ?? 0,
                    'ratting_checkbox' => $session->Ratting_checkbox ?? 0,
                    'remark_checkbox' => $session->Remark_checkbox ?? 0,
                    'bio_attendance' => $session->Bio_attendance ?? 0,
                ];
            });
        }
    }
    
    return view('admin.dashboard.sessions', compact('sessions'));
}

}