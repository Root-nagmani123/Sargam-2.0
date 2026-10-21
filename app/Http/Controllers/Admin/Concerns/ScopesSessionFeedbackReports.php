<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\CourseMaster;
use App\Services\FacultyFeedbackReportService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

trait ScopesSessionFeedbackReports
{
    protected function isFacultySessionFeedbackReport(): bool
    {
        return (bool) request()->attributes->get('is_faculty_feedback_report', false);
    }

    /**
     * @return array<int, int>|null null = use admin role-based course list
     */
    protected function facultyReportCourseIds(): ?array
    {
        if (! $this->isFacultySessionFeedbackReport()) {
            return null;
        }

        $ids = request()->attributes->get('faculty_report_course_ids', []);

        return is_array($ids) ? array_values(array_map('intval', $ids)) : [];
    }

    protected function applyFeedbackReportCourseScope(QueryBuilder $query, string $column = 'cm.pk'): void
    {
        $scoped = $this->facultyReportCourseIds();

        if ($scoped !== null) {
            if ($scoped === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn($column, $scoped);
            }

            return;
        }

        $roleCourseIds = get_Role_by_course();
        if (! empty($roleCourseIds)) {
            $query->whereIn($column, $roleCourseIds);
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|QueryBuilder  $query
     */
    protected function applyFeedbackReportCourseScopeOnPrograms($query): void
    {
        $scoped = $this->facultyReportCourseIds();

        if ($scoped !== null) {
            if ($scoped === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('pk', $scoped);
            }

            return;
        }

        $roleCourseIds = get_Role_by_course();
        if (! empty($roleCourseIds)) {
            $query->whereIn('pk', $roleCourseIds);
        }
    }

    protected function coursesForFeedbackDatabase(string $courseType): Collection
    {
        if ($this->isFacultySessionFeedbackReport()) {
            $facultyPk = (int) request()->attributes->get('faculty_report_faculty_pk');

            return app(FacultyFeedbackReportService::class)
                ->getPrograms($facultyPk, $courseType)
                ->map(fn ($name, $pk) => (object) ['pk' => (int) $pk, 'course_name' => $name])
                ->values();
        }

        $userId = auth()->id();
        $currentDate = now()->toDateString();

        $coursesQuery = CourseMaster::where('active_inactive', 1)
            ->when($courseType === 'current', function ($q) use ($currentDate) {
                $q->where(function ($q2) use ($currentDate) {
                    $q2->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $currentDate);
                });
            })
            ->when($courseType === 'archived', function ($q) use ($currentDate) {
                $q->whereDate('end_date', '<', $currentDate);
            });

        $this->applyFeedbackReportCourseScopeOnPrograms($coursesQuery);

        if (auth()->user()->role == 'student') {
            $coursesQuery->whereHas('students', function ($q) use ($userId) {
                $q->where('student_master_pk', $userId)
                    ->where('active_inactive', 1);
            });
        }

        return $coursesQuery->select('pk', 'course_name')
            ->orderBy('course_name')
            ->get();
    }

    protected function assertFacultyReportCourseAccess(Request $request, ?int $courseId): void
    {
        if (! $this->isFacultySessionFeedbackReport() || $courseId === null || $courseId <= 0) {
            return;
        }

        $scoped = $this->facultyReportCourseIds() ?? [];

        if (! in_array($courseId, $scoped, true)) {
            throw ValidationException::withMessages([
                'course_id' => ['You do not have access to this programme.'],
            ]);
        }
    }

    protected function assertFacultyReportProgramAccess(?int $programId): void
    {
        if (! $this->isFacultySessionFeedbackReport() || $programId === null || $programId <= 0) {
            return;
        }

        $scoped = $this->facultyReportCourseIds() ?? [];

        if (! in_array($programId, $scoped, true)) {
            throw ValidationException::withMessages([
                'program_id' => ['You do not have access to this programme.'],
            ]);
        }
    }

    protected function defaultProgramIdForFacultyReport(string $courseType): ?int
    {
        if (! $this->isFacultySessionFeedbackReport()) {
            return null;
        }

        $facultyPk = (int) request()->attributes->get('faculty_report_faculty_pk');
        $service = app(FacultyFeedbackReportService::class);

        if ($courseType === 'current') {
            return $service->getDefaultProgramId($facultyPk);
        }

        $ids = $this->facultyReportCourseIds() ?? [];
        if ($ids === []) {
            return null;
        }

        return CourseMaster::query()
            ->whereIn('pk', $ids)
            ->where(function ($q) {
                $q->where('active_inactive', 0)
                    ->orWhereDate('end_date', '<', Carbon::today());
            })
            ->orderByDesc('end_date')
            ->value('pk');
    }
}
