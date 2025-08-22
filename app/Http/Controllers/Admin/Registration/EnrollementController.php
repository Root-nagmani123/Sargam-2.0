<?php

// namespace App\Http\Controllers\Admin\Registration;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;

// class EnrollementController extends Controller
// {
//     //
// }

// app/Http/Controllers/EnrollmentController.php
// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use App\Models\CourseMaster;
// use App\Models\StudentMasterCourseMap;
// use App\Models\ServiceMaster;

// class EnrollmentController extends Controller
// {
//     public function create()
//     {
//         // Get all active courses
//         $courses = CourseMaster::where('active_inactive', 1)->get();

//         // Get previous courses from student course map with course details
//         $previousCourses = StudentMasterCourseMap::with('course')
//             ->where('active_inactive', 1)
//             ->get()
//             ->unique('course_master_pk'); // Get unique courses

//         // Get all active services
//         $services = ServiceMaster::where('active_inactive', 1)->get();

//         return view('enrollment.create', compact('courses', 'previousCourses', 'services'));
//     }

//     public function store(Request $request)
//     {
//         // Validate the request
//         $request->validate([
//             'course_master_pk' => 'required|exists:course_master,pk',
//             'previous_courses' => 'array',
//             'services' => 'array'
//         ]);

//         // Deactivate previous enrollments
//         if ($request->has('previous_courses')) {
//             StudentMasterCourseMap::whereIn('pk', $request->previous_courses)
//                 ->update(['active_inactive' => 0]);
//         }

//         // Create new enrollment
//         $enrollment = new StudentMasterCourseMap();
//         $enrollment->course_master_pk = $request->course_master_pk;
//         $enrollment->student_master_pk = auth()->id(); // Assuming student is authenticated
//         $enrollment->active_inactive = 1;
//         $enrollment->save();

//         // Attach services if any
//         if ($request->has('services')) {
//             // Assuming you have a pivot table for services
//             $enrollment->services()->attach($request->services);
//         }

//         return redirect()->back()->with('success', 'Enrollment successful!');
//     }
// }


namespace App\Http\Controllers\Admin\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseMaster;
use App\Models\StudentMasterCourseMap;
use App\Models\ServiceMaster;
use Illuminate\Support\Facades\DB;


class EnrollementController extends Controller
{
    public function create()
    {
        // Get all active courses
        $courses = CourseMaster::where('active_inactive', 1)->get();

        // Get previous courses from student course map with course details
        $previousCourses = StudentMasterCourseMap::with('course')
            // ->where('active_inactive', 1)
            ->get()
            ->unique('course_master_pk'); // Get unique courses
        // @dump($previousCourses);die;

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
        // dd($newCoursePk);

        if (empty($studentIds)) {
            return back()->withErrors(['selected_students' => 'No students selected for enrollment.']);
        }

        DB::beginTransaction();
        try {
            foreach ($studentIds as $studentId) {
                // 1️⃣ Deactivate all old course enrollments for this student
                DB::table('student_master_course__map')
                    ->where('student_master_pk', $studentId)
                    ->update(['active_inactive' => 0]);

                // 2️⃣ Insert new enrollment record for selected course
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


    //  public function filterStudents(Request $request)
    // {
    //     try {
    //         $previousCourses = $request->input('previous_courses', []);
    //         $services = $request->input('services', []);

    //         if (empty($previousCourses)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Please select at least one previous course'
    //             ]);
    //         }

    //         // Build the query to get students based on filters
    //         $query = DB::table('student_master_course_map as smcm')
    //             ->join('student_master as sm', 'smcm.student_master_pk', '=', 'sm.pk')
    //             ->join('course_master as cm', 'smcm.course_master_pk', '=', 'cm.pk')
    //             ->leftJoin('service_master as svm', 'smcm.service_master_pk', '=', 'svm.pk')
    //             ->whereIn('smcm.course_master_pk', $previousCourses)
    //             ->where('smcm.active_inactive', 1)
    //             ->where('sm.active_inactive', 1)
    //             ->select(
    //                 'sm.pk as student_pk',
    //                 'sm.student_name',
    //                 'sm.ot_code',
    //                 'cm.course_name',
    //                 'svm.service_name'
    //             );

    //         // Filter by services if provided
    //         if (!empty($services)) {
    //             $query->whereIn('smcm.service_master_pk', $services);
    //         }

    //         $students = $query->get();
    //         dd($students);

    //         return response()->json([
    //             'success' => true,
    //             'students' => $students
    //         ]);

    //     } catch (\Exception $e) {
    //         \Log::error('Error filtering students: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error filtering students'
    //         ]);
    //     }
    // }

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
                ->leftJoin('service_master as svm', 'sm.service_master_pk', '=', 'svm.pk') // Fixed join
                ->whereIn('smcm.course_master_pk', $previousCourses)
                // ->where('smcm.active_inactive', 1)
                // ->where('sm.active_inactive', 1)
                ->select(
                    'sm.pk as student_pk',
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
}
