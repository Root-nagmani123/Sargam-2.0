<?php

namespace Modules\Protocol\Services;

use App\Models\{EmployeeMaster,User};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * People lookups the Protocol module needs. Kept here rather than in the shared
 * User::getEmployeesAndFacultyForComplaint(), which Issue Management owns (PR #348 F-019).
 */
class ProtocolDirectory
{
    const APPROVER_ROLE = 'Protocol Approver';

    /**
     * Non-student employees with the login (user_credentials pk) the module grants
     * permissions to and recommends requests to. One row per employee.
     *
     * @return Collection { employee_pk, employee_name, mobile, designation_name, image_path, user_credentials_pk }
     */
    public function employees($departmentId = null): Collection
    {
        $query = DB::table('user_credentials as uc')
            ->join('employee_master as e', function ($join) {
                $join->on('uc.user_id', '=', 'e.pk');
                if (Schema::hasColumn('employee_master', 'pk_old')) {
                    $join->orOn('uc.user_id', '=', 'e.pk_old');
                }
            })
            ->leftJoin('designation_master as d', 'e.designation_master_pk', '=', 'd.pk')
            ->where('uc.user_category', '!=', 'S')
            ->select(
                'e.pk as employee_pk',
                DB::raw("TRIM(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, ''))) as employee_name"),
                DB::raw("COALESCE(e.mobile, '') as mobile"),
                'd.designation_name',
                DB::raw('MIN(uc.image_path) as image_path'),
                DB::raw('MIN(uc.pk) as user_credentials_pk')
            )
            ->orderBy('e.first_name')
            ->groupBy('e.pk', 'e.first_name', 'e.middle_name', 'e.last_name', 'e.mobile', 'd.designation_name');

        if ($departmentId) {
            $query->where('e.department_master_pk', $departmentId);
        }

        return $query->get();
    }

    /**
     * Employees holding the Protocol Approver role — the "Recommend to" list.
     * user_credentials_pk is the login that holds the role, since only that login
     * can open the manager queue (recommended_to_id === Auth::id()).
     */
    public function approvers(): Collection
    {
        $approverPkByEmployee = User::whereHas('roles', fn ($q) => $q->where('name', self::APPROVER_ROLE))
            ->orderBy('pk')
            ->get(['pk', 'user_id'])
            ->groupBy('user_id')
            ->map(fn ($logins) => $logins->first()->pk);

        return $this->employees()
            ->whereIn('employee_pk', $approverPkByEmployee->keys())
            ->each(function ($employee) use ($approverPkByEmployee) {
                $employee->user_credentials_pk = $approverPkByEmployee[$employee->employee_pk] ?? $employee->user_credentials_pk;
            })
            ->values();
    }

    /** user_credentials pks a request may be recommended to */
    public function approverLoginPks(): array
    {
        return $this->approvers()->pluck('user_credentials_pk')->map(fn ($pk) => (int) $pk)->all();
    }

    /**
     * The employee a login raises requests as: user_id is an employee pk or pk_old,
     * resolved by the codebase's rule (User::resolveEmployeePk). Null when it names
     * nobody or is ambiguous.
     */
    public function employeeFor(User $user): ?EmployeeMaster
    {
        $employeePk = User::resolveEmployeePk($user->user_id);

        return $employeePk === null
            ? null
            : EmployeeMaster::select('pk', 'department_master_pk')->find($employeePk);
    }
}
