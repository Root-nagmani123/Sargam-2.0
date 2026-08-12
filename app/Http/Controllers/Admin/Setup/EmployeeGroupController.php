<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\DataTableSearchHelper;
use Yajra\DataTables\Facades\DataTables;
use App\Models\EmployeeGroupMaster;
use Illuminate\Validation\Rule;

class EmployeeGroupController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable();
        }

        return view('admin.setup.employee_group.index');
    }

    /**
     * Server-side feed for the listing grid (search/sort/paginate happen in SQL).
     */
    protected function datatable()
    {
        $query = EmployeeGroupMaster::query();
        if (! DataTableSearchHelper::clientOrdered()) {
            $query->orderBy('pk', 'desc');
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('name', fn ($row) => e($row->emp_group_name))
            ->addColumn('action', function ($row) {
                return '<div class="d-flex gap-2">'
                    .'<a href="'.route('admin.setup.employee_group.edit', encrypt($row->pk)).'" class="text-success openEditEmployeeGroup" title="Edit">'
                    .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i></a>'
                    .'<form action="'.route('admin.setup.employee_group.delete', encrypt($row->pk)).'" method="POST" onsubmit="return confirm(\'Delete this Employee Group?\')">'
                    .csrf_field().method_field('DELETE')
                    .'<button type="submit" class="btn btn-link p-0 text-danger" title="Delete">'
                    .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i></button>'
                    .'</form></div>';
            })
            ->addColumn('status', function ($row) {
                return '<div class="form-check form-switch d-inline-block">'
                    .'<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    .' data-table="employee_group_master" data-column="active_inactive" data-id="'.e($row->pk).'"'
                    .($row->active_inactive == 1 ? ' checked' : '').'></div>';
            })
            ->setRowAttr(['data-pk' => fn ($row) => $row->pk])
            ->filterColumn('name', fn ($q, $keyword) => $q->where('emp_group_name', 'like', "%{$keyword}%"))
            ->orderColumn('name', 'emp_group_name $1')
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            return view('admin.setup.employee_group._form');
        }
        return redirect()->route('admin.setup.employee_group.index');
    }

    public function edit(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch(\Exception $e){ abort(404); }
        $employeeGroup = EmployeeGroupMaster::findOrFail($pk);
        if ($request->ajax()) {
            return view('admin.setup.employee_group._form', compact('employeeGroup'));
        }
        return redirect()->route('admin.setup.employee_group.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_group_name' => ['required','string','max:150', Rule::unique('employee_group_master','emp_group_name')],
        ]);
        $model = new EmployeeGroupMaster();
        $model->emp_group_name = $validated['employee_group_name'];
        $model->active_inactive = 1;
        $model->save();
        if ($request->ajax()) {
            return response()->json([
                'success'=>true,
                'action'=>'create',
                'data'=>[
                    'pk'=>$model->pk,
                    'encrypted_pk'=>encrypt($model->pk),
                    'emp_group_name'=>$model->emp_group_name,
                    'active_inactive'=>$model->active_inactive,
                ]
            ]);
        }
        return redirect()->route('admin.setup.employee_group.index')->with('success','Employee Group created');
    }

    public function update(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch(\Exception $e){ abort(404); }
        $model = EmployeeGroupMaster::findOrFail($pk);
        $validated = $request->validate([
            'employee_group_name' => ['required','string','max:150', Rule::unique('employee_group_master','emp_group_name')->ignore($model->pk,'pk')],
        ]);
        $model->emp_group_name = $validated['employee_group_name'];
        $model->save();
        if ($request->ajax()) {
            return response()->json([
                'success'=>true,
                'action'=>'update',
                'data'=>[
                    'pk'=>$model->pk,
                    'encrypted_pk'=>encrypt($model->pk),
                    'emp_group_name'=>$model->emp_group_name,
                    'active_inactive'=>$model->active_inactive,
                ]
            ]);
        }
        return redirect()->route('admin.setup.employee_group.index')->with('success','Employee Group updated');
    }

    public function delete(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch(\Exception $e){ abort(404); }
        $model = EmployeeGroupMaster::findOrFail($pk);
        $model->delete();
        if($request->ajax()) { return response()->json(['success'=>true,'deleted'=>true]); }
        return redirect()->route('admin.setup.employee_group.index')->with('success','Deleted');
    }
}
