<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcActivityDepartment;
use App\Models\FC\FcActivityMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class FcActivityMasterManageController extends Controller
{
    public function index(Request $request): View
    {
        $deptFilter = $request->query('department_id');
        $departments = FcActivityDepartment::query()->ordered()->get();

        return view('admin.fc-activities.setup.masters.index', compact('departments', 'deptFilter'));
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = FcActivityMaster::query()
            ->with('department:id,code,name')
            ->when(
                $request->filled('department_id'),
                fn ($q) => $q->where('department_id', $request->input('department_id'))
            );

        return DataTables::eloquent($query)
            ->addColumn('dept_name', fn (FcActivityMaster $m) => e($m->department->name ?? ''))
            ->filterColumn('dept_name', function ($q, $keyword) {
                $q->whereHas('department', function ($q2) use ($keyword) {
                    $q2->where('name', 'like', '%'.$keyword.'%');
                });
            })
            ->editColumn('menuid', fn (FcActivityMaster $m) => '<code>'.e($m->menuid).'</code>')
            ->editColumn('ccode', fn (FcActivityMaster $m) => $m->ccode ? e($m->ccode) : '—')
            ->addColumn('status_display', fn (FcActivityMaster $m) => $m->status ? 'On' : 'Off')
            ->addColumn('joined_display', fn (FcActivityMaster $m) => $m->is_joined_marker ? 'Yes' : '')
            ->addColumn('action', function (FcActivityMaster $m) use ($request) {
                $returnDept = (string) $request->input('department_id', '');
                $payload = [
                    'updateUrl' => route('fc-reg.admin.activity-setup.masters.update', $m),
                    'edit_master_id' => $m->id,
                    'department_id' => (int) $m->department_id,
                    'menuid' => $m->menuid,
                    'menun' => $m->menun,
                    'ccode' => $m->ccode ?? '',
                    'sort_order' => (int) $m->sort_order,
                    'status' => (int) $m->status,
                    'entry_policy' => $m->entry_policy,
                    'is_joined_marker' => (int) $m->is_joined_marker,
                ];
                $json = e(json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));
                $destroy = route('fc-reg.admin.activity-setup.masters.destroy', $m);

                return '<button type="button" class="btn btn-link btn-sm p-0 js-master-edit" data-bs-toggle="modal" data-bs-target="#modalMasterEdit" data-master-edit="'.$json.'">Edit</button>'
                    .'<form action="'.e($destroy).'" method="POST" class="d-inline" onsubmit="return confirm(\'Delete activity master? Existing OT rows may orphan.\')">'
                    .csrf_field().method_field('DELETE')
                    .'<input type="hidden" name="return_department_id" value="'.e($returnDept).'">'
                    .'<button type="submit" class="btn btn-link btn-sm text-danger p-0">Delete</button></form>';
            })
            ->rawColumns(['menuid', 'action'])
            ->toJson();
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request, null);

        DB::transaction(function () use ($validated) {
            if (($validated['is_joined_marker'] ?? 0) === 1) {
                FcActivityMaster::query()->update(['is_joined_marker' => 0]);
            }
            FcActivityMaster::create($validated);
        });

        return $this->redirectToMastersIndex($request, 'Activity created.');
    }

    public function update(Request $request, FcActivityMaster $master): RedirectResponse
    {
        $validated = $this->validatePayload($request, (int) $master->id);

        DB::transaction(function () use ($validated, $master) {
            if (($validated['is_joined_marker'] ?? 0) === 1) {
                FcActivityMaster::query()->where('id', '!=', $master->id)->update(['is_joined_marker' => 0]);
            }
            $master->update($validated);
        });

        return $this->redirectToMastersIndex($request, 'Activity updated.');
    }

    public function destroy(Request $request, FcActivityMaster $master): RedirectResponse
    {
        $master->delete();

        return $this->redirectToMastersIndex($request, 'Activity deleted.');
    }

    private function redirectToMastersIndex(Request $request, string $message): RedirectResponse
    {
        $dept = $request->input('return_department_id', $request->query('department_id'));
        $qs = $dept !== null && $dept !== '' ? ['department_id' => $dept] : [];

        return redirect()->route('fc-reg.admin.activity-setup.masters.index', $qs)->with('success', $message);
    }

    private function validatePayload(Request $request, ?int $id = null): array
    {
        $uniqueRule = $id !== null
            ? 'required|string|max:30|regex:/^[a-z0-9_]+$/|unique:fc_activity_master,menuid,'.$id.',id'
            : 'required|string|max:30|regex:/^[a-z0-9_]+$/|unique:fc_activity_master,menuid';

        $validated = $request->validate([
            'department_id' => 'required|exists:fc_activity_department,id',
            'menuid' => $uniqueRule,
            'menun' => 'required|string|max:100',
            'ccode' => 'nullable|string|max:120',
            'sort_order' => 'nullable|integer|min:0|max:99999',
            'status' => 'required|in:0,1',
            'entry_policy' => 'required|in:unique,upsert,repeat',
        ]);

        $validated['ccode'] = trim((string) ($validated['ccode'] ?? '')) ?: null;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_joined_marker'] = $request->boolean('is_joined_marker') ? 1 : 0;

        return $validated;
    }
}
