<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsMasterGrid;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\FacultyMaster;
use App\Models\CourseMaster;
use App\Models\CourseCordinatorMaster;
use App\Models\StudentMaster;
use App\Models\StudentMasterCourseMap;
use App\Models\StudentMedicalExemption;
use App\Models\CalendarEvent;
use Illuminate\Pagination\LengthAwarePaginator;

class MedicalExceptionFacultyViewController extends Controller
{
    use ExportsMasterGrid;

    /** menus.permission_name of this screen (menus row "Medical Exception Faculty"); the sidebar link checks it too. */
    public const ADMIN_PERMISSION = 'medical_exception_faculty';


    public function index(Request $request)
    {
        return view('admin.medical_exception.faculty_view', $this->viewData($request));
    }

    /**
     * Export - CSV | Excel | PDF | Print (rendering lives in ExportsMasterGrid).
     *
     * Rows come from viewData(), the same builder (and the same access check)
     * as the grid, so a download can never show more than the screen does.
     */
    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, self::$exportFormats, true), 404);

        $payload = $this->viewData($request);
        $search = $request->query('q');
        $search = is_string($search) ? trim($search) : '';
        $order = $request->query('order');
        $defs = $this->exportColumnDefs();
        $rows = $this->filterLikeGrid(collect($payload['data']), $defs, $search);

        return $this->renderMasterExport(
            $format,
            is_string($order) ? $this->sortLikeGrid($rows, $defs, $order) : $rows,
            $this->resolveExportColumns($request, $defs),
            'Medical Exemption Faculty View',
            'MedicalExemptionFacultyView',
            $this->exportFilterLine($payload, $search),
            'No medical exemption records to export',
            'landscape'
        );
    }

    /**
     * Canonical export columns, in display order.
     *
     * Keys must match MEF_EXPORT_COLUMN_KEYS in faculty_view.blade.php. Values
     * are formatted exactly as the grid renders them, so the ?q= filter matches
     * what the user searched on screen.
     *
     * @return array<string, array<string, mixed>>
     */
    private function exportColumnDefs(): array
    {
        return [
            'sno' => ['heading' => 'S.No.', 'width' => '4%', 'align' => 'center', 'value' => fn ($row, int $index) => $index + 1],
            'course' => ['heading' => 'Course Name', 'width' => '12%', 'value' => fn ($row) => $row->course_name ?? 'N/A'],
            'faculty' => ['heading' => 'Faculty Name', 'width' => '10%', 'value' => fn ($row) => $row->faculty_name ?? 'N/A'],
            'topics' => ['heading' => 'Topics', 'width' => '16%', 'value' => fn ($row) => $row->topics ?? 'N/A'],
            'student' => ['heading' => 'Student Name', 'width' => '10%', 'value' => fn ($row) => $row->student_name ?? 'N/A'],
            'date' => ['heading' => 'Medical Exemption Date', 'width' => '7%', 'align' => 'center', 'value' => fn ($row) => self::formatDate($row->from_date, 'd-m-Y')],
            'time' => ['heading' => 'Medical Exemption Time', 'width' => '6%', 'align' => 'center', 'value' => fn ($row) => self::formatDate($row->from_date, 'h:i A')],
            'ot_code' => ['heading' => 'OT Code', 'width' => '7%', 'value' => fn ($row) => $row->ot_code ?? 'N/A'],
            'application_type' => ['heading' => 'Application Type', 'width' => '10%', 'value' => fn ($row) => $row->application_type ?? 'N/A'],
            'exemption_count' => ['heading' => 'Exemption Count', 'width' => '6%', 'align' => 'center', 'value' => fn ($row) => $row->exemption_count ?? 0],
            'submitted_on' => ['heading' => 'Submitted On', 'width' => '12%', 'align' => 'center', 'value' => fn ($row) => self::formatDate($row->submitted_on, 'd-m-Y h:i A')],
        ];
    }

    private static function formatDate($value, string $format): string
    {
        return $value ? Carbon::parse($value)->format($format) : 'N/A';
    }

    /**
     * Apply the grid's client-side search to the export rows.
     *
     * Mirrors DataTables 1.13's "smart" search: the term is split on spaces, a
     * "double-quoted phrase" stays one word, and every word must appear,
     * case-insensitively, somewhere in the row's rendered text (S.No. is not
     * searchable; the Document column searches as "View" / "N/A").
     */
    private function filterLikeGrid(Collection $rows, array $defs, string $search): Collection
    {
        // Same tokenizer and quote stripping as DataTables' _fnFilterCreateSearch().
        preg_match_all('/["\x{201C}][^"\x{201D}]+["\x{201D}]|[^ ]+/u', $search, $matches);
        $words = array_filter(array_map(function (string $word) {
            if (preg_match('/^"(.*)"$/us', $word, $m) || preg_match('/^\x{201C}(.*)\x{201D}$/us', $word, $m)) {
                $word = $m[1];
            }

            return mb_strtolower(preg_replace('/"/', '', $word, 1));
        }, $matches[0]), 'strlen');

        if ($words) {
            unset($defs['sno']);
            $rows = $rows->filter(function ($row) use ($defs, $words) {
                $cells = array_map(fn ($col) => (string) $col['value']($row, 0), $defs);
                $cells[] = $row->medical_document ? 'View' : 'N/A';
                $haystack = mb_strtolower(implode('  ', $cells));

                foreach ($words as $word) {
                    if (!str_contains($haystack, $word)) {
                        return false;
                    }
                }

                return true;
            });
        }

        return $rows->values();
    }

    /**
     * Apply the grid's current client-side ordering (?order=key:asc,key:desc).
     *
     * Mirrors DataTables 1.13: date columns sort on their data-order timestamp, a
     * column whose every value is numeric sorts as a number, anything else as a
     * lower-cased string; ties keep the server order. Unknown keys are ignored.
     */
    private function sortLikeGrid(Collection $rows, array $defs, string $order): Collection
    {
        $sortValues = array_map(fn ($col) => $col['value'], $defs);
        unset($sortValues['sno']); // not orderable on the grid
        $sortValues['date'] = fn ($row) => $row->from_date ? Carbon::parse($row->from_date)->timestamp : 0;
        $sortValues['submitted_on'] = fn ($row) => $row->submitted_on ? Carbon::parse($row->submitted_on)->timestamp : 0;
        $sortValues['document'] = fn ($row) => $row->medical_document ? 'View' : 'N/A';

        $rules = [];
        foreach (explode(',', $order) as $part) {
            [$key, $dir] = array_pad(explode(':', $part, 2), 2, 'asc');
            if (!isset($sortValues[$key])) {
                continue;
            }
            // DataTables trims each cell's HTML before typing and sorting it.
            $values = $rows->map(fn ($row) => trim((string) $sortValues[$key]($row, 0)));
            $numeric = $values->every(fn ($value) => is_numeric($value));
            $rules[] = [
                $values->map(fn ($value) => $numeric ? (float) $value : mb_strtolower($value))->all(),
                $numeric,
                $dir === 'desc' ? -1 : 1,
            ];
        }

        if (!$rules) {
            return $rows;
        }

        $positions = $rows->keys()->all();
        usort($positions, function ($a, $b) use ($rules) {
            foreach ($rules as [$values, $numeric, $sign]) {
                $cmp = $numeric ? $values[$a] <=> $values[$b] : strcmp($values[$a], $values[$b]);
                if ($cmp !== 0) {
                    return $cmp * $sign;
                }
            }

            return $a <=> $b;
        });

        return collect($positions)->map(fn ($position) => $rows[$position]);
    }

    /** The header band's "applied filters" line, or null when nothing narrowed the rows. */
    private function exportFilterLine(array $payload, string $search): ?string
    {
        $parts = [];

        if (!empty($payload['courseFilter'])) {
            // Not from $payload['courses']: the admin dropdown lists running courses only.
            $parts[] = 'Course: '.(CourseMaster::where('pk', $payload['courseFilter'])->value('course_name') ?? $payload['courseFilter']);
        }
        if (!empty($payload['dateFromFilter'])) {
            $parts[] = 'From: '.self::formatDate($payload['dateFromFilter'], 'd-m-Y');
        }
        if ($search !== '') {
            $parts[] = 'Search: '.$search;
        }

        return $parts ? implode(' | ', $parts) : null;
    }

    /** Everything the page (and its export) shows, for the logged-in user. */
    private function viewData(Request $request): array
    {
        $currentDate = now()->format('Y-m-d');
        
        // Faculty accounts hold the "Faculty" role; without it here they got the admin view.
        if (hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Faculty')) {
            $userId = Auth::user()->user_id;
        
            $facultyPk = DB::table('faculty_master')
                ->where(login_faculty_key_column(), $userId)
                ->value('pk');
        
            // Faculty Login View - Show only their courses
            return $this->facultyLoginView($request, $facultyPk, $currentDate);
        }

        // Admin View lists every faculty's trainees and exemptions, so it needs the
        // screen's permission (Super Admin always passes); the route itself is
        // auth-only because the faculty branch above must stay reachable.
        abort_unless(hasMenuPermission(self::ADMIN_PERMISSION), 403, 'You do not have permission to open this screen.');

        return $this->adminView($request, $currentDate);
    }

    /**
     * Faculty Login View - Shows medical exceptions for courses where faculty is coordinator
     */
    private function facultyLoginView(Request $request, $facultyPk, $currentDate)
    {
  // Get filter parameters
  $courseFilter = $request->get('course');
  // course[]=x is ignored like a malformed date (the export's filter line cast it to string: 500).
  $courseFilter = is_scalar($courseFilter) ? $courseFilter : null;
  $dateFromFilter = $request->get('date_from');
  // Only a real Y-m-d date filters; anything else is ignored like an empty field
  // (raw input reached Carbon::parse() in the export header and threw a 500).
  $parsedDate = is_string($dateFromFilter) ? \DateTime::createFromFormat('!Y-m-d', $dateFromFilter) : false;
  if (!$parsedDate || $parsedDate->format('Y-m-d') !== $dateFromFilter) {
      $dateFromFilter = null;
  }

  // Courses the faculty is CC or ACC of (PR #335 F-008 rule 3). A login with no
  // faculty_master row coordinates nothing.
  $courseIds = $facultyPk === null ? [] : CourseCordinatorMaster::courseIdsForFaculty((int) $facultyPk);

  if (empty($courseIds)) {
      $data = collect();
      $courses = collect();
  } else {
      // Build query with optimized whereIn
      $query = DB::table('student_medical_exemption as sme')
          ->join('course_master as cm', 'cm.pk', '=', 'sme.course_master_pk')
          ->join('student_master as sm', 'sm.pk', '=', 'sme.student_master_pk')
          ->join('employee_master as em', 'em.pk', '=', 'sme.employee_master_pk')
          ->leftJoin('exemption_medical_speciality_master as ems', 'ems.pk', '=', 'sme.exemption_medical_speciality_pk')
          ->whereIn('sme.course_master_pk', $courseIds)
          ->where('cm.active_inactive', 1)
          ->where('sme.active_inactive', 1);

      // Apply course filter
      if ($courseFilter) {
          $query->where('sme.course_master_pk', $courseFilter);
      }

      // Apply date filter
      if ($dateFromFilter) {
          $query->whereDate('sme.from_date', '>=', $dateFromFilter);
      }

      // Select fields
      $data = $query->select([
              'cm.course_name',
              DB::raw("CONCAT_WS(' ', em.first_name, em.middle_name, em.last_name) as faculty_name"),
              'sme.description as topics',
              'sm.display_name as student_name',
              'sme.from_date',
              'sme.to_date',
              'sm.generated_OT_code as ot_code',
              'sme.doc_upload as medical_document',
              'ems.speciality_name as application_type',
              DB::raw('COUNT(sme.pk) OVER (PARTITION BY sme.course_master_pk) as exemption_count'),
              'sme.created_date as submitted_on'
          ])
          ->orderBy('cm.course_name')
          ->orderBy('sme.from_date', 'desc')
          ->get();

      // Get courses for filter dropdown (reuse courseIds)
      $courses = DB::table('course_master')
          ->whereIn('pk', $courseIds)
          ->where('active_inactive', 1)
          ->select('pk', 'course_name')
          ->orderBy('course_name')
          ->get();
  }

  return compact('data', 'courses', 'courseFilter', 'dateFromFilter');
}        


    
    /**
     * Admin view for non-faculty users (original functionality)
     */
    private function adminView(Request $request, $currentDate)
    {
        // Get filter parameters (accept both course and course_filter for compatibility)
        $facultyFilter = $request->get('faculty_filter');
        $courseFilter = $request->get('course_filter') ?: $request->get('course');
        $courseFilter = is_scalar($courseFilter) ? $courseFilter : null; // as in facultyLoginView()
        
        // Get all faculties who have courses assigned (from timetable)
        $facultiesQuery = FacultyMaster::query()
            ->whereHas('timetableCourses', function($q) {
                $q->where('active_inactive', 1);
            })
            ->with(['timetableCourses' => function($q) {
                $q->where('active_inactive', 1)
                  ->with('courseGroupTypeMaster');
            }]);
        
        if ($facultyFilter) {
            $facultiesQuery->where('pk', $facultyFilter);
        }
        
        $faculties = $facultiesQuery->get();
        
        // Collect all unique course IDs first
        $allCourseIds = collect();
        foreach ($faculties as $faculty) {
            foreach ($faculty->timetableCourses as $timetable) {
                if ($timetable->course_master_pk) {
                    $allCourseIds->push($timetable->course_master_pk);
                } elseif ($timetable->course_group_type_master) {
                    $allCourseIds->push($timetable->course_group_type_master);
                }
            }
        }
        $allCourseIds = $allCourseIds->unique()->filter()->values();
        
        // Initialize empty collections
        $courses = collect();
        $coordinators = collect();
        $studentMappings = collect();
        $students = collect();
        $exemptions = collect();
        $courseIds = [];
        
        // Batch load all courses, coordinators, students, and exemptions only if we have course IDs
        if ($allCourseIds->isNotEmpty()) {
            $coursesQuery = CourseMaster::whereIn('pk', $allCourseIds)
                ->where('active_inactive', 1)
                ->where('end_date', '>', $currentDate);
            
            if ($courseFilter) {
                $coursesQuery->where('pk', $courseFilter);
            }
            
            $courses = $coursesQuery->get(['pk', 'course_name'])->keyBy('pk');
            $courseIds = $courses->pluck('pk')->toArray();
            
            if (!empty($courseIds)) {
                // Batch load coordinators
                $coordinators = CourseCordinatorMaster::whereIn('courses_master_pk', $courseIds)
                    ->get(['courses_master_pk', 'Coordinator_name', 'Assistant_Coordinator_name'])
                    ->keyBy('courses_master_pk');
                
                // Batch load student mappings
                $studentMappings = StudentMasterCourseMap::whereIn('course_master_pk', $courseIds)
                    ->where('active_inactive', 1)
                    ->get(['course_master_pk', 'student_master_pk'])
                    ->groupBy('course_master_pk');
                
                $allStudentIds = $studentMappings->flatten()->pluck('student_master_pk')->unique()->values()->toArray();
                
                if (!empty($allStudentIds)) {
                    // Batch load students
                    $students = StudentMaster::whereIn('pk', $allStudentIds)
                        ->where('status', 1)
                        ->get(['pk', 'generated_OT_code', 'display_name'])
                        ->keyBy('pk');
                    
                    // Batch load exemptions
                    $exemptions = StudentMedicalExemption::whereIn('course_master_pk', $courseIds)
                        ->whereIn('student_master_pk', $allStudentIds)
                        ->where('from_date', '<=', $currentDate)
                        ->where(function($q) use ($currentDate) {
                            $q->where('to_date', '>=', $currentDate)
                              ->orWhereNull('to_date');
                        })
                        ->where('active_inactive', 1)
                        ->get(['course_master_pk', 'student_master_pk'])
                        ->groupBy('course_master_pk')
                        ->map(function($group) {
                            return $group->pluck('student_master_pk')->unique()->count();
                        });
                }
            }
        }
        
        // Build the data structure
        $facultyData = [];
        
        foreach ($faculties as $faculty) {
            // Get unique courses for this faculty from timetable. Its own variable:
            // $courseIds (every active course) still scopes the admin query below.
            $facultyCourseIds = collect();
            foreach ($faculty->timetableCourses as $timetable) {
                if ($timetable->course_master_pk) {
                    $facultyCourseIds->push($timetable->course_master_pk);
                } elseif ($timetable->course_group_type_master) {
                    $facultyCourseIds->push($timetable->course_group_type_master);
                }
            }
            $facultyCourseIds = $facultyCourseIds->unique()->filter();

            if ($facultyCourseIds->isEmpty()) {
                continue;
            }

            $coursesData = [];

            foreach ($facultyCourseIds as $courseId) {
                if (!$courseId || !isset($courses[$courseId])) {
                    continue;
                }
                
                $course = $courses[$courseId];
                
                // Filter by course if specified
                if ($courseFilter && $course->pk != $courseFilter) {
                    continue;
                }
                
                // Get ACC/CC for this course
                $coordinator = $coordinators->get($courseId);
                $cc = $coordinator ? ($coordinator->Coordinator_name ?? 'Not Assigned') : 'Not Assigned';
                $acc = $coordinator ? ($coordinator->Assistant_Coordinator_name ?? 'Not Assigned') : 'Not Assigned';
                
                // Get students enrolled in this course
                $courseStudentIds = $studentMappings->get($courseId, collect())->pluck('student_master_pk')->toArray();
                $courseStudents = collect();
                foreach ($courseStudentIds as $studentId) {
                    if ($students->has($studentId)) {
                        $courseStudents->push($students[$studentId]);
                    }
                }
                
                // Get exemption count
                $exemptionCount = $exemptions->get($courseId, 0);
                
                $coursesData[] = [
                    'course_id' => $course->pk,
                    'course_name' => $course->course_name,
                    'cc' => $cc,
                    'acc' => $acc,
                    'students' => $courseStudents,
                    'total_students' => $courseStudents->count(),
                    'exemption_count' => $exemptionCount,
                ];
            }
            
            if (!empty($coursesData)) {
                $facultyData[] = [
                    'faculty_id' => $faculty->pk,
                    'faculty_name' => $faculty->full_name ?? 'N/A',
                    'courses' => $coursesData,
                ];
            }
        }
        
        // Get all faculties for filter dropdown
        $allFaculties = FacultyMaster::whereHas('timetableCourses', function($q) {
            $q->where('active_inactive', 1);
        })->orderBy('full_name')->get(['pk', 'full_name']);
        
        // Get all active courses for filter dropdown (active_inactive = 1 and end_date > current date)
        $allCourses = CourseMaster::where('active_inactive', 1)
            ->where('end_date', '>', $currentDate)
            ->orderBy('course_name')
            ->get(['pk', 'course_name']);
        
        // Query medical exceptions for admin view (similar to faculty view)
        $adminDataQuery = DB::table('student_medical_exemption as sme')
            ->join('course_master as cm', 'cm.pk', '=', 'sme.course_master_pk')
            ->join('student_master as sm', 'sm.pk', '=', 'sme.student_master_pk')
            ->join('employee_master as em', 'em.pk', '=', 'sme.employee_master_pk')
            ->leftJoin('exemption_medical_speciality_master as ems', 'ems.pk', '=', 'sme.exemption_medical_speciality_pk')
            ->where('cm.active_inactive', 1)
            ->where('sme.active_inactive', 1);
        
        // Apply course filter if specified
        if ($courseFilter) {
            $adminDataQuery->where('sme.course_master_pk', $courseFilter);
        } else {
            // If no course filter, only show courses that are in the faculty data
            if (!empty($courseIds)) {
                $adminDataQuery->whereIn('sme.course_master_pk', $courseIds);
            } else {
                // If no courses found, return empty
                $adminDataQuery->whereRaw('1 = 0');
            }
        }
        
        $adminData = $adminDataQuery->select([
                'cm.course_name',
                DB::raw("CONCAT_WS(' ', em.first_name, em.middle_name, em.last_name) as faculty_name"),
                'sme.description as topics',
                'sm.display_name as student_name',
                'sme.from_date',
                'sme.to_date',
                'sm.generated_OT_code as ot_code',
                'sme.doc_upload as medical_document',
                'ems.speciality_name as application_type',
                DB::raw('COUNT(sme.pk) OVER (PARTITION BY sme.course_master_pk) as exemption_count'),
                'sme.created_date as submitted_on'
            ])
            ->orderBy('cm.course_name')
            ->orderBy('sme.from_date', 'desc')
            ->get();
        
        // Build paginated rows for admin tables
        $perPage = 10;
        $adminCoursePage = (int) $request->get('admin_course_page', 1);
        $adminStudentPage = (int) $request->get('admin_students_page', 1);

        // Flatten course rows
        $adminCourseRows = [];
        foreach ($facultyData as $faculty) {
            foreach ($faculty['courses'] as $course) {
                $adminCourseRows[] = [
                    'faculty_name' => $faculty['faculty_name'],
                    'course_name' => $course['course_name'],
                    'cc' => $course['cc'],
                    'acc' => $course['acc'],
                    'total_students' => $course['total_students'],
                    'exemption_count' => $course['exemption_count'],
                ];
            }
        }

        $adminCourseTotal = count($adminCourseRows);
        $adminCourseItems = array_slice($adminCourseRows, ($adminCoursePage - 1) * $perPage, $perPage);
        $adminCoursePaginator = new LengthAwarePaginator(
            $adminCourseItems,
            $adminCourseTotal,
            $perPage,
            $adminCoursePage,
            ['path' => $request->url(), 'pageName' => 'admin_course_page']
        );

        // Flatten student rows (all students per course)
        $adminStudentRows = [];
        foreach ($facultyData as $faculty) {
            foreach ($faculty['courses'] as $course) {
                if ($course['students']->count() > 0) {
                    foreach ($course['students'] as $student) {
                        $adminStudentRows[] = [
                            'faculty_name' => $faculty['faculty_name'],
                            'course_name' => $course['course_name'],
                            'generated_OT_code' => $student->generated_OT_code,
                            'display_name' => $student->display_name,
                        ];
                    }
                } else {
                    $adminStudentRows[] = [
                        'faculty_name' => $faculty['faculty_name'],
                        'course_name' => $course['course_name'],
                        'generated_OT_code' => '',
                        'display_name' => 'No students enrolled in this course.',
                    ];
                }
            }
        }

        $adminStudentsTotal = count($adminStudentRows);
        $adminStudentsItems = array_slice($adminStudentRows, ($adminStudentPage - 1) * $perPage, $perPage);
        $adminStudentsPaginator = new LengthAwarePaginator(
            $adminStudentsItems,
            $adminStudentsTotal,
            $perPage,
            $adminStudentPage,
            ['path' => $request->url(), 'pageName' => 'admin_students_page']
        );

        return [
            'data' => $adminData, // Medical exceptions data for admin view
            'courses' => $allCourses, // Map allCourses to courses for dropdown
            'courseFilter' => $courseFilter,
            'dateFromFilter' => null,
            'facultyData' => $facultyData,
            'allFaculties' => $allFaculties,
            'allCourses' => $allCourses,
            'facultyFilter' => $facultyFilter,
            'adminCoursePaginator' => $adminCoursePaginator,
            'adminStudentsPaginator' => $adminStudentsPaginator,
        ];
    }
}

