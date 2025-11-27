<?php

namespace App\Http\Controllers\Admin\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseMaster;
use App\Models\StudentMasterCourseMap;
use App\Models\ServiceMaster;
use App\Models\StudentMaster;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentEnrollmentExport as StudentsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\StudentEnrollmentExport;
use Illuminate\Validation\Rule;
use Mpdf\Mpdf;


class EnrollementController extends Controller
{
    public function create()
    {
        // Get all active courses
        $courses = CourseMaster::where('active_inactive', 1)->get();

        // Get previous courses from student course map with course details
        $previousCourses = StudentMasterCourseMap::with('course')
            ->get()
            ->unique('course_master_pk'); // Get unique courses
        // Get all active services
        $services = ServiceMaster::all();

        return view('admin.registration.enrollement', compact('courses', 'previousCourses', 'services'));
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'course_master_pk' => 'required|integer',
    //         'selected_students' => 'required|string', // will be comma separated IDs
    //     ]);
    //     $newCoursePk = $request->input('course_master_pk');
    //     $studentIds = explode(',', $request->input('selected_students'));
    //     if (empty($studentIds)) {
    //         return back()->withErrors(['selected_students' => 'No students selected for enrollment.']);
    //     }

    //     DB::beginTransaction();
    //     try {
    //         foreach ($studentIds as $studentId) {
    //             //  Deactivate all old course enrollments for this student
    //             DB::table('student_master_course__map')
    //                 ->where('student_master_pk', $studentId)
    //                 ->update(['active_inactive' => 0]);

    //             //  Insert new enrollment record for selected course
    //             DB::table('student_master_course__map')->insert([
    //                 'student_master_pk' => $studentId,
    //                 'course_master_pk'  => $newCoursePk,
    //                 'active_inactive'   => 1,
    //                 'created_date'      => now(),
    //                 'modified_date'     => now(),
    //             ]);
    //         }

    //         DB::commit();
    //         return redirect()->back()
    //             ->with('success', 'Students enrolled successfully.')
    //             ->with('selected_course', $request->course_master_pk);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->withErrors(['error' => 'Enrollment failed: ' . $e->getMessage()]);
    //     }
    // }

    public function store(Request $request)
{
    $validated = $request->validate([
        'course_master_pk' => [
            'required', 
            'integer',
            Rule::exists('course_master', 'pk')->where('active_inactive', 1)
        ],
        'selected_students' => 'required|string',
    ]);

    $newCoursePk = $validated['course_master_pk'];
    $studentIds = array_filter(explode(',', $validated['selected_students']));
    
    if (empty($studentIds)) {
        return back()->withErrors(['selected_students' => 'No valid students selected for enrollment.']);
    }

    DB::beginTransaction();
    try {
        // Get existing enrollments for these students in this course
        $existingEnrollments = StudentMasterCourseMap::whereIn('student_master_pk', $studentIds)
            ->where('course_master_pk', $newCoursePk)
            ->get()
            ->keyBy('student_master_pk');

        $studentsToInsert = [];
        $studentsToUpdate = [];
        $now = now();

        foreach ($studentIds as $studentId) {
            if (isset($existingEnrollments[$studentId])) {
                // Student already enrolled - mark for update if inactive
                if ($existingEnrollments[$studentId]->active_inactive == 0) {
                    $studentsToUpdate[] = $studentId;
                }
            } else {
                // New enrollment - mark for insertion
                $studentsToInsert[] = $studentId;
            }
        }

        // Step 1: Deactivate ALL courses for all selected students
        StudentMasterCourseMap::whereIn('student_master_pk', $studentIds)
            ->update(['active_inactive' => 0]);

        // Step 2: Activate/insert the new course enrollments
        if (!empty($studentsToUpdate)) {
            // Reactivate existing enrollments
            StudentMasterCourseMap::whereIn('student_master_pk', $studentsToUpdate)
                ->where('course_master_pk', $newCoursePk)
                ->update([
                    'active_inactive' => 1,
                    'modified_date' => $now
                ]);
        }

        if (!empty($studentsToInsert)) {
            // Insert new enrollments
            $insertData = [];
            foreach ($studentsToInsert as $studentId) {
                $insertData[] = [
                    'student_master_pk' => $studentId,
                    'course_master_pk' => $newCoursePk,
                    'active_inactive' => 1,
                    'created_date' => $now,
                    'modified_date' => $now,
                ];
            }
            StudentMasterCourseMap::insert($insertData);
        }

        DB::commit();
        
        $totalProcessed = count($studentsToInsert) + count($studentsToUpdate);
        return redirect()->back()
            ->with('success', "Enrollment completed! {$totalProcessed} students processed.")
            ->with('selected_course', $newCoursePk);
            
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'Enrollment failed: ' . $e->getMessage()]);
    }
}

    public function filterStudents(Request $request)
    {
        try {

            $previousCourses = $request->input('previous_courses', []);
            $services = $request->input('services', []);
            if (empty($previousCourses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one previous course'
                ]);
            }

            // Corrected query with proper table relationships
            $query = DB::table('student_master_course__map as smcm')
                ->join('student_master as sm', 'smcm.student_master_pk', '=', 'sm.pk')
                ->join('course_master as cm', 'smcm.course_master_pk', '=', 'cm.pk')
                ->leftJoin('service_master as svm', 'sm.service_master_pk', '=', 'svm.pk')
                ->whereIn('smcm.course_master_pk', $previousCourses)
                ->select(
                    'sm.pk as student_pk',
                    'cm.pk as course_pk',
                    DB::raw("CONCAT(sm.first_name, ' ', COALESCE(sm.middle_name, ''), ' ', COALESCE(sm.last_name, '')) as student_name"),
                    'sm.generated_OT_code as ot_code',
                    'cm.course_name',
                    'svm.service_name'
                );

            // Filter by services if provided
            if (!empty($services)) {
                $query->whereIn('sm.service_master_pk', $services);
            }

            $students = $query->get();

            return response()->json([
                'success' => true,
                'students' => $students
            ]);
        } catch (\Exception $e) {
            \Log::error('Error filtering students: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error filtering students: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Student–Course list (simple listing with pk values)
     */

    // public function studentCourses(Request $request)
    // {
    //     $courseId = $request->input('course_id');
    //     $status = $request->input('status');

    //     // Course dropdown (always needed)
    //     $courses = CourseMaster::where('active_inactive', 1)
    //         ->orderBy('course_name')
    //         ->pluck('course_name', 'pk');

    //     // For AJAX requests, return only the table partial
    //     if ($request->ajax()) {
    //         // Base enrollments query
    //         $enrollments = StudentMasterCourseMap::with([
    //             'studentMaster.service',
    //             'course'
    //         ])
    //             ->when($courseId, fn($q) => $q->where('course_master_pk', $courseId))
    //             ->when($status !== null && $status !== '', fn($q) => $q->where('active_inactive', $status))
    //             ->orderByDesc('created_date')
    //             ->get();

    //         // Count query
    //         $baseQuery = StudentMasterCourseMap::query();
    //         if ($courseId) $baseQuery->where('course_master_pk', $courseId);
    //         if ($status !== null && $status !== '') $baseQuery->where('active_inactive', $status);

    //         $filteredCount = $baseQuery->count();

    //         return response()->json([
    //             'success' => true,
    //             'html' => view('admin.registration.student_courses_table', compact('enrollments'))->render(),
    //             'filteredCount' => $filteredCount
    //         ]);
    //     }

    //     // For initial page load - empty data
    //     $enrollments = collect();
    //     $filteredCount = 0;
    //     $totalCount = StudentMasterCourseMap::count();

    //     return view('admin.registration.student_courselist', compact(
    //         'enrollments',
    //         'courses',
    //         'courseId',
    //         'totalCount',
    //         'filteredCount',
    //         'status'
    //     ));
    // }

   public function studentCourses(Request $request)
{
    $courseId = $request->input('course_id');
    $status = $request->input('status');
    $courseStatus = $request->input('course_status', 'active'); // Default to 'active'

    // Course dropdown with both active and inactive courses
    $courses = CourseMaster::query()
        ->when($courseStatus === 'active', fn($q) => $q->where('active_inactive', 1))
        ->when($courseStatus === 'inactive', fn($q) => $q->where('active_inactive', 0))
        // 'all' will return all courses without status filter
        ->orderBy('course_name')
        ->pluck('course_name', 'pk');

    // Handle AJAX request for course dropdown updates only
    if ($request->ajax() && $request->has('ajax_courses')) {
        return response()->json([
            'success' => true,
            'courses' => $courses
        ]);
    }

    // For AJAX requests, return only the table partial
    if ($request->ajax()) {
        // Base enrollments query
        $enrollments = StudentMasterCourseMap::with([
            'studentMaster.service',
            'course'
        ])
            ->when($courseId, fn($q) => $q->where('course_master_pk', $courseId))
            ->when($status !== null && $status !== '', fn($q) => $q->where('active_inactive', $status))
            ->orderByDesc('created_date')
            ->get();

        // Count query
        $baseQuery = StudentMasterCourseMap::query();
        if ($courseId) $baseQuery->where('course_master_pk', $courseId);
        if ($status !== null && $status !== '') $baseQuery->where('active_inactive', $status);

        $filteredCount = $baseQuery->count();

        return response()->json([
            'success' => true,
            'html' => view('admin.registration.student_courses_table', compact('enrollments'))->render(),
            'filteredCount' => $filteredCount
        ]);
    }

    // For initial page load - empty data
    $enrollments = collect();
    $filteredCount = 0;
    $totalCount = StudentMasterCourseMap::count();

    return view('admin.registration.student_courselist', compact(
        'enrollments',
        'courses',
        'courseId',
        'totalCount',
        'filteredCount',
        'status',
        'courseStatus' // Pass course status to view
    ));
}




    public function StudenEnroll_export(Request $request)
    {
        $courseId = $request->input('course');
        $status = $request->input('status');
        $format = $request->input('format');

        // Use the SAME query as your main page for consistency
        $enrollmentsQuery = StudentMasterCourseMap::with([
            'studentMaster.service',
            'course'
        ])
            ->when($courseId, fn($q) => $q->where('course_master_pk', $courseId))
            ->when($status !== null && $status !== '', fn($q) => $q->where('active_inactive', $status))
            ->orderByDesc('created_date');

        $enrollments = $enrollmentsQuery->get();
        $totalCount = $enrollments->count();

        // Get course name if course filter is applied
        $courseName = null;
        if ($courseId) {
            $course = CourseMaster::find($courseId);
            $courseName = $course ? $course->course_name : null;
        }

        if ($format === 'xlsx') {
            $export = new StudentEnrollmentExport($courseId, $status);
            return Excel::download($export, 'student_enrollments.xlsx');
        }

        if ($format === 'csv') {
            $export = new StudentEnrollmentExport($courseId, $status);
            return Excel::download($export, 'student_enrollments.csv');
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.report.studentsenroll_pdf', compact(
                'enrollments',
                'courseName',
                'totalCount',
                'status'
            ));

            return $pdf->setPaper('a4', 'landscape')
                ->setOption('enable-smart-shrinking', true)
                ->setOption('viewport-size', '1280x1024')
                ->download('student_enrollments.pdf');
        }

        return back()->with('error', 'Invalid export format selected.');
    }

    // EnrollmentController.php
    public function getEnrolledStudents(Request $request)
    {
        try {
            $query = StudentMaster::with(['courses'])
                ->whereHas('courses');

            // Filter by course
            if ($request->has('course') && !empty($request->course)) {
                $query->whereHas('courses', function ($q) use ($request) {
                    $q->where('course_master_pk', $request->course);
                });
            }

            // Search by name or email
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('first_name', 'like', "%{$searchTerm}%")
                        ->orWhere('last_name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                });
            }

            $perPage = $request->per_page ?? 10;
            $students = $query->paginate($perPage);

            // Format the data
            $formattedStudents = $students->map(function ($student) {
                $latestCourse = $student->courses->sortByDesc('pivot.created_date')->first();

                return [
                    'id' => $student->pk,
                    'name' => trim($student->first_name . ' ' . $student->last_name),
                    'email' => $student->email,
                    'phone' => $student->contact_no,
                    'course_name' => $latestCourse->course_name ?? null,
                    'enrollment_date' => $latestCourse->pivot->created_date
                        ? date('M d, Y', strtotime($latestCourse->pivot->created_date))
                        : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedStudents,
                'total' => $students->total(),
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching enrolled students',
                'error'   => $e->getMessage(), // <-- add this to debug
            ], 500);
        }
    }



    public function exportEnrolledStudents(Request $request)
    {
        try {
            $query = StudentMaster::with(['courses'])
                ->whereHas('courses');

            // Apply filters
            if ($request->has('course') && !empty($request->course)) {
                $query->whereHas('courses', function ($q) use ($request) {
                    $q->where('course_master_pk', $request->course);
                });
            }

            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('first_name', 'like', "%{$searchTerm}%")
                        ->orWhere('last_name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                });
            }

            $students = $query->get();

            // Build HTML for PDF
            $html = '
        <h2 style="text-align:center;">Enrolled Students</h2>
        <table border="1" cellspacing="0" cellpadding="6" width="100%">
            <thead>
                <tr style="background-color:#f2f2f2;">
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Course</th>
                    <th>Enrollment Date</th>
                </tr>
            </thead>
            <tbody>';

            foreach ($students as $student) {
                $latestCourse = $student->courses->sortByDesc('pivot.created_date')->first();

                $html .= '<tr>
                <td>' . $student->pk . '</td>
                <td>' . trim($student->first_name . ' ' . $student->last_name) . '</td>
                <td>' . $student->email . '</td>
                <td>' . $student->contact_no . '</td>
                <td>' . ($latestCourse->course_name ?? 'N/A') . '</td>
                <td>' . ($latestCourse->pivot->created_date
                    ? date('Y-m-d', strtotime($latestCourse->pivot->created_date))
                    : 'N/A') . '</td>
            </tr>';
            }

            $html .= '</tbody></table>';

            // Generate PDF
            $mpdf = new Mpdf();
            $mpdf->WriteHTML($html);

            $filename = 'enrolled_students_' . date('Y-m-d') . '.pdf';
            return response($mpdf->Output($filename, 'S'))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error exporting PDF: ' . $e->getMessage());
        }
    }
}
