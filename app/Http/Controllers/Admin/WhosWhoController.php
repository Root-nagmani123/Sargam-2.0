<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\StudentMaster;
use App\Models\StudentMasterCourseMap;
use App\Models\State;
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
        // Get active courses for the dropdown
        $currentDate = Carbon::now()->format('Y-m-d');
        $courses = CourseMaster::where('active_inactive', 1)
            ->where('end_date', '>=', $currentDate)
            ->orderBy('course_name')
            ->get(['pk', 'course_name', 'couse_short_name']);

        return view('admin.faculty.whos_who', compact('courses'));
    }

    /**
     * Get courses list (AJAX)
     * Supports course_type: 'active' (end_date >= today) or 'archive' (end_date < today)
     */
    public function getCourses(Request $request)
    {
        try {
            $courseType = $request->input('course_type', 'active');
            $currentDate = Carbon::now()->format('Y-m-d');

            $query = CourseMaster::where('active_inactive', 1);

            if ($courseType === 'archive') {
                $query->where('end_date', '<', $currentDate);
            } else {
                $query->where('end_date', '>=', $currentDate);
            }

            $courses = $query->orderBy('course_name')
                ->get(['pk', 'course_name', 'couse_short_name']);

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
     * Get cadres list for filter (AJAX)
     * Filtered by course_type and optionally course_id
     */
    public function getCadres(Request $request)
    {
        try {
            $courseType = $request->input('course_type', 'active');
            $courseId = $request->input('course_id', '');
            $currentDate = Carbon::now()->format('Y-m-d');

            $studentPks = StudentMasterCourseMap::where('active_inactive', 1)
                ->whereHas('studentMaster', fn($q) => $q->where('status', 1))
                ->whereHas('course', function($q) use ($courseType, $currentDate) {
                    $q->where('active_inactive', 1);
                    $courseType === 'archive'
                        ? $q->where('end_date', '<', $currentDate)
                        : $q->where('end_date', '>=', $currentDate);
                })
                ->when($courseId, fn($q) => $q->where('course_master_pk', $courseId))
                ->pluck('student_master_pk')
                ->unique();

            $cadres = DB::table('cadre_master as c')
                ->join('student_master as sm', 'sm.cadre_master_pk', '=', 'c.pk')
                ->whereIn('sm.pk', $studentPks)
                ->whereNotNull('sm.cadre_master_pk')
                ->select('c.pk', 'c.cadre_name')
                ->distinct()
                ->orderBy('c.cadre_name')
                ->get();

            return response()->json(['success' => true, 'cadres' => $cadres]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching cadres: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get counsellor groups list for filter (AJAX)
     * Returns groups from group_type_master_course_master_map, filtered by course_type and optionally course_id
     */
    public function getCounsellorGroups(Request $request)
    {
        try {
            $courseType = $request->input('course_type', 'active');
            $courseId = $request->input('course_id', '');
            $currentDate = Carbon::now()->format('Y-m-d');

            $query = DB::table('group_type_master_course_master_map as gmap')
                ->join('course_master as cm', 'gmap.course_name', '=', 'cm.pk')
                ->leftJoin('course_group_type_master as cgt', 'gmap.type_name', '=', 'cgt.pk')
                ->where('gmap.active_inactive', 1)
                ->where('cm.active_inactive', 1)
                ->whereNotNull('gmap.group_name')
                ->where('gmap.group_name', '!=', '');

            if ($courseType === 'archive') {
                $query->where('cm.end_date', '<', $currentDate);
            } else {
                $query->where('cm.end_date', '>=', $currentDate);
            }
            if ($courseId) {
                $query->where('gmap.course_name', $courseId);
            }

            $groups = $query
                ->select('gmap.pk as group_pk', 'gmap.group_name', 'cgt.type_name as counsellor_type_name')
                ->distinct()
                ->orderBy('gmap.group_name')
                ->get();

            return response()->json(['success' => true, 'groups' => $groups]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching counsellor groups: ' . $e->getMessage()
            ], 500);
        }
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
            $courseType = $request->input('course_type', 'active');
            $cadreId = $request->input('cadre_id', '');
            $groupId = $request->input('group_id', '');
            $category = $request->input('category', '');
            $status = $request->input('status', '');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $sortBy = $request->input('sort_by', 'name_asc'); // Default sort by name ascending

            // Convert course_id to integer if provided and validate
            if (!empty($courseId)) {
                $courseId = (int) $courseId;
                if ($courseId <= 0) {
                    $courseId = '';
                }
            } else {
                $courseId = '';
            }

            /**
             * Query starts from student_master_course__map table
             * This table links students (student_master_pk) to courses (course_master_pk)
             * Each record represents one student enrolled in one course
             */
            $query = StudentMasterCourseMap::with([
                'studentMaster.service',  // Join with student_master and service_master
                'studentMaster.cadre',    // Join with student_master and cadre_master
                'course'                  // Join with course_master
            ])
            ->where('student_master_course__map.active_inactive', 1) // Only active enrollments in student_master_course__map
            ->whereHas('studentMaster', function($q) {
                $q->where('status', 1); // Only active students from student_master
            })
            ->whereHas('course', function($q) use ($courseType) {
                $q->where('active_inactive', 1);
                $currentDate = Carbon::now()->format('Y-m-d');
                if ($courseType === 'archive') {
                    $q->where('end_date', '<', $currentDate);
                } else {
                    $q->where('end_date', '>=', $currentDate);
                }
            });

            /**
             * Filter by course using course_master_pk from student_master_course__map
             * When a course is selected, we filter by course_master_pk column in the mapping table
             * This ensures we only get students enrolled in that specific course
             */
            if (!empty($courseId) && $courseId > 0) {
                // Filter by course_master_pk column in student_master_course__map table
                $query->where('student_master_course__map.course_master_pk', $courseId);
                
                // Verify the course exists and matches the course type (active/archive)
                $currentDate = Carbon::now()->format('Y-m-d');
                $courseQuery = CourseMaster::where('pk', $courseId)->where('active_inactive', 1);
                if ($courseType === 'archive') {
                    $courseQuery->where('end_date', '<', $currentDate);
                } else {
                    $courseQuery->where('end_date', '>=', $currentDate);
                }
                $courseExists = $courseQuery->exists();
                    
                if (!$courseExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected course not found or inactive',
                        'students' => [],
                        'count' => 0
                    ]);
                }
            }

            // Filter by name
            if (!empty($name)) {
                $query->whereHas('studentMaster', function($q) use ($name) {
                    $q->where('display_name', 'like', '%' . $name . '%')
                      ->orWhere('first_name', 'like', '%' . $name . '%')
                      ->orWhere('last_name', 'like', '%' . $name . '%')
                      ->orWhere('generated_OT_code', 'like', '%' . $name . '%');
                });
            }

            // Filter by cadre
            if (!empty($cadreId) && $cadreId > 0) {
                $query->whereHas('studentMaster', function($q) use ($cadreId) {
                    $q->where('cadre_master_pk', $cadreId);
                });
            }

            // Filter by counsellor group (student must be in this group for the course)
            if (!empty($groupId) && $groupId > 0) {
                $query->whereExists(function($sub) use ($groupId) {
                    $sub->select(DB::raw(1))
                        ->from('student_course_group_map as scgm')
                        ->whereColumn('scgm.student_master_pk', 'student_master_course__map.student_master_pk')
                        ->where('scgm.group_type_master_course_master_map_pk', $groupId);
                });
            }

            // Filter by category (service)
            if (!empty($category)) {
                $query->whereHas('studentMaster.service', function($q) use ($category) {
                    $q->where('service_name', 'like', '%' . $category . '%');
                });
            }

            // Filter by status
            if (!empty($status)) {
                $query->whereHas('studentMaster', function($q) use ($status) {
                    $q->where('status', $status);
                });
            }

            // Log query parameters for debugging
            \Log::info('Who\'s Who Query - Using student_master_course__map', [
                'course_id' => $courseId,
                'name' => $name,
                'category' => $category,
                'status' => $status,
                'table' => 'student_master_course__map',
                'filter_column' => 'course_master_pk'
            ]);

            // Get total count before pagination and sorting modifications
            // Count before adding joins to avoid duplicate counting issues
            $totalCount = $query->count();

            // Apply pagination
            $currentPage = max(1, (int) $page);
            $perPage = max(1, min(100, (int) $perPage)); // Limit between 1 and 100
            $totalPages = ceil($totalCount / $perPage);

            // Ensure current page is valid
            if ($currentPage > $totalPages && $totalPages > 0) {
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

            // Log results
            \Log::info('Who\'s Who Query Results from student_master_course__map', [
                'total_count' => $totalCount,
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
                'returned_count' => $studentMaps->count(),
                'course_filter_applied' => !empty($courseId)
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

                // If no course filter and course is missing, try to get first course for this student matching course_type
                if (!$course && empty($courseId)) {
                    $fallbackDate = Carbon::now()->format('Y-m-d');
                    $firstCourseMap = StudentMasterCourseMap::with('course')
                        ->where('student_master_pk', $student->pk)
                        ->where('active_inactive', 1)
                        ->whereHas('course', function($q) use ($courseType, $fallbackDate) {
                            $q->where('active_inactive', 1);
                            if ($courseType === 'archive') {
                                $q->where('end_date', '<', $fallbackDate);
                            } else {
                                $q->where('end_date', '>=', $fallbackDate);
                            }
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
                 * This queries the mapping table to find all course_master_pk values for this student
                 */
                $enrolledCoursesDate = Carbon::now()->format('Y-m-d');
                $enrolledCourses = StudentMasterCourseMap::with('course')
                    ->where('student_master_pk', $student->pk)
                    ->where('active_inactive', 1)
                    ->whereHas('course', function($q) use ($courseType, $enrolledCoursesDate) {
                        $q->where('active_inactive', 1);
                        if ($courseType === 'archive') {
                            $q->where('end_date', '<', $enrolledCoursesDate);
                        } else {
                            $q->where('end_date', '>=', $enrolledCoursesDate);
                        }
                    })
                    ->get();

                // Format education (if available in database, otherwise empty)
                $education = [];
                // You can add education data from a separate table if available

                // Format hobbies (if available in database, otherwise empty)
                $hobbies = [];
                // You can add hobbies data from a separate table if available

                // Get state name
                $stateName = 'N/A';
                if ($student->domicile_state_pk) {
                    $state = State::find($student->domicile_state_pk);
                    $stateName = $state ? $state->state_name : 'State ID: ' . $student->domicile_state_pk;
                }

                // Get stream name if available
                $streamName = 'N/A';
                if ($student->highest_stream_pk) {
                    $stream = DB::table('stream_master')->where('pk', $student->highest_stream_pk)->first();
                    $streamName = $stream ? $stream->stream_name : 'Stream ID: ' . $student->highest_stream_pk;
                }

                // Format batch
                $batch = 'N/A';
                if ($course && $course->start_year) {
                    $endYear = $course->end_date ? Carbon::parse($course->end_date)->format('Y') : (Carbon::parse($course->start_year)->addYear()->format('Y'));
                    $batch = $course->start_year . '-' . $endYear;
                }

                $students[] = [
                    'id' => $student->generated_OT_code ?? ('STU-' . $student->pk),
                    'name' => $student->display_name ?? (trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))),
                    'roll' => 'Roll ' . ($student->rank ?? 'N/A'),
                    'service' => $student->service->service_name ?? 'N/A',
                    'courseName' => $course->course_name ?? 'N/A',
                    'courseCode' => $course->couse_short_name ?? $course->course_name ?? 'N/A',
                    'batch' => $batch,
                    'image' => $student->photo_path ? asset('storage/' . $student->photo_path) : 'https://via.placeholder.com/180x180?text=' . urlencode(substr($student->display_name ?? 'Student', 0, 1)),
                    'dob' => $student->dob ? Carbon::parse($student->dob)->format('m/d/Y') : 'N/A',
                    'domicile' => $stateName,
                    'attempts' => $student->rank ?? 'N/A',
                    'stream' => $streamName,
                    'room' => $student->room_no ?? 'N/A',
                    'email' => $student->email ?? 'N/A',
                    'contact' => $student->contact_no ?? 'N/A',
                    'lastService' => $student->last_service_pk ?? 'N/A',
                    'hobbies' => $hobbies,
                    'education' => $education,
                    'enrolledCourses' => $enrolledCourses->map(function($ec) {
                        return [
                            'courseName' => $ec->course->course_name ?? 'N/A',
                            'courseCode' => $ec->course->couse_short_name ?? 'N/A',
                            'courseId' => $ec->course_master_pk ?? null
                        ];
                    })->toArray(),
                    // Store the mapping table primary key for reference
                    'courseMapPk' => $map->pk,
                    'courseMasterPk' => $map->course_master_pk,
                    'studentMasterPk' => $map->student_master_pk
                ];
            }

            return response()->json([
                'success' => true,
                'students' => $students,
                'pagination' => [
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => $totalPages,
                    'from' => $totalCount > 0 ? (($currentPage - 1) * $perPage) + 1 : 0,
                    'to' => min($currentPage * $perPage, $totalCount),
                    'has_more' => $currentPage < $totalPages
                ],
                'sort' => [
                    'sort_by' => $sortBy,
                    'sort_column' => $sortColumn,
                    'sort_direction' => $sortDirection
                ],
                'filters' => [
                    'course_id' => $courseId,
                    'course_type' => $courseType,
                    'cadre_id' => $cadreId,
                    'group_id' => $groupId,
                    'name' => $name,
                    'category' => $category,
                    'status' => $status
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching students: ' . $e->getMessage(),
                'error' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Get static info (tutor group, tutor name, house name, house tutors)
     * This can be customized based on your database structure
     */
    public function getStaticInfo(Request $request)
    {
        try {
            $courseId = $request->input('course_id', '');

            // This is a placeholder - adjust based on your actual database structure
            $staticInfo = [
                'tutorGroup' => '0',
                'tutorName' => 'N/A',
                'houseName' => 'N/A',
                'houseTutors' => 'N/A'
            ];

            // You can add logic here to fetch from your database
            // For example, if you have tutor_group_master, house_master tables, etc.

            return response()->json([
                'success' => true,
                'data' => $staticInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching static info: ' . $e->getMessage()
            ], 500);
        }
    }
}
