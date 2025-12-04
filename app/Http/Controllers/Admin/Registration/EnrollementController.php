<?php

namespace App\Http\Controllers\Admin\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseMaster;
use App\Models\StudentMasterCourseMap;
use App\Models\ServiceMaster;
use App\Models\FcRegistrationMaster;
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
    $currentDate = now()->format('Y-m-d');
    
    // Get courses that are active AND currently within their date range
    $courses = CourseMaster::where('active_inactive', 1)
        ->where(function($query) use ($currentDate) {
            $query->whereNull('start_year')
                  ->orWhere('start_year', '<=', $currentDate);
        })
        ->where(function($query) use ($currentDate) {
            $query->whereNull('end_date')
                  ->orWhere('end_date', '>=', $currentDate);
        })
        ->get();

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
    $studentIds = array_unique(array_filter(explode(',', $validated['selected_students'])));

    if (empty($studentIds)) {
        return back()->withErrors(['selected_students' => 'No valid students selected for enrollment.']);
    }

    DB::beginTransaction();
    try {
        $now = now();
        $successCount = 0;

        // First, deactivate all other courses for these students
        StudentMasterCourseMap::whereIn('student_master_pk', $studentIds)
            ->where('course_master_pk', '!=', $newCoursePk)
            ->update(['active_inactive' => 0]);

        // Then, activate/insert the new course enrollment
        foreach ($studentIds as $studentId) {
            $enrollment = StudentMasterCourseMap::updateOrCreate(
                [
                    'student_master_pk' => $studentId,
                    'course_master_pk' => $newCoursePk
                ],
                [
                    'active_inactive' => 1,
                    'modified_date' => $now
                ]
            );

            // If this was newly created, set created_date
            if ($enrollment->wasRecentlyCreated) {
                $enrollment->created_date = $now;
                $enrollment->save();
            }

            $successCount++;
        }

        DB::commit();

        return redirect()->back()
            ->with('success', "Enrollment completed! {$successCount} students processed.")
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

            // Query with proper relationships
            $query = DB::table('student_master_course__map as smcm')
                ->join('student_master as sm', 'smcm.student_master_pk', '=', 'sm.pk')
                ->join('course_master as cm', 'smcm.course_master_pk', '=', 'cm.pk')
                ->leftJoin('service_master as svm', 'sm.service_master_pk', '=', 'svm.pk')
                ->whereIn('smcm.course_master_pk', $previousCourses)
                ->select(
                    'sm.pk as student_pk',
                    'cm.pk as course_pk',
                    'sm.first_name',
                    'sm.middle_name',
                    'sm.last_name',
                    DB::raw("CONCAT(sm.first_name, ' ', COALESCE(sm.middle_name, ''), ' ', sm.last_name) as student_name"),
                    'sm.generated_OT_code as ot_code',
                    'cm.course_name',
                    'svm.service_name'
                )
                ->distinct();

            // Filter by services if provided
            if (!empty($services)) {
                $query->whereIn('sm.service_master_pk', $services);
            }

            $students = $query->get();

            // Transform students to include edit URLs
            $transformedStudents = $students->map(function ($student) {
                return [
                    'student_pk' => $student->student_pk,
                    'student_name' => $student->student_name,
                    'first_name' => $student->first_name,
                    'middle_name' => $student->middle_name,
                    'last_name' => $student->last_name,
                    'ot_code' => $student->ot_code,
                    'course_name' => $student->course_name,
                    'service_name' => $student->service_name,
                    'edit_url' => route('enrollment.edit', $student->student_pk) // This is the key!
                ];
            });

            return response()->json([
                'success' => true,
                'students' => $transformedStudents
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




    // public function StudenEnroll_export(Request $request)
    // {
    //     $courseId = $request->input('course');
    //     $status = $request->input('status');
    //     $format = $request->input('format');

    //     // Use the SAME query as your main page for consistency
    //     $enrollmentsQuery = StudentMasterCourseMap::with([
    //         'studentMaster.service',
    //         'course'
    //     ])
    //         ->when($courseId, fn($q) => $q->where('course_master_pk', $courseId))
    //         ->when($status !== null && $status !== '', fn($q) => $q->where('active_inactive', $status))
    //         ->orderByDesc('created_date');

    //     $enrollments = $enrollmentsQuery->get();
    //     $totalCount = $enrollments->count();

    //     // Get course name if course filter is applied
    //     $courseName = null;
    //     if ($courseId) {
    //         $course = CourseMaster::find($courseId);
    //         $courseName = $course ? $course->course_name : null;
    //     }

    //     if ($format === 'xlsx') {
    //         $export = new StudentEnrollmentExport($courseId, $status);
    //         return Excel::download($export, 'student_enrollments.xlsx');
    //     }

    //     if ($format === 'csv') {
    //         $export = new StudentEnrollmentExport($courseId, $status);
    //         return Excel::download($export, 'student_enrollments.csv');
    //     }

    //     if ($format === 'pdf') {
    //         $pdf = Pdf::loadView('admin.report.studentsenroll_pdf', compact(
    //             'enrollments',
    //             'courseName',
    //             'totalCount',
    //             'status'
    //         ));

    //         return $pdf->setPaper('a4', 'landscape')
    //             ->setOption('enable-smart-shrinking', true)
    //             ->setOption('viewport-size', '1280x1024')
    //             ->download('student_enrollments.pdf');
    //     }

    //     return back()->with('error', 'Invalid export format selected.');
    // }

    public function StudenEnroll_export(Request $request)
    {
        $courseId = $request->input('course');
        $status = $request->input('status');
        $courseStatus = $request->input('course_status', 'active'); // Add course_status
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
            $export = new StudentEnrollmentExport($enrollments); // Pass the filtered data
            return Excel::download($export, 'student_enrollments.xlsx');
        }

        if ($format === 'csv') {
            $export = new StudentEnrollmentExport($enrollments); // Pass the filtered data
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

    // dd($request->all());

    // CLEAN AND FIX TYPE
    $type = strtolower(trim($request->input('type', 'pdf')));
    \Log::info("Detected cleaned export type = $type");
    

    try {

        $query = StudentMasterCourseMap::with([
            'studentMaster.service',
            'course'
        ])->where('active_inactive', 1);

        if ($request->has('course') && !empty($request->course)) {
            $query->where('course_master_pk', $request->course);
        }

        $enrollments = $query->get();

        $courseName = 'All Active Courses';
        if (!empty($request->course)) {
            $course = CourseMaster::find($request->course);
            $courseName = $course ? $course->course_name : 'Selected Course';
        }
    \Log::info('Export params', $request->all());

        if ($type === 'excel') {
            return Excel::download(
                new StudentEnrollmentExport($enrollments, $courseName),
                'enrolled_students_' . str_replace([' ', '/', '\\'], '_', $courseName) . '_' . date('Y-m-d') . '.xlsx'
            );
        }

        if ($type === 'csv') {
            return Excel::download(
                new StudentEnrollmentExport($enrollments, $courseName),
                'enrolled_students_' . str_replace([' ', '/', '\\'], '_', $courseName) . '_' . date('Y-m-d') . '.csv'
            );
        }

        // DEFAULT PDF
        return $this->exportEnrolledStudentsPDF($enrollments, $courseName);

    } catch (\Exception $e) {
        \Log::error('Export error: ' . $e->getMessage());
        return back()->with('error', 'Error exporting: ' . $e->getMessage());
    }
}


// Separate method for PDF export
private function exportEnrolledStudentsPDF($enrollments, $courseName)
{
    $pdf = Pdf::loadView('admin.export.enrolled_students_pdf', [
        'enrollments' => $enrollments,
        'courseName' => $courseName,
        'exportDate' => now()->format('Y-m-d H:i:s'),
        'totalCount' => $enrollments->count()
    ]);

    $filename = 'enrolled_students_' . str_replace([' ', '/', '\\'], '_', $courseName) . '_' . date('Y-m-d') . '.pdf';
    
    return $pdf->setPaper('a4', 'landscape')
        ->setOption('enable-smart-shrinking', true)
        ->download($filename);
}

    /**
     * Show the form for editing student information.
     */
    public function edit($studentId)
    {
        try {
            // Fetch student details
            $student = StudentMaster::findOrFail($studentId);

            // Get all active services for dropdown
            $services = ServiceMaster::where('active_inactive', 1)->get();

            // Use the correct view path
            return view('admin.registration.enrol_edit', compact('student', 'services'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('enrollment.create')
                ->with('error', 'Student not found.');
        } catch (\Exception $e) {
            \Log::error('Error in edit method: ' . $e->getMessage());
            return redirect()->route('enrollment.create')
                ->with('error', 'Error loading student: ' . $e->getMessage());
        }
    }
    /**
     * Update student information.
     */
    public function update(Request $request, $studentId)
    {
        try {
            DB::beginTransaction();

            // Find student
            $student = StudentMaster::findOrFail($studentId);

            // Get old values for finding in fc_registration_master
            $oldUserId = $student->user_id;
            $oldEmail = $student->email;

            // Define validation rules for fields present in BOTH tables
            $commonRules = [
                'display_name' => 'nullable|string|max:100',
                'first_name' => 'required|string|max:100',
                'middle_name' => 'nullable|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|max:75|unique:student_master,email,' . $studentId . ',pk',
                'contact_no' => 'required|string|max:15',
                'service_master_pk' => 'required|exists:service_master,pk',
                'web_auth' => 'nullable|string|max:100',
                'dob' => 'nullable|date',
                'exam_year' => 'nullable|string|max:4',
                'rank' => 'nullable|string|max:11',
                'user_id' => 'required|string|max:45|unique:student_master,user_id,' . $studentId . ',pk',
            ];

            // Validate
            $validatedData = $request->validate($commonRules);

            // Update student_master
            $student->update($validatedData);

            // Update fc_registration_master (try multiple ways to find record)
            $fcUpdated = false;

            // Try to find record in fc_registration_master
            $fcRecord = FcRegistrationMaster::where('pk', $studentId)
                ->orWhere('user_id', $oldUserId)
                ->orWhere('email', $oldEmail)
                ->first();

            if ($fcRecord) {
                $fcRecord->update($validatedData);
                $fcUpdated = true;
            } else {
                // Create new record if not found
                // $fcRecord = FcRegistrationMaster::create(array_merge(
                //     ['pk' => $studentId],
                //     $validatedData,
                //     ['created_date' => now()]
                // ));
                $fcUpdated = true;
            }

            DB::commit();

            return redirect()->route('enrollment.create')
                ->with('success', 'Student information updated successfully!')
                ->with('selected_course', session('selected_course'));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

}