<?php

namespace App\Services\FC;

use App\Models\FC\FcActivityDepartment;
use App\Models\FC\FcActivityDepartmentUser;
use App\Models\FC\FcActivityMaster;
use App\Models\FC\FcStaffActivityAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class FcPostArrivalAccessService
{
    private bool $memoizedAccess = false;

    private ?FcStaffActivityAccess $accessRowMemo = null;

    /**
     * Row with department_id null ⇒ FC activity coordinator (all departments).
     * No row ⇒ not coordinator; scope comes from fc_activity_department_user and/or a single department row.
     */
    private function accessRow(): ?FcStaffActivityAccess
    {
        if ($this->memoizedAccess) {
            return $this->accessRowMemo;
        }
        $this->memoizedAccess = true;

        $u = Auth::user()?->username;
        if (! $u) {
            return null;
        }
        $this->accessRowMemo = FcStaffActivityAccess::query()->where('user_name', $u)->first();

        return $this->accessRowMemo;
    }

    public function isCoordinator(): bool
    {
        $row = $this->accessRow();

        return $row !== null && $row->department_id === null;
    }

    /**
     * No fc_staff_activity_access row and no department assignments — full FC activities admin (all masters for entry, etc.).
     */
    private function hasLegacyFullAdminActivitiesAccess(): bool
    {
        return $this->accessRow() === null && $this->departmentIdsFromUserAssignments() === [];
    }

    /**
     * Activity setup (departments + masters) and related nav. Middleware `fc.activity.coordinator`.
     * True for: FC coordinator, staff listed in fc_activity_department_user, or legacy admins with
     * no staff-access row and no pivot (full access). False for single-department-only fc_staff rows.
     */
    public function canManageActivitySetup(): bool
    {
        if ($this->isCoordinator()) {
            return true;
        }
        if ($this->departmentIdsFromUserAssignments() !== []) {
            return true;
        }
        if ($this->accessRow() !== null) {
            return false;
        }

        return true;
    }

    /**
     * Legacy single-department restriction from fc_staff_activity_access (used when pivot has no rows).
     *
     * @return int|null One department id, or null if unrestricted
     */
    public function restrictedDepartmentId(): ?int
    {
        $row = $this->accessRow();

        if ($row === null || $row->department_id === null) {
            return null;
        }

        return (int) $row->department_id;
    }

    /**
     * Department IDs from fc_activity_department_user (assignments on department setup screen).
     *
     * @return array<int>
     */
    private function departmentIdsFromUserAssignments(): array
    {
        $pk = Auth::user()?->getAuthIdentifier();
        if (! $pk) {
            return [];
        }

        return FcActivityDepartmentUser::query()
            ->join('fc_activity_department as d', 'd.id', '=', 'fc_activity_department_user.fc_activity_department_id')
            ->where('fc_activity_department_user.user_credentials_pk', $pk)
            ->where('d.status', 1)
            ->pluck('d.id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Departments visible in navigation & status pickers.
     *
     * @return Collection<int, FcActivityDepartment>
     */
    public function visibleDepartments(): Collection
    {
        return FcActivityDepartment::query()->active()->ordered()->get();
    }

    /**
     * Legacy / global FC activities views (status, reports) — no department scoping.
     *
     * @return null Always unrestricted.
     */
    public function allowedDepartmentIds(): ?array
    {
        return null;
    }

    /**
     * Department scope for FC Activities home: “Select Activity” dropdown and OT activity entry (store/update/delete).
     *
     * @return array<int>|null null = all active departments (coordinator or legacy full-access admin)
     */
    public function departmentIdsForActivityEntry(): ?array
    {
        if ($this->isCoordinator()) {
            return null;
        }
        if ($this->hasLegacyFullAdminActivitiesAccess()) {
            return null;
        }
        $fromPivot = $this->departmentIdsFromUserAssignments();
        if ($fromPivot !== []) {
            return $fromPivot;
        }
        $rid = $this->restrictedDepartmentId();
        if ($rid !== null) {
            return [$rid];
        }

        return [];
    }

    public function assertDepartmentCodeAllowed(string $deptCode): void
    {
        FcActivityDepartment::query()->where('code', $deptCode)->where('status', 1)->firstOrFail();
    }

    public function assertMenuidAllowedForUser(string $menuid): void
    {
        $master = FcActivityMaster::query()->where('menuid', $menuid)->where('status', 1)->first();
        if (! $master) {
            abort(422, 'Unknown activity.');
        }
    }

    /**
     * OT activity entry on FC Activities home — active master must fall in {@see departmentIdsForActivityEntry()}.
     */
    public function assertMenuidAllowedForOtEntry(string $menuid): void
    {
        $master = FcActivityMaster::query()->where('menuid', $menuid)->where('status', 1)->first();
        if (! $master) {
            abort(422, 'Unknown activity.');
        }
        $ids = $this->departmentIdsForActivityEntry();
        if ($ids === null) {
            return;
        }
        if ($ids === [] || ! in_array((int) $master->department_id, $ids, true)) {
            abort(403, 'Activity not allowed for your department assignments.');
        }
    }

    public function medicalDepartmentId(): ?int
    {
        return FcActivityDepartment::query()->where('code', 'medical')->where('status', 1)->value('id');
    }

    public function canAccessMedicalModule(): bool
    {
        if ($this->isCoordinator()) {
            return true;
        }
        if ($this->canManageActivitySetup()) {
            return true;
        }
        $mid = $this->medicalDepartmentId();
        if ($mid === null) {
            return false;
        }

        $fromPivot = $this->departmentIdsFromUserAssignments();
        if ($fromPivot !== []) {
            return in_array($mid, $fromPivot, true);
        }
        $rid = $this->restrictedDepartmentId();
        if ($rid !== null) {
            return $rid === $mid;
        }

        return false;
    }
}
