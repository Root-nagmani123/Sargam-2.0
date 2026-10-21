<?php

namespace App\Services;

use App\Models\CourseCordinatorMaster;
use App\Models\CourseMaster;
use App\Models\FacultyMaster;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FacultyFeedbackReportService
{
    public function resolveFacultyPk(?int $userId = null): ?int
    {
        if ($userId !== null) {
            $user = auth()->user();
            if (! $user || (int) $user->user_id !== $userId) {
                return FacultyMaster::where('employee_master_pk', $userId)->value('pk')
                    ?: (FacultyMaster::where('pk', $userId)->exists() ? $userId : null);
            }
        }

        return get_auth_faculty_master_pk();
    }

    public function assertFacultyRole(): void
    {
        if (! is_faculty_portal_user()) {
            abort(403, 'This page is only available for faculty members.');
        }
    }

    /**
     * Course IDs the faculty may access: timetable assignments plus course/assistant coordinator roles.
     */
    public function getAccessibleCourseIds(int $facultyPk): Collection
    {
        $timetableCourseIds = DB::table('timetable')
            ->where(function ($query) use ($facultyPk) {
                $query->where('faculty_master', $facultyPk)
                    ->orWhereRaw('JSON_CONTAINS(COALESCE(NULLIF(faculty_master, ""), "[]"), ?)', ['"'.$facultyPk.'"'])
                    ->orWhereRaw('FIND_IN_SET(?, faculty_master)', [$facultyPk])
                    ->orWhereRaw('JSON_CONTAINS(COALESCE(NULLIF(internal_faculty, ""), "[]"), ?)', ['"'.$facultyPk.'"'])
                    ->orWhereRaw('FIND_IN_SET(?, internal_faculty)', [$facultyPk]);
            })
            ->distinct()
            ->pluck('course_master_pk');

        $coordinatorCourseIds = CourseCordinatorMaster::where(function ($query) use ($facultyPk) {
            $query->where('Coordinator_name', $facultyPk)
                ->orWhere('Assistant_Coordinator_name', $facultyPk);
        })
            ->pluck('courses_master_pk');

        return $timetableCourseIds
            ->merge($coordinatorCourseIds)
            ->filter()
            ->unique()
            ->values();
    }

    public function getPrograms(int $facultyPk, string $courseType = 'current'): Collection
    {
        $courseIds = $this->getAccessibleCourseIds($facultyPk);

        if ($courseIds->isEmpty()) {
            return collect();
        }

        $query = CourseMaster::query()
            ->whereIn('pk', $courseIds)
            ->select('pk', 'course_name', 'active_inactive', 'end_date');

        if ($courseType === 'current') {
            $query->where('active_inactive', 1)
                ->whereDate('end_date', '>=', Carbon::today());
        } else {
            $query->where(function ($q) {
                $q->where('active_inactive', 0)
                    ->orWhereDate('end_date', '<', Carbon::today());
            });
        }

        return $query->orderBy('course_name')->pluck('course_name', 'pk');
    }

    public function getDefaultProgramId(int $facultyPk): ?int
    {
        $courseIds = $this->getAccessibleCourseIds($facultyPk);

        if ($courseIds->isEmpty()) {
            return null;
        }

        return CourseMaster::query()
            ->whereIn('pk', $courseIds)
            ->where('active_inactive', 1)
            ->whereDate('end_date', '>=', Carbon::today())
            ->orderByDesc('end_date')
            ->value('pk');
    }

    /**
     * All matching feedback rows (portal filters), sorted by session date.
     */
    public function getAllProcessedItems(int $viewerFacultyPk, array $filters): Collection
    {
        $courseType = $filters['course_type'] ?? 'current';
        $programId = $filters['program_id'] ?? '';
        $fromDate = $filters['from_date'] ?? '';
        $toDate = $filters['to_date'] ?? '';

        $accessibleIds = $this->getAccessibleCourseIds($viewerFacultyPk);

        if ($accessibleIds->isEmpty()) {
            return collect();
        }

        $query = DB::table('topic_feedback as tf')
            ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
            ->join('course_master as cm', 'tt.course_master_pk', '=', 'cm.pk')
            ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
            ->select(
                'tf.topic_name',
                'cm.pk as program_id',
                'cm.course_name as program_name',
                'cm.active_inactive as program_status',
                'cm.end_date as program_end_date',
                'fm.full_name as faculty_name',
                'fm.faculty_type',
                'tf.faculty_pk',
                'tt.START_DATE',
                'tt.END_DATE',
                'tt.class_session',
                'tf.timetable_pk',
                DB::raw('SUM(CASE WHEN tf.content = "5" THEN 1 ELSE 0 END) as content_5'),
                DB::raw('SUM(CASE WHEN tf.content = "4" THEN 1 ELSE 0 END) as content_4'),
                DB::raw('SUM(CASE WHEN tf.content = "3" THEN 1 ELSE 0 END) as content_3'),
                DB::raw('SUM(CASE WHEN tf.content = "2" THEN 1 ELSE 0 END) as content_2'),
                DB::raw('SUM(CASE WHEN tf.content = "1" THEN 1 ELSE 0 END) as content_1'),
                DB::raw('SUM(CASE WHEN tf.presentation = "5" THEN 1 ELSE 0 END) as presentation_5'),
                DB::raw('SUM(CASE WHEN tf.presentation = "4" THEN 1 ELSE 0 END) as presentation_4'),
                DB::raw('SUM(CASE WHEN tf.presentation = "3" THEN 1 ELSE 0 END) as presentation_3'),
                DB::raw('SUM(CASE WHEN tf.presentation = "2" THEN 1 ELSE 0 END) as presentation_2'),
                DB::raw('SUM(CASE WHEN tf.presentation = "1" THEN 1 ELSE 0 END) as presentation_1'),
                DB::raw('COUNT(DISTINCT tf.student_master_pk) as participants'),
                DB::raw('GROUP_CONCAT(DISTINCT CASE 
                WHEN tf.remark IS NOT NULL 
                AND TRIM(tf.remark) != "" 
                THEN tf.remark 
                ELSE NULL 
             END SEPARATOR "|||") as remarks')
            )
            ->where('tf.is_submitted', 1)
            ->whereIn('cm.pk', $accessibleIds);

        $query->groupBy(
            'tf.topic_name',
            'cm.pk',
            'cm.course_name',
            'cm.active_inactive',
            'cm.end_date',
            'fm.full_name',
            'fm.faculty_type',
            'tf.faculty_pk',
            'tt.START_DATE',
            'tt.END_DATE',
            'tt.class_session',
            'tf.timetable_pk'
        );

        $query->where('tf.faculty_pk', $viewerFacultyPk);

        if ($programId !== '' && $programId !== null) {
            $programId = (int) $programId;
            if (! $accessibleIds->contains($programId)) {
                return collect();
            }

            $query->where('cm.pk', $programId);
        }

        if ($fromDate) {
            $query->whereDate('tt.START_DATE', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('tt.END_DATE', '<=', $toDate);
        }

        if ($courseType === 'archived') {
            $query->where(function ($q) {
                $q->where('cm.active_inactive', 0)
                    ->orWhereDate('cm.end_date', '<', Carbon::today());
            });
        } elseif ($courseType === 'current') {
            $query->where('cm.active_inactive', 1)
                ->whereDate('cm.end_date', '>=', Carbon::today());
        }

        $query->orderBy('tt.START_DATE', 'asc')
            ->orderByRaw("
              CASE 
                  WHEN tt.class_session LIKE '%AM%' THEN 1
                  WHEN tt.class_session LIKE '%PM%' THEN 2
                  WHEN tt.class_session REGEXP '^[0-9]' THEN 3
                  ELSE 4
              END,
              tt.class_session ASC
          ");

        DB::statement('SET SESSION group_concat_max_len = 1000000;');

        return $query->get()
            ->map(fn ($item) => $this->mapFeedbackRow($item))
            ->sortBy('raw_start_date')
            ->values();
    }

    /**
     * @return array{items: \Illuminate\Support\Collection, currentPage: int, totalPages: int, totalRecords: int}
     */
    public function getPaginatedReport(int $viewerFacultyPk, array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, (int) ($filters['per_page'] ?? 1));

        $processed = $this->getAllProcessedItems($viewerFacultyPk, $filters);
        $totalRecords = $processed->count();
        $totalPages = $totalRecords > 0 ? (int) ceil($totalRecords / $perPage) : 0;

        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }

        $items = $processed->slice(($page - 1) * $perPage, $perPage)->values();

        if ($items->isEmpty() && $totalRecords > 0) {
            $page = 1;
            $items = $processed->slice(0, $perPage)->values();
        }

        return [
            'items' => $items,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getExportSpreadsheetRows(int $viewerFacultyPk, array $filters): array
    {
        return $this->getAllProcessedItems($viewerFacultyPk, $filters)
            ->map(fn (array $row) => $this->mapItemToExportRow($row))
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function buildPortalExportFiltersMeta(int $viewerFacultyPk, array $filters): array
    {
        $programId = $filters['program_id'] ?? '';
        $fromDate = $filters['from_date'] ?? '';
        $toDate = $filters['to_date'] ?? '';
        $courseType = $filters['course_type'] ?? 'current';

        $programName = 'All Programs';
        if ($programId !== '' && $programId !== null) {
            $programName = CourseMaster::where('pk', $programId)->value('course_name') ?? 'All Programs';
        }

        $facultyName = FacultyMaster::where('pk', $viewerFacultyPk)->value('full_name') ?? 'My Feedback';

        return [
            'program' => $programName,
            'faculty_name' => $facultyName,
            'date_range' => ($fromDate && $toDate)
                ? Carbon::parse($fromDate)->format('d-M-Y').' to '.Carbon::parse($toDate)->format('d-M-Y')
                : 'All Dates',
            'course_type' => $courseType === 'current' ? 'Current Courses' : 'Archived Courses',
            'faculty_type' => 'Course Faculty',
        ];
    }

    private function mapItemToExportRow(array $row): array
    {
        $remarksText = implode("\n", $row['remarks'] ?? []);

        return [
            'Program Name' => $row['program_name'] ?? '',
            'Course Status' => $row['course_status'] ?? '',
            'Faculty Name' => $row['faculty_name'] ?? '',
            'Faculty Type' => $row['faculty_type'] ?? '',
            'Topic' => $row['topic_name'] ?? '',
            'Lecture Date' => $row['formatted_start_date'] ?? '',
            'Time' => $row['time_display'] ?? '',
            'Total Participants' => $row['participants'] ?? 0,
            'Content - Excellent' => $row['content_counts']['5'] ?? 0,
            'Content - Very Good' => $row['content_counts']['4'] ?? 0,
            'Content - Good' => $row['content_counts']['3'] ?? 0,
            'Content - Average' => $row['content_counts']['2'] ?? 0,
            'Content - Below Average' => $row['content_counts']['1'] ?? 0,
            'Content Percentage' => number_format($row['content_percentage'] ?? 0, 2).'%',
            'Presentation - Excellent' => $row['presentation_counts']['5'] ?? 0,
            'Presentation - Very Good' => $row['presentation_counts']['4'] ?? 0,
            'Presentation - Good' => $row['presentation_counts']['3'] ?? 0,
            'Presentation - Average' => $row['presentation_counts']['2'] ?? 0,
            'Presentation - Below Average' => $row['presentation_counts']['1'] ?? 0,
            'Presentation Percentage' => number_format($row['presentation_percentage'] ?? 0, 2).'%',
            'Remarks' => $remarksText,
        ];
    }

    private function mapFeedbackRow(object $item): array
    {
        $contentCounts = [
            '5' => (int) $item->content_5,
            '4' => (int) $item->content_4,
            '3' => (int) $item->content_3,
            '2' => (int) $item->content_2,
            '1' => (int) $item->content_1,
        ];

        $presentationCounts = [
            '5' => (int) $item->presentation_5,
            '4' => (int) $item->presentation_4,
            '3' => (int) $item->presentation_3,
            '2' => (int) $item->presentation_2,
            '1' => (int) $item->presentation_1,
        ];

        $facultyTypeMap = ['1' => 'Internal', '2' => 'Guest'];
        $facultyTypeDisplay = $facultyTypeMap[$item->faculty_type] ?? ucfirst((string) $item->faculty_type);

        $courseStatus = 'Archived';
        if ($item->program_status == 1 && Carbon::parse($item->program_end_date)->gte(Carbon::today())) {
            $courseStatus = 'Current';
        }

        $startDate = $item->START_DATE ? Carbon::parse($item->START_DATE) : null;
        $sessionTime = $this->cleanSessionTime($item->class_session ?? '');
        $timeDisplay = $sessionTime !== '' ? "({$sessionTime})" : '';

        return [
            'topic_name' => $item->topic_name ?? '',
            'program_id' => $item->program_id ?? '',
            'program_name' => $item->program_name ?? '',
            'course_status' => $courseStatus,
            'faculty_name' => $item->faculty_name ?? '',
            'faculty_type' => $facultyTypeDisplay,
            'start_date' => $item->START_DATE ?? '',
            'formatted_start_date' => $startDate ? $startDate->format('d-M-Y') : '',
            'time_display' => $timeDisplay,
            'participants' => (int) ($item->participants ?? 0),
            'content_counts' => $contentCounts,
            'presentation_counts' => $presentationCounts,
            'content_percentage' => $this->percentageFromCounts($contentCounts),
            'presentation_percentage' => $this->percentageFromCounts($presentationCounts),
            'remarks' => $this->parseRemarks($item->remarks ?? ''),
            'raw_start_date' => $startDate ? $startDate->format('Y-m-d H:i:s') : null,
        ];
    }

    private function percentageFromCounts(array $counts): float
    {
        $weighted = 0;
        $total = 0;

        foreach ([5, 4, 3, 2, 1] as $rating) {
            $count = $counts[(string) $rating] ?? 0;
            $weighted += $rating * $count;
            $total += $count;
        }

        if ($total === 0) {
            return 0.0;
        }

        $maxRating = 0;
        for ($r = 5; $r >= 1; $r--) {
            if (($counts[(string) $r] ?? 0) > 0) {
                $maxRating = $r;
                break;
            }
        }

        if ($maxRating === 0) {
            return 0.0;
        }

        return round(($weighted / ($total * $maxRating)) * 100, 2);
    }

    private function parseRemarks(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $remarks = array_filter(array_map('trim', explode('|||', $raw)), function ($remark) {
            return $remark !== ''
                && ! in_array($remark, ['.', '..', '...', '-', '--'], true)
                && strlen($remark) > 1;
        });

        $remarks = array_values(array_unique($remarks));
        sort($remarks);

        return $remarks;
    }

    private function cleanSessionTime(?string $classSession): string
    {
        if (empty($classSession)) {
            return '';
        }

        $sessionTime = trim(str_replace(['08:00 AM - 08:00 PM', '00:00 - 00:00', '00:00 to 00:00'], '', $classSession));
        $sessionTime = trim($sessionTime);

        return ($sessionTime === '' || $sessionTime === '-') ? '' : $sessionTime;
    }
}
