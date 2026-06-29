<?php

namespace App\Services;

use App\Models\FacultyMaster;
use App\Models\LeaveApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FacultyLeaveApprovalService
{
    /**
     * Resolve faculty_master.pk for the logged-in leave approver.
     * Falls back when user_id is not linked to faculty_master (e.g. Faculty role on a student-category login).
     */
    public function resolveFacultyPk(): ?int
    {
        if ($pk = get_auth_faculty_master_pk()) {
            return $pk;
        }

        $user = Auth::user();
        if (! $user || ! is_faculty_portal_user()) {
            return null;
        }

        $approverFacultyIds = DB::table('stationed_leave_faculty_approver')
            ->distinct()
            ->pluck('faculty_master_pk')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if ($approverFacultyIds === []) {
            return null;
        }

        $candidates = FacultyMaster::query()
            ->whereIn('pk', $approverFacultyIds)
            ->where('active_inactive', 1)
            ->get(['pk', 'first_name', 'last_name', 'full_name', 'email_id', 'alternate_email_id', 'mobile_no', 'employee_master_pk']);

        if (! empty($user->mobile_no)) {
            $match = $candidates->first(fn ($faculty) => (string) $faculty->mobile_no === (string) $user->mobile_no);
            if ($match) {
                return (int) $match->pk;
            }
        }

        $login = strtolower(trim((string) $user->user_name));
        if ($login !== '') {
            $match = $candidates->first(function ($faculty) use ($login) {
                foreach ([$faculty->email_id, $faculty->alternate_email_id] as $email) {
                    if ($email && strtolower(trim((string) $email)) === $login) {
                        return true;
                    }
                }

                return false;
            });
            if ($match) {
                return (int) $match->pk;
            }

            $loginKey = $this->normalizeIdentityKey($login);
            if ($loginKey !== '') {
                $matches = $candidates->filter(function ($faculty) use ($loginKey) {
                    $nameKey = $this->normalizeIdentityKey($faculty->full_name ?? '');
                    if ($nameKey !== '' && $nameKey === $loginKey) {
                        return true;
                    }

                    $partsKey = $this->normalizeIdentityKey(trim(
                        ($faculty->first_name ?? '') . ' ' . ($faculty->last_name ?? '')
                    ));

                    return $partsKey !== '' && $partsKey === $loginKey;
                });

                if ($matches->count() === 1) {
                    return (int) $matches->first()->pk;
                }
            }
        }

        if (($user->user_category ?? '') === 'E' && (int) $user->user_id > 0) {
            $match = $candidates->first(fn ($faculty) => (int) $faculty->employee_master_pk === (int) $user->user_id);
            if ($match) {
                return (int) $match->pk;
            }
        }

        return null;
    }

    protected function normalizeIdentityKey(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($value)) ?? '';
    }

    /**
     * Course IDs where the faculty is assigned as a stationed-leave approval authority.
     *
     * @return list<int>
     */
    public function getApproverCourseIds(int $facultyPk): array
    {
        return DB::table('stationed_leave_faculty_approver as slfa')
            ->join('stationed_leave_master as slm', 'slm.pk', '=', 'slfa.stationed_leave_master_pk')
            ->where('slfa.faculty_master_pk', $facultyPk)
            ->where('slfa.is_approval_authority', 1)
            ->where('slm.active_inactive', 1)
            ->pluck('slm.course_master_pk')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resolve the user_ids of every faculty marked as approval authority for stationed
     * leave on the given course. Used to notify approvers when a request is submitted.
     *
     * @return list<int>
     */
    public function getApproverUserIdsForCourse(int $coursePk): array
    {
        $facultyPks = DB::table('stationed_leave_faculty_approver as slfa')
            ->join('stationed_leave_master as slm', 'slm.pk', '=', 'slfa.stationed_leave_master_pk')
            ->where('slm.course_master_pk', $coursePk)
            ->where('slm.active_inactive', 1)
            ->where('slfa.is_approval_authority', 1)
            ->pluck('slfa.faculty_master_pk')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($facultyPks === []) {
            return [];
        }

        return FacultyMaster::query()
            ->whereIn('pk', $facultyPks)
            ->where('active_inactive', 1)
            ->pluck('employee_master_pk')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function canFacultyAccessLeave(int $facultyPk, LeaveApplication $application): bool
    {
        if ($application->leave_type !== LeaveApplication::TYPE_STATIONED_LEAVE) {
            return false;
        }

        return in_array(
            (int) $application->course_master_pk,
            $this->getApproverCourseIds($facultyPk),
            true
        );
    }

    public function canFacultyActOnLeave(int $facultyPk, LeaveApplication $application): bool
    {
        if ((int) $application->status !== LeaveApplication::STATUS_PENDING) {
            return false;
        }

        if ($application->leave_type !== LeaveApplication::TYPE_STATIONED_LEAVE) {
            return false;
        }

        return $this->canFacultyAccessLeave($facultyPk, $application);
    }

    public function studentDisplayName(?object $student): string
    {
        if (! $student) {
            return '-';
        }

        $name = trim((string) ($student->display_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        return trim(implode(' ', array_filter([
            $student->first_name ?? '',
            $student->middle_name ?? '',
            $student->last_name ?? '',
        ]))) ?: '-';
    }
}
