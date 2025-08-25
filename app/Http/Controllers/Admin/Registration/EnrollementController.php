<?php

namespace App\Http\Controllers\Admin\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseMaster;
use App\Models\StudentMasterCourseMap;
use App\Models\ServiceMaster;
use App\Models\StudentMaster;
use Illuminate\Support\Facades\DB;


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

    public function studentCourses(Request $request)
    {
        // If course filter is applied
        $courseId = $request->input('course_id');
        $students = StudentMaster::with(['courses', 'service'])
            ->when($courseId, function ($query) use ($courseId) {
                $query->whereHas('courses', function ($q) use ($courseId) {
                    $q->where('course_master.pk', $courseId);
                });
            })
            ->get();

        // Count total students (all records, without filter)
        $totalStudents = StudentMaster::count();

        // Count filtered students (with filter or all if no filter)
        $filteredCount = $students->count();

        // Get courses for filter dropdown
        $courses = CourseMaster::where('active_inactive', 1)->pluck('course_name', 'pk'); // pk => course_name

        return view('admin.registration.student_courselist', compact('students', 'courses', 'courseId', 'totalStudents', 'filteredCount'));
    }
}
