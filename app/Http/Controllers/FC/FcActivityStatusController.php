<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcActivityDepartment;
use App\Models\FC\FcActivityMaster;
use App\Models\FC\FcOtActivity;
use App\Models\FC\FcOtDetail;
use App\Services\FC\FcPostArrivalAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FcActivityStatusController extends Controller
{
    public function __construct(private FcPostArrivalAccessService $access)
    {
    }

    public function picker(): RedirectResponse|View
    {
        $depts = $this->access->visibleDepartments();
        if ($depts->count() === 1) {
            return redirect()->route(
                'fc-reg.admin.activities.status.grid',
                ['deptCode' => $depts->first()->code]
            );
        }

        return view('admin.fc-activities.status.picker', [
            'departments' => $depts,
        ]);
    }

    public function departmentGrid(string $deptCode): View
    {
        $this->access->assertDepartmentCodeAllowed($deptCode);

        $dept = FcActivityDepartment::query()->where('code', $deptCode)->where('status', 1)->firstOrFail();

        $masters = FcActivityMaster::query()
            ->where('department_id', $dept->id)
            ->active()
            ->ordered()
            ->get(['id', 'menuid', 'menun']);

        $ots = FcOtDetail::query()->active()->orderBy('otcode')->get([
            'username', 'otname', 'otcode', 'mobileno', 'service',
        ]);

        $menuids = $masters->pluck('menuid')->all();

        $actMap = FcOtActivity::query()
            ->where('status', 1)
            ->when($menuids !== [], fn ($q) => $q->whereIn('activity', $menuids))
            ->get(['username', 'activity', 'activityval'])
            ->groupBy('username')
            ->map(fn ($acts) => $acts->pluck('activityval', 'activity'));

        $columnDefs = $masters->map(fn ($m) => [
            'menuid' => $m->menuid,
            'header' => $m->menun,
        ]);

        $rows = $ots->map(function ($ot) use ($actMap, $menuids) {
            $vals = $actMap[$ot->username] ?? collect();

            return [
                'username' => $ot->username,
                'otname' => $ot->otname,
                'otcode' => $ot->otcode,
                'mobileno' => $ot->mobileno,
                'service' => $ot->service,
                'activities' => array_combine(
                    $menuids,
                    array_map(fn ($c) => $vals[$c] ?? null, $menuids)
                ),
            ];
        });

        return view('admin.fc-activities.status.grid', [
            'department' => $dept,
            'columnDefs' => $columnDefs,
            'rows' => $rows,
            'combined' => false,
            'showSetupLinks' => $this->access->canManageActivitySetup(),
            'canAccessMedical' => $this->access->canAccessMedicalModule(),
        ]);
    }

    /**
     * Coordinator-only: OTs × all activities (active) across departments.
     */
    public function matrix(): View
    {
        $departments = FcActivityDepartment::query()->active()->ordered()->with([
            'masters' => fn ($q) => $q->where('status', 1)->ordered(),
        ])->get();

        $masters = collect();
        foreach ($departments as $d) {
            foreach ($d->masters as $m) {
                $masters->push([
                    'dept_code' => $d->code,
                    'dept_name' => $d->name,
                    'menuid' => $m->menuid,
                    'menun' => $m->menun,
                ]);
            }
        }

        $menuids = $masters->pluck('menuid')->unique()->values()->all();

        $columnDefs = $masters->map(fn ($row) => [
            'menuid' => $row['menuid'],
            'header' => $row['dept_name'].' — '.$row['menun'],
        ]);

        $ots = FcOtDetail::query()->active()->orderBy('otcode')->get([
            'username', 'otname', 'otcode',
        ]);

        $actMap = FcOtActivity::query()
            ->where('status', 1)
            ->when($menuids !== [], fn ($q) => $q->whereIn('activity', $menuids))
            ->get(['username', 'activity', 'activityval'])
            ->groupBy('username')
            ->map(fn ($acts) => $acts->pluck('activityval', 'activity'));

        $rows = $ots->map(function ($ot) use ($actMap, $menuids) {
            $vals = $actMap[$ot->username] ?? collect();

            return [
                'username' => $ot->username,
                'otname' => $ot->otname,
                'otcode' => $ot->otcode,
                'activities' => array_combine(
                    $menuids,
                    array_map(fn ($c) => $vals[$c] ?? null, $menuids)
                ),
            ];
        });

        return view('admin.fc-activities.status.grid', [
            'department' => null,
            'columnDefs' => $columnDefs,
            'rows' => $rows,
            'combined' => true,
            'showSetupLinks' => $this->access->canManageActivitySetup(),
            'canAccessMedical' => $this->access->canAccessMedicalModule(),
        ]);
    }
}
