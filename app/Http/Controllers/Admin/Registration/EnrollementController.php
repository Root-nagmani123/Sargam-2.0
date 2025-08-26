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

    public function store(Request $request)
    {
        $request->validate([
            'course_master_pk' => 'required|integer',
            'selected_students' => 'required|string', // will be comma separated IDs
        ]);
        $newCoursePk = $request->input('course_master_pk');
        $studentIds = explode(',', $request->input('selected_students'));
        if (empty($studentIds)) {
            return back()->withErrors(['selected_students' => 'No students selected for enrollment.']);
        }

        DB::beginTransaction();
        try {
            foreach ($studentIds as $studentId) {
                //  Deactivate all old course enrollments for this student
                DB::table('student_master_course__map')
                    ->where('student_master_pk', $studentId)
                    ->update(['active_inactive' => 0]);

                //  Insert new enrollment record for selected course
                DB::table('student_master_course__map')->insert([
                    'student_master_pk' => $studentId,
                    'course_master_pk'  => $newCoursePk,
                    'active_inactive'   => 1,
                    'created_date'      => now(),
                    'modified_date'     => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('enrollment.create')
                ->with('success', 'Students successfully enrolled into the new course.');
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
    // public function studentCourses()
    // {
    //     // Get students with courses and service, eager load everything
    //     $students = StudentMaster::with(['courses', 'service'])->get();
    //     $courses = CourseMaster::pluck('name', 'id'); // id => name


    //     return view('admin.registration.student_courselist', compact('students', 'courses'));
    // }

    // public function studentCourses(Request $request)
    // {
    //     // If course filter is applied
    //     $courseId = $request->input('course_id');
    //     $students = StudentMaster::with(['courses', 'service'])
    //         ->when($courseId, function ($query) use ($courseId) {
    //             $query->whereHas('courses', function ($q) use ($courseId) {
    //                 $q->where('course_master.pk', $courseId);
    //             });
    //         })
    //         ->get();
    //         @dump($students);

    //     // Count total students (all records, without filter)
    //     $totalStudents = StudentMaster::count();

    //     // Count filtered students (with filter or all if no filter)
    //     $filteredCount = $students->count();

    //     // Get courses for filter dropdown
    //     $courses = CourseMaster::where('active_inactive', 1)->pluck('course_name', 'pk'); // pk => course_name

    //     return view('admin.registration.student_courselist', compact('students', 'courses', 'courseId', 'totalStudents', 'filteredCount'));
    // }

    //     public function studentCourses(Request $request)
    // {
    //     $courseId = $request->input('course_id');

    //     // Total count of all course mappings (not distinct students)
    //     $totalStudents = \App\Models\StudentMaster::with('courses')->get()->pluck('courses')->flatten()->count();

    //     // Query with filter (if course applied)
    //     $students = StudentMaster::with(['courses', 'service'])
    //         ->when($courseId, function ($query) use ($courseId) {
    //             $query->whereHas('courses', function ($q) use ($courseId) {
    //                 $q->where('course_master.pk', $courseId);
    //             });
    //         })
    //         ->get();

    //     // Count filtered records (including duplicates per course mapping)
    //     $filteredCount = $students->map(function ($student) use ($courseId) {
    //         return $student->courses->when($courseId, function ($courses) use ($courseId) {
    //             return $courses->where('pk', $courseId);
    //         });
    //     })->flatten()->count();

    //     // Courses for filter dropdown
    //     $courses = CourseMaster::where('active_inactive', 1)->pluck('course_name', 'pk');

    //     return view('admin.registration.student_courselist', compact(
    //         'students', 'courses', 'courseId', 'totalStudents', 'filteredCount'
    //     ));
    // }

    public function studentCourses(Request $request)
    {
        $courseId = $request->input('course_id');
        $status = $request->input('status');

        // Dropdown options: only active courses
        $courses = CourseMaster::where('active_inactive', 1)
            ->orderBy('course_name')
            ->pluck('course_name', 'pk');

        // Base enrollments (each row = one student-course mapping)
        $enrollments = StudentMasterCourseMap::with([
            'studentMaster.service', // student + service
            'course'                 // course
        ])
            ->when($courseId, fn($q) => $q->where('course_master_pk', $courseId))
            ->when($status !== null && $status !== '', fn($q) => $q->where('active_inactive', $status))
            ->orderByDesc('created_date')
            ->get(); // use get() because DataTables will handle paging

        // Counts (rows, not distinct students)
        $totalCount    = StudentMasterCourseMap::count();
        $filteredCount = $courseId
            ? StudentMasterCourseMap::where('course_master_pk', $courseId)->count()
            : $totalCount;

        return view('admin.registration.student_courselist', compact(
            'enrollments',
            'courses',
            'courseId',
            'totalCount',
            'filteredCount',
            'status'
        ));
    }

    // Enrollment Export functionality finally working
    //    public function StudenEnroll_export(Request $request)
    // {
    //     $format = $request->input('format');
    //     $course = $request->input('course');
    //     $status = $request->input('status');

    //     // Excel / CSV
    //     if (in_array($format, ['xlsx', 'csv'])) {
    //         return Excel::download(new StudentEnrollmentExport($course, $status), "students.$format");
    //     }

    //     // PDF
    //     if ($format === 'pdf') {
    //         $students = (new StudentEnrollmentExport($course, $status))->collection();
    //         $pdf = Pdf::loadView('admin.report.studentsenroll_pdf', compact('students'));
    //         return $pdf->download('students.pdf');
    //     }

    //     return back()->with('error', 'Invalid export format selected.');
    // }

    public function StudenEnroll_export(Request $request)
    {
        $courseId = $request->input('course');
        $status = $request->input('status'); // 1=Active, 2=Inactive
        $format = $request->input('format');

        $export = new StudentEnrollmentExport($courseId, $status);

        if ($format === 'xlsx') {
            return Excel::download($export, 'students.xlsx');
        }

        if ($format === 'csv') {
            return Excel::download($export, 'students.csv');
        }

        if ($format === 'pdf') {
            $students = $export->collection();
            $pdf = Pdf::loadView('admin.report.studentsenroll_pdf', compact('students'));
            return $pdf->download('students.pdf');
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
