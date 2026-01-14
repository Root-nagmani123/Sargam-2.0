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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str; // Add this import
use App\Exports\PendingFeedbackExport;




class FeedbackController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function database()
    {
        try {
            $data_course_id =  get_Role_by_course();
            // Fetch active courses
            $courses = CourseMaster::where('active_inactive', 1);
            if (!empty($data_course_id)) {
                $courses->whereIn('pk', $data_course_id);
            }
            $courses = $courses->select('pk', 'course_name')
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
        $data_course_id =  get_Role_by_course();


        // Get filter parameters with defaults
        $programName = $request->input('program_name');
        $facultyName = $request->input('faculty_name');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $courseType = $request->input('course_type', 'archived');

        // 1. Get programs from course_master table
        $programs = DB::table('course_master')
            ->when(!empty($data_course_id), function ($query) use ($data_course_id) {
                $query->whereIn('pk', $data_course_id);
            })
            ->distinct()
            ->orderBy('course_name')
            ->pluck('course_name', 'course_name');

        if ($programs->isEmpty()) {
            $programs = collect([
                'Phase-I 2024' => 'Phase-I 2024'
            ]);
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
            // ================= PRESENTATION =================
            $presentationWeightedSum =
                (5 * $presentation_5) +
                (4 * $presentation_4) +
                (3 * $presentation_3) +
                (2 * $presentation_2) +
                (1 * $presentation_1);

            $presentationTotal =
                $presentation_5 + $presentation_4 +
                $presentation_3 + $presentation_2 +
                $presentation_1;

            // dynamic max rating used
            $presentationMaxRating = 0;
            if ($presentation_5 > 0) $presentationMaxRating = 5;
            elseif ($presentation_4 > 0) $presentationMaxRating = 4;
            elseif ($presentation_3 > 0) $presentationMaxRating = 3;
            elseif ($presentation_2 > 0) $presentationMaxRating = 2;
            elseif ($presentation_1 > 0) $presentationMaxRating = 1;

            $presentationPercentage = ($presentationTotal > 0 && $presentationMaxRating > 0)
                ? round(($presentationWeightedSum / ($presentationTotal * $presentationMaxRating)) * 100, 2)
                : 0;

            $contentWeightedSum =
                (5 * $content_5) +
                (4 * $content_4) +
                (3 * $content_3) +
                (2 * $content_2) +
                (1 * $content_1);

            $contentTotal =
                $content_5 + $content_4 +
                $content_3 + $content_2 +
                $content_1;

            $contentMaxRating = 0;
            if ($content_5 > 0) $contentMaxRating = 5;
            elseif ($content_4 > 0) $contentMaxRating = 4;
            elseif ($content_3 > 0) $contentMaxRating = 3;
            elseif ($content_2 > 0) $contentMaxRating = 2;
            elseif ($content_1 > 0) $contentMaxRating = 1;

            $contentPercentage = ($contentTotal > 0 && $contentMaxRating > 0)
                ? round(($contentWeightedSum / ($contentTotal * $contentMaxRating)) * 100, 2)
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
        $data_course_id =  get_Role_by_course();

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
        if (!empty($data_course_id)) {
            $programsQuery->whereIn('pk', $data_course_id);
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
            );
        $query->where('tf.is_submitted', 1);
        if (!empty($data_course_id)) {
            $query->whereIn('cm.pk', $data_course_id);
        }
        $query->whereNotNull('tf.presentation')
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
            // $contentWeightedSum = (5 * $content_5) + (4 * $content_4) + (3 * $content_3) + (2 * $content_2) + (1 * $content_1);
            // $contentTotal = $content_5 + $content_4 + $content_3 + $content_2 + $content_1;
            // $contentPercentage = $contentTotal > 0 ? round(($contentWeightedSum / ($contentTotal * 5)) * 100, 2) : 0;

            // $presentationWeightedSum = (5 * $presentation_5) + (4 * $presentation_4) + (3 * $presentation_3) + (2 * $presentation_2) + (1 * $presentation_1);
            // $presentationTotal = $presentation_5 + $presentation_4 + $presentation_3 + $presentation_2 + $presentation_1;
            // $presentationPercentage = $presentationTotal > 0 ? round(($presentationWeightedSum / ($presentationTotal * 5)) * 100, 2) : 0;
            $contentWeightedSum =
                (5 * $content_5) +
                (4 * $content_4) +
                (3 * $content_3) +
                (2 * $content_2) +
                (1 * $content_1);

            $contentTotal =
                $content_5 + $content_4 + $content_3 + $content_2 + $content_1;

            // find max rating actually used
            $contentMaxRating = 0;
            if ($content_5 > 0) $contentMaxRating = 5;
            elseif ($content_4 > 0) $contentMaxRating = 4;
            elseif ($content_3 > 0) $contentMaxRating = 3;
            elseif ($content_2 > 0) $contentMaxRating = 2;
            elseif ($content_1 > 0) $contentMaxRating = 1;

            $contentPercentage = ($contentTotal > 0 && $contentMaxRating > 0)
                ? round(($contentWeightedSum / ($contentTotal * $contentMaxRating)) * 100, 2)
                : 0;

            $presentationWeightedSum =
                (5 * $presentation_5) +
                (4 * $presentation_4) +
                (3 * $presentation_3) +
                (2 * $presentation_2) +
                (1 * $presentation_1);

            $presentationTotal =
                $presentation_5 + $presentation_4 + $presentation_3 + $presentation_2 + $presentation_1;

            // find max rating actually used
            $presentationMaxRating = 0;
            if ($presentation_5 > 0) $presentationMaxRating = 5;
            elseif ($presentation_4 > 0) $presentationMaxRating = 4;
            elseif ($presentation_3 > 0) $presentationMaxRating = 3;
            elseif ($presentation_2 > 0) $presentationMaxRating = 2;
            elseif ($presentation_1 > 0) $presentationMaxRating = 1;

            $presentationPercentage = ($presentationTotal > 0 && $presentationMaxRating > 0)
                ? round(($presentationWeightedSum / ($presentationTotal * $presentationMaxRating)) * 100, 2)
                : 0;

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

        // Define faculty type map here - BEFORE using it in the map function
        $facultyTypeMap = [
            '1' => 'Internal',
            '2' => 'Guest',
        ];

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

        // Process data for export - use the $facultyTypeMap defined above
        $processedData = $feedbackData->map(function ($item) use ($facultyTypeMap) {
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
            // ================= PRESENTATION =================
            $presentationWeightedSum =
                (5 * $presentation_5) +
                (4 * $presentation_4) +
                (3 * $presentation_3) +
                (2 * $presentation_2) +
                (1 * $presentation_1);

            $presentationTotal =
                $presentation_5 + $presentation_4 +
                $presentation_3 + $presentation_2 +
                $presentation_1;

            // dynamic max rating used
            $presentationMaxRating = 0;
            if ($presentation_5 > 0) $presentationMaxRating = 5;
            elseif ($presentation_4 > 0) $presentationMaxRating = 4;
            elseif ($presentation_3 > 0) $presentationMaxRating = 3;
            elseif ($presentation_2 > 0) $presentationMaxRating = 2;
            elseif ($presentation_1 > 0) $presentationMaxRating = 1;

            $presentationPercentage = ($presentationTotal > 0 && $presentationMaxRating > 0)
                ? round(($presentationWeightedSum / ($presentationTotal * $presentationMaxRating)) * 100, 2)
                : 0;


            $contentWeightedSum =
                (5 * $content_5) +
                (4 * $content_4) +
                (3 * $content_3) +
                (2 * $content_2) +
                (1 * $content_1);

            $contentTotal =
                $content_5 + $content_4 +
                $content_3 + $content_2 +
                $content_1;

            $contentMaxRating = 0;
            if ($content_5 > 0) $contentMaxRating = 5;
            elseif ($content_4 > 0) $contentMaxRating = 4;
            elseif ($content_3 > 0) $contentMaxRating = 3;
            elseif ($content_2 > 0) $contentMaxRating = 2;
            elseif ($content_1 > 0) $contentMaxRating = 1;

            $contentPercentage = ($contentTotal > 0 && $contentMaxRating > 0)
                ? round(($contentWeightedSum / ($contentTotal * $contentMaxRating)) * 100, 2)
                : 0;


            // Process remarks (same as web view)
            $remarks = [];
            if (!empty($item->remarks)) {
                $rawRemarks = explode('|||', $item->remarks);
                $remarks = array_filter(array_map('trim', $rawRemarks));
                $remarks = array_unique($remarks);
                $remarks = array_slice($remarks, 0, 10);
            }

            // Get faculty type display name using the $facultyTypeMap from use()
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
            return $this->exportExcelWithDesign($processedData, $request);
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
                        (count($facultyType) === 2 ? 'All Types' : (in_array('1', $facultyType) ? 'Internal' : 'Guest')) :
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

    private function exportExcelWithDesign($data, $request)
    {
        // Create Excel file with custom design
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set basic properties
        $spreadsheet->getProperties()
            ->setCreator("Sargam LMS")
            ->setTitle("Faculty Feedback Report")
            ->setSubject("Faculty Feedback with Comments")
            ->setDescription("Export of faculty feedback data with detailed ratings and comments")
            ->setKeywords("faculty feedback ratings comments")
            ->setCategory("Report");

        // Set default styles - CORRECTED LINE: Use $spreadsheet, not $sheet
        $defaultFont = [
            'name' => 'Arial',
            'size' => 10,
        ];

        $spreadsheet->getDefaultStyle()->applyFromArray([
            'font' => $defaultFont,
        ]);

        // Start row counter
        $row = 1;

        // HEADER SECTION
        $sheet->setCellValue('A' . $row, 'Faculty Feedback with Comments (Admin View)');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'AF2910']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        $sheet->setCellValue('A' . $row, 'Sargam | Lal Bahadur Shastri Institute of Management');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        $sheet->setCellValue('A' . $row, 'Report Generated: ' . now()->format('d-M-Y H:i'));
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['italic' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;

        // FILTERS SECTION
        $sheet->setCellValue('A' . $row, 'Applied Filters');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'AF2910']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8F9FA']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D7DE']
                ]
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
        ]);
        $row++;

        // Get filter values from request
        $programId = $request->input('program_id');
        $facultyName = $request->input('faculty_name');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $courseType = $request->input('course_type', 'archived');
        $facultyType = $request->input('faculty_type', []);

        // Filter 1 row
        // $sheet->setCellValue('A' . $row, 'Course Status:');
        // $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
        // $sheet->setCellValue('B' . $row, $courseType === 'current' ? 'Current Courses' : 'Archived Courses');

        // $sheet->setCellValue('D' . $row, 'Program:');
        // $sheet->getStyle('D' . $row)->applyFromArray(['font' => ['bold' => true]]);
        // $sheet->setCellValue('E' . $row, $programId ? $this->getProgramName($programId) : 'All Programs');
        // $row++;

        // // Filter 2 row
        // $sheet->setCellValue('A' . $row, 'Faculty Name:');
        // $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
        // $sheet->setCellValue('B' . $row, $facultyName ?: 'All Faculty');

        // $sheet->setCellValue('D' . $row, 'Faculty Type:');
        // $sheet->getStyle('D' . $row)->applyFromArray(['font' => ['bold' => true]]);

        // $facultyTypeText = 'All Types';
        // if (!empty($facultyType)) {
        //     if (count($facultyType) === 2) {
        //         $facultyTypeText = 'All Types';
        //     } elseif (in_array('1', $facultyType)) {
        //         $facultyTypeText = 'Internal';
        //     } else {
        //         $facultyTypeText = 'Guest';
        //     }
        // }
        // $sheet->setCellValue('E' . $row, $facultyTypeText);
        // $row++;

        // // Filter 3 row
        // $sheet->setCellValue('A' . $row, 'Date Range:');
        // $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);

        // $dateRange = 'All Dates';
        // if ($fromDate && $toDate) {
        //     $dateRange = Carbon::parse($fromDate)->format('d-M-Y') . ' to ' . Carbon::parse($toDate)->format('d-M-Y');
        // }
        // $sheet->setCellValue('B' . $row, $dateRange);

        // $sheet->setCellValue('D' . $row, 'Total Records:');
        // $sheet->getStyle('D' . $row)->applyFromArray(['font' => ['bold' => true]]);
        // $sheet->setCellValue('E' . $row, count($data));
        // $row += 2;

        // Loop through each feedback item
        foreach ($data as $index => $item) {
            // META INFO SECTION
            $sheet->setCellValue('A' . $row, 'Course:');
            $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
            $sheet->setCellValue('B' . $row, $item['Program Name']);
            if (isset($item['Course Status'])) {
                $sheet->setCellValue('D' . $row, $item['Course Status']);
                $sheet->getStyle('D' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E9ECEF']
                    ],
                    'font' => ['size' => 9],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);
            }
            $row++;

            $sheet->setCellValue('A' . $row, 'Faculty:');
            $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
            $sheet->setCellValue('B' . $row, $item['Faculty Name']);
            if (isset($item['Faculty Type'])) {
                $sheet->setCellValue('D' . $row, $item['Faculty Type']);
                $sheet->getStyle('D' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E9ECEF']
                    ],
                    'font' => ['size' => 9],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);
            }
            $row++;

            $sheet->setCellValue('A' . $row, 'Topic:');
            $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
            $sheet->setCellValue('B' . $row, $item['Topic']);
            $row++;

            if (!empty($item['Lecture Date'])) {
                $sheet->setCellValue('A' . $row, 'Lecture Date:');
                $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);

                $dateTimeText = $item['Lecture Date'];
                if (!empty($item['Start Time']) && !empty($item['End Time'])) {
                    $dateTimeText .= ' (' . $item['Start Time'] . ' – ' . $item['End Time'] . ')';
                }
                $sheet->setCellValue('B' . $row, $dateTimeText);
                $row++;
            }
            $row++;

            // FEEDBACK TABLE HEADER
            $tableStartRow = $row;
            $sheet->setCellValue('A' . $row, 'Rating');
            $sheet->setCellValue('B' . $row, 'Content *');
            $sheet->setCellValue('C' . $row, 'Presentation *');

            // Style table header
            $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EEF4FB']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'D0D7DE']
                    ]
                ],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]);
            $row++;

            // EXCELLENT ROW
            $sheet->setCellValue('A' . $row, 'Excellent');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'AF2910']],
            ]);
            $sheet->setCellValue('B' . $row, $item['Content - Excellent']);
            $sheet->setCellValue('C' . $row, $item['Presentation - Excellent']);
            $this->applyTableCellStyle($sheet, 'A' . $row . ':C' . $row);
            $row++;

            // VERY GOOD ROW
            $sheet->setCellValue('A' . $row, 'Very Good');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'AF2910']],
            ]);
            $sheet->setCellValue('B' . $row, $item['Content - Very Good']);
            $sheet->setCellValue('C' . $row, $item['Presentation - Very Good']);
            $this->applyTableCellStyle($sheet, 'A' . $row . ':C' . $row);
            $row++;

            // GOOD ROW
            $sheet->setCellValue('A' . $row, 'Good');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'AF2910']],
            ]);
            $sheet->setCellValue('B' . $row, $item['Content - Good']);
            $sheet->setCellValue('C' . $row, $item['Presentation - Good']);
            $this->applyTableCellStyle($sheet, 'A' . $row . ':C' . $row);
            $row++;

            // AVERAGE ROW
            $sheet->setCellValue('A' . $row, 'Average');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'AF2910']],
            ]);
            $sheet->setCellValue('B' . $row, $item['Content - Average']);
            $sheet->setCellValue('C' . $row, $item['Presentation - Average']);
            $this->applyTableCellStyle($sheet, 'A' . $row . ':C' . $row);
            $row++;

            // BELOW AVERAGE ROW
            $sheet->setCellValue('A' . $row, 'Below Average');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'AF2910']],
            ]);
            $sheet->setCellValue('B' . $row, $item['Content - Below Average']);
            $sheet->setCellValue('C' . $row, $item['Presentation - Below Average']);
            $this->applyTableCellStyle($sheet, 'A' . $row . ':C' . $row);
            $row++;

            // PERCENTAGE ROW
            $sheet->setCellValue('A' . $row, 'Percentage');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'AF2910']],
            ]);
            $sheet->setCellValue('B' . $row, $item['Content Percentage']);
            $sheet->setCellValue('C' . $row, $item['Presentation Percentage']);
            $sheet->getStyle('B' . $row . ':C' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'AF2910']],
            ]);
            $this->applyTableCellStyle($sheet, 'A' . $row . ':C' . $row);
            $row++;

            // TOTAL PARTICIPANTS NOTE
            $sheet->setCellValue('A' . $row, '* is defined as Total Student Count: ' . $item['Total Participants']);
            $sheet->mergeCells('A' . $row . ':C' . $row);
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['italic' => true, 'size' => 9],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]);
            $row++;

            // REMARKS SECTION
            if (!empty($item['Remarks'])) {
                $remarks = explode("\n", $item['Remarks']);
                if (!empty(array_filter($remarks))) {
                    $row++;
                    $sheet->setCellValue('A' . $row, 'Remarks (' . count(array_filter($remarks)) . ')');
                    $sheet->mergeCells('A' . $row . ':C' . $row);
                    $sheet->getStyle('A' . $row)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'AF2910']
                        ],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                    ]);
                    $row++;

                    $remarkStartRow = $row;
                    foreach ($remarks as $remarkIndex => $remark) {
                        if (trim($remark) !== '') {
                            $sheet->setCellValue('A' . $row, ($remarkIndex + 1) . '. ' . trim($remark));
                            $sheet->mergeCells('A' . $row . ':C' . $row);
                            $row++;
                        }
                    }

                    // Style remarks area
                    $sheet->getStyle('A' . $remarkStartRow . ':C' . ($row - 1))->applyFromArray([
                        'borders' => [
                            'outline' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => 'D0D7DE']
                            ],
                            'inside' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE
                            ]
                        ],
                        'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP],
                    ]);
                }
            }

            // Add separator between records (except last one)
            if ($index < count($data) - 1) {
                $row++;
                $sheet->mergeCells('A' . $row . ':C' . $row);
                $sheet->getStyle('A' . $row)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DASHED);
                $sheet->getStyle('A' . $row)->getBorders()->getTop()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('DDDDDD'));
                $row += 2;
            }
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);

        // Auto size for remarks
        $sheet->getColumnDimension('A')->setAutoSize(true);

        // Add footer
        $row += 2;
        $sheet->setCellValue('A' . $row, 'Confidential - For Internal Use Only');
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '666666']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;
        $sheet->setCellValue('A' . $row, 'Generated by Sargam Faculty Feedback System');
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '666666']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        // Create writer and save to temporary file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $filename = 'faculty_feedback_' . date('Y_m_d_H_i') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'faculty_feedback_');
        $writer->save($tempFile);

        // Return download response
        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    private function applyTableCellStyle($sheet, $range)
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D7DE']
                ]
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
    }

    public function feedbackDetails(Request $request)
    {


        // \Log::info('Feedback Details Request: ', $request->all());
        try {
            $data_course_id =  get_Role_by_course();
            // Get filter parameters with defaults
            $programId = $request->input('program_id', '');
            $facultyName = $request->input('faculty_name', '');
            $fromDate = $request->input('from_date', '');
            $toDate = $request->input('to_date', '');
            $courseType = $request->input('course_type', 'current');
            $facultyType = $request->input('faculty_type', []);
            $page = $request->input('page', 1);

            // Ensure faculty_type is always an array
            if (is_string($facultyType)) {
                $facultyType = [$facultyType];
            }

            // Get active courses initially (for current type)
            $programsQuery = DB::table('course_master')
                ->select('pk as id', 'course_name', 'active_inactive', 'start_year', 'end_date');

            if ($courseType === 'current') {
                $programsQuery->where('active_inactive', 1)
                    ->whereDate('end_date', '>=', Carbon::today());
                if (!empty($data_course_id)) {
                    $programsQuery->whereIn('pk', $data_course_id);
                }
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
            // print_r($programs->toArray());die;
            // Define faculty types
            $facultyTypes = [
                '2' => 'Guest',
                '1' => 'Internal',
            ];

            // Get faculty suggestions if faculty type is selected
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

            // Build main query for feedback details
            // Update the select statement in the feedbackDetails method:
            // Build main query for feedback details
            $query = DB::table('topic_feedback as tf')
                ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
                ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
                ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
                ->join('student_master as sm', 'tf.student_master_pk', '=', 'sm.pk')
                ->select(
                    'tf.pk as feedback_id',
                    'tf.content',
                    'tf.presentation',
                    'tf.remark',
                    'tf.created_date as feedback_date',
                    // Create full name from separate name fields
                    DB::raw("CONCAT(
            COALESCE(sm.first_name, ''),
            CASE 
                WHEN sm.middle_name IS NOT NULL AND sm.middle_name != '' 
                THEN CONCAT(' ', sm.middle_name) 
                ELSE '' 
            END,
            CASE 
                WHEN sm.last_name IS NOT NULL AND sm.last_name != '' 
                THEN CONCAT(' ', sm.last_name) 
                ELSE '' 
            END
        ) as ot_name"),
                    DB::raw("COALESCE( sm.generated_OT_code) as ot_code"),
                    'tf.student_master_pk',
                    'cm.pk as program_id',
                    'cm.course_name as program_name',
                    'cm.active_inactive as program_status',
                    'cm.end_date as program_end_date',
                    'fm.full_name as faculty_name',
                    'fm.faculty_type',
                    'tf.faculty_pk',
                    'tt.START_DATE',
                    'tt.END_DATE',
                    'tt.subject_topic as topic_name',
                    'tf.timetable_pk'
                )

                ->where('tf.is_submitted', 1);
            if (!empty($data_course_id)) {
                $query->whereIn('cm.pk', $data_course_id);
            }
            $query->whereNotNull('tf.presentation')
                ->whereNotNull('tf.content')
                ->where('tf.presentation', '!=', '')
                ->where('tf.content', '!=', '');

            // Rest of the method remains the same...

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
            if (hasRole('Internal Faculty') || hasRole('Guest Faculty')) {
                $facultyPk = (Auth::user()->user_id);
                $query->where('fm.employee_master_pk', $facultyPk);
            }

            // Order by
            $query->orderBy('tt.START_DATE', 'DESC')
                ->orderBy('fm.full_name')
                ->orderBy('sm.first_name');

            // Get total count for pagination
            $totalRecords = $query->count();

            // Apply pagination - 10 records per page
            $perPage = 10;
            $currentPage = $page;
            $totalPages = ceil($totalRecords / $perPage);

            // Ensure current page is valid
            if ($currentPage < 1) {
                $currentPage = 1;
            } elseif ($currentPage > $totalPages && $totalPages > 0) {
                $currentPage = $totalPages;
            }

            // Get paginated data
            $feedbackData = $query->offset(($currentPage - 1) * $perPage)
                ->limit($perPage)
                ->get();

            // Process data for display
            $processedData = $feedbackData->map(function ($item) {
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
                    'feedback_id' => $item->feedback_id ?? '',
                    'ot_name' => $item->ot_name ?? '',
                    'ot_code' => $item->ot_code ?? '',
                    'content' => $item->content ?? '',
                    'presentation' => $item->presentation ?? '',
                    'remark' => $item->remark ?? '',
                    'feedback_date' => $item->feedback_date ? Carbon::parse($item->feedback_date)->format('d-M-Y H:i') : '',
                    'program_id' => $item->program_id ?? '',
                    'program_name' => $item->program_name ?? '',
                    'course_status' => $courseStatus,
                    'faculty_name' => $item->faculty_name ?? '',
                    'faculty_type' => $facultyTypeDisplay,
                    'start_date' => $item->START_DATE ? Carbon::parse($item->START_DATE)->format('d-M-Y H:i') : '',
                    'end_date' => $item->END_DATE ? Carbon::parse($item->END_DATE)->format('H:i') : '',
                    'topic_name' => $item->topic_name ?? '',
                ];
            });

            // Group data by program, faculty, and topic for display
            $groupedData = $processedData->groupBy(function ($item) {
                return $item['program_name'] . '|' . $item['faculty_name'] . '|' . $item['topic_name'];
            });
            // print_r($groupedData);

            // Prepare response based on request type
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'groupedData' => $groupedData,
                    'currentPage' => $currentPage,
                    'totalPages' => $totalPages,
                    'totalRecords' => $totalRecords,
                    'programs' => $programs,
                    'facultyTypes' => $facultyTypes,
                    'facultySuggestions' => $facultySuggestions,
                    'currentProgram' => $programId,
                    'currentFaculty' => $facultyName,
                    'fromDate' => $fromDate,
                    'toDate' => $toDate,
                    'courseType' => $courseType,
                    'selectedFacultyTypes' => $facultyType,
                    'refreshTime' => now()->format('d-M-Y H:i'),
                ]);
            }

            return view('admin.feedback.feedback_details', [
                'groupedData' => $groupedData,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalRecords' => $totalRecords,
                'programs' => $programs,
                'facultyTypes' => $facultyTypes,
                'facultySuggestions' => $facultySuggestions,
                'currentProgram' => $programId,
                'currentFaculty' => $facultyName,
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'courseType' => $courseType,
                'selectedFacultyTypes' => $facultyType,
                'refreshTime' => now()->format('d-M-Y H:i'),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in feedbackDetails: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error loading data: ' . $e->getMessage()
                ], 500);
            }

            return view('admin.feedback.feedback_details', [
                'groupedData' => collect(),
                'currentPage' => 1,
                'totalPages' => 0,
                'totalRecords' => 0,
                'programs' => collect(),
                'facultyTypes' => [],
                'facultySuggestions' => collect(),
                'currentProgram' => '',
                'currentFaculty' => '',
                'fromDate' => '',
                'toDate' => '',
                'courseType' => 'current',
                'selectedFacultyTypes' => [],
                'refreshTime' => now()->format('d-M-Y H:i'),
            ]);
        }
    }

    public function exportFeedbackDetails(Request $request)
    {
        try {
            // Get filter parameters
            $programId = $request->input('program_id');
            $facultyName = $request->input('faculty_name');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $courseType = $request->input('course_type', 'current');
            $facultyType = $request->input('faculty_type', []);
            $exportType = $request->input('export_type', 'excel');

            // Ensure faculty_type is always an array
            if (is_string($facultyType)) {
                $facultyType = [$facultyType];
            }

            // Build query for export (same as feedbackDetails but without pagination)
            $query = DB::table('topic_feedback as tf')
                ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
                ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
                ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
                ->join('student_master as sm', 'tf.student_master_pk', '=', 'sm.pk')
                ->select(
                    'tf.pk as feedback_id',
                    'tf.content',
                    'tf.presentation',
                    'tf.remark',
                    'tf.created_date as feedback_date',
                    DB::raw("COALESCE(sm.display_name, 
                    CONCAT(
                        COALESCE(sm.first_name, ''),
                        ' ',
                        COALESCE(sm.last_name, '')
                    )
                ) as ot_name"),
                    DB::raw("COALESCE(sm.generated_OT_code) as ot_code"),
                    'cm.course_name as program_name',
                    'cm.active_inactive as program_status',
                    'cm.end_date as program_end_date',
                    'fm.full_name as faculty_name',
                    'fm.faculty_type',
                    'tt.START_DATE',
                    'tt.END_DATE',
                    'tt.subject_topic as topic_name'
                )
                ->where('tf.is_submitted', 1)
                ->whereNotNull('tf.presentation')
                ->whereNotNull('tf.content')
                ->where('tf.presentation', '!=', '')
                ->where('tf.content', '!=', '');

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

            // Order by
            $query->orderBy('tt.START_DATE', 'DESC')
                ->orderBy('fm.full_name')
                ->orderByRaw("COALESCE(sm.display_name, CONCAT(COALESCE(sm.first_name, ''), ' ', COALESCE(sm.last_name, '')))");

            // Get all data for export (no pagination)
            $feedbackData = $query->get();

            // Process data for export
            $processedData = $feedbackData->map(function ($item) {
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

                // Format dates
                $sessionDate = '';
                $sessionTime = '';
                if ($item->START_DATE) {
                    $sessionDate = Carbon::parse($item->START_DATE)->format('d-M-Y');
                    $sessionTime = Carbon::parse($item->START_DATE)->format('H:i');
                    if ($item->END_DATE) {
                        $sessionTime .= ' - ' . Carbon::parse($item->END_DATE)->format('H:i');
                    }
                }

                $feedbackDate = $item->feedback_date ? Carbon::parse($item->feedback_date)->format('d-M-Y H:i') : '';

                return [
                    'S.No.' => '', // Will be populated in export
                    'Course Name' => $item->program_name ?? '',
                    'Course Status' => $courseStatus,
                    'Faculty Name' => $item->faculty_name ?? '',
                    'Faculty Type' => $facultyTypeDisplay,
                    'Topic' => $item->topic_name ?? '',
                    'Session Date' => $sessionDate,
                    'Session Time' => $sessionTime,
                    'OT Name' => $item->ot_name ?? '',
                    'OT Code' => $item->ot_code ?? '',
                    'Content Rating' => $item->content ?? '',
                    'Presentation Rating' => $item->presentation ?? '',
                    'Remarks' => $item->remark ?? '',
                    'Feedback Date' => $feedbackDate,
                ];
            });

            // Export based on type
            if ($exportType === 'excel') {
                return $this->exportExcelFeedbackDetails($processedData, $request);
            } else {
                return $this->exportPdfFeedbackDetails($processedData, $request);
            }
        } catch (\Exception $e) {
            \Log::error('Error in exportFeedbackDetails: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Export failed: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    private function exportExcelFeedbackDetails($data, $request)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("Sargam LMS")
            ->setTitle("Faculty Feedback Detailed Report")
            ->setSubject("Detailed feedback report with all individual responses")
            ->setDescription("Export of all individual faculty feedback responses")
            ->setKeywords("feedback faculty students detailed report")
            ->setCategory("Report");

        // Set default font
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $row = 1;

        // HEADER
        $sheet->setCellValue('A' . $row, 'Faculty Feedback Detailed Report - All Individual Responses');
        $sheet->mergeCells('A' . $row . ':N' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'AF2910']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        $sheet->setCellValue('A' . $row, 'Sargam | Lal Bahadur Shastri Institute of Management');
        $sheet->mergeCells('A' . $row . ':N' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        $sheet->setCellValue('A' . $row, 'Report Generated: ' . now()->format('d-M-Y H:i'));
        $sheet->mergeCells('A' . $row . ':N' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['italic' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;

        // FILTERS SECTION
        $sheet->setCellValue('A' . $row, 'Applied Filters');
        $sheet->mergeCells('A' . $row . ':N' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'AF2910']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
        ]);
        $row++;

        // Get filter values
        $programId = $request->input('program_id');
        $facultyName = $request->input('faculty_name');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $courseType = $request->input('course_type', 'current');
        $facultyType = $request->input('faculty_type', []);

        $sheet->setCellValue('A' . $row, 'Course Status:');
        $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
        $sheet->setCellValue('B' . $row, $courseType === 'current' ? 'Current Courses' : 'Archived Courses');

        $sheet->setCellValue('E' . $row, 'Program:');
        $sheet->getStyle('E' . $row)->applyFromArray(['font' => ['bold' => true]]);
        $sheet->setCellValue('F' . $row, $programId ? $this->getProgramName($programId) : 'All Programs');
        $row++;

        $sheet->setCellValue('A' . $row, 'Faculty Name:');
        $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
        $sheet->setCellValue('B' . $row, $facultyName ?: 'All Faculty');

        $sheet->setCellValue('E' . $row, 'Faculty Type:');
        $sheet->getStyle('E' . $row)->applyFromArray(['font' => ['bold' => true]]);
        $facultyTypeText = !empty($facultyType) ?
            (count($facultyType) === 2 ? 'All Types' : (in_array('1', $facultyType) ? 'Internal' : 'Guest')) :
            'All Types';
        $sheet->setCellValue('F' . $row, $facultyTypeText);
        $row++;

        $sheet->setCellValue('A' . $row, 'Date Range:');
        $sheet->getStyle('A' . $row)->applyFromArray(['font' => ['bold' => true]]);
        $dateRange = ($fromDate && $toDate) ?
            Carbon::parse($fromDate)->format('d-M-Y') . ' to ' . Carbon::parse($toDate)->format('d-M-Y') :
            'All Dates';
        $sheet->setCellValue('B' . $row, $dateRange);

        $sheet->setCellValue('E' . $row, 'Total Records:');
        $sheet->getStyle('E' . $row)->applyFromArray(['font' => ['bold' => true]]);
        $sheet->setCellValue('F' . $row, count($data));
        $row += 2;

        // COLUMN HEADERS
        $headers = [
            'S.No.',
            'Course Name',
            'Course Status',
            'Faculty Name',
            'Faculty Type',
            'Topic',
            'Session Date',
            'Session Time',
            'OT Name',
            'OT Code',
            'Content Rating',
            'Presentation Rating',
            'Remarks',
            'Feedback Date'
        ];

        $headerRow = $row;
        foreach ($headers as $index => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) . $headerRow;
            $sheet->setCellValue($cell, $header);
        }

        // Style headers
        $sheet->getStyle('A' . $headerRow . ':N' . $headerRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'AF2910']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        // DATA ROWS
        $ratingColors = [
            '5' => '198754',
            '4' => '20c997',
            '3' => 'ffc107',
            '2' => 'fd7e14',
            '1' => 'dc3545',
        ];

        foreach ($data as $index => $item) {
            $item['S.No.'] = $index + 1;

            foreach ($headers as $colIndex => $header) {
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . $row;
                $value = $item[$header] ?? '';
                $sheet->setCellValue($cell, $value);

                // Apply special formatting for rating columns
                if ($header === 'Content Rating' || $header === 'Presentation Rating') {
                    $rating = strval($value);
                    if (isset($ratingColors[$rating])) {
                        $sheet->getStyle($cell)->applyFromArray([
                            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $ratingColors[$rating]]],
                            'font' => ['bold' => true, 'color' => ['rgb' => in_array($rating, ['3']) ? '000000' : 'FFFFFF']],
                            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                        ]);
                    }
                }

                // Wrap text for remarks
                if ($header === 'Remarks') {
                    $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
                }
            }

            // Add borders to the row
            $sheet->getStyle('A' . $row . ':N' . $row)->applyFromArray([
                'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
            ]);

            $row++;

            // Add a blank row after every 10 records for readability
            if (($index + 1) % 10 === 0) {
                $row++;
            }
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);  // S.No.
        $sheet->getColumnDimension('B')->setWidth(25); // Course Name
        $sheet->getColumnDimension('C')->setWidth(12); // Course Status
        $sheet->getColumnDimension('D')->setWidth(25); // Faculty Name
        $sheet->getColumnDimension('E')->setWidth(12); // Faculty Type
        $sheet->getColumnDimension('F')->setWidth(30); // Topic
        $sheet->getColumnDimension('G')->setWidth(12); // Session Date
        $sheet->getColumnDimension('H')->setWidth(15); // Session Time
        $sheet->getColumnDimension('I')->setWidth(25); // OT Name
        $sheet->getColumnDimension('J')->setWidth(15); // OT Code
        $sheet->getColumnDimension('K')->setWidth(15); // Content Rating
        $sheet->getColumnDimension('L')->setWidth(18); // Presentation Rating
        $sheet->getColumnDimension('M')->setWidth(40); // Remarks
        $sheet->getColumnDimension('N')->setWidth(18); // Feedback Date

        // Auto-size remarks column
        $sheet->getColumnDimension('M')->setAutoSize(true);

        // Freeze header row
        $sheet->freezePane('A' . ($headerRow + 1));

        // Add footer
        $row += 2;
        $sheet->setCellValue('A' . $row, 'Confidential - For Internal Use Only');
        $sheet->mergeCells('A' . $row . ':N' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '666666']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        // Create writer and save
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'feedback_details_' . date('Y_m_d_H_i') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'feedback_details_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    private function exportPdfFeedbackDetails($data, $request)
    {
        // Add serial numbers to data
        $data = $data->map(function ($item, $index) {
            $item['S.No.'] = $index + 1;
            return $item;
        });

        // Get filter values for PDF
        $programId = $request->input('program_id');
        $facultyName = $request->input('faculty_name');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $courseType = $request->input('course_type', 'current');
        $facultyType = $request->input('faculty_type', []);

        $pdfData = [
            'data' => $data,
            'filters' => [
                'course_status' => $courseType === 'current' ? 'Current Courses' : 'Archived Courses',
                'program' => $programId ? $this->getProgramName($programId) : 'All Programs',
                'faculty_name' => $facultyName ?: 'All Faculty',
                'faculty_type' => !empty($facultyType) ?
                    (count($facultyType) === 2 ? 'All Types' : (in_array('1', $facultyType) ? 'Internal' : 'Guest')) :
                    'All Types',
                'date_range' => ($fromDate && $toDate) ?
                    Carbon::parse($fromDate)->format('d-M-Y') . ' to ' . Carbon::parse($toDate)->format('d-M-Y') :
                    'All Dates',
                'total_records' => count($data),
            ],
            'export_date' => now()->format('d-M-Y H:i'),
            'rating_colors' => [
                '5' => '#198754', // Green
                '4' => '#20c997', // Light green
                '3' => '#ffc107', // Yellow
                '2' => '#fd7e14', // Orange
                '1' => '#dc3545', // Red
            ],
        ];

        // Load PDF view
        $pdf = PDF::loadView('admin.feedback.feedback_details_pdf', $pdfData)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'dpi' => 96,
                'margin_top' => 20,
                'margin_right' => 10,
                'margin_bottom' => 15,
                'margin_left' => 10,
            ]);

        return $pdf->download('feedback_details_' . date('Y_m_d_H_i') . '.pdf');
    }





    /**
     * Show pending feedback students for admin
     */
   public function pendingStudents(Request $request)
{
    $courses = Cache::remember('active_courses', 3600, function () {
        return DB::table('course_master')
            ->where('active_inactive', 1)
            ->orderBy('course_name')
            ->get();
    });

    $query = $this->buildPendingQuery();

    if ($request->filled('course_pk')) {
        $query->where('t.course_master_pk', $request->course_pk);
    }

    $pendingStudents = $query
        ->orderBy('from_date', 'asc')
        ->orderBy('session_end_time', 'asc')
        ->paginate(20)
        ->appends($request->query());

    return view('admin.feedback.pending_students', compact('pendingStudents', 'courses'));
}


    /**
     * Export pending feedback as PDF
     */
 public function exportPendingStudentsPDF(Request $request)
{
    set_time_limit(300);
    ini_set('memory_limit', '1024M');

    $query = $this->buildPendingQuery();

    if ($request->filled('course_pk')) {
        $query->where('t.course_master_pk', $request->course_pk);
    }

    $students = collect();

    // 🔁 Chunking prevents memory crash
    $query->orderBy('t.START_DATE')->chunk(200, function ($rows) use (&$students) {
        $students = $students->merge($rows);
    });

    if ($students->isEmpty()) {
        return back()->with('error', 'No pending feedback records found.');
    }

    $pdf = PDF::loadView('admin.feedback.pending_students_pdf', [
        'students' => $students,
        'course_name' => $this->getCourseName($request->course_pk),
        'export_date' => now()->format('d-m-Y H:i:s'),
    ])
    ->setPaper('A4', 'landscape');

    return $pdf->download('pending_feedback_' . now()->format('Ymd_His') . '.pdf');
}




    /**
     * Export pending feedback as Excel
     */
  public function exportPendingStudentsExcel(Request $request)
{
    $request->validate([
        'course_pk' => 'nullable|integer|exists:course_master,pk'
    ]);

    $query = $this->buildPendingQuery();

    if ($request->filled('course_pk')) {
        $query->where('t.course_master_pk', $request->course_pk);
    }

    return Excel::download(
        new PendingFeedbackExport(clone $query),
        'pending_feedback_' . now()->format('Y-m-d_H-i') . '.xlsx'
    );
}


    /**
     * Build optimized pending feedback query
     */
   private function buildPendingQuery()
{
    return DB::table('timetable as t')
        ->select([
            't.pk as timetable_pk',
            't.subject_topic',
            'c.course_name',
            'v.venue_name',
            'f.full_name as faculty_name',
            DB::raw("TRIM(CONCAT(
                sm.first_name,' ',
                IFNULL(sm.middle_name,''),' ',
                IFNULL(sm.last_name,'')
            )) as student_name"),
            'sm.email',
            'sm.contact_no',
            'sm.generated_OT_code',
            't.START_DATE as from_date',
            DB::raw("DATE_FORMAT(t.START_DATE, '%d-%m-%Y') as formatted_date"),
            't.class_session',
            DB::raw("
                STR_TO_DATE(
                    TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                    '%h:%i %p'
                ) as session_end_time
            ")
        ])
        ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
        ->join('venue_master as v', 't.venue_id', '=', 'v.venue_id')
        ->leftJoin('faculty_master as f', function ($join) {
            $join->whereRaw("
                f.pk = (
                    CASE 
                        WHEN JSON_VALID(t.faculty_master) 
                        THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(t.faculty_master, '$[0]')) AS UNSIGNED)
                        ELSE CAST(t.faculty_master AS UNSIGNED)
                    END
                )
            ");
        })
        ->join('student_master_course__map as smcm', function ($join) {
            $join->on('smcm.course_master_pk', '=', 't.course_master_pk')
                 ->where('smcm.active_inactive', 1);
        })
        ->join('student_master as sm', 'sm.pk', '=', 'smcm.student_master_pk')
        ->join('course_student_attendance as csa', function ($join) {
            $join->on('csa.timetable_pk', '=', 't.pk')
                 ->on('csa.Student_master_pk', '=', 'sm.pk')
                 ->where('csa.status', '1');
        })
        ->leftJoin('topic_feedback as tf', function ($join) {
            $join->on('tf.timetable_pk', '=', 't.pk')
                 ->on('tf.student_master_pk', '=', 'sm.pk')
                 ->where('tf.is_submitted', 1);
        })
        ->where('t.feedback_checkbox', 1)
        ->whereNull('tf.pk')
        ->whereRaw("
            TIMESTAMP(
                t.END_DATE,
                STR_TO_DATE(
                    TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                    '%h:%i %p'
                )
            ) <= NOW()
        ")
        ->distinct();
}


    /**
     * Get pending students optimized for export
     */
    private function getPendingStudentsOptimized($course_pk = null)
    {
        // Step 1: Get filtered timetable IDs
        $timetableQuery = DB::table('timetable as t')
            ->select('t.pk')
            ->where('t.feedback_checkbox', 1)
            ->whereRaw("
                TIMESTAMP(
                    t.END_DATE,
                    STR_TO_DATE(
                        TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                        '%h:%i %p'
                    )
                ) <= CONVERT_TZ(NOW(), '+00:00', '+05:30')
            ");

        if ($course_pk) {
            $timetableQuery->where('t.course_master_pk', $course_pk);
        }

        $timetableIds = $timetableQuery->pluck('pk')->toArray();

        if (empty($timetableIds)) {
            return collect();
        }

        // Step 2: Process in chunks for memory efficiency
        $chunkSize = 1000;
        $results = collect();
        
        foreach (array_chunk($timetableIds, $chunkSize) as $chunk) {
            $query = DB::table('timetable as t')
                ->select([
                    't.pk as timetable_pk',
                    't.subject_topic',
                    'c.course_name',
                    'v.venue_name',
                    'f.full_name as faculty_name',
                    DB::raw("TRIM(CONCAT(sm.first_name,' ',IFNULL(sm.middle_name,''),' ',IFNULL(sm.last_name,''))) as student_name"),
                    'sm.email',
                    'sm.contact_no',
                    'sm.generated_OT_code',
                    't.START_DATE as from_date',
                    DB::raw("DATE_FORMAT(t.START_DATE, '%d-%m-%Y') as formatted_date"),
                    't.class_session'
                ])
                ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
                ->join('venue_master as v', 't.venue_id', '=', 'v.venue_id')
                ->leftJoin('faculty_master as f', function ($join) {
                    $join->whereRaw("
                        f.pk = (
                            CASE 
                                WHEN JSON_VALID(t.faculty_master) 
                                THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(t.faculty_master, '$[0]')) AS UNSIGNED)
                                ELSE CAST(t.faculty_master AS UNSIGNED)
                            END
                        )
                    ");
                })
                ->join('student_master_course__map as smcm', function($join) {
                    $join->on('smcm.course_master_pk', '=', 't.course_master_pk')
                         ->where('smcm.active_inactive', 1);
                })
                ->join('student_master as sm', 'sm.pk', '=', 'smcm.student_master_pk')
                ->join('course_student_attendance as csa', function($join) {
                    $join->on('csa.timetable_pk', '=', 't.pk')
                         ->on('csa.Student_master_pk', '=', 'sm.pk')
                         ->where('csa.status', '1');
                })
                ->leftJoin('topic_feedback as tf', function($join) {
                    $join->on('tf.timetable_pk', '=', 't.pk')
                         ->on('tf.student_master_pk', '=', 'sm.pk')
                         ->where('tf.is_submitted', 1);
                })
                ->whereIn('t.pk', $chunk)
                ->whereNull('tf.pk')
                ->orderBy('t.START_DATE', 'asc');

            $chunkResults = $query->get();
            
            // FIXED: Ensure we merge properly
            if ($chunkResults && count($chunkResults) > 0) {
                $results = $results->merge($chunkResults);
            }
            
            // Clear memory
            unset($chunkResults);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        return $results;
    }

    /**
     * Generate PDF
     */
    private function generatePDF($students, $course_name)
    {
        // FIXED: Ensure $students is a collection, not a string
        if (!($students instanceof Collection)) {
            if (is_array($students)) {
                $students = collect($students);
            } elseif (is_string($students)) {
                $students = collect();
            } else {
                $students = collect();
            }
        }

        $data = [
            'students' => $students,
            'course_name' => $course_name,
            'export_date' => now()->format('d-m-Y H:i:s'),
            'total_count' => $students->count()
        ];

        return PDF::loadView('admin.feedback.pending_students_pdf', $data)
            ->setPaper('A4', 'landscape')
            ->setOption('defaultFont', 'sans-serif')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);
    }

    /**
     * Get course name helper
     */
   /**
 * Get course name helper - SIMPLIFIED VERSION
 */
private function getCourseName($course_pk)
{
    if (!$course_pk) {
        return 'All Courses';
    }

    try {
        // Direct query without caching to avoid issues
        $result = DB::table('course_master')
            ->where('pk', $course_pk)
            ->value('course_name'); // Use value() to get just the course_name
        
        return $result ?: 'All Courses';
    } catch (\Exception $e) {
        \Log::error('Error getting course name for pk ' . $course_pk . ': ' . $e->getMessage());
        return 'All Courses';
    }
}

    /**
     * Simple slug creation function (alternative to Str::slug)
     */
    private function createSlug($text)
    {
        // Replace spaces and special characters with underscores
        $text = preg_replace('/[^a-zA-Z0-9]+/', '_', $text);
        
        // Convert to lowercase
        $text = strtolower($text);
        
        // Trim underscores from start and end
        $text = trim($text, '_');
        
        // Replace multiple underscores with single
        $text = preg_replace('/_+/', '_', $text);
        
        return $text;
    }

    /**
     * Get statistics for dashboard
     */
    public function getPendingStats(Request $request)
    {
        $cacheKey = 'pending_feedback_stats_' . ($request->course_pk ?? 'all');
        
        $stats = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = DB::table('timetable as t')
                ->select([
                    DB::raw('COUNT(DISTINCT t.pk) as total_sessions'),
                    DB::raw('COUNT(DISTINCT sm.pk) as total_students'),
                    DB::raw('COUNT(DISTINCT c.pk) as total_courses')
                ])
                ->join('course_master as c', 't.course_master_pk', '=', 'c.pk')
                ->join('student_master_course__map as smcm', 'smcm.course_master_pk', '=', 'c.pk')
                ->join('student_master as sm', 'sm.pk', '=', 'smcm.student_master_pk')
                ->join('course_student_attendance as csa', function($join) {
                    $join->on('csa.timetable_pk', '=', 't.pk')
                         ->on('csa.Student_master_pk', '=', 'sm.pk')
                         ->where('csa.status', '1');
                })
                ->leftJoin('topic_feedback as tf', function($join) {
                    $join->on('tf.timetable_pk', '=', 't.pk')
                         ->on('tf.student_master_pk', '=', 'sm.pk')
                         ->where('tf.is_submitted', 1);
                })
                ->where('t.feedback_checkbox', 1)
                ->whereRaw("
                    TIMESTAMP(
                        t.END_DATE,
                        STR_TO_DATE(
                            TRIM(SUBSTRING_INDEX(t.class_session, '-', -1)),
                            '%h:%i %p'
                        )
                    ) <= CONVERT_TZ(NOW(), '+00:00', '+05:30')
                ")
                ->whereNull('tf.pk');

            if ($request->filled('course_pk')) {
                $query->where('t.course_master_pk', $request->course_pk);
            }

            $result = $query->first();
            
            // FIXED: Ensure we return an object, not a string
            return $result ?: (object) [
                'total_sessions' => 0,
                'total_students' => 0,
                'total_courses' => 0
            ];
        });

        return response()->json($stats);
    }
}
    
