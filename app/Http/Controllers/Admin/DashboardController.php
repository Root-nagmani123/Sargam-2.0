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
    $query = DB::table('course_master')
        ->where('active_inactive', 1)
        ->where('start_year', '<=', now()->toDateString())
        ->where('end_date', '>=', now()->toDateString());

    if ($request->ajax()) {
        return $this->courseDatatable($query);
    }

    return view('admin.dashboard.active_course');
}

function incoming_course(Request $request)
{
    $query = DB::table('course_master')
        ->where('active_inactive', 1)
        ->where('start_year', '>', now()->toDateString())
        ->orderBy('start_year');

    if ($request->ajax()) {
        return $this->courseDatatable($query);
    }

    return view('admin.dashboard.incoming_course');
}

/**
 * Shared server-side feed for the Active / Upcoming course grids
 * (search, sort and paging are resolved in SQL).
 */
private function courseDatatable($query)
{
    return \Yajra\DataTables\Facades\DataTables::of(
        $query->select(['pk', 'course_name', 'couse_short_name', 'start_year', 'end_date'])
    )
        ->addIndexColumn()
        ->editColumn('couse_short_name', fn ($row) => $row->couse_short_name ?: '—')
        ->editColumn('start_year', fn ($row) => $row->start_year ? Carbon::parse($row->start_year)->format('d M Y') : '—')
        ->editColumn('end_date', fn ($row) => $row->end_date ? Carbon::parse($row->end_date)->format('d M Y') : '—')
        ->addColumn('action', fn ($row) => '<a href="'.route('programme.show', encrypt($row->pk)).'" class="btn btn-sm btn-primary" target="_blank">View Details</a>')
        ->rawColumns(['action'])
        ->make(true);
}

function guest_faculty(Request $request)
{
    if ($request->ajax()) {
        return $this->facultyDatatable(2);
    }

    return view('admin.dashboard.guest_faculty');
}

function inhouse_faculty(Request $request){
    if ($request->ajax()) {
        return $this->facultyDatatable(1);
    }

    return view('admin.dashboard.inhouse_faculty');
}

/**
 * Server-side feed for the Guest / In-House faculty grids.
 * Session count and feedback averages are computed for the visible page only.
 */
private function facultyDatatable(int $facultyType)
{
    $badgeLabel = $facultyType === 2 ? 'Guest' : 'Inhouse';
    $badgeClass = $facultyType === 2 ? 'badge-guest' : 'badge-inhouse';
    $isAdmin = hasRole('Admin');

    $query = DB::table('faculty_master')
        ->where('faculty_type', $facultyType)
        ->where('active_inactive', 1)
        ->select(['pk', 'full_name', 'email_id', 'mobile_no', 'faculty_sector']);

    $table = \Yajra\DataTables\Facades\DataTables::of($query)
        ->addIndexColumn()
        ->addColumn('faculty_type', fn () => '<span class="badge rounded-1 '.$badgeClass.' bg-success-subtle text-success border border-success-subtle">'.$badgeLabel.'</span>')
        ->editColumn('full_name', fn ($row) => '<span class="faculty-name">'.e($row->full_name).'</span>')
        ->editColumn('email_id', fn ($row) => $row->email_id
            ? '<a href="mailto:'.e($row->email_id).'" class="email-link">'
                .'<span class="material-symbols-rounded align-text-bottom me-1" style="font-size: 1rem;">mail</span>'
                .e($row->email_id).'</a>'
            : '<span class="text-muted small">N/A</span>')
        ->editColumn('mobile_no', fn ($row) => e($row->mobile_no ?: 'N/A'))
        ->addColumn('faculty_sector_label', function ($row) {
            if ((int) $row->faculty_sector === 1) {
                return '<span class="badge rounded-1 badge-sector-gov border border-primary-subtle">Government</span>';
            }
            if ((int) $row->faculty_sector === 2) {
                return '<span class="badge rounded-1 badge-sector-private border border-warning-subtle">Private</span>';
            }

            return '<span class="badge rounded-1 badge-sector-other border border-secondary-subtle">Other</span>';
        })
        ->addColumn('session_count', function ($row) {
            $count = $this->getSessionCountForFaculty((int) ($row->pk ?? 0));

            return '<span class="session-count-badge d-inline-flex align-items-center gap-1">'
                .'<span class="material-symbols-rounded align-text-bottom" style="font-size: 1rem;">event</span>'
                .$count.'</span>';
        })
        ->addColumn('feedback_average', fn ($row) => $this->facultyFeedbackAverageHtml((int) ($row->pk ?? 0)));

    if ($isAdmin) {
        $table->addColumn('action', fn ($row) => '<a href="'.route('feedback.average', ['faculty_name' => $row->full_name]).'" class="btn btn-view-feedback btn-sm">'
            .'<span class="material-symbols-rounded">visibility</span> View Feedback</a>');
    }

    return $table
        ->rawColumns(['faculty_type', 'full_name', 'email_id', 'faculty_sector_label', 'session_count', 'feedback_average', 'action'])
        ->make(true);
}

/**
 * Feedback average cell for one faculty (same thresholds/markup as the old Blade table).
 */
private function facultyFeedbackAverageHtml(int $facultyPk): string
{
    $summary = DB::table('topic_feedback as tf')
        ->select(
            DB::raw('COUNT(*) as total_feedback'),
            DB::raw('ROUND(AVG(CAST(tf.content AS DECIMAL(10,2))) * 20, 2) as avg_content'),
            DB::raw('ROUND(AVG(CAST(tf.presentation AS DECIMAL(10,2))) * 20, 2) as avg_presentation')
        )
        ->where('tf.is_submitted', 1)
        ->where('tf.faculty_pk', $facultyPk)
        ->first();

    $totalFeedback = (int) ($summary->total_feedback ?? 0);
    if ($totalFeedback <= 0) {
        return '<span class="text-muted small">No feedback yet</span>';
    }

    $scoreClass = function ($score) {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'average';
        return 'poor';
    };

    $avgContent = (float) ($summary->avg_content ?? 0);
    $avgPresentation = (float) ($summary->avg_presentation ?? 0);

    return '<div class="feedback-average">'
        .'<div class="feedback-score"><span class="feedback-label">Content:</span>'
        .'<span class="feedback-value '.$scoreClass($avgContent).'">'.number_format($avgContent, 1).'%</span></div>'
        .'<div class="feedback-score"><span class="feedback-label">Presentation:</span>'
        .'<span class="feedback-value '.$scoreClass($avgPresentation).'">'.number_format($avgPresentation, 1).'%</span></div>'
        .'</div>';
}

private function getFacultyWithMetrics(int $facultyType)
{
    $faculties = DB::table('faculty_master')
        ->where('faculty_type', $facultyType)
        ->where('active_inactive', 1)
        ->get();

    $feedbackSummaryByFaculty = DB::table('topic_feedback as tf')
        ->select(
            'tf.faculty_pk',
            DB::raw('COUNT(*) as total_feedback'),
            DB::raw('ROUND(AVG(CAST(tf.content AS DECIMAL(10,2))) * 20, 2) as avg_content'),
            DB::raw('ROUND(AVG(CAST(tf.presentation AS DECIMAL(10,2))) * 20, 2) as avg_presentation')
        )
        ->where('tf.is_submitted', 1)
        ->groupBy('tf.faculty_pk')
        ->get()
        ->keyBy('faculty_pk');

    return $faculties->map(function ($faculty) use ($feedbackSummaryByFaculty) {
        $facultyPk = (int) ($faculty->pk ?? 0);
        $summary = $feedbackSummaryByFaculty->get($facultyPk);

        $faculty->session_count = $facultyPk > 0 ? $this->getSessionCountForFaculty($facultyPk) : 0;
        $faculty->feedback_summary = [
            'avg_content' => $summary ? (float) $summary->avg_content : 0,
            'avg_presentation' => $summary ? (float) $summary->avg_presentation : 0,
            'total_feedback' => $summary ? (int) $summary->total_feedback : 0,
        ];

        return $faculty;
    });
}

private function getSessionCountForFaculty(int $facultyPk): int
{
    return CalendarEvent::query()
        ->where('active_inactive', 1)
        ->where(function ($query) use ($facultyPk) {
            $query->whereRaw('JSON_CONTAINS(faculty_master, ?)', ['"' . $facultyPk . '"'])
                ->orWhereRaw('FIND_IN_SET(?, faculty_master)', [$facultyPk]);
        })
        ->count();
}

function sessions(Request $request)
{
    $query = $this->sessionsQuery();

    if ($request->ajax()) {
        return $this->sessionsDatatable($query);
    }

    return view('admin.dashboard.sessions');
}

/**
 * Sessions visible to the signed-in faculty user (null when the user is not faculty
 * or has no faculty_master row — the grid then shows no records).
 */
private function sessionsQuery()
{
    $userId = Auth::user()->user_id;

    // Fetch sessions for Internal Faculty or Guest Faculty
    if (! (hasRole('Internal Faculty') || hasRole('Guest Faculty'))) {
        return null;
    }

    // Get faculty_master.pk from user_id
    $faculty = FacultyMaster::where('employee_master_pk', $userId)->first();
    if (! $faculty) {
        return null;
    }

    $facultyPk = $faculty->pk;

    return CalendarEvent::where('active_inactive', 1)
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
        ->orderBy('class_session', 'asc');
}

/**
 * Server-side feed for the Sessions grid. Row details (course / subject / module /
 * group names, venue, session time) are resolved for the visible page only.
 */
private function sessionsDatatable($query)
{
    if ($query === null) {
        return \Yajra\DataTables\Facades\DataTables::of(collect([]))->make(true);
    }

    $mapped = [];
    $map = function ($session) use (&$mapped) {
        $key = (string) $session->pk;
        if (! array_key_exists($key, $mapped)) {
            $mapped[$key] = $this->mapSessionRow($session);
        }

        return $mapped[$key];
    };

    return \Yajra\DataTables\Facades\DataTables::of($query)
        ->addIndexColumn()
        ->addColumn('course_name', fn ($s) => e($map($s)['course_name']))
        ->addColumn('subject_name', fn ($s) => e($map($s)['subject_name']))
        ->addColumn('module_name', fn ($s) => e($map($s)['module_name']))
        ->addColumn('topic', fn ($s) => e($map($s)['topic']))
        ->addColumn('group_names', fn ($s) => e($map($s)['group_names']))
        ->addColumn('venue_name', fn ($s) => e($map($s)['venue_name']))
        ->addColumn('session_time', fn ($s) => e($map($s)['session_time']))
        ->addColumn('session_date', fn ($s) => e($map($s)['session_date']))
        ->addColumn('status', function ($s) {
            $sessionDate = Carbon::parse($s->START_DATE);
            if ($sessionDate->isToday()) {
                return '<span class="badge bg-primary">Today</span>';
            }
            if ($sessionDate->isPast()) {
                return '<span class="badge bg-secondary">Completed</span>';
            }

            return '<span class="badge bg-success">Upcoming</span>';
        })
        ->filterColumn('topic', fn ($q, $keyword) => $q->where('subject_topic', 'like', "%{$keyword}%"))
        ->rawColumns(['status'])
        ->make(true);
}

/**
 * Shape one timetable row for the Sessions grid.
 */
private function mapSessionRow($session): array
{
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
}

}
