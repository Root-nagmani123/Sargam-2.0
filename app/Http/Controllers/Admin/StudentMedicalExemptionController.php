<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentMedicalExemption;
use App\Models\CourseMaster;
use App\Models\StudentMaster;
use App\Models\ExemptionCategoryMaster;
use App\Models\ExemptionMedicalSpecialityMaster;
use App\Models\EmployeeMaster;
use App\Models\StudentCourseGroupMap;
use App\Models\GroupTypeMasterCourseMasterMap;
use App\Exports\StudentMedicalExemptionExport;
use App\Services\NotificationService;
use App\Services\NotificationReceiverService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;


class StudentMedicalExemptionController extends Controller
{
    public function index(Request $request)
    {
       $query = StudentMedicalExemption::with(['student', 'category', 'speciality', 'course', 'employee']);
       
       // Filter by course status (Active/Archive)
       $filter = $request->get('filter', 'active'); // Default to 'active'
       $currentDate = now()->format('Y-m-d');
       
       if ($filter === 'active') {
           // Active Courses: end_date > current date
           $query->whereHas('course', function($q) use ($currentDate) {
               $q->where('end_date', '>', $currentDate);
           });
       } elseif ($filter === 'archive') {
           // Archive Courses: end_date < current date
           $query->whereHas('course', function($q) use ($currentDate) {
               $q->where('end_date', '<', $currentDate);
           });
       }
        $data_course_id =  get_Role_by_course();
         if(!empty($data_course_id))
        {
            $query->whereIn('course_master_pk',$data_course_id);
        }
       
       // Filter by specific course if selected
       $courseFilter = $request->get('course_filter');
       if ($courseFilter) {
           $query->where('course_master_pk', $courseFilter);
       }
       
       // Filter by date range
       $fromDateFilter = $request->get('from_date_filter');
       $toDateFilter = $request->get('to_date_filter');
       
       if ($fromDateFilter || $toDateFilter) {
           // Filter records where the exemption period overlaps with the selected date range
           if ($fromDateFilter && $toDateFilter) {
               // Both dates provided: exemption period overlaps if (to_date >= from_date_filter OR to_date IS NULL) AND from_date <= to_date_filter
               $query->where(function($q) use ($fromDateFilter, $toDateFilter) {
                   $q->where('to_date', '>=', $fromDateFilter)
                     ->orWhereNull('to_date');
               })
               ->where('from_date', '<=', $toDateFilter);
           } elseif ($fromDateFilter) {
               // Only from_date provided: show records where to_date >= from_date_filter OR to_date IS NULL
               $query->where(function($q) use ($fromDateFilter) {
                   $q->where('to_date', '>=', $fromDateFilter)
                     ->orWhereNull('to_date');
               });
           } elseif ($toDateFilter) {
               // Only to_date provided: show records where from_date <= to_date_filter
               $query->where('from_date', '<=', $toDateFilter);
           }
       }
       
       // Search functionality
       $search = $request->get('search');
       if ($search && $search != '') {
           $query->where(function($q) use ($search) {
               // Search in student name
               $q->whereHas('student', function($studentQuery) use ($search) {
                   $studentQuery->where('display_name', 'like', '%' . $search . '%')
                                ->orWhere('generated_OT_code', 'like', '%' . $search . '%');
               })
               // Search in course name
               ->orWhereHas('course', function($courseQuery) use ($search) {
                   $courseQuery->where('course_name', 'like', '%' . $search . '%');
               })
               // Search in employee name
               ->orWhereHas('employee', function($employeeQuery) use ($search) {
                   $employeeQuery->where('first_name', 'like', '%' . $search . '%')
                                 ->orWhere('last_name', 'like', '%' . $search . '%');
               })
               // Search in category name
               ->orWhereHas('category', function($categoryQuery) use ($search) {
                   $categoryQuery->where('exemp_category_name', 'like', '%' . $search . '%');
               })
               // Search in speciality name
               ->orWhereHas('speciality', function($specialityQuery) use ($search) {
                   $specialityQuery->where('speciality_name', 'like', '%' . $search . '%');
               })
               // Search in OPD category
               ->orWhere('opd_category', 'like', '%' . $search . '%');
           });
       }
       
       $records = $query->orderBy('pk', 'desc')->paginate(10);
       
       // Get courses filtered by Active/Archive status for dropdown
       $coursesQuery = CourseMaster::where('active_inactive', '1');
        $data_course_id =  get_Role_by_course();
         if(!empty($data_course_id))
        {
            $coursesQuery->whereIn('pk',$data_course_id);
        }
       if ($filter === 'active') {
           $coursesQuery->where('end_date', '>', $currentDate);
       } elseif ($filter === 'archive') {
           $coursesQuery->where('end_date', '<', $currentDate);
       }
       $courses = $coursesQuery->orderBy('course_name', 'asc')->get();
    
        $search = $request->get('search', '');
        return view('admin.student_medical_exemption.index', compact('records', 'filter', 'courses', 'courseFilter', 'search'));
    }

   public function create()
{
    $courses = CourseMaster::where('active_inactive', '1');
    $data_course_id =  get_Role_by_course();
    if(!empty($data_course_id))
    {
        $courses = $courses->whereIn('pk',$data_course_id);
    }
        $courses = $courses->where('end_date', '>', now())
        ->get();
  
    $categories = ExemptionCategoryMaster::where('active_inactive', '1')->get();
    $specialities = ExemptionMedicalSpecialityMaster::where('active_inactive', '1')->get();

    return view('admin.student_medical_exemption.create', compact('courses', 'categories', 'specialities'));
}


    /**
     * Check if a date-time range overlaps with existing exemptions for the same student
     * Two ranges overlap if: new_start < existing_end AND new_end > existing_start
     */
    private function checkOverlap($studentId, $fromDate, $toDate, $excludeId = null)
    {
        $query = StudentMedicalExemption::where('student_master_pk', $studentId);
        
        // Exclude current record when updating
        if ($excludeId !== null) {
            $query->where('pk', '!=', $excludeId);
        }
        
        $existingExemptions = $query->get();
        
        $newFrom = \Carbon\Carbon::parse($fromDate);
        // Use a far future date if to_date is null (ongoing exemption)
        $newTo = $toDate ? \Carbon\Carbon::parse($toDate) : \Carbon\Carbon::create(2099, 12, 31, 23, 59, 59);
        
        foreach ($existingExemptions as $exemption) {
            $existingFrom = \Carbon\Carbon::parse($exemption->from_date);
            // Use a far future date if to_date is null (ongoing exemption)
            $existingTo = $exemption->to_date ? \Carbon\Carbon::parse($exemption->to_date) : \Carbon\Carbon::create(2099, 12, 31, 23, 59, 59);
            
            // Check for overlap: new_start < existing_end AND new_end > existing_start
            $overlaps = $newFrom < $existingTo && $newTo > $existingFrom;
            
            if ($overlaps) {
                $existingFromFormatted = $existingFrom->format('d M Y H:i');
                $existingToFormatted = $exemption->to_date ? \Carbon\Carbon::parse($exemption->to_date)->format('d M Y H:i') : 'Ongoing';
                return "This time range overlaps with an existing exemption for this student (from {$existingFromFormatted} to {$existingToFormatted}).";
            }
        }
        
        return null;
    }

    public function store(Request $request)
{
    
    $validated = $request->validate([
        'course_master_pk' => 'required|numeric',
        'student_master_pk' => 'required|numeric',
        'employee_master_pk' => 'required|numeric',
        'exemption_category_master_pk' => 'required|numeric',
        'from_date' => 'required|date',
        'to_date' => 'nullable|date|after_or_equal:from_date',
        'opd_category' => 'nullable|string|max:50',
        'exemption_medical_speciality_pk' => 'required|numeric',
        'Description' => 'nullable|string',
        'active_inactive' => 'nullable|boolean',
    ]);

    // Check for overlapping time ranges for the same student
    $overlapError = $this->checkOverlap(
        $validated['student_master_pk'],
        $validated['from_date'],
        $validated['to_date']
    );
    
    if ($overlapError) {
        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['from_date' => $overlapError]);
    }

    // Set default status to Active (1) if not provided
    if (!isset($validated['active_inactive'])) {
        $validated['active_inactive'] = 1;
    }

    // Handle file upload if exists
    if ($request->hasFile('Doc_upload')) {
        $file = $request->file('Doc_upload');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('uploads/exemptions', $filename, 'public');
        $validated['Doc_upload'] = $path;
    }

    $medicalExemption = StudentMedicalExemption::create($validated);

    // Send notifications to relevant users
    try {
        $notificationService = app(NotificationService::class);
        $receiverService = app(NotificationReceiverService::class);

        // Get student and course information for notification
        $student = StudentMaster::find($validated['student_master_pk']);
        $course = CourseMaster::find($validated['course_master_pk']);

        $studentName = $student ? $student->display_name : 'Student';
        $courseName = $course ? $course->course_name : 'Course';
        $fromDate = date('d M Y', strtotime($validated['from_date']));
        $toDate = $validated['to_date'] ? date('d M Y', strtotime($validated['to_date'])) : 'Ongoing';

        // Get receiver user_ids
        $receiverUserIds = $receiverService->getMedicalExemptionReceivers(
            $validated['student_master_pk'],
            $validated['course_master_pk']
        );

        if (!empty($receiverUserIds)) {
            $title = 'Medical Exemption Added';
            $message = "A medical exemption has been added for student {$studentName} (Course: {$courseName}) from {$fromDate} to {$toDate}.";

            // Send notifications to all receivers
            $notificationService->createMultiple(
                $receiverUserIds,
                'medical_exemption',
                'Medical Exemption',
                $medicalExemption->pk,
                $title,
                $message
            );
        }
    } catch (\Exception $e) {
        // Log error but don't fail the request
        \Log::error('Failed to send medical exemption notifications: ' . $e->getMessage());
    }

    return redirect()->route('student.medical.exemption.index')->with('success', 'Record created successfully.');
}


   public function edit($id)
{
    $record = StudentMedicalExemption::findOrFail(decrypt($id));

    $courses = CourseMaster::where('active_inactive', '1');
    $data_course_id =  get_Role_by_course();
    if(!empty($data_course_id))
    {
        $courses = $courses->whereIn('pk',$data_course_id);
    }
    $courses = $courses->where('end_date', '>', now())
        ->get();
    $students = StudentMaster::select('pk', 'generated_OT_code', 'display_name')
        ->where('status', '1')
        ->orderBy('display_name', 'asc')
        ->get();
    $categories = ExemptionCategoryMaster::where('active_inactive', '1')->get();
    $specialities = ExemptionMedicalSpecialityMaster::where('active_inactive', '1')->get();

    return view('admin.student_medical_exemption.edit', compact('record', 'courses', 'students', 'categories', 'specialities'));
}
public function update(Request $request, $id)
{
    $validated = $request->validate([
        'course_master_pk' => 'required|numeric',
        'student_master_pk' => 'required|numeric',
        'employee_master_pk' => 'nullable|numeric',
        'exemption_category_master_pk' => 'required|numeric',
        'from_date' => 'required|date',
        'to_date' => 'nullable|date|after_or_equal:from_date',
        'opd_category' => 'nullable|string|max:50',
        'exemption_medical_speciality_pk' => 'required|numeric',
        'Description' => 'nullable|string',
        'active_inactive' => 'required|boolean',
    ]);

    $record = StudentMedicalExemption::findOrFail(decrypt($id));
    
    // Check for overlapping time ranges for the same student (excluding current record)
    $overlapError = $this->checkOverlap(
        $validated['student_master_pk'],
        $validated['from_date'],
        $validated['to_date'],
        $record->pk
    );
    
    if ($overlapError) {
        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['from_date' => $overlapError]);
    }

    if ($request->hasFile('Doc_upload')) {
        $file = $request->file('Doc_upload');
        $filename = time() . '_' . $file->getClientOriginalName();
        $validated['Doc_upload'] = $file->storeAs('uploads/exemptions', $filename, 'public');
    }

    $record->update($validated);

    // Send notifications to relevant users
    try {
        $notificationService = app(NotificationService::class);
        $receiverService = app(NotificationReceiverService::class);

        // Get student and course information for notification
        $student = StudentMaster::find($validated['student_master_pk']);
        $course = CourseMaster::find($validated['course_master_pk']);

        $studentName = $student ? $student->display_name : 'Student';
        $courseName = $course ? $course->course_name : 'Course';
        $fromDate = date('d M Y', strtotime($validated['from_date']));
        $toDate = $validated['to_date'] ? date('d M Y', strtotime($validated['to_date'])) : 'Ongoing';

        // Get receiver user_ids
        $receiverUserIds = $receiverService->getMedicalExemptionReceivers(
            $validated['student_master_pk'],
            $validated['course_master_pk']
        );

        if (!empty($receiverUserIds)) {
            $title = 'Medical Exemption Updated';
            $message = "A medical exemption has been updated for student {$studentName} (Course: {$courseName}) from {$fromDate} to {$toDate}.";

            // Send notifications to all receivers
            $notificationService->createMultiple(
                $receiverUserIds,
                'medical_exemption',
                'Medical Exemption',
                $record->pk,
                $title,
                $message
            );
        }
    } catch (\Exception $e) {
        // Log error but don't fail the request
        \Log::error('Failed to send medical exemption notifications: ' . $e->getMessage());
    }

    return redirect()->route('student.medical.exemption.index')->with('success', 'Record updated successfully.');
}


    public function delete($id)
    {
        StudentMedicalExemption::destroy(decrypt($id));
        return redirect()->route('student.medical.exemption.index')->with('success', 'Deleted successfully.');
    }
    public function getStudentsByCourse(Request $request)
    {
        $courseId = $request->input('course_id');
        
        // Get students from Course Group Mapping (Phase-1 mapped students)
        // Join: StudentCourseGroupMap -> GroupTypeMasterCourseMasterMap -> Course
        $students = DB::table('student_course_group_map')
            ->join('group_type_master_course_master_map', 'student_course_group_map.group_type_master_course_master_map_pk', '=', 'group_type_master_course_master_map.pk')
            ->join('student_master', 'student_course_group_map.student_master_pk', '=', 'student_master.pk')
            ->where('group_type_master_course_master_map.course_name', $courseId)
            ->where('student_master.status', '1')
            ->select('student_master.pk', 'student_master.generated_OT_code', 'student_master.display_name')
            ->distinct()
            ->orderBy('student_master.display_name', 'asc')
            ->get();
       
       return response()->json(['students' => $students]);

    }

    public function export(Request $request)
    {
        $filter = $request->get('filter', 'active');
        $courseFilter = $request->get('course_filter');
        $fromDateFilter = $request->get('from_date_filter');
        $toDateFilter = $request->get('to_date_filter');
        $search = $request->get('search');
        
        $fileName = 'medical-exemption-export-' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(
            new StudentMedicalExemptionExport($filter, $courseFilter, $search, $fromDateFilter, $toDateFilter),
            $fileName
        );
    }
}
