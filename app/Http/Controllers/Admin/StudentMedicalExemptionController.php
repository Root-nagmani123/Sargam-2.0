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
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class StudentMedicalExemptionController extends Controller
{

    public function index(Request $request)
    {
        /* =========================================================
         | AJAX REQUEST (DataTable)
         ========================================================= */
        if ($request->ajax()) {

            /* ===============================
             | 1. Build Cache Key
             =============================== */
            /* $cacheKey = 'student_medical_exemption_ids_' . md5(json_encode([
                'custom_search' => $request->custom_search,
                'course_id'     => $request->course_id,
                'from_date'     => $request->from_date,
                'to_date'       => $request->to_date,
                'status'        => $request->get('status', 'active'),
            ])); */


            $cacheKey = 'student_medical_exemption_ids_' . md5(json_encode([
                        'custom_search' => $request->custom_search,
                        'course_id'     => $request->course_id,
                        'from_date'     => $request->from_date,
                        'to_date'       => $request->to_date,
                        'status'        => $request->get('status', 'active'),
                        'start'         => $request->start,
                        'length'        => $request->length,
                    ]));


            /* ===============================
             | 2. Cache ONLY IDs (SAFE)
             =============================== */
            $ids = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request) {

                $query = StudentMedicalExemption::query();

                /* 🔍 Custom Search */
                if ($request->filled('custom_search')) {

                    $search = $request->custom_search;

                    $query->where(function ($q) use ($search) {

                        $q->whereHas('student', function ($qs) use ($search) {
                            $qs->where('display_name', 'like', "{$search}%")
                               ->orWhere('generated_OT_code', 'like', "%{$search}%");
                        })

                        ->orWhereHas('course', function ($qs) use ($search) {
                            $qs->where('course_name', 'like', "{$search}%");
                        })

                        ->orWhereHas('employee', function ($qs) use ($search) {
                            $qs->where('first_name', 'like', "{$search}%")
                               ->orWhere('last_name', 'like', "{$search}%");
                        })

                        ->orWhereHas('category', function ($qs) use ($search) {
                            $qs->where('exemp_category_name', 'like', "{$search}%");
                        })

                        ->orWhereHas('speciality', function ($qs) use ($search) {
                            $qs->where('speciality_name', 'like', "{$search}%");
                        })

                        ->orWhere('opd_category', 'like', "{$search}%");
                    });
                }

                /* 🎓 Course Filter */
                if ($request->filled('course_id')) {
                    $query->where('course_master_pk', $request->course_id);
                }

                /* 📅 Date Filter */
                if ($request->filled('from_date') && $request->filled('to_date')) {
                    $query->whereBetween('from_date', [
                        $request->from_date,
                        $request->to_date
                    ]);
                } elseif ($request->filled('from_date')) {
                    $query->whereDate('from_date', '>=', $request->from_date);
                } elseif ($request->filled('to_date')) {
                    $query->whereDate('from_date', '<=', $request->to_date);
                }

                /* 📌 Active / Archive Filter */
                $currentDate = now()->format('Y-m-d');
                $status = $request->get('status', 'active');

                if ($status === 'active') {
                    $query->whereHas('course', function ($q) use ($currentDate) {
                        $q->whereDate('end_date', '>=', $currentDate);
                    });
                } else {
                    $query->whereHas('course', function ($q) use ($currentDate) {
                        $q->whereDate('end_date', '<', $currentDate);
                    });
                }

                /* ✅ RETURN ONLY IDs (NO PDO ISSUE) */
                //return $query->pluck('pk')->toArray();
                return $query->orderBy('pk', 'desc')->pluck('pk')->toArray();
            });

            /* ===============================
             | 3. Rebuild Query for DataTable
             =============================== */
           /* $query = StudentMedicalExemption::with([
                'student',
                'category',
                'speciality',
                'course',
                'employee'
           ])
           ->whereIn('pk', $ids)
                ->orderByRaw("FIELD(pk, " . implode(',', $ids) . ")");*/

                $query = StudentMedicalExemption::with([
                            'student',
                            'category',
                            'speciality',
                            'course',
                            'employee'
                        ]);

                        if (!empty($ids)) {
                            $query->whereIn('pk', $ids)
                                ->orderByRaw("FIELD(pk, " . implode(',', $ids) . ")");
                        } else {
                            // IMPORTANT: return empty result without SQL error
                            $query->whereRaw('1 = 0');
                        }



            // // ->whereIn('pk', $ids);

            /* ===============================
             | 4. DataTable Response
             =============================== */
            return DataTables::of($query)

                ->addIndexColumn()

                ->addColumn('student', fn($row) =>
                    $row->student->display_name ?? 'N/A'
                )

                ->addColumn('ot_code', fn($row) =>
                    $row->student->generated_OT_code ?? 'N/A'
                )

                ->addColumn('course', fn($row) =>
                    $row->course->course_name ?? 'N/A'
                )

                ->addColumn('assigned_by', function ($row) {
                    if ($row->employee && $row->employee->first_name) {
                        return trim($row->employee->first_name . ' ' . $row->employee->last_name);
                    }
                    return 'N/A';
                })

                ->addColumn('category', fn($row) =>
                    $row->category->exemp_category_name ?? 'N/A'
                )

                ->addColumn('speciality', fn($row) =>
                    $row->speciality->speciality_name ?? 'N/A'
                )

                ->addColumn('from_to', fn($row) =>
                    Carbon::parse($row->from_date)->format('d-m-Y') .
                    ' to ' .
                    Carbon::parse($row->to_date)->format('d-m-Y')
                )

                ->addColumn('opd_type', fn($row) =>
                    $row->opd_category ?? 'N/A'
                )

                ->addColumn('document', function ($row) {
                    if ($row->Doc_upload) {
                        return '<a href="' . asset('storage/' . $row->Doc_upload) . '" target="_blank"
                                class="btn btn-sm btn-info">
                                <i class="material-icons material-symbols-rounded">description</i>
                            </a>';
                    }
                    return '<span class="text-muted">N/A</span>';
                })

                ->addColumn('action', function ($row) {

                    $editUrl = route('student.medical.exemption.edit', encrypt($row->pk));
                    $deleteUrl = route('student.medical.exemption.delete', encrypt($row->pk));
                    $disabled = $row->active_inactive == 1 ? 'disabled' : '';

                    return '
                        <a href="' . $editUrl . '">
                            <i class="material-icons material-symbols-rounded">edit</i>
                        </a>

                        <a href="javascript:void(0)"
                           class="delete-btn ' . $disabled . '"
                           data-url="' . $deleteUrl . '">
                            <i class="material-icons material-symbols-rounded">delete</i>
                        </a>';
                })

                ->addColumn('status', function ($row) {
                    $checked = $row->active_inactive == 1 ? 'checked' : '';
                    return '
                        <div class="form-check form-switch">
                            <input class="form-check-input status-toggle"
                                type="checkbox"
                                data-table="student_medical_exemption"
                                data-column="active_inactive"
                                data-id="' . $row->pk . '" ' . $checked . '>
                        </div>';
                })

                ->rawColumns(['document', 'action', 'status'])
                ->make(true);
        }

        /* =========================================================
         | NORMAL PAGE LOAD
         ========================================================= */
        $courses = CourseMaster::where('active_inactive', '1')
            ->orderBy('course_name', 'asc')
            ->get();

        $search = $request->get('search', '');

        return view(
            'admin.student_medical_exemption.index',
            compact('courses', 'search')
        );
    }

    	public function create()
{

    $courses = CourseMaster::where('active_inactive', '1');

    $data_course_id = get_Role_by_course();

    if (!empty($data_course_id)) {
        $courses = $courses->whereIn('pk', $data_course_id);
    }

    $courses = $courses
        ->where('end_date', '>', now())
        ->get();

    $categories = ExemptionCategoryMaster::where('active_inactive', '1')->get();
    $specialities = ExemptionMedicalSpecialityMaster::where('active_inactive', '1')->get();

    return view('admin.student_medical_exemption.create', compact(
        'courses',
        'categories',
        'specialities'
    ));
}


   public function create_2701226()
{
    $courses = CourseMaster::where('active_inactive', '1');
    $data_course_id =  get_Role_by_course();
    if(!empty($data_course_id))
    {
        $courses = CourseMaster::where('active_inactive', '1');
        $data_course_id =  get_Role_by_course();
        if (!empty($data_course_id)) {
            $courses = $courses->whereIn('pk', $data_course_id);
        }
        $courses = $courses->where('end_date', '>', now())
            ->get();

        $categories = ExemptionCategoryMaster::where('active_inactive', '1')->get();
        $specialities = ExemptionMedicalSpecialityMaster::where('active_inactive', '1')->get();

        return view('admin.student_medical_exemption.create', compact('courses', 'categories', 'specialities'));
    }
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
            'Doc_upload' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:3072',
        ], [
		// Custom messages
			'Doc_upload.required' => 'Please upload a document.',
			'Doc_upload.mimes' => 'Only image files (jpg, png, webp) or PDF are allowed.',
			'Doc_upload.max' => 'Document size must not exceed 3 MB.',
			]
        );

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
            Log::error('Failed to send medical exemption notifications: ' . $e->getMessage());
        }

        return redirect()->route('student.medical.exemption.index')->with('success', 'Record created successfully.');
    }


    public function edit($id)
    {
        $record = StudentMedicalExemption::findOrFail(decrypt($id));

        $courses = CourseMaster::where('active_inactive', '1');
        $data_course_id =  get_Role_by_course();
        if (!empty($data_course_id)) {
            $courses = $courses->whereIn('pk', $data_course_id);
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
            Log::error('Failed to send medical exemption notifications: ' . $e->getMessage());
        }

        return redirect()->route('student.medical.exemption.index')->with('success', 'Record updated successfully.');
    }

 public function delete($id)
    {
        //StudentMedicalExemption::destroy(decrypt($id));
        $record = StudentMedicalExemption::findOrFail(decrypt($id));

        if ($record->active_inactive == 1) {
            return response()->json([
            'message' => 'Active medical exemption cannot be deleted.'
            ], 403);
            }

            $record->delete();

            return response()->json([
            'message' => 'Medical exemption deleted successfully'
            ]);
       // return redirect()->route('student.medical.exemption.index')->with('success', 'Deleted successfully.');
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
