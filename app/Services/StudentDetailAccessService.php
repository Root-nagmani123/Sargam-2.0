<?php

namespace App\Services;

use App\Models\CourseMaster;
use App\Models\StudentCourseGroupMap;
use App\Models\StudentMasterCourseMap;
use Illuminate\Support\Facades\DB;

class StudentDetailAccessService
{
    public function canFacultyViewStudent(int $facultyPk, int $studentPk): bool
    {
        $coordinatorCourses = DB::table('course_coordinator_master')
            ->where(function ($query) use ($facultyPk) {
                $query->where('Coordinator_name', $facultyPk)
                    ->orWhere('Assistant_Coordinator_name', $facultyPk)
                    ->orWhereRaw('FIND_IN_SET(?, Assistant_Coordinator_name)', [$facultyPk]);
            })
            ->pluck('courses_master_pk');

        $activeCoordinatorCourses = $coordinatorCourses->isEmpty()
            ? collect()
            : CourseMaster::query()
                ->whereIn('pk', $coordinatorCourses)
                ->where('active_inactive', 1)
                ->where('end_date', '>=', now()->toDateString())
                ->pluck('pk');

        if ($activeCoordinatorCourses->isNotEmpty()
            && StudentMasterCourseMap::query()
                ->where('student_master_pk', $studentPk)
                ->whereIn('course_master_pk', $activeCoordinatorCourses)
                ->where('active_inactive', 1)
                ->exists()) {
            return true;
        }

        $groupMappings = DB::table('group_type_master_course_master_map')
            ->where('facility_id', $facultyPk)
            ->where('active_inactive', 1)
            ->get();

        if ($groupMappings->isEmpty()) {
            return false;
        }

        $activeCourseIds = CourseMaster::query()
            ->whereIn('pk', $groupMappings->pluck('course_name')->unique())
            ->where('active_inactive', 1)
            ->where('end_date', '>=', now()->toDateString())
            ->pluck('pk');

        if ($activeCourseIds->isEmpty()) {
            return false;
        }

        $activeGroupMappingPks = $groupMappings
            ->whereIn('course_name', $activeCourseIds)
            ->pluck('pk')
            ->unique();

        return StudentCourseGroupMap::query()
            ->where('student_master_pk', $studentPk)
            ->whereIn('group_type_master_course_master_map_pk', $activeGroupMappingPks)
            ->where('active_inactive', 1)
            ->exists();
    }
}
