<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcActivityDepartment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class FcActivityDepartmentController extends Controller
{
    public function index(): View
    {
        $staffForAssignment = DB::table('user_credentials')
            ->where('user_category', '!=', 'S')
            ->whereNotNull('user_name')
            ->where('user_name', '!=', '')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->select(['pk', 'user_name', 'first_name', 'last_name'])
            ->get();

        return view('admin.fc-activities.setup.departments.index', compact('staffForAssignment'));
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = FcActivityDepartment::query()
            ->withCount(['masters', 'staffAssignments'])
            ->with('staffAssignments:id,fc_activity_department_id,user_credentials_pk');

        return DataTables::eloquent($query)
            ->editColumn('code', fn (FcActivityDepartment $d) => '<code>'.e($d->code).'</code>')
            ->addColumn('status_display', fn (FcActivityDepartment $d) => $d->status ? 'Active' : 'Off')
            ->addColumn('staff_count', fn (FcActivityDepartment $d) => (int) $d->staff_assignments_count)
            ->addColumn('action', function (FcActivityDepartment $d) {
                $payload = [
                    'updateUrl' => route('fc-reg.admin.activity-setup.departments.update', $d),
                    'edit_department_id' => $d->id,
                    'code' => $d->code,
                    'name' => $d->name,
                    'sort_order' => (int) $d->sort_order,
                    'status' => (int) $d->status,
                    'assigned_user_pks' => $d->staffAssignments->pluck('user_credentials_pk')->values()->all(),
                ];
                $json = e(json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));
                $destroy = route('fc-reg.admin.activity-setup.departments.destroy', $d);

                return '<button type="button" class="btn btn-link btn-sm p-0 js-dept-edit" data-bs-toggle="modal" data-bs-target="#modalDeptEdit" data-dept-edit="'.$json.'">Edit</button>'
                    .'<form action="'.e($destroy).'" method="POST" class="d-inline" onsubmit="return confirm(\'Delete?\')">'
                    .csrf_field().method_field('DELETE')
                    .'<button type="submit" class="btn btn-link btn-sm text-danger p-0">Delete</button></form>';
            })
            ->rawColumns(['code', 'action'])
            ->toJson();
    }

    public function store(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'code' => 'required|string|max:40|regex:/^[a-z0-9_]+$/|unique:fc_activity_department,code',
            'name' => 'required|string|max:150',
            'sort_order' => 'nullable|integer|min:0|max:99999',
            'assigned_user_pks' => 'nullable|array',
            'assigned_user_pks.*' => [
                'integer',
                Rule::exists('user_credentials', 'pk')->where(fn ($q) => $q->where('user_category', '!=', 'S')),
            ],
        ]);
        $v['status'] = 1;
        $v['sort_order'] = $v['sort_order'] ?? 0;
        unset($v['assigned_user_pks']);

        $department = FcActivityDepartment::create($v);
        $department->syncAssignedStaffPks($request->input('assigned_user_pks', []));

        return redirect()->route('fc-reg.admin.activity-setup.departments.index')->with('success', 'Department added.');
    }

    public function update(Request $request, FcActivityDepartment $department): RedirectResponse
    {
        $v = $request->validate([
            'code' => 'required|string|max:40|regex:/^[a-z0-9_]+$/|unique:fc_activity_department,code,'.$department->id,
            'name' => 'required|string|max:150',
            'sort_order' => 'nullable|integer|min:0|max:99999',
            'status' => 'required|in:0,1',
            'assigned_user_pks' => 'nullable|array',
            'assigned_user_pks.*' => [
                'integer',
                Rule::exists('user_credentials', 'pk')->where(fn ($q) => $q->where('user_category', '!=', 'S')),
            ],
        ]);
        $assigned = $request->input('assigned_user_pks', []);
        unset($v['assigned_user_pks']);

        $department->update($v);
        $department->syncAssignedStaffPks($assigned);

        return redirect()->route('fc-reg.admin.activity-setup.departments.index')->with('success', 'Department updated.');
    }

    public function destroy(FcActivityDepartment $department): RedirectResponse
    {
        if ($department->masters()->exists()) {
            return back()->with('error', 'Cannot delete department with linked activities.');
        }

        $department->delete();

        return redirect()->route('fc-reg.admin.activity-setup.departments.index')->with('success', 'Department deleted.');
    }
}
