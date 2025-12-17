<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CourseMaster;
use App\Models\FacultyMaster;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
}
