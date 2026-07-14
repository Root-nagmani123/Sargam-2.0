<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentMedicalExemption;
use App\Models\CourseMaster;
use App\Models\StudentMaster;
use App\Models\ExemptionCategoryMaster;
use App\Models\ExemptionMedicalSpecialityMaster;
use App\Models\MedicalCaseMaster;
use App\Models\EmployeeMaster;
use App\Models\StudentCourseGroupMap;
use App\Models\GroupTypeMasterCourseMasterMap;
use App\Exports\StudentMedicalExemptionExport;
use App\Services\NotificationService;
use App\Services\NotificationReceiverService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Barryvdh\DomPDF\Facade\Pdf;
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
            // Version prefix so any create/update/delete instantly invalidates
            // every cached ID list (see flushListCache()).
            $cacheVersion = Cache::get('student_medical_exemption_cache_version', 1);
            $cacheKey = 'student_medical_exemption_ids_v' . $cacheVersion . '_' . md5(json_encode([
                'custom_search'      => $request->custom_search,
                'course_id'          => $request->course_id,
                'from_date'          => $request->from_date,
                'to_date'            => $request->to_date,
                'status'             => $request->get('status', 'active'),
                'opd_category'       => $request->opd_category,
                'exemption_category' => $request->exemption_category,
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

                        // Consistent "contains" match across every searchable field so a
                        // term is found no matter where it sits in the value.
                        $q->whereHas('student', function ($qs) use ($search) {
                            $qs->where('display_name', 'like', "%{$search}%")
                               ->orWhere('generated_OT_code', 'like', "%{$search}%");
                        })

                        ->orWhereHas('course', function ($qs) use ($search) {
                            $qs->where('course_name', 'like', "%{$search}%");
                        })

                        ->orWhereHas('employee', function ($qs) use ($search) {
                            $qs->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%");
                        })

                        ->orWhereHas('category', function ($qs) use ($search) {
                            $qs->where('exemp_category_name', 'like', "%{$search}%");
                        })

                        ->orWhereHas('speciality', function ($qs) use ($search) {
                            $qs->where('speciality_name', 'like', "%{$search}%");
                        })

                        ->orWhere('opd_category', 'like', "%{$search}%")
                        ->orWhere('Description', 'like', "%{$search}%")
                        ->orWhere('pt_outdoor_advise', 'like', "%{$search}%");
                    });
                }

                /* 🎓 Course Filter */
                if ($request->filled('course_id')) {
                    $query->where('course_master_pk', $request->course_id);
                }

                /* 🩺 Medical Case Filter */
                if ($request->filled('opd_category')) {
                    $query->where('opd_category', $request->opd_category);
                }

                /* 📋 Exemption Category Filter */
                if ($request->filled('exemption_category')) {
                    $query->where('exemption_category_master_pk', $request->exemption_category);
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
            $query = StudentMedicalExemption::with([
                'student',
                'category',
                'speciality',
                'course',
                'employee',
                'creator'
            ]);

            if (empty($ids)) {
                // Avoid invalid SQL like FIELD(pk, ) when there are no matches.
                $query->whereRaw('1 = 0');
            } else {
                $idList = implode(',', array_map('intval', $ids));
                $query->whereIn('pk', $ids)
                    ->orderByRaw("FIELD(pk, {$idList})");
            }

            /* ===============================
             | 4. DataTable Response
             =============================== */
            return DataTables::of($query)

                ->addIndexColumn()

                ->addColumn('date', fn($row) =>
                    $row->from_date
                        ? Carbon::parse($row->from_date)->format('d/m/Y')
                        : 'N/A'
                )

                ->addColumn('student', function ($row) {
                    $name = $row->student->display_name ?? 'N/A';
                    $ot = $row->student->generated_OT_code ?? null;

                    return $ot ? "{$name} - {$ot}" : $name;
                })

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

                ->addColumn('created_by', function ($row) {
                    if ($row->creator) {
                        $name = trim(($row->creator->first_name ?? '') . ' ' . ($row->creator->last_name ?? ''));
                        return $name !== '' ? $name : ($row->creator->user_name ?? 'N/A');
                    }
                    return 'N/A';
                })

                ->addColumn('created_on', fn($row) =>
                    $row->created_date
                        ? Carbon::parse($row->created_date)->format('d/m/Y h:i A')
                        : 'N/A'
                )

                ->addColumn('speciality', fn($row) =>
                    $row->speciality->speciality_name ?? 'N/A'
                )

                ->addColumn('duration', function ($row) {
                    $from = $row->from_date
                        ? Carbon::parse($row->from_date)->format('d/m/Y H:i')
                        : 'N/A';
                    $to = $row->to_date
                        ? Carbon::parse($row->to_date)->format('d/m/Y H:i')
                        : 'N/A';

                    return $from . ' - ' . $to;
                })

                ->addColumn('days', fn($row) =>
                    $row->days ?? 'N/A'
                )

                ->addColumn('category', fn($row) =>
                    $row->category->exemp_category_name ?? 'N/A'
                )

                ->addColumn('opd_type', fn($row) =>
                    $row->opd_category ?? 'N/A'
                )

                ->addColumn('pt_advise', fn($row) =>
                    $row->pt_outdoor_advise ?: '-'
                )

                ->addColumn('description', fn($row) =>
                    $row->Description ?: '-'
                )

                // Legacy combined fields — keeps older cached DataTable configs working.
                ->addColumn('arrival_date', fn($row) =>
                    $row->from_date
                        ? Carbon::parse($row->from_date)->format('d-m-Y')
                        : 'N/A'
                )

                ->addColumn('arrival_time', fn($row) =>
                    $row->from_date
                        ? Carbon::parse($row->from_date)->format('h:i A')
                        : 'N/A'
                )

                ->addColumn('departure_date', fn($row) =>
                    $row->to_date
                        ? Carbon::parse($row->to_date)->format('d-m-Y')
                        : 'N/A'
                )

                ->addColumn('departure_time', fn($row) =>
                    $row->to_date
                        ? Carbon::parse($row->to_date)->format('h:i A')
                        : 'N/A'
                )

                ->addColumn('arrival', fn($row) =>
                    $row->from_date
                        ? Carbon::parse($row->from_date)->format('d-m-Y H:i')
                        : 'N/A'
                )

                ->addColumn('departure', fn($row) =>
                    $row->to_date
                        ? Carbon::parse($row->to_date)->format('d-m-Y H:i')
                        : 'N/A'
                )

                ->addColumn('from_to', fn($row) =>
                    ($row->from_date ? Carbon::parse($row->from_date)->format('d-m-Y') : 'N/A')
                    . ' to '
                    . ($row->to_date ? Carbon::parse($row->to_date)->format('d-m-Y') : 'N/A')
                )

                ->addColumn('document', function ($row) {
                    if ($row->Doc_upload) {
                        return '<a href="' . asset('storage/' . $row->Doc_upload) . '" target="_blank" rel="noopener noreferrer" class="sme-doc-view">View</a>';
                    }
                    return 'NA';
                })

                ->addColumn('action', function ($row) {

                    $editUrl = route('student.medical.exemption.edit', encrypt($row->pk));
                    $deleteUrl = route('student.medical.exemption.delete', encrypt($row->pk));

                    return '
                        <div class="sme-row-actions">
                            <a href="' . $editUrl . '" class="sme-act sme-act-edit sme-edit-btn" title="Edit" aria-label="Edit">
                                <i class="material-icons material-symbols-rounded">edit</i>
                            </a>

                            <a href="javascript:void(0)"
                               class="delete-btn sme-act sme-act-delete"
                               data-url="' . $deleteUrl . '" title="Delete" aria-label="Delete">
                                <i class="material-icons material-symbols-rounded">delete</i>
                            </a>
                        </div>';
                })

                ->addColumn('status', function ($row) {
                    return $row->active_inactive == 1
                        ? '<span class="sme-status sme-status-active">Active</span>'
                        : '<span class="sme-status sme-status-inactive">Inactive</span>';
                })

                ->rawColumns(['document', 'action', 'status'])
                ->make(true);
        }

        /* =========================================================
         | NORMAL PAGE LOAD
         ========================================================= */
        // Active tab = courses still running; Archive tab = ended courses
        // (mirrors Course Master's active/archived split). The course filter
        // dropdown swaps between these two lists as the tab changes.
        $courses = CourseMaster::where('active_inactive', '1')
            ->where('end_date', '>', now())
            ->orderBy('course_name', 'asc')
            ->get();

        $archivedCourses = CourseMaster::where('active_inactive', '1')
            ->where('end_date', '<', now())
            ->orderBy('course_name', 'asc')
            ->get();

        $search = $request->get('search', '');

        $categories = ExemptionCategoryMaster::where('active_inactive', '1')->get();

        // IPD / OPD / After OPD / Referral / … — driven by the Medical Case Master.
        $opdOptions = MedicalCaseMaster::where('active_inactive', 1)
            ->orderBy('pk')
            ->pluck('case_name')
            ->toArray();

        return view(
            'admin.student_medical_exemption.index',
            compact('courses', 'archivedCourses', 'search', 'categories', 'opdOptions')
        );
    }

    	public function create()
{
    // Every active (non-expired) course — the medical-exemption form is not
    // role-scoped by course (a doctor can raise an exemption for any current course).
    $courses = CourseMaster::where('active_inactive', '1')
        ->where('end_date', '>', now())
        ->orderBy('course_name')
        ->get();

    $categories = ExemptionCategoryMaster::where('active_inactive', '1')->get();
    $specialities = ExemptionMedicalSpecialityMaster::where('active_inactive', '1')->get();
    $doctors = $this->getDoctors();

    // IPD / OPD / After OPD / Referral / … — driven by the Medical Case Master.
    $opdOptions = MedicalCaseMaster::where('active_inactive', 1)
        ->orderBy('pk')
        ->pluck('case_name')
        ->toArray();

    return view('admin.student_medical_exemption.create', compact(
        'courses',
        'categories',
        'specialities',
        'opdOptions',
        'doctors'
    ));
}

    /**
     * Active employees holding a medical-officer designation (Medical Officer /
     * Senior Medical Officer) — the selectable list for Treating Doctor Name.
     */
    private function getDoctors()
    {
        return EmployeeMaster::whereIn('designation_master_pk', [1347521458, 1986142397])
            ->where('status', 1)
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Students (course-group mapped) for a course — mirrors getStudentsByCourse so the
     * edit form can server-render the OT dropdown for the record's own course.
     */
    private function studentsForCourse($courseId)
    {
        if (empty($courseId)) {
            return collect();
        }

        return DB::table('student_course_group_map as scg')
            ->join('group_type_master_course_master_map as gm', 'scg.group_type_master_course_master_map_pk', '=', 'gm.pk')
            ->join('student_master as sm', 'scg.student_master_pk', '=', 'sm.pk')
            ->where('gm.course_name', $courseId)
            ->where('sm.status', '1')
            ->select('sm.pk', 'sm.generated_OT_code', 'sm.display_name')
            ->distinct()
            ->orderBy('sm.display_name')
            ->get();
    }

    /**
     * Combine a date ("Y-m-d") and a time ("H:i") into "Y-m-d H:i", or null.
     */
    private function combineDateTime($date, $time): ?string
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }
        $time = trim((string) $time);
        return $time === '' ? $date : ($date . ' ' . $time);
    }

    /**
     * Inclusive number of days between two datetimes (same day = 1).
     */
    private function computeDays($from, $to): ?int
    {
        if (empty($from) || empty($to)) {
            return null;
        }
        try {
            $f = \Carbon\Carbon::parse($from)->startOfDay();
            $t = \Carbon\Carbon::parse($to)->startOfDay();
            return (int) $f->diffInDays($t) + 1;
        } catch (\Exception $e) {
            return null;
        }
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

    /**
     * Invalidate every cached DataTable ID list by bumping the version prefix.
     * Called after any create / update / delete so new rows appear immediately.
     */
    private function flushListCache(): void
    {
        Cache::forever(
            'student_medical_exemption_cache_version',
            (int) Cache::get('student_medical_exemption_cache_version', 1) + 1
        );
    }

    public function store(Request $request)
    {
        // Catch PHP-level upload errors (e.g. file exceeded upload_max_filesize in php.ini)
        if ($request->hasFile('Doc_upload')) {
            $uploadedFile = $request->file('Doc_upload');
            if (!$uploadedFile->isValid() && in_array($uploadedFile->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE])) {
                return response()->json([
                    'message' => 'The attachment file is too large. Please upload a file smaller than 5 MB.',
                    'errors'  => ['Doc_upload' => ['The attachment file is too large. Please upload a file smaller than 5 MB.']],
                ], 422);
            }
        }

        // Combine the split Arrival / Departure date + time inputs into datetimes.
        $request->merge([
            'from_date' => $this->combineDateTime($request->arrival_date, $request->arrival_time),
            'to_date'   => $this->combineDateTime($request->departure_date, $request->departure_time),
        ]);

        $validated = $request->validate([
            'course_master_pk' => 'required|numeric',
            'student_master_pk' => 'required|numeric',
            'employee_master_pk' => 'required|numeric',
            'exemption_category_master_pk' => 'nullable|numeric',
            'opd_category' => 'required|string|max:50',
            'arrival_date' => 'required|date',
            'arrival_time' => 'nullable',
            'departure_date' => 'nullable|date',
            'departure_time' => 'nullable',
            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'pt_outdoor_advise' => 'nullable|string|max:255',
            'exemption_medical_speciality_pk' => 'required|numeric',
            'Description' => 'nullable|string',
            'active_inactive' => 'nullable|boolean',
            'Doc_upload' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ], [
            'course_master_pk.required' => 'Please select a course.',
            'student_master_pk.required' => 'Please select an officer trainee.',
            'employee_master_pk.required' => 'The treating doctor is required.',
            'exemption_category_master_pk.required' => 'Please select an exemption category.',
            'exemption_medical_speciality_pk.required' => 'Please select a medical speciality.',
            'opd_category.required' => 'Please select Medical Case.',
            'arrival_date.required' => 'The start date is required.',
            'to_date.after_or_equal' => 'The end date/time must be after the start date/time.',
            'Doc_upload.file'  => 'The attachment must be a valid file.',
            'Doc_upload.mimes' => 'Only PDF, JPG, JPEG, PNG, DOC, or DOCX files are allowed.',
            'Doc_upload.max'   => 'The attachment file must not exceed 5 MB (5120 KB).',
        ]);

        // Days = inclusive span between start and end dates.
        $validated['days'] = $this->computeDays($validated['from_date'], $validated['to_date']);
        // Strip the split-input helpers (not table columns).
        unset($validated['arrival_date'], $validated['arrival_time'], $validated['departure_date'], $validated['departure_time']);

        // Check for overlapping time ranges for the same student
        $overlapError = $this->checkOverlap(
            $validated['student_master_pk'],
            $validated['from_date'],
            $validated['to_date']
        );

        if ($overlapError) {
            // Modal (AJAX) submits expect 422 JSON so the error surfaces inline.
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => $overlapError,
                    'errors'  => ['from_date' => [$overlapError]],
                ], 422);
            }
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['from_date' => $overlapError]);
        }

        // Set default status to Active (1) if not provided
        if (!isset($validated['active_inactive'])) {
            $validated['active_inactive'] = 1;
        }

        $validated['created_by'] = auth()->id();

        // Handle file upload if exists
        if ($request->hasFile('Doc_upload')) {
            $file = $request->file('Doc_upload');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/exemptions', $filename, 'public');
            $validated['Doc_upload'] = $path;
        }

        $medicalExemption = StudentMedicalExemption::create($validated);

        // New row must appear immediately — drop the cached DataTable ID lists.
        $this->flushListCache();

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

    public function show($id)
    {
        $record = StudentMedicalExemption::with([
            'student',
            'category',
            'speciality',
            'course',
            'employee',
        ])->findOrFail(decrypt($id));

        $from = $record->from_date ? Carbon::parse($record->from_date) : null;
        $to = $record->to_date ? Carbon::parse($record->to_date) : null;

        $doctorName = 'N/A';
        if ($record->employee) {
            $doctorName = trim(($record->employee->first_name ?? '') . ' ' . ($record->employee->last_name ?? ''));
            if ($doctorName === '') {
                $doctorName = 'N/A';
            }
        }

        return response()->json([
            'course_name' => $record->course->course_name ?? 'N/A',
            'student_name' => $record->student->display_name ?? 'N/A',
            'ot_code' => $record->student->generated_OT_code ?? 'N/A',
            'doctor_name' => $doctorName,
            'category' => $record->category->exemp_category_name ?? 'N/A',
            'opd_category' => $record->opd_category ?? 'N/A',
            'arrival_date' => $from ? $from->format('d-m-Y') : 'N/A',
            'arrival_time' => $from ? $from->format('h:i A') : 'N/A',
            'departure_date' => $to ? $to->format('d-m-Y') : 'N/A',
            'departure_time' => $to ? $to->format('h:i A') : 'N/A',
            'speciality' => $record->speciality->speciality_name ?? 'N/A',
            'days' => $record->days ?? 'N/A',
            'description' => $record->Description ?: '—',
            'pt_outdoor_advise' => $record->pt_outdoor_advise ?: '—',
            'document_url' => $record->Doc_upload ? asset('storage/' . $record->Doc_upload) : null,
            'status' => $record->active_inactive == 1 ? 'Active' : 'Inactive',
            'created_date' => $record->created_date
                ? Carbon::parse($record->created_date)->format('d-m-Y H:i')
                : null,
        ]);
    }


    public function edit($id)
    {
        $record = StudentMedicalExemption::findOrFail(decrypt($id));
        $courses = CourseMaster::where('active_inactive', '1')
            ->where('end_date', '>', now())
            ->orderBy('course_name')
            ->get();

        // Ensure the record's own (possibly archived) course is selectable too.
        if ($record->course_master_pk && !$courses->contains('pk', $record->course_master_pk)) {
            $ownCourse = CourseMaster::find($record->course_master_pk);
            if ($ownCourse) {
                $courses->push($ownCourse);
            }
        }

        // Students of the record's course for the OT dropdown (the cascade reloads
        // this list when the course is changed).
        $students = $this->studentsForCourse($record->course_master_pk);

        $categories = ExemptionCategoryMaster::where('active_inactive', '1')->get();
        $specialities = ExemptionMedicalSpecialityMaster::where('active_inactive', '1')->get();

        // IPD / OPD / After OPD / Referral / … — driven by the Medical Case Master.
        $opdOptions = MedicalCaseMaster::where('active_inactive', 1)
            ->orderBy('pk')
            ->pluck('case_name')
            ->toArray();

        $doctors = $this->getDoctors();

        // Ensure the record's own (possibly inactive) doctor is selectable too.
        if ($record->employee_master_pk && !$doctors->contains('pk', $record->employee_master_pk)) {
            $ownDoctor = EmployeeMaster::find($record->employee_master_pk);
            if ($ownDoctor) {
                $doctors->push($ownDoctor);
            }
        }

        return view('admin.student_medical_exemption.edit', compact('record', 'courses', 'students', 'categories', 'specialities', 'opdOptions', 'doctors'));
    }
    public function update(Request $request, $id)
    {
        // Catch PHP-level upload errors (e.g. file exceeded upload_max_filesize in php.ini)
        if ($request->hasFile('Doc_upload')) {
            $uploadedFile = $request->file('Doc_upload');
            if (!$uploadedFile->isValid() && in_array($uploadedFile->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE])) {
                return response()->json([
                    'message' => 'The attachment file is too large. Please upload a file smaller than 5 MB.',
                    'errors'  => ['Doc_upload' => ['The attachment file is too large. Please upload a file smaller than 5 MB.']],
                ], 422);
            }
        }

        // Combine the split Arrival / Departure date + time inputs into datetimes.
        $request->merge([
            'from_date' => $this->combineDateTime($request->arrival_date, $request->arrival_time),
            'to_date'   => $this->combineDateTime($request->departure_date, $request->departure_time),
        ]);

        $validated = $request->validate([
            'course_master_pk' => 'required|numeric',
            'student_master_pk' => 'required|numeric',
            'employee_master_pk' => 'nullable|numeric',
            'exemption_category_master_pk' => 'nullable|numeric',
            'opd_category' => 'required|string|max:50',
            'arrival_date' => 'required|date',
            'arrival_time' => 'nullable',
            'departure_date' => 'nullable|date',
            'departure_time' => 'nullable',
            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'pt_outdoor_advise' => 'nullable|string|max:255',
            'exemption_medical_speciality_pk' => 'required|numeric',
            'Description' => 'nullable|string',
            'active_inactive' => 'required|boolean',
            'Doc_upload' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ], [
            'course_master_pk.required' => 'Please select a course.',
            'student_master_pk.required' => 'Please select an officer trainee.',
            'exemption_category_master_pk.required' => 'Please select an exemption category.',
            'exemption_medical_speciality_pk.required' => 'Please select a medical speciality.',
            'opd_category.required' => 'Please select Medical Case.',
            'arrival_date.required' => 'The start date is required.',
            'to_date.after_or_equal' => 'The end date/time must be after the start date/time.',
            'Doc_upload.file'  => 'The attachment must be a valid file.',
            'Doc_upload.mimes' => 'Only PDF, JPG, JPEG, PNG, DOC, or DOCX files are allowed.',
            'Doc_upload.max'   => 'The attachment file must not exceed 5 MB (5120 KB).',
        ]);

        $validated['days'] = $this->computeDays($validated['from_date'], $validated['to_date']);
        unset($validated['arrival_date'], $validated['arrival_time'], $validated['departure_date'], $validated['departure_time']);

        $record = StudentMedicalExemption::findOrFail(decrypt($id));

        // Check for overlapping time ranges for the same student (excluding current record)
        $overlapError = $this->checkOverlap(
            $validated['student_master_pk'],
            $validated['from_date'],
            $validated['to_date'],
            $record->pk
        );

        if ($overlapError) {
            // Modal (AJAX) submits expect 422 JSON so the error surfaces inline.
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => $overlapError,
                    'errors'  => ['from_date' => [$overlapError]],
                ], 422);
            }
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

        // Reflect the edit immediately in the DataTable.
        $this->flushListCache();

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
        StudentMedicalExemption::destroy(decrypt($id));

        // Removed row must disappear from the DataTable right away.
        $this->flushListCache();

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
        $opdCategoryFilter = $request->get('opd_category_filter');
        $exemptionCategoryFilter = $request->get('exemption_category_filter');
        $format = strtolower((string) $request->get('format', 'excel'));

        if (! in_array($format, ['csv', 'excel', 'xlsx', 'pdf'], true)) {
            $format = 'excel';
        }

        $export = new StudentMedicalExemptionExport($filter, $courseFilter, $search, $fromDateFilter, $toDateFilter, $opdCategoryFilter, $exemptionCategoryFilter);
        $fileName = 'medical-exemption-export-' . now()->format('Y-m-d_H-i-s');

        if ($format === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            $header = $this->buildExportHeaderData($courseFilter);

            $pdf = Pdf::loadView('admin.student_medical_exemption.export_pdf', array_merge([
                'headings' => $export->columnHeadings(),
                'rows' => $export->pdfRows(),
                'filterLine' => $this->buildExportFilterLine($request),
                'printedOn' => now()->format('d-m-Y H:i'),
                'reportTitle' => 'Student Medical Exemption',
            ], $header))
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'isPhpEnabled' => true,
                    'dpi' => 96,
                ]);

            return $pdf->download($fileName . '.pdf');
        }

        // Styled workbook (logos, blue header band, bordered zebra rows) so the
        // download visually matches the Print / PDF layout — a plain CSV can't.
        return Excel::download($export, $fileName . '.xlsx', ExcelFormat::XLSX);
    }

    /**
     * Branded LBSNAA header assets for the PDF export — the same emblem / Hindi
     * title / 75-years logo and course line used by the official report layout.
     *
     * @return array{logoLeft:?string,logoRight:?string,titleHindi:?string,courseName:string,courseDuration:string}
     */
    private function buildExportHeaderData($courseFilter): array
    {
        $toDataUri = static function (string $path): ?string {
            if (! is_file($path) || ! is_readable($path)) {
                return null;
            }
            $raw = @file_get_contents($path);
            if ($raw === false) {
                return null;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                default => 'image/png',
            };

            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        };

        $courseName = '';
        $courseDuration = '';
        if (! empty($courseFilter)) {
            $course = CourseMaster::find($courseFilter);
            if ($course) {
                $courseName = (string) ($course->course_name ?? '');
                $start = ! empty($course->start_date ?? $course->start_year ?? null)
                    ? Carbon::parse($course->start_date ?? $course->start_year)->format('j F Y') : '';
                $end = ! empty($course->end_date)
                    ? Carbon::parse($course->end_date)->format('j F Y') : '';
                $courseDuration = ($start && $end) ? $start . ' to ' . $end : '';
            }
        }

        return [
            'logoLeft' => $toDataUri(public_path('admin_assets/images/logos/logo_new.png')),
            'logoRight' => $toDataUri(public_path('admin_assets/images/logos/constitution-75.png'))
                ?: $toDataUri(public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png')),
            'titleHindi' => $toDataUri(public_path('admin_assets/images/logos/lbsnaa-title-hi.png')),
            'courseName' => $courseName,
            'courseDuration' => $courseDuration,
        ];
    }

    private function buildExportFilterLine(Request $request): string
    {
        $parts = [];

        $status = $request->get('filter', 'active');
        $parts[] = 'Status: ' . ($status === 'archive' ? 'Archived' : 'Active');

        if ($request->filled('course_filter')) {
            $course = CourseMaster::find($request->course_filter);
            $parts[] = 'Course: ' . ($course->course_name ?? $request->course_filter);
        }

        if ($request->filled('opd_category_filter')) {
            $parts[] = 'Medical Case: ' . $request->opd_category_filter;
        }

        if ($request->filled('exemption_category_filter')) {
            $category = ExemptionCategoryMaster::find($request->exemption_category_filter);
            $parts[] = 'Exemption Category: ' . ($category->exemp_category_name ?? $request->exemption_category_filter);
        }

        if ($request->filled('search')) {
            $parts[] = 'Search: ' . $request->search;
        }

        if ($request->filled('from_date_filter') || $request->filled('to_date_filter')) {
            $parts[] = 'Period: ' . ($request->from_date_filter ?: '…') . ' to ' . ($request->to_date_filter ?: '…');
        }

        return implode(' | ', $parts);
    }
}
