<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CourseMaster;
use App\Models\FacultyMaster;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\FacultyFeedbackExport;

class FeedbackController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function database()
    {
        try {
            // Fetch active courses
            $courses = CourseMaster::where('active_inactive', 1)
                ->select('pk', 'course_name')
                ->orderBy('course_name')
                ->get();

            // Fetch all faculties for dropdown
            $faculties = FacultyMaster::where('active_inactive', 1)
                ->select('pk', 'full_name')
                ->orderBy('full_name')
                ->get();

            return view('admin.feedback.feedback_database', compact('courses', 'faculties'));
        } catch (\Exception $e) {
            \Log::error('Error in FeedbackController@database: ' . $e->getMessage());

            // Fallback
            $courses = collect();
            $faculties = collect();
            return view('admin.feedback.feedback_database', compact('courses', 'faculties'));
        }
    }

    public function getDatabaseData(Request $request)
    {
        try {
            $validated = $request->validate([
                'course_id' => 'required|integer',
                'search_param' => 'nullable|string|in:all,faculty,topic',
                'search_value' => 'nullable|string',
                'faculty_id' => 'nullable|integer',
                'topic_value' => 'nullable|string',
            ]);

            $courseId = $request->course_id;

            // Main query - removed ROW_NUMBER() window function
            $query = DB::table('topic_feedback as tf')
                ->select([
                    'f.pk as faculty_id',
                    'f.full_name as faculty_name',
                    'f.email_id as faculty_email',
                    DB::raw('IFNULL(f.Permanent_Address, "N/A") as faculty_address'),
                    'c.course_name',
                    't.subject_topic',
                    DB::raw('AVG(tf.content) * 20 as avg_content_percent'),
                    DB::raw('AVG(tf.presentation) * 20 as avg_presentation_percent'),
                    DB::raw('COUNT(DISTINCT tf.student_master_pk) as participant_count'),
                    DB::raw('DATE(t.START_DATE) as session_date'),
                    DB::raw('GROUP_CONCAT(DISTINCT tf.remark SEPARATOR " | ") as all_comments'),
                    't.pk as timetable_pk',
                    'tf.created_date' // Added for ordering
                ])
                ->join('timetable as t', 'tf.timetable_pk', '=', 't.pk')
                ->join('faculty_master as f', 't.faculty_master', '=', 'f.pk')
                ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
                ->where('t.course_master_pk', $courseId);

            // Apply search filters based on search_param
            if ($request->filled('search_param')) {
                switch ($request->search_param) {
                    case 'faculty':
                        if ($request->filled('faculty_id')) {
                            $query->where('f.pk', $request->faculty_id);
                        }
                        break;
                    case 'topic':
                        if ($request->filled('topic_value')) {
                            $query->where('t.subject_topic', 'like', "%{$request->topic_value}%");
                        }
                        break;
                }
            }

            // Apply date filter for performance (last 2 years)
            $query->where('t.START_DATE', '>=', Carbon::now()->subYears(2));

            // Group by - added all selected columns
            $query->groupBy(
                'f.pk',
                'f.full_name',
                'f.email_id',
                'f.Residence_address',
                'f.Permanent_Address',
                'c.course_name',
                't.subject_topic',
                't.START_DATE',
                't.pk',
                'tf.created_date' // Added to GROUP BY
            )
                ->orderBy('t.START_DATE', 'DESC');

            // Get total count for pagination
            $total = DB::table(DB::raw("({$query->toSql()}) as sub"))
                ->mergeBindings($query)
                ->count();

            // Apply pagination
            $perPage = $request->per_page ?? 10;
            $page = $request->page ?? 1;

            $feedbackData = $query->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            // Calculate row numbers manually
            $feedbackData = $feedbackData->map(function ($item, $index) use ($page, $perPage) {
                $item->row_num = (($page - 1) * $perPage) + $index + 1;
                // ✅ MATCH faculty decrypt($id) logic
                $item->faculty_enc_id = encrypt($item->faculty_id);
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $feedbackData,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getDatabaseData: ' . $e->getMessage());
            \Log::error('Error Trace:', ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'error' => 'Error loading data: ' . $e->getMessage(),
                'data' => [],
                'total' => 0
            ], 500);
        }
    }

    public function getTopicsForCourse(Request $request)
    {
        try {
            $request->validate([
                'course_id' => 'required|integer'
            ]);

            $topics = DB::table('timetable')
                ->where('course_master_pk', $request->course_id)
                ->whereNotNull('subject_topic')
                ->where('subject_topic', '!=', '')
                ->select('subject_topic')
                ->distinct()
                ->orderBy('subject_topic')
                ->get()
                ->pluck('subject_topic');

            return response()->json([
                'success' => true,
                'topics' => $topics
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getTopicsForCourse: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function exportDatabase(Request $request)
    {
        try {
            $validated = $request->validate([
                'course_id' => 'required|integer',
                'export_type' => 'required|in:excel,csv,pdf',
                'search_param' => 'nullable|string',
                'faculty_id' => 'nullable|integer',
                'topic_value' => 'nullable|string'
            ]);

            $data = $this->getDatabaseQuery($request)->get();

            // Transform for export
            $exportData = $data->map(function ($item, $index) {
                return [
                    'S.No.' => $index + 1,
                    'Faculty Name' => $item->faculty_name ?? 'N/A',
                    'Course Name' => $item->course_name ?? 'N/A',
                    'Faculty Address' => ($item->faculty_address ?? 'N/A') .
                        ($item->faculty_email ? "\n" . $item->faculty_email : ''),
                    'Topic' => $item->subject_topic ?? 'N/A',
                    'Content (%)' => $item->avg_content_percent ?
                        number_format($item->avg_content_percent, 2) : '0.00',
                    'Presentation (%)' => $item->avg_presentation_percent ?
                        number_format($item->avg_presentation_percent, 2) : '0.00',
                    'No. of Participants' => $item->participant_count ?? 0,
                    'Session Date' => $item->session_date ?
                        Carbon::parse($item->session_date)->format('d-m-Y') : 'N/A',
                    'Comments' => $item->all_comments ?? 'No comments'
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $exportData,
                'filename' => 'feedback_database_' . date('Y_m_d_H_i_s')
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in exportDatabase: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getDatabaseQuery(Request $request)
    {
        $query = DB::table('topic_feedback as tf')
            ->select([
                'f.pk as faculty_id',
                'f.full_name as faculty_name',
                'f.email_id as faculty_email',
                DB::raw('IFNULL(f.Permanent_Address, "N/A") as faculty_address'),
                'c.course_name',
                't.subject_topic',
                DB::raw('AVG(tf.content) * 20 as avg_content_percent'),
                DB::raw('AVG(tf.presentation) * 20 as avg_presentation_percent'),
                DB::raw('COUNT(DISTINCT tf.student_master_pk) as participant_count'),
                DB::raw('DATE(t.START_DATE) as session_date'),
                DB::raw('GROUP_CONCAT(DISTINCT tf.remark SEPARATOR " | ") as all_comments')
            ])
            ->join('timetable as t', 'tf.timetable_pk', '=', 't.pk')
            ->join('faculty_master as f', 't.faculty_master', '=', 'f.pk')
            ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
            ->where('t.course_master_pk', $request->course_id);

        // Apply filters
        if ($request->filled('search_param')) {
            if ($request->search_param === 'faculty' && $request->filled('faculty_id')) {
                $query->where('f.pk', $request->faculty_id);
            } elseif ($request->search_param === 'topic' && $request->filled('topic_value')) {
                $query->where('t.subject_topic', 'like', "%{$request->topic_value}%");
            }
        }

        $query->groupBy(
            'f.pk',
            'f.full_name',
            'f.email_id',
            'f.Residence_address',
            'f.Permanent_Address',
            'c.course_name',
            't.subject_topic',
            't.START_DATE'
        )
            ->orderBy('t.START_DATE', 'DESC');

        return $query;
    }

    public function showFacultyAverage(Request $request)
    {
        // Get filter parameters with defaults
        $programName = $request->input('program_name');
        $facultyName = $request->input('faculty_name');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $courseType = $request->input('course_type', 'archived');

        // 1. Get programs from course_master table
        $programs = DB::table('course_master')
            ->select('course_name')
            ->distinct()
            ->orderBy('course_name')
            ->pluck('course_name', 'course_name');

        if ($programs->isEmpty()) {
            $programs = collect(['Phase-I 2024' => 'Phase-I 2024']);
        }

        // 2. Get faculties from faculty_master
        $faculties = DB::table('faculty_master')
            ->select('full_name as name', 'pk')
            ->orderBy('full_name')
            ->pluck('name', 'pk');

        if ($faculties->isEmpty()) {
            // Fallback: Get faculty names from topic_feedback faculty_pk
            $faculties = DB::table('topic_feedback as tf')
                ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
                ->select('fm.full_name as name', 'fm.pk')
                ->distinct()
                ->orderBy('fm.full_name')
                ->pluck('name', 'fm.pk');
        }

        // Set default program if not selected
        if (!$programName && !$programs->isEmpty()) {
            $programName = $programs->keys()->first();
        }

        // 3. Build the main query with CORRECT JOIN
        $query = DB::table('topic_feedback as tf')
            ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
            ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk') // FIXED: course_master_pk
            ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
            ->select(
                'tf.faculty_pk',
                'fm.full_name as faculty_name', // Get faculty name directly
                'tf.topic_name',
                'cm.course_name as program_name',
                DB::raw('COUNT(DISTINCT tf.student_master_pk) as participants'),
                // Presentation rating counts
                DB::raw('SUM(CASE WHEN tf.presentation = "5" THEN 1 ELSE 0 END) as presentation_5'),
                DB::raw('SUM(CASE WHEN tf.presentation = "4" THEN 1 ELSE 0 END) as presentation_4'),
                DB::raw('SUM(CASE WHEN tf.presentation = "3" THEN 1 ELSE 0 END) as presentation_3'),
                DB::raw('SUM(CASE WHEN tf.presentation = "2" THEN 1 ELSE 0 END) as presentation_2'),
                DB::raw('SUM(CASE WHEN tf.presentation = "1" THEN 1 ELSE 0 END) as presentation_1'),
                // Content rating counts (note: tf.content is varchar in your table)
                DB::raw('SUM(CASE WHEN tf.content = "5" THEN 1 ELSE 0 END) as content_5'),
                DB::raw('SUM(CASE WHEN tf.content = "4" THEN 1 ELSE 0 END) as content_4'),
                DB::raw('SUM(CASE WHEN tf.content = "3" THEN 1 ELSE 0 END) as content_3'),
                DB::raw('SUM(CASE WHEN tf.content = "2" THEN 1 ELSE 0 END) as content_2'),
                DB::raw('SUM(CASE WHEN tf.content = "1" THEN 1 ELSE 0 END) as content_1')
            )
            ->where('tf.is_submitted', 1)
            ->whereNotNull('tf.presentation')
            ->whereNotNull('tf.content')
            ->groupBy('tf.faculty_pk', 'tf.topic_name', 'cm.course_name', 'fm.full_name');

        // Apply filters
        if ($programName && $programName !== 'All Programs') {
            $query->where('cm.course_name', 'LIKE', '%' . $programName . '%');
        }

        if ($facultyName && $facultyName !== 'All Faculty') {
            $query->where('tf.faculty_pk', $facultyName);
        }

        if ($fromDate) {
            $query->whereDate('tf.created_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('tf.created_date', '<=', $toDate);
        }

        // For archived courses - filter by end date
        if ($courseType === 'archived') {
            $query->whereDate('tt.END_DATE', '<', Carbon::today());
        }

        // Execute query
        $feedbackData = $query->get();

        // 4. Process each record to calculate percentages
        $processedData = $feedbackData->map(function ($item) {
            // IMPORTANT: Convert counts to integers since they're from SUM() functions
            $presentation_5 = (int)$item->presentation_5;
            $presentation_4 = (int)$item->presentation_4;
            $presentation_3 = (int)$item->presentation_3;
            $presentation_2 = (int)$item->presentation_2;
            $presentation_1 = (int)$item->presentation_1;

            $content_5 = (int)$item->content_5;
            $content_4 = (int)$item->content_4;
            $content_3 = (int)$item->content_3;
            $content_2 = (int)$item->content_2;
            $content_1 = (int)$item->content_1;

            // Calculate presentation percentage
            $presentationWeightedSum = (5 * $presentation_5) +
                (4 * $presentation_4) +
                (3 * $presentation_3) +
                (2 * $presentation_2) +
                (1 * $presentation_1);

            $presentationTotal = $presentation_5 + $presentation_4 +
                $presentation_3 + $presentation_2 +
                $presentation_1;

            $presentationPercentage = $presentationTotal > 0
                ? round(($presentationWeightedSum / ($presentationTotal * 5)) * 100, 2)
                : 0;

            // Calculate content percentage
            $contentWeightedSum = (5 * $content_5) +
                (4 * $content_4) +
                (3 * $content_3) +
                (2 * $content_2) +
                (1 * $content_1);

            $contentTotal = $content_5 + $content_4 +
                $content_3 + $content_2 +
                $content_1;

            $contentPercentage = $contentTotal > 0
                ? round(($contentWeightedSum / ($contentTotal * 5)) * 100, 2)
                : 0;

            return [
                'faculty_pk' => $item->faculty_pk,
                'faculty_name' => $item->faculty_name,
                'topic_name' => $item->topic_name,
                'program_name' => $item->program_name,
                'participants' => (int)$item->participants,
                'presentation_percentage' => $presentationPercentage,
                'content_percentage' => $contentPercentage,
                'presentation_counts' => [
                    '5' => $presentation_5,
                    '4' => $presentation_4,
                    '3' => $presentation_3,
                    '2' => $presentation_2,
                    '1' => $presentation_1,
                ],
                'content_counts' => [
                    '5' => $content_5,
                    '4' => $content_4,
                    '3' => $content_3,
                    '2' => $content_2,
                    '1' => $content_1,
                ]
            ];
        });

        return view('admin.feedback.faculty_average', [
            'feedbackData' => $processedData,
            'programs' => $programs,
            'faculties' => $faculties,
            'currentProgram' => $programName,
            'currentFaculty' => $facultyName,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'courseType' => $courseType,
            'refreshTime' => now()->format('d-M-Y H:i'),
        ]);
    }

    public function facultyView(Request $request)
    {
        // Handle POST requests (form submissions)
        if ($request->isMethod('post')) {
            // Get all parameters from POST
            $programId = $request->input('program_id');
            $facultyName = $request->input('faculty_name');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $courseType = $request->input('course_type', 'archived');
            $facultyType = $request->input('faculty_type', []);
            $page = $request->input('page', 1);
        } else {
            // GET request (initial load or refresh)
            $programId = $request->input('program_id', '');
            $facultyName = $request->input('faculty_name', '');
            $fromDate = $request->input('from_date', '');
            $toDate = $request->input('to_date', '');
            $courseType = $request->input('course_type', 'archived');
            $facultyType = $request->input('faculty_type', []);
            $page = $request->input('page', 1);
        }

        // Ensure faculty_type is always an array
        if (is_string($facultyType)) {
            $facultyType = [$facultyType];
        }

        // Get programs based on course type - THIS MUST BE OUTSIDE THE IF/ELSE
        $programsQuery = DB::table('course_master')
            ->select('pk as id', 'course_name', 'active_inactive', 'start_year', 'end_date');

        if ($courseType === 'current') {
            $programsQuery->where('active_inactive', 1)
                ->whereDate('end_date', '>=', Carbon::today());
        } else {
            $programsQuery->where(function ($query) {
                $query->where('active_inactive', 0)
                    ->orWhereDate('end_date', '<', Carbon::today());
            });
        }

        $programs = $programsQuery->orderBy('course_name')
            ->pluck('course_name', 'id');

        if ($programs->isEmpty()) {
            $programs = collect([]);
        }

        // Define faculty types for filter checkboxes
        $facultyTypes = [
            '2' => 'Guest',
            '1' => 'Internal',
        ];

        // Get faculty suggestions - THIS ALSO MUST BE OUTSIDE THE IF/ELSE
        $facultySuggestions = collect();
        if (!empty($facultyType)) {
            $facultyQuery = DB::table('faculty_master')
                ->select('full_name', 'pk', 'faculty_type')
                ->whereIn('faculty_type', $facultyType)
                ->whereNotNull('full_name')
                ->orderBy('full_name');

            if ($facultyName) {
                $facultyQuery->where('full_name', 'LIKE', '%' . $facultyName . '%');
            }

            $facultySuggestions = $facultyQuery->limit(10)->get();
        }

        // Build query for detailed feedback data
        $query = DB::table('topic_feedback as tf')
            ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
            ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
            ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
            ->select(
                'tf.topic_name',
                'cm.pk as program_id',
                'cm.course_name as program_name',
                'cm.active_inactive as program_status',
                'cm.end_date as program_end_date',
                'fm.full_name as faculty_name',
                'fm.faculty_type',
                'tf.faculty_pk',
                'tt.START_DATE',
                'tt.END_DATE',
                'tf.timetable_pk',
                DB::raw('SUM(CASE WHEN tf.content = "5" THEN 1 ELSE 0 END) as content_5'),
                DB::raw('SUM(CASE WHEN tf.content = "4" THEN 1 ELSE 0 END) as content_4'),
                DB::raw('SUM(CASE WHEN tf.content = "3" THEN 1 ELSE 0 END) as content_3'),
                DB::raw('SUM(CASE WHEN tf.content = "2" THEN 1 ELSE 0 END) as content_2'),
                DB::raw('SUM(CASE WHEN tf.content = "1" THEN 1 ELSE 0 END) as content_1'),
                DB::raw('SUM(CASE WHEN tf.presentation = "5" THEN 1 ELSE 0 END) as presentation_5'),
                DB::raw('SUM(CASE WHEN tf.presentation = "4" THEN 1 ELSE 0 END) as presentation_4'),
                DB::raw('SUM(CASE WHEN tf.presentation = "3" THEN 1 ELSE 0 END) as presentation_3'),
                DB::raw('SUM(CASE WHEN tf.presentation = "2" THEN 1 ELSE 0 END) as presentation_2'),
                DB::raw('SUM(CASE WHEN tf.presentation = "1" THEN 1 ELSE 0 END) as presentation_1'),
                DB::raw('COUNT(DISTINCT tf.student_master_pk) as participants'),
                DB::raw('GROUP_CONCAT(DISTINCT CASE WHEN tf.remark IS NOT NULL AND tf.remark != "" THEN tf.remark END SEPARATOR "|||") as remarks')
            )
            ->where('tf.is_submitted', 1)
            ->whereNotNull('tf.presentation')
            ->whereNotNull('tf.content')
            ->groupBy('tf.topic_name', 'cm.pk', 'cm.course_name', 'cm.active_inactive', 'cm.end_date', 'fm.full_name', 'fm.faculty_type', 'tf.faculty_pk', 'tt.START_DATE', 'tt.END_DATE', 'tf.timetable_pk');

        // Apply filters
        if ($programId && $programId !== '') {
            $query->where('cm.pk', $programId);
        }

        if ($facultyName && $facultyName !== 'All Faculty') {
            $query->where('fm.full_name', 'LIKE', '%' . $facultyName . '%');
        }

        if (!empty($facultyType)) {
            $query->whereIn('fm.faculty_type', $facultyType);
        }

        if ($fromDate) {
            $query->whereDate('tt.START_DATE', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('tt.END_DATE', '<=', $toDate);
        }

        // Course type filter
        if ($courseType === 'archived') {
            $query->where(function ($q) {
                $q->where('cm.active_inactive', 0)
                    ->orWhereDate('cm.end_date', '<', Carbon::today());
            });
        } elseif ($courseType === 'current') {
            $query->where('cm.active_inactive', 1)
                ->whereDate('cm.end_date', '>=', Carbon::today());
        }

        // Get the data
        $feedbackData = $query->get();

        // Process ALL data for display
        $processedData = $feedbackData->map(function ($item) {
            // Convert to integers
            $content_5 = (int)$item->content_5;
            $content_4 = (int)$item->content_4;
            $content_3 = (int)$item->content_3;
            $content_2 = (int)$item->content_2;
            $content_1 = (int)$item->content_1;

            $presentation_5 = (int)$item->presentation_5;
            $presentation_4 = (int)$item->presentation_4;
            $presentation_3 = (int)$item->presentation_3;
            $presentation_2 = (int)$item->presentation_2;
            $presentation_1 = (int)$item->presentation_1;

            // Calculate percentages
            $contentWeightedSum = (5 * $content_5) + (4 * $content_4) + (3 * $content_3) + (2 * $content_2) + (1 * $content_1);
            $contentTotal = $content_5 + $content_4 + $content_3 + $content_2 + $content_1;
            $contentPercentage = $contentTotal > 0 ? round(($contentWeightedSum / ($contentTotal * 5)) * 100, 2) : 0;

            $presentationWeightedSum = (5 * $presentation_5) + (4 * $presentation_4) + (3 * $presentation_3) + (2 * $presentation_2) + (1 * $presentation_1);
            $presentationTotal = $presentation_5 + $presentation_4 + $presentation_3 + $presentation_2 + $presentation_1;
            $presentationPercentage = $presentationTotal > 0 ? round(($presentationWeightedSum / ($presentationTotal * 5)) * 100, 2) : 0;

            // Process remarks
            $remarks = [];
            if (!empty($item->remarks)) {
                $rawRemarks = explode('|||', $item->remarks);
                $remarks = array_filter(array_map('trim', $rawRemarks));
                $remarks = array_unique($remarks);
                $remarks = array_slice($remarks, 0, 10);
            }

            // Get faculty type display name
            $facultyTypeMap = [
                '1' => 'Internal',
                '2' => 'Guest',
            ];
            $facultyTypeDisplay = $facultyTypeMap[$item->faculty_type] ?? ucfirst($item->faculty_type);

            // Determine course status
            $courseStatus = 'Archived';
            if ($item->program_status == 1 && Carbon::parse($item->program_end_date)->gte(Carbon::today())) {
                $courseStatus = 'Current';
            }

            return [
                'topic_name' => $item->topic_name ?? '',
                'program_id' => $item->program_id ?? '',
                'program_name' => $item->program_name ?? '',
                'course_status' => $courseStatus,
                'faculty_name' => $item->faculty_name ?? '',
                'faculty_type' => $facultyTypeDisplay,
                'faculty_pk' => $item->faculty_pk ?? '',
                'start_date' => $item->START_DATE ?? '',
                'end_date' => $item->END_DATE ?? '',
                'timetable_pk' => $item->timetable_pk ?? '',
                'participants' => (int)($item->participants ?? 0),
                'content_counts' => [
                    '5' => $content_5,
                    '4' => $content_4,
                    '3' => $content_3,
                    '2' => $content_2,
                    '1' => $content_1,
                ],
                'presentation_counts' => [
                    '5' => $presentation_5,
                    '4' => $presentation_4,
                    '3' => $presentation_3,
                    '2' => $presentation_2,
                    '1' => $presentation_1,
                ],
                'content_percentage' => $contentPercentage,
                'presentation_percentage' => $presentationPercentage,
                'remarks' => $remarks,
            ];
        });

        // Paginate the processed array data
        $perPage = 1;
        $currentPage = $page;
        $totalRecords = $processedData->count();
        $totalPages = ceil($totalRecords / $perPage);

        // Ensure current page is valid
        if ($currentPage < 1) {
            $currentPage = 1;
        } elseif ($currentPage > $totalPages && $totalPages > 0) {
            $currentPage = $totalPages;
        }

        // Slice the processed data for the current page
        $currentPageData = $processedData->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // If current page has no data but total records > 0, go to page 1
        if ($currentPageData->isEmpty() && $totalRecords > 0) {
            $currentPage = 1;
            $currentPageData = $processedData->slice(0, $perPage)->values();
        }

        // Return view with ALL necessary variables for both initial load and AJAX
        return view('admin.feedback.faculty_view', [
            'feedbackData' => $currentPageData,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'programs' => $programs, // This was missing in AJAX response
            'facultyTypes' => $facultyTypes, // This was also missing
            'facultySuggestions' => $facultySuggestions, // This was also missing
            'currentProgram' => $programId,
            'currentFaculty' => $facultyName,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'courseType' => $courseType,
            'selectedFacultyTypes' => $facultyType,
            'refreshTime' => now()->format('d-M-Y H:i'),
        ]);
    }

    public function getFacultySuggestions(Request $request)
    {
        // Handle faculty_type parameter - it might be string or array
        $selectedTypes = $request->input('faculty_type', []);
        $searchTerm = $request->input('faculty_name', '');

        // Ensure selectedTypes is always an array
        if (is_string($selectedTypes)) {
            $selectedTypes = [$selectedTypes];
        } elseif (empty($selectedTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'No faculty type selected',
                'faculties' => []
            ]);
        }

        // Validate and clean faculty types (only allow 1 and 2)
        $validTypes = array_filter($selectedTypes, function ($type) {
            return in_array($type, ['1', '2']); // Only Internal and Guest
        });

        if (empty($validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid faculty type selected',
                'faculties' => []
            ]);
        }

        // Build query
        $query = DB::table('faculty_master')
            ->select('full_name', 'faculty_type')
            ->whereIn('faculty_type', $validTypes)
            ->whereNotNull('full_name')
            ->where('full_name', '!=', '');

        if (!empty($searchTerm)) {
            $query->where('full_name', 'LIKE', '%' . $searchTerm . '%');
        }

        $faculties = $query->orderBy('full_name')->limit(20)->get();

        // Map faculty types to display names (only Internal and Guest)
        $facultyTypeMap = [
            '1' => 'Internal',
            '2' => 'Guest',
        ];

        $faculties = $faculties->map(function ($faculty) use ($facultyTypeMap) {
            return [
                'full_name' => $faculty->full_name,
                'faculty_type_display' => $facultyTypeMap[$faculty->faculty_type] ?? 'Unknown'
            ];
        });

        return response()->json([
            'success' => true,
            'faculties' => $faculties
        ]);
    }

    public function exportFacultyFeedback(Request $request)
    {
        // Get filter parameters
        $programId = $request->input('program_id');
        $facultyName = $request->input('faculty_name');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $courseType = $request->input('course_type', 'archived');
        $facultyType = $request->input('faculty_type', []);
        $exportType = $request->input('export_type', 'excel');

        // Ensure faculty_type is always an array
        if (is_string($facultyType)) {
            $facultyType = [$facultyType];
        }

        // Build query for detailed feedback data (same as facultyView)
        $query = DB::table('topic_feedback as tf')
            ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
            ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
            ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
            ->select(
                'tf.topic_name',
                'cm.course_name as program_name',
                'cm.active_inactive as program_status',
                'cm.end_date as program_end_date',
                'fm.full_name as faculty_name',
                'fm.faculty_type',
                'tt.START_DATE',
                'tt.END_DATE',
                DB::raw('SUM(CASE WHEN tf.content = "5" THEN 1 ELSE 0 END) as content_5'),
                DB::raw('SUM(CASE WHEN tf.content = "4" THEN 1 ELSE 0 END) as content_4'),
                DB::raw('SUM(CASE WHEN tf.content = "3" THEN 1 ELSE 0 END) as content_3'),
                DB::raw('SUM(CASE WHEN tf.content = "2" THEN 1 ELSE 0 END) as content_2'),
                DB::raw('SUM(CASE WHEN tf.content = "1" THEN 1 ELSE 0 END) as content_1'),
                DB::raw('SUM(CASE WHEN tf.presentation = "5" THEN 1 ELSE 0 END) as presentation_5'),
                DB::raw('SUM(CASE WHEN tf.presentation = "4" THEN 1 ELSE 0 END) as presentation_4'),
                DB::raw('SUM(CASE WHEN tf.presentation = "3" THEN 1 ELSE 0 END) as presentation_3'),
                DB::raw('SUM(CASE WHEN tf.presentation = "2" THEN 1 ELSE 0 END) as presentation_2'),
                DB::raw('SUM(CASE WHEN tf.presentation = "1" THEN 1 ELSE 0 END) as presentation_1'),
                DB::raw('COUNT(DISTINCT tf.student_master_pk) as participants'),
                DB::raw('GROUP_CONCAT(DISTINCT CASE WHEN tf.remark IS NOT NULL AND tf.remark != "" THEN tf.remark END SEPARATOR "|||") as remarks')
            )
            ->where('tf.is_submitted', 1)
            ->whereNotNull('tf.presentation')
            ->whereNotNull('tf.content')
            ->groupBy('tf.topic_name', 'cm.pk', 'cm.course_name', 'cm.active_inactive', 'cm.end_date', 'fm.full_name', 'fm.faculty_type', 'tt.START_DATE', 'tt.END_DATE');

        // Apply filters
        if ($programId && $programId !== '') {
            $query->where('cm.pk', $programId);
        }

        if ($facultyName && $facultyName !== 'All Faculty') {
            $query->where('fm.full_name', 'LIKE', '%' . $facultyName . '%');
        }

        if (!empty($facultyType)) {
            $query->whereIn('fm.faculty_type', $facultyType);
        }

        if ($fromDate) {
            $query->whereDate('tt.START_DATE', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('tt.END_DATE', '<=', $toDate);
        }

        // Course type filter
        if ($courseType === 'archived') {
            $query->where(function ($q) {
                $q->where('cm.active_inactive', 0)
                    ->orWhereDate('cm.end_date', '<', Carbon::today());
            });
        } elseif ($courseType === 'current') {
            $query->where('cm.active_inactive', 1)
                ->whereDate('cm.end_date', '>=', Carbon::today());
        }

        // Get the data
        $feedbackData = $query->get();

        // Process data for export
        $processedData = $feedbackData->map(function ($item) {
        // Convert to integers
        $content_5 = (int)$item->content_5;
        $content_4 = (int)$item->content_4;
        $content_3 = (int)$item->content_3;
        $content_2 = (int)$item->content_2;
        $content_1 = (int)$item->content_1;

        $presentation_5 = (int)$item->presentation_5;
        $presentation_4 = (int)$item->presentation_4;
        $presentation_3 = (int)$item->presentation_3;
        $presentation_2 = (int)$item->presentation_2;
        $presentation_1 = (int)$item->presentation_1;

        // Calculate percentages (same as web view)
        $contentWeightedSum = (5 * $content_5) + (4 * $content_4) + (3 * $content_3) + (2 * $content_2) + (1 * $content_1);
        $contentTotal = $content_5 + $content_4 + $content_3 + $content_2 + $content_1;
        $contentPercentage = $contentTotal > 0 ? round(($contentWeightedSum / ($contentTotal * 5)) * 100, 2) : 0;

        $presentationWeightedSum = (5 * $presentation_5) + (4 * $presentation_4) + (3 * $presentation_3) + (2 * $presentation_2) + (1 * $presentation_1);
        $presentationTotal = $presentation_5 + $presentation_4 + $presentation_3 + $presentation_2 + $presentation_1;
        $presentationPercentage = $presentationTotal > 0 ? round(($presentationWeightedSum / ($presentationTotal * 5)) * 100, 2) : 0;

        // Process remarks (same as web view)
        $remarks = [];
        if (!empty($item->remarks)) {
            $rawRemarks = explode('|||', $item->remarks);
            $remarks = array_filter(array_map('trim', $rawRemarks));
            $remarks = array_unique($remarks);
            $remarks = array_slice($remarks, 0, 10);
        }

        // Get faculty type display name (same as web view)
        $facultyTypeMap = [
            '1' => 'Internal',
            '2' => 'Guest',
        ];
        $facultyTypeDisplay = $facultyTypeMap[$item->faculty_type] ?? ucfirst($item->faculty_type);

        // Determine course status (same as web view)
        $courseStatus = 'Archived';
        if ($item->program_status == 1 && Carbon::parse($item->program_end_date)->gte(Carbon::today())) {
            $courseStatus = 'Current';
        }

        // Format date and time
        $lectureDate = '';
        $startTime = '';
        $endTime = '';
        
        if ($item->START_DATE) {
            $lectureDate = Carbon::parse($item->START_DATE)->format('d-M-Y');
            $startTime = Carbon::parse($item->START_DATE)->format('H:i');
        }
        
        if ($item->END_DATE) {
            $endTime = Carbon::parse($item->END_DATE)->format('H:i');
        }

        return [
            'Program Name' => $item->program_name ?? '',
            'Course Status' => $courseStatus,
            'Faculty Name' => $item->faculty_name ?? '',
            'Faculty Type' => $facultyTypeDisplay,
            'Topic' => $item->topic_name ?? '',
            'Lecture Date' => $lectureDate,
            'Start Time' => $startTime,
            'End Time' => $endTime,
            'Total Participants' => (int)($item->participants ?? 0),
            'Content - Excellent' => $content_5,
            'Content - Very Good' => $content_4,
            'Content - Good' => $content_3,
            'Content - Average' => $content_2,
            'Content - Below Average' => $content_1,
            'Content Percentage' => number_format($contentPercentage, 2) . '%',
            'Presentation - Excellent' => $presentation_5,
            'Presentation - Very Good' => $presentation_4,
            'Presentation - Good' => $presentation_3,
            'Presentation - Average' => $presentation_2,
            'Presentation - Below Average' => $presentation_1,
            'Presentation Percentage' => number_format($presentationPercentage, 2) . '%',
            'Remarks' => implode("\n", $remarks),
        ];
    });

    // Export based on type
    if ($exportType === 'excel') {
        return Excel::download(new FacultyFeedbackExport($processedData), 'faculty_feedback_' . date('Y_m_d') . '.xlsx');
    } else {
        // PDF Export with exact web view design
        $data = [
            'feedbackData' => $processedData,
            'filters' => [
                'program' => $programId ? $this->getProgramName($programId) : 'All Programs',
                'faculty_name' => $facultyName ?: 'All Faculty',
                'date_range' => ($fromDate && $toDate) ? 
                    Carbon::parse($fromDate)->format('d-M-Y') . ' to ' . Carbon::parse($toDate)->format('d-M-Y') : 
                    'All Dates',
                'course_type' => $courseType === 'current' ? 'Current Courses' : 'Archived Courses',
                'faculty_type' => !empty($facultyType) ? 
                    (count($facultyType) === 2 ? 'All Types' : 
                     (in_array('1', $facultyType) ? 'Internal' : 'Guest')) : 
                    'All Types',
            ],
            'export_date' => now()->format('d-M-Y H:i'),
        ];

        $pdf = PDF::loadView('admin.feedback.faculty_feedback_pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'dpi' => 96,
                'margin_top' => 15,
                'margin_right' => 15,
                'margin_bottom' => 15,
                'margin_left' => 15,
            ]);

        return $pdf->download('faculty_feedback_' . date('Y_m_d') . '.pdf');
    }
}

    private function getProgramName($programId)
    {
        $program = DB::table('course_master')
            ->where('pk', $programId)
            ->first();

        return $program ? $program->course_name : 'Unknown Program';
    }
}
