<?php

namespace App\Services\COE;

use App\Models\CourseCordinatorMaster;
use App\Models\FacultyMaster;
use App\Models\User;

/**
 * Course Coordinator (CC) lookups for COE examination drives.
 *
 * A CC is not a Spatie role: course_coordinator_master.Coordinator_name holds
 * the CC's faculty_master.pk per course (one row per assistant, so it repeats).
 * A faculty reaches their login through faculty_master.employee_master_pk
 * (= user_credentials.user_id). Only the main CC is used, not assistants.
 */
class CourseCoordinatorService
{
    /** @return int[] faculty_master.pk of the CC(s) of a course */
    public function coordinatorFacultyPks(int $coursePk): array
    {
        return CourseCordinatorMaster::where('courses_master_pk', $coursePk)
            ->whereNotNull('Coordinator_name')
            ->pluck('Coordinator_name')
            ->map(fn ($pk) => (int) $pk)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** @return int[] login user_ids of the CC(s) of a course; a CC without a linked login is left out */
    public function coordinatorUserIds(int $coursePk): array
    {
        return FacultyMaster::whereIn('pk', $this->coordinatorFacultyPks($coursePk))
            ->whereNotNull('employee_master_pk')
            ->pluck('employee_master_pk')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** @return int[] courses the user is CC of */
    public function coursePksFor(User $user): array
    {
        $facultyPks = FacultyMaster::where('employee_master_pk', $user->user_id)->pluck('pk')->all();
        if ($facultyPks === []) {
            return [];
        }

        return CourseCordinatorMaster::whereIn('Coordinator_name', $facultyPks)
            ->pluck('courses_master_pk')
            ->map(fn ($pk) => (int) $pk)
            ->unique()
            ->values()
            ->all();
    }

    public function isCoordinatorOf(User $user, int $coursePk): bool
    {
        return in_array($coursePk, $this->coursePksFor($user), true);
    }
}
