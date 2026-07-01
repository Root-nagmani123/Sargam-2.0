<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CadreMaster;
use App\Models\CourseMaster;
use App\Models\ServiceMaster;
use App\Models\StudentMaster;
use App\Models\StudentMasterCourseMap;
use App\Models\State;
use App\Support\DataTableRedisCache;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WhosWhoController extends Controller
{
    /**
     * Display the Who's Who page
     */
    public function index()
    {
        $cacheKey = 'whos_who_courses:v1:' . Carbon::now()->format('Y-m-d');
        $courses = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'FACULTY_WHOS_WHO_CACHE_ENABLED',
                'seconds' => 'FACULTY_WHOS_WHO_CACHE_SECONDS',
            ],
            'WhosWhoController@index',
            fn () => $this->queryActiveCoursesForWhosWho()
        );

        $cadres = CadreMaster::orderBy('cadre_name')->get(['pk', 'cadre_name']);
        $services = ServiceMaster::orderBy('service_name')->get(['pk', 'service_name']);

        return view('admin.faculty.whos_who', compact('courses', 'cadres', 'services'));
    }

    /**
     * Get courses list (AJAX)
     */
    public function getCourses()
    {
        try {
            $cacheKey = 'whos_who_courses:v1:' . Carbon::now()->format('Y-m-d');
            $courses = DataTableRedisCache::remember(
                $cacheKey,
                [
                    'enabled' => 'FACULTY_WHOS_WHO_CACHE_ENABLED',
                    'seconds' => 'FACULTY_WHOS_WHO_CACHE_SECONDS',
                ],
                'WhosWhoController@getCourses',
                fn () => $this->queryActiveCoursesForWhosWho()
            );

            return response()->json([
                'success' => true,
                'courses' => $courses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching courses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Active courses for Who's Who (shared cache key with {@see getCourses}).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\CourseMaster>
     */
    private function queryActiveCoursesForWhosWho()
    {
        $currentDate = Carbon::now()->format('Y-m-d');

        return CourseMaster::where('active_inactive', 1)
            ->where('end_date', '>=', $currentDate)
            ->orderBy('course_name')
            ->get(['pk', 'course_name', 'couse_short_name']);
    }

    /**
     * Get students by filters (AJAX)
     * 
     * This method uses student_master_course__map table to get students enrolled in courses.
     * Each row in student_master_course__map represents a student enrolled in a specific course.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStudents(Request $request)
    {
        try {
            $name = $request->input('name', '');
            $courseId = $request->input('course_id', '');
            $cadreId = $request->input('cadre_id', '');
            $serviceId = $request->input('service_id', '');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $sortBy = $request->input('sort_by', 'name_asc');

            // Convert course_id to integer if provided and validate
            if (!empty($courseId)) {
                $courseId = (int) $courseId;
                if ($courseId <= 0) {
                    $courseId = '';
                }
            } else {
                $courseId = '';
            }

            if (!empty($cadreId) && (int) $cadreId > 0) {
                $cadreId = (int) $cadreId;
            } else {
                $cadreId = '';
            }

            if (!empty($serviceId) && (int) $serviceId > 0) {
                $serviceId = (int) $serviceId;
            } else {
                $serviceId = '';
            }

            if (!empty($courseId) && $courseId > 0) {
                if (! CourseMaster::where('pk', $courseId)->where('active_inactive', 1)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected course not found or inactive',
                        'students' => [],
                        'count' => 0,
                    ]);
                }
            }

            $cacheKey = 'whos_who_students:v2:' . md5(json_encode([
                'name' => $name,
                'course_id' => $courseId,
                'cadre_id' => $cadreId,
                'service_id' => $serviceId,
                'page' => $page,
                'per_page' => $perPage,
                'sort_by' => $sortBy,
            ]));

            return response()->json(
                DataTableRedisCache::remember(
                    $cacheKey,
                    [
                        'enabled' => 'FACULTY_WHOS_WHO_CACHE_ENABLED',
                        'seconds' => 'FACULTY_WHOS_WHO_STUDENTS_CACHE_SECONDS',
                    ],
                    'WhosWhoController@getStudents',
                    fn () => $this->buildStudentsResponsePayload($request)
                )
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching students: ' . $e->getMessage(),
                'error' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Heavy Who's Who student grid payload (cached JSON shape).
     *
     * @return array<string, mixed>
     */
    private function buildStudentsResponsePayload(Request $request): array
    {
        $name = $request->input('name', '');
        $courseId = $request->input('course_id', '');
        $cadreId = $request->input('cadre_id', '');
        $serviceId = $request->input('service_id', '');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'name_asc');
        $forExport = filter_var($request->input('for_export'), FILTER_VALIDATE_BOOLEAN);

        // Convert course_id to integer if provided and validate
        if (!empty($courseId) && $courseId > 0) {
            $courseId = (int) $courseId;
        } else {
            $courseId = '';
        }

        if (!empty($cadreId) && (int) $cadreId > 0) {
            $cadreId = (int) $cadreId;
        } else {
            $cadreId = '';
        }

        if (!empty($serviceId) && (int) $serviceId > 0) {
            $serviceId = (int) $serviceId;
        } else {
            $serviceId = '';
        }

        /**
         * Query starts from student_master_course__map table
         * This table links students (student_master_pk) to courses (course_master_pk)
         * Each record represents one student enrolled in one course
         */
        $query = StudentMasterCourseMap::with([
            'studentMaster.service',  // Join with student_master and service_master
            'studentMaster.cadre',    // Join with student_master and cadre_master
            'course',                  // Join with course_master
        ])
            ->where('student_master_course__map.active_inactive', 1) // Only active enrollments in student_master_course__map
            ->whereHas('studentMaster', function ($q) {
                $q->where('status', 1); // Only active students from student_master
            })
            ->whereHas('course', function ($q) {
                $q->where('active_inactive', 1); // Only active courses from course_master
            });

        /**
         * Filter by course using course_master_pk from student_master_course__map
         * When a course is selected, we filter by course_master_pk column in the mapping table
         * This ensures we only get students enrolled in that specific course
         */
        if (!empty($courseId) && $courseId > 0) {
            $query->where('student_master_course__map.course_master_pk', $courseId);
        }

        // Filter by name
        if (!empty($name)) {
            $query->whereHas('studentMaster', function ($q) use ($name) {
                $q->where('display_name', 'like', '%' . $name . '%')
                    ->orWhere('first_name', 'like', '%' . $name . '%')
                    ->orWhere('last_name', 'like', '%' . $name . '%')
                    ->orWhere('generated_OT_code', 'like', '%' . $name . '%');
            });
        }

        // Filter by cadre
        if (!empty($cadreId)) {
            $query->whereHas('studentMaster', function ($q) use ($cadreId) {
                $q->where('cadre_master_pk', $cadreId);
            });
        }

        // Filter by service
        if (!empty($serviceId)) {
            $query->whereHas('studentMaster', function ($q) use ($serviceId) {
                $q->where('service_master_pk', $serviceId);
            });
        }

        // Log query parameters for debugging
        \Log::info('Who\'s Who Query - Using student_master_course__map', [
            'course_id' => $courseId,
            'name' => $name,
            'cadre_id' => $cadreId,
            'service_id' => $serviceId,
            'table' => 'student_master_course__map',
            'filter_column' => 'course_master_pk',
        ]);

        // Get total count before pagination and sorting modifications
        // Count before adding joins to avoid duplicate counting issues
        $totalCount = $query->count();

        // Apply pagination
        $currentPage = max(1, (int) $page);
        if ($forExport) {
            $perPage = max(1, $totalCount);
            $totalPages = 1;
            $currentPage = 1;
            $sortBy = 'roll_asc';
        } else {
            $perPage = max(1, min(100, (int) $perPage));
            $totalPages = ceil($totalCount / $perPage);
        }

        // Ensure current page is valid
        if (!$forExport && $currentPage > $totalPages && $totalPages > 0) {
            $currentPage = $totalPages;
        }

        // Apply sorting based on sort_by parameter
        // Use joins for sorting to avoid N+1 queries
        $sortColumn = 'student_master_pk';
        $sortDirection = 'asc';

        if (!empty($sortBy)) {
            switch ($sortBy) {
                case 'name_asc':
                    $query->select('student_master_course__map.*')
                        ->join('student_master', 'student_master_course__map.student_master_pk', '=', 'student_master.pk')
                        ->orderBy('student_master.display_name', 'asc')
                        ->orderBy('student_master.first_name', 'asc')
                        ->orderBy('student_master.last_name', 'asc');
                    $sortColumn = 'display_name';
                    $sortDirection = 'asc';
                    break;
                case 'name_desc':
                    $query->select('student_master_course__map.*')
                        ->join('student_master', 'student_master_course__map.student_master_pk', '=', 'student_master.pk')
                        ->orderBy('student_master.display_name', 'desc')
                        ->orderBy('student_master.first_name', 'desc')
                        ->orderBy('student_master.last_name', 'desc');
                    $sortColumn = 'display_name';
                    $sortDirection = 'desc';
                    break;
                case 'roll_asc':
                    $query->select('student_master_course__map.*')
                        ->join('student_master', 'student_master_course__map.student_master_pk', '=', 'student_master.pk')
                        ->orderBy('student_master.rank', 'asc')
                        ->orderBy('student_master.display_name', 'asc');
                    $sortColumn = 'rank';
                    $sortDirection = 'asc';
                    break;
                case 'roll_desc':
                    $query->select('student_master_course__map.*')
                        ->join('student_master', 'student_master_course__map.student_master_pk', '=', 'student_master.pk')
                        ->orderBy('student_master.rank', 'desc')
                        ->orderBy('student_master.display_name', 'desc');
                    $sortColumn = 'rank';
                    $sortDirection = 'desc';
                    break;
                case 'service_asc':
                    $query->select('student_master_course__map.*')
                        ->join('student_master', 'student_master_course__map.student_master_pk', '=', 'student_master.pk')
                        ->leftJoin('service_master', 'student_master.service_master_pk', '=', 'service_master.pk')
                        ->orderBy('service_master.service_name', 'asc')
                        ->orderBy('student_master.display_name', 'asc');
                    $sortColumn = 'service_name';
                    $sortDirection = 'asc';
                    break;
                case 'service_desc':
                    $query->select('student_master_course__map.*')
                        ->join('student_master', 'student_master_course__map.student_master_pk', '=', 'student_master.pk')
                        ->leftJoin('service_master', 'student_master.service_master_pk', '=', 'service_master.pk')
                        ->orderBy('service_master.service_name', 'desc')
                        ->orderBy('student_master.display_name', 'desc');
                    $sortColumn = 'service_name';
                    $sortDirection = 'desc';
                    break;
                case 'course_asc':
                    $query->select('student_master_course__map.*')
                        ->join('course_master', 'student_master_course__map.course_master_pk', '=', 'course_master.pk')
                        ->orderBy('course_master.course_name', 'asc')
                        ->orderBy('student_master_course__map.student_master_pk', 'asc');
                    $sortColumn = 'course_name';
                    $sortDirection = 'asc';
                    break;
                case 'course_desc':
                    $query->select('student_master_course__map.*')
                        ->join('course_master', 'student_master_course__map.course_master_pk', '=', 'course_master.pk')
                        ->orderBy('course_master.course_name', 'desc')
                        ->orderBy('student_master_course__map.student_master_pk', 'desc');
                    $sortColumn = 'course_name';
                    $sortDirection = 'desc';
                    break;
                default:
                    $query->orderBy('student_master_course__map.pk', 'asc');
            }
        } else {
            $query->orderBy('student_master_course__map.pk', 'asc');
        }

        // Execute query with pagination - results are from student_master_course__map table
        // Each result represents a student enrolled in a course
        // Note: The with() relationships are still loaded even after joins because we select student_master_course__map.*
        // Joins are on primary keys (one-to-one), so no duplicates should occur
        $studentMaps = $query->offset(($currentPage - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $studentPks = $studentMaps->pluck('student_master_pk')->unique()->filter()->values();
        $counsellorHouseLookup = $studentPks->isNotEmpty()
            ? $this->loadCounsellorAndHouseLookup($studentPks)
            : collect();

        $categoryPks = $studentMaps
            ->map(fn ($map) => $map->studentMaster?->admission_category_pk)
            ->filter()
            ->unique()
            ->values();
        $categoriesByPk = $categoryPks->isNotEmpty()
            ? DB::table('admission_category_master')->whereIn('pk', $categoryPks)->get()->keyBy('pk')
            : collect();

        $statePks = $studentMaps
            ->map(fn ($map) => $map->studentMaster?->state_master_pk)
            ->filter()
            ->unique()
            ->values();
        $statesByPk = $statePks->isNotEmpty()
            ? State::whereIn('pk', $statePks)->pluck('state_name', 'pk')
            : collect();

        $streamPks = $studentMaps
            ->map(fn ($map) => $map->studentMaster?->highest_stream_pk)
            ->filter()
            ->unique()
            ->values();
        $streamsByPk = $streamPks->isNotEmpty()
            ? DB::table('stream_master')->whereIn('pk', $streamPks)->pluck('stream_name', 'pk')
            : collect();

        // Log results
        \Log::info('Who\'s Who Query Results from student_master_course__map', [
            'total_count' => $totalCount,
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'returned_count' => $studentMaps->count(),
            'course_filter_applied' => !empty($courseId),
        ]);

        /**
         * Format the data from student_master_course__map results
         * Each $map entry is a record from student_master_course__map table containing:
         * - student_master_pk: The student ID
         * - course_master_pk: The course ID (this is what we filter by)
         * - active_inactive: Enrollment status
         */
        $students = [];
        foreach ($studentMaps as $map) {
            // Get student details via relationship from student_master table
            $student = $map->studentMaster;
            // Get course details via relationship from course_master table
            // This course is the one the student is enrolled in (from course_master_pk in the map)
            $course = $map->course;

            // Skip if student data is missing
            if (!$student) {
                continue;
            }

            // If course filter is applied but course is missing, skip this record
            if (!empty($courseId) && !$course) {
                continue;
            }

            // If no course filter and course is missing, try to get first active course for this student
            if (!$course && empty($courseId)) {
                $firstCourseMap = StudentMasterCourseMap::with('course')
                    ->where('student_master_pk', $student->pk)
                    ->where('active_inactive', 1)
                    ->whereHas('course', function ($q) {
                        $q->where('active_inactive', 1);
                    })
                    ->first();
                $course = $firstCourseMap ? $firstCourseMap->course : null;
            }

            // Skip if still no course found
            if (!$course) {
                continue;
            }

            /**
             * Get all courses this student is enrolled in from student_master_course__map
             */
            $enrolledCourses = collect();
            if (!$forExport) {
                $enrolledCourses = StudentMasterCourseMap::with('course')
                    ->where('student_master_pk', $student->pk)
                    ->where('active_inactive', 1)
                    ->whereHas('course', function ($q) {
                        $q->where('active_inactive', 1);
                    })
                    ->get();
            }

            // Format education (if available in database, otherwise empty)
            $education = [];
            // You can add education data from a separate table if available

            // Format hobbies (if available in database, otherwise empty)
            $hobbies = [];
            // You can add hobbies data from a separate table if available

            $counsellorName = 'N/A';
            $houseName = 'N/A';
            $lookupKey = $student->pk . '_' . $map->course_master_pk;
            $groupInfo = $counsellorHouseLookup->get($lookupKey);
            if ($groupInfo) {
                $counsellorName = filled($groupInfo->counsellor_name)
                    ? $groupInfo->counsellor_name
                    : 'N/A';
                $houseName = filled($groupInfo->house_group)
                    ? $groupInfo->house_group
                    : 'N/A';
            }

            // Domicile state: state_master.state_name via student_master.state_master_pk
            $stateName = 'N/A';
            if ($student->state_master_pk) {
                $stateName = $statesByPk[$student->state_master_pk] ?? 'N/A';
            }

            // District column maps to student_master.city (per Who's Who SQL)
            $cityName = filled($student->city) ? $student->city : 'N/A';

            $categoryName = 'N/A';
            if ($student->admission_category_pk && isset($categoriesByPk[$student->admission_category_pk])) {
                $categoryRow = $categoriesByPk[$student->admission_category_pk];
                $categoryName = $categoryRow->Seat_name ?? $categoryRow->seat_name ?? 'N/A';
            }

            $fullAddress = filled($student->address) ? $student->address : 'N/A';

            $streamName = 'N/A';
            if ($student->highest_stream_pk && isset($streamsByPk[$student->highest_stream_pk])) {
                $streamName = $streamsByPk[$student->highest_stream_pk];
            }

            $cadreName = $student->cadre->cadre_name ?? null;

            // Format batch
            $batch = 'N/A';
            if ($course && $course->start_year) {
                $endYear = $course->end_date ? Carbon::parse($course->end_date)->format('Y') : (Carbon::parse($course->start_year)->addYear()->format('Y'));
                $batch = $course->start_year . '-' . $endYear;
            }

            $students[] = [
                'id' => $student->generated_OT_code ?? ('STU-' . $student->pk),
                'name' => $student->display_name ?? (trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))),
                'rank' => $student->rank ?? 'N/A',
                'cadre' => filled($cadreName) ? $cadreName : 'N/A',
                'code' => $student->generated_OT_code ?? 'N/A',
                'counsellor' => $counsellorName,
                'house' => $houseName,
                'roll' => 'Roll ' . ($student->rank ?? 'N/A'),
                'service' => $student->service->service_name ?? 'N/A',
                'courseName' => $course->course_name ?? 'N/A',
                'courseCode' => $course->couse_short_name ?? $course->course_name ?? 'N/A',
                'batch' => $batch,
                'image' => $student->photo_path ? asset('storage/' . $student->photo_path) : 'https://via.placeholder.com/180x180?text=' . urlencode(substr($student->display_name ?? 'Student', 0, 1)),
                'image_src' => $forExport ? $this->resolveStudentPhotoDataUri($student->photo_path) : null,
                'dob' => $student->dob ? Carbon::parse($student->dob)->format('d-M-y') : 'N/A',
                'domicile' => strtoupper($stateName),
                'district' => strtoupper($cityName),
                'category' => strtoupper($categoryName),
                'address' => $fullAddress,
                'attempts' => $student->rank ?? 'N/A',
                'stream' => $streamName,
                'room' => $student->room_no ?? 'N/A',
                'email' => $student->email ?? 'N/A',
                'contact' => $student->contact_no ?? 'N/A',
                'lastService' => $student->last_service_pk ?? 'N/A',
                'hobbies' => $hobbies,
                'education' => $education,
                'enrolledCourses' => $enrolledCourses->map(function ($ec) {
                    return [
                        'courseName' => $ec->course->course_name ?? 'N/A',
                        'courseCode' => $ec->course->couse_short_name ?? 'N/A',
                        'courseId' => $ec->course_master_pk ?? null,
                    ];
                })->toArray(),
                // Store the mapping table primary key for reference
                'courseMapPk' => $map->pk,
                'courseMasterPk' => $map->course_master_pk,
                'studentMasterPk' => $map->student_master_pk,
            ];
        }

        return [
            'success' => true,
            'students' => $students,
            'pagination' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total' => $totalCount,
                'total_pages' => $totalPages,
                'from' => $totalCount > 0 ? (($currentPage - 1) * $perPage) + 1 : 0,
                'to' => min($currentPage * $perPage, $totalCount),
                'has_more' => $currentPage < $totalPages,
            ],
            'sort' => [
                'sort_by' => $sortBy,
                'sort_column' => $sortColumn,
                'sort_direction' => $sortDirection,
            ],
            'filters' => [
                'course_id' => $courseId,
                'name' => $name,
                'cadre_id' => $cadreId,
                'service_id' => $serviceId,
            ],
        ];
    }
    /**
     * Download Who's Who directory PDF (respects current filters).
     */
    public function downloadPdf(Request $request)
    {
        try {
            $exportRequest = $request->duplicate();
            $exportRequest->merge([
                'for_export' => true,
                'sort_by' => 'roll_asc',
            ]);

            $payload = $this->buildStudentsResponsePayload($exportRequest);
            $students = $payload['students'] ?? [];

            if (empty($students)) {
                return redirect()
                    ->route('admin.faculty.whos-who')
                    ->with('error', 'No students found for the selected filters.');
            }

            $courseId = $request->input('course_id', '');
            $cadreId = $request->input('cadre_id', '');
            $serviceId = $request->input('service_id', '');
            $search = trim((string) $request->input('name', ''));

            $courseLabel = $courseId
                ? (optional(CourseMaster::find((int) $courseId))->course_name ?? 'Selected Course')
                : 'All Courses';
            $cadreLabel = $cadreId
                ? (optional(CadreMaster::find((int) $cadreId))->cadre_name ?? 'Selected Cadre')
                : 'All Cadres';
            $serviceLabel = $serviceId
                ? (optional(ServiceMaster::find((int) $serviceId))->service_name ?? 'Selected Service')
                : 'All Services';

            $pdf = Pdf::loadView('admin.faculty.whos_who_pdf', [
                'students'     => $students,
                'courseLabel'  => $courseLabel,
                'cadreLabel'   => $cadreLabel,
                'serviceLabel' => $serviceLabel,
                'searchLabel'  => $search,
                'generatedAt'  => Carbon::now()->format('d M Y, h:i A'),
            ])
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont'          => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => true,
                    'dpi'                  => 96,
                ]);

            $filename = 'whos-who-' . now()->format('Y-m-d_H-i-s') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.faculty.whos-who')
                ->with('error', 'Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Counsellor name and house group per student/course (matches Who's Who SQL logic).
     *
     * @param  \Illuminate\Support\Collection<int, int|string>  $studentPks
     * @return \Illuminate\Support\Collection<string, object>
     */
    private function loadCounsellorAndHouseLookup($studentPks)
    {
        $rows = DB::table('student_course_group_map as c')
            ->join('group_type_master_course_master_map as d', 'c.group_type_master_course_master_map_pk', '=', 'd.pk')
            ->leftJoin('course_group_type_master as e', 'd.type_name', '=', 'e.pk')
            ->leftJoin('faculty_master as cf', 'cf.pk', '=', 'd.facility_id')
            ->whereIn('c.student_master_pk', $studentPks)
            ->where('c.active_inactive', 1)
            ->select([
                'c.student_master_pk',
                'd.course_name as course_master_pk',
                DB::raw("MAX(CASE WHEN e.type_name LIKE '%Counsellor%' THEN TRIM(CONCAT_WS(' ', cf.first_name, cf.last_name)) END) AS counsellor_name"),
                DB::raw("MAX(CASE WHEN e.type_name LIKE '%Counsellor%' THEN cf.full_name END) AS counsellor_full_name"),
                DB::raw("MAX(CASE WHEN e.type_name LIKE '%Counsellor%' THEN d.group_name END) AS counsellor_group_state"),
                DB::raw("MAX(CASE WHEN e.type_name LIKE '%House%' THEN d.group_name END) AS house_group"),
            ])
            ->groupBy('c.student_master_pk', 'd.course_name')
            ->get();

        return $rows->mapWithKeys(function ($row) {
            $counsellorName = trim((string) ($row->counsellor_name ?? ''));
            if ($counsellorName === '') {
                $counsellorName = trim((string) ($row->counsellor_full_name ?? ''));
            }
            $row->counsellor_name = $counsellorName !== '' ? $counsellorName : null;

            return [$row->student_master_pk . '_' . $row->course_master_pk => $row];
        });
    }

    /**
     * Embed student photo for DomPDF rendering.
     */
    private function resolveStudentPhotoDataUri(?string $photoPath): ?string
    {
        if (empty($photoPath)) {
            return null;
        }

        foreach ([
            storage_path('app/public/' . ltrim($photoPath, '/')),
            public_path('storage/' . ltrim($photoPath, '/')),
        ] as $fullPath) {
            if (!is_file($fullPath) || !is_readable($fullPath)) {
                continue;
            }

            $raw = @file_get_contents($fullPath);
            if ($raw === false) {
                continue;
            }

            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                default => 'image/jpeg',
            };

            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        }

        return null;
    }

    /**
     * Get static info (tutor group, tutor name, house name, house tutors)
     * This can be customized based on your database structure
     */
    public function getStaticInfo(Request $request)
    {
        try {
            $courseId = $request->input('course_id', '');
            $cacheKey = 'whos_who_static:v1:' . md5((string) $courseId) . ':' . Carbon::now()->format('Y-m-d');

            $body = DataTableRedisCache::remember(
                $cacheKey,
                [
                    'enabled' => 'FACULTY_WHOS_WHO_CACHE_ENABLED',
                    'seconds' => 'FACULTY_WHOS_WHO_CACHE_SECONDS',
                ],
                'WhosWhoController@getStaticInfo',
                function () {
                    $staticInfo = [
                        'tutorGroup' => '0',
                        'tutorName' => 'N/A',
                        'houseName' => 'N/A',
                        'houseTutors' => 'N/A',
                    ];

                    return [
                        'success' => true,
                        'data' => $staticInfo,
                    ];
                }
            );

            return response()->json($body);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching static info: ' . $e->getMessage()
            ], 500);
        }
    }
}
