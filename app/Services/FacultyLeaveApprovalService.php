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
     * Course IDs where the faculty is assigned as a stationed-leave approver.
     *
     * @return list<int>
     */
    public function getApproverCourseIds(int $facultyPk): array
    {
        return DB::table('stationed_leave_faculty_approver as slfa')
            ->join('stationed_leave_master as slm', 'slm.pk', '=', 'slfa.stationed_leave_master_pk')
            ->where('slfa.faculty_master_pk', $facultyPk)
            ->where('slm.active_inactive', 1)
            ->pluck('slm.course_master_pk')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resolve the user_ids of every faculty assigned to approve stationed leave
     * for the given course. Used to notify approvers when a request is submitted.
     *
     * @return list<int>
     */
    public function getApproverUserIdsForCourse(int $coursePk): array
    {
        $facultyPks = DB::table('stationed_leave_faculty_approver as slfa')
            ->join('stationed_leave_master as slm', 'slm.pk', '=', 'slfa.stationed_leave_master_pk')
            ->where('slm.course_master_pk', $coursePk)
            ->where('slm.active_inactive', 1)
            ->pluck('slfa.faculty_master_pk')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($facultyPks === []) {
            return [];
        }

        $faculties = FacultyMaster::query()
            ->whereIn('pk', $facultyPks)
            ->where('active_inactive', 1)
            ->get(['pk', 'first_name', 'last_name', 'full_name', 'email_id', 'alternate_email_id', 'mobile_no', 'employee_master_pk']);

        $userIds = [];
        foreach ($faculties as $faculty) {
            $userId = $this->resolveFacultyLoginUserId($faculty);
            if ($userId) {
                $userIds[] = $userId;
            }
        }

        return array_values(array_unique($userIds));
    }

    /**
     * Resolve the login user_credentials.user_id for a faculty record.
     *
     * The login is NOT faculty_master.employee_master_pk — faculty often sign in
     * through a credential that links only by mobile, email, or name (mirroring the
     * reverse matching in resolveFacultyPk()). We try those in the same order.
     */
    protected function resolveFacultyLoginUserId(object $faculty): ?int
    {
        // 1) Mobile match.
        if (! empty($faculty->mobile_no)) {
            $userId = DB::table('user_credentials')
                ->where('mobile_no', (string) $faculty->mobile_no)
                ->value('user_id');
            if ($userId) {
                return (int) $userId;
            }
        }

        // 2) Email match (primary or alternate) against the login user_name or email.
        foreach (array_filter([$faculty->email_id, $faculty->alternate_email_id]) as $email) {
            $email = strtolower(trim((string) $email));
            if ($email === '') {
                continue;
            }
            $userId = DB::table('user_credentials')
                ->whereRaw('LOWER(user_name) = ?', [$email])
                ->orWhereRaw('LOWER(email_id) = ?', [$email])
                ->value('user_id');
            if ($userId) {
                return (int) $userId;
            }
        }

        // 3) Normalized-name match (e.g. login "ankita.dhanda" == faculty "Ankita Dhanda").
        $nameKeys = array_filter([
            $this->normalizeIdentityKey($faculty->full_name ?? ''),
            $this->normalizeIdentityKey(trim(($faculty->first_name ?? '') . ' ' . ($faculty->last_name ?? ''))),
        ]);

        $firstNameToken = $this->normalizeIdentityKey((string) ($faculty->first_name ?? ''));
        foreach (array_unique($nameKeys) as $nameKey) {
            if ($nameKey === '') {
                continue;
            }
            // Narrow the scan with a LIKE on the first-name token, then compare on the
            // fully-normalized key (which ignores dots/spaces) in PHP.
            $candidates = DB::table('user_credentials')
                ->select('user_id', 'user_name')
                ->when($firstNameToken !== '', fn ($q) => $q->whereRaw('LOWER(user_name) LIKE ?', [$firstNameToken . '%']))
                ->get()
                ->filter(fn ($cred) => $this->normalizeIdentityKey((string) $cred->user_name) === $nameKey);

            if ($candidates->count() === 1) {
                return (int) $candidates->first()->user_id;
            }
        }

        // 4) Last resort: a credential whose user_id equals employee_master_pk.
        if (! empty($faculty->employee_master_pk)) {
            $userId = DB::table('user_credentials')
                ->where('user_id', (int) $faculty->employee_master_pk)
                ->value('user_id');
            if ($userId) {
                return (int) $userId;
            }
        }

        return null;
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
