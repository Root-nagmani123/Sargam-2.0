<?php

namespace App\Http\Controllers\Admin\Setup;

use App\DataTables\Master\EmployeeTypeMasterDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\DataTableSearchHelper;
use Yajra\DataTables\Facades\DataTables;
use App\Models\EmployeeTypeMaster;
use Illuminate\Validation\Rule;

class EmployeeTypeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable();
        }

        return view('admin.setup.employee_type.index');
    }

    /**
     * Server-side feed for the listing grid (search/sort/paginate happen in SQL).
     */
    protected function datatable()
    {
        $query = EmployeeTypeMaster::query();
        if (! DataTableSearchHelper::clientOrdered()) {
            $query->orderBy('pk', 'desc');
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('name', fn ($row) => e($row->category_type_name))
            ->addColumn('action', function ($row) {
                return '<div class="d-flex gap-2">'
                    .'<a href="'.route('admin.setup.employee_type.edit', encrypt($row->pk)).'" class="text-success openEditEmployeeType" title="Edit">'
                    .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i></a>'
                    .'<form action="'.route('admin.setup.employee_type.delete', encrypt($row->pk)).'" method="POST" onsubmit="return confirm(\'Delete this Employee Type?\')">'
                    .csrf_field().method_field('DELETE')
                    .'<button type="submit" class="btn btn-link p-0 text-danger" title="Delete">'
                    .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i></button>'
                    .'</form></div>';
            })
            ->addColumn('status', function ($row) {
                return '<div class="form-check form-switch d-inline-block">'
                    .'<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    .' data-table="employee_type_master" data-column="active_inactive" data-id="'.e($row->pk).'"'
                    .($row->active_inactive == 1 ? ' checked' : '').'></div>';
            })
            ->setRowAttr(['data-pk' => fn ($row) => $row->pk])
            ->filterColumn('name', fn ($q, $keyword) => $q->where('category_type_name', 'like', "%{$keyword}%"))
            ->orderColumn('name', 'category_type_name $1')
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            return view('admin.setup.employee_type._form');
        }
        return redirect()->route('admin.setup.employee_type.index');
    }

    public function edit(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }
        $employeeType = EmployeeTypeMaster::findOrFail($pk);
        if ($request->ajax()) {
            return view('admin.setup.employee_type._form', compact('employeeType'));
        }
        return redirect()->route('admin.setup.employee_type.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_type_name' => [
                'required', 'string', 'max:150',
                Rule::unique('employee_type_master', 'category_type_name')
            ],
        ]);

        $model = new EmployeeTypeMaster();
        $model->category_type_name = $validated['employee_type_name'];
        $model->active_inactive = 1;
        $model->save();

        EmployeeTypeMasterDataTable::bumpListingCacheEpoch();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'action' => 'create',
                'data' => [
                    'pk' => $model->pk,
                    'encrypted_pk' => encrypt($model->pk),
                    'category_type_name' => $model->category_type_name,
                    'active_inactive' => $model->active_inactive,
                ]
            ]);
        }
        return redirect()->route('admin.setup.employee_type.index')->with('success','Employee Type created');
    }

    public function update(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }
        $model = EmployeeTypeMaster::findOrFail($pk);
        $validated = $request->validate([
            'employee_type_name' => [
                'required','string','max:150',
                Rule::unique('employee_type_master','category_type_name')->ignore($model->pk,'pk')
            ],
        ]);
        $model->category_type_name = $validated['employee_type_name'];
        $model->save();

        EmployeeTypeMasterDataTable::bumpListingCacheEpoch();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'action' => 'update',
                'data' => [
                    'pk' => $model->pk,
                    'encrypted_pk' => encrypt($model->pk),
                    'category_type_name' => $model->category_type_name,
                    'active_inactive' => $model->active_inactive,
                ]
            ]);
        }
        return redirect()->route('admin.setup.employee_type.index')->with('success','Employee Type updated');
    }

    public function delete(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch (\Exception $e) { abort(404); }
        $model = EmployeeTypeMaster::findOrFail($pk);
        $model->delete();

        EmployeeTypeMasterDataTable::bumpListingCacheEpoch();

        if ($request->ajax()) {
            return response()->json(['success'=>true,'deleted'=>true]);
        }
        return redirect()->route('admin.setup.employee_type.index')->with('success','Deleted');
    }
}
