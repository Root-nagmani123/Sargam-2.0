<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseStudentAttendance;
use App\Models\MDOEscotDutyMap;
use App\Models\StudentMaster;
use App\Models\StudentMasterCourseMap;
use App\Models\StudentMedicalExemption;
use App\Services\OTNoticeMemoService;
use Illuminate\Support\Facades\DB;

class ParticipantHistoryController extends Controller
{
    /**
     * Display comprehensive participant history across all courses
     * Shows: previous courses, academic reports, notices, memos, session attendance, circular activity
     */
    public function show($id)
    {
        try {
            $studentPk = decrypt($id);
        } catch (\Exception $e) {
            return redirect()->route('admin.dashboard.students')
                ->with('error', 'Invalid participant ID.');
        }

        $student = StudentMaster::with(['service'])->find($studentPk);
        if (!$student) {
            return redirect()->route('admin.dashboard.students')
                ->with('error', 'Participant not found.');
        }

        // Get ALL courses (active + inactive) - full history
        $courseMaps = StudentMasterCourseMap::with('course')
            ->where('student_master_pk', $studentPk)
            ->orderByDesc('created_date')
            ->get();

        $courseIds = $courseMaps->pluck('course_master_pk')->filter()->unique()->values()->toArray();

        // Medical exemptions (grouped by course)
        $medicalExemptions = StudentMedicalExemption::with(['course', 'category', 'speciality'])
            ->where('student_master_pk', $studentPk)
            ->where('active_inactive', 1)
            ->orderBy('from_date', 'desc')
            ->get()
            ->groupBy('course_master_pk');

        // Duties (grouped by course)
        $duties = MDOEscotDutyMap::with(['courseMaster', 'mdoDutyTypeMaster', 'facultyMaster'])
            ->where('selected_student_list', $studentPk)
            ->orderBy('mdo_date', 'desc')
            ->get()
            ->groupBy('course_master_pk');

        // Notices and memos
        $noticeMemoService = app(OTNoticeMemoService::class);
        $notices = collect($noticeMemoService->getNotices($studentPk))->groupBy('course_master_pk');
        $memos = collect($noticeMemoService->getMemos($studentPk))->groupBy('course_master_pk');

        // Attendance per course (summary + session records)
        $attendanceByCourse = $this->getAttendanceByCourse($studentPk, $courseIds);

        // Overall summary across all courses
        $overallSummary = $this->getOverallSummary(
            $medicalExemptions,
            $duties,
            $notices,
            $memos,
            $attendanceByCourse
        );

        return view('admin.participant_history.show', compact(
            'student',
            'courseMaps',
            'medicalExemptions',
            'duties',
            'notices',
            'memos',
            'attendanceByCourse',
            'overallSummary'
        ));
    }

    /**
     * Get attendance data grouped by course
     */
    protected function getAttendanceByCourse(int $studentPk, array $courseIds): array
    {
        $result = [];

        foreach ($courseIds as $coursePk) {
            $summaryRow = CourseStudentAttendance::where('Student_master_pk', $studentPk)
                ->where('course_master_pk', $coursePk)
                ->selectRaw('
                    COUNT(*) as total_sessions,
                    COALESCE(SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END), 0) as present_count,
                    COALESCE(SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END), 0) as late_count,
                    COALESCE(SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END), 0) as absent_count,
                    COALESCE(SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END), 0) as mdo_count,
                    COALESCE(SUM(CASE WHEN status = 5 THEN 1 ELSE 0 END), 0) as escort_count,
                    COALESCE(SUM(CASE WHEN status = 6 THEN 1 ELSE 0 END), 0) as medical_exempt_count,
                    COALESCE(SUM(CASE WHEN status = 7 THEN 1 ELSE 0 END), 0) as other_exempt_count
                ')
                ->first();

            $summary = $summaryRow ?? (object) [
                'total_sessions' => 0, 'present_count' => 0, 'late_count' => 0, 'absent_count' => 0,
                'mdo_count' => 0, 'escort_count' => 0, 'medical_exempt_count' => 0, 'other_exempt_count' => 0,
            ];

            // Session-wise attendance for this course
            $sessions = DB::table('course_student_attendance as csa')
                ->leftJoin('timetable as t', 'csa.timetable_pk', '=', 't.pk')
                ->leftJoin('venue_master as v', 't.venue_id', '=', 'v.venue_id')
                ->leftJoin('class_session_master as cs', 't.class_session', '=', 'cs.pk')
                ->leftJoin('faculty_master as f', 't.faculty_master', '=', 'f.pk')
                ->where('csa.Student_master_pk', $studentPk)
                ->where('csa.course_master_pk', $coursePk)
                ->select(
                    'csa.pk',
                    'csa.status',
                    't.START_DATE as session_date',
                    't.subject_topic',
                    'v.venue_name',
                    'cs.start_time',
                    'cs.end_time',
                    'f.full_name as faculty_name'
                )
                ->orderBy('t.START_DATE', 'asc')
                ->get();

            $result[$coursePk] = [
                'summary' => $summary,
                'sessions' => $sessions,
            ];
        }

        return $result;
    }

    /**
     * Get overall summary counts
     */
    protected function getOverallSummary($medicalExemptions, $duties, $notices, $memos, $attendanceByCourse): array
    {
        return [
            'medical_count' => $medicalExemptions->flatten(1)->count(),
            'duty_count' => $duties->flatten(1)->count(),
            'notice_count' => $notices->flatten(1)->count(),
            'memo_count' => $memos->flatten(1)->count(),
            'courses_count' => collect($attendanceByCourse)->count(),
            'total_present' => collect($attendanceByCourse)->sum(fn ($a) => $a['summary']->present_count ?? 0),
            'total_absent' => collect($attendanceByCourse)->sum(fn ($a) => $a['summary']->absent_count ?? 0),
            'total_late' => collect($attendanceByCourse)->sum(fn ($a) => $a['summary']->late_count ?? 0),
        ];
    }
}
