<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\DataTableSearchHelper;
use Yajra\DataTables\Facades\DataTables;
use App\Models\DesignationMaster;
use Illuminate\Validation\Rule;

class DesignationMasterSetupController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable();
        }

        return view('admin.setup.designation_master.index');
    }

    /**
     * Server-side feed for the listing grid (search/sort/paginate happen in SQL).
     */
    protected function datatable()
    {
        $query = DesignationMaster::query();
        if (! DataTableSearchHelper::clientOrdered()) {
            $query->orderBy('pk', 'desc');
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('name', fn ($row) => e($row->designation_name))
            ->addColumn('action', function ($row) {
                return '<div class="d-flex gap-2">'
                    .'<a href="'.route('admin.setup.designation_master.edit', encrypt($row->pk)).'" class="text-success openEditDesignation" title="Edit">'
                    .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i></a>'
                    .'<form action="'.route('admin.setup.designation_master.delete', encrypt($row->pk)).'" method="POST" onsubmit="return confirm(\'Delete this Designation?\')">'
                    .csrf_field().method_field('DELETE')
                    .'<button type="submit" class="btn btn-link p-0 text-danger" title="Delete">'
                    .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i></button>'
                    .'</form></div>';
            })
            ->addColumn('status', function ($row) {
                return '<div class="form-check form-switch d-inline-block">'
                    .'<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    .' data-table="designation_master" data-column="active_inactive" data-id="'.e($row->pk).'"'
                    .($row->active_inactive == 1 ? ' checked' : '').'></div>';
            })
            ->setRowAttr(['data-pk' => fn ($row) => $row->pk])
            ->filterColumn('name', fn ($q, $keyword) => $q->where('designation_name', 'like', "%{$keyword}%"))
            ->orderColumn('name', 'designation_name $1')
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function create(Request $request)
    {
        if($request->ajax()) {
            return view('admin.setup.designation_master._form');
        }
        return redirect()->route('admin.setup.designation_master.index');
    }

    public function edit(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch(\Exception $e){ abort(404); }
        $designation = DesignationMaster::findOrFail($pk);
        if($request->ajax()) {
            return view('admin.setup.designation_master._form', compact('designation'));
        }
        return redirect()->route('admin.setup.designation_master.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'designation_name' => ['required','string','max:150', Rule::unique('designation_master','designation_name')],
        ]);
        $model = new DesignationMaster();
        $model->designation_name = $validated['designation_name'];
        $model->active_inactive = 1;
        $model->save();
        if($request->ajax()) {
            return response()->json([
                'success'=>true,
                'action'=>'create',
                'data'=>[
                    'pk'=>$model->pk,
                    'encrypted_pk'=>encrypt($model->pk),
                    'designation_name'=>$model->designation_name,
                    'active_inactive'=>$model->active_inactive,
                ]
            ]);
        }
        return redirect()->route('admin.setup.designation_master.index')->with('success','Designation created');
    }

    public function update(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch(\Exception $e){ abort(404); }
        $model = DesignationMaster::findOrFail($pk);
        $validated = $request->validate([
            'designation_name' => ['required','string','max:150', Rule::unique('designation_master','designation_name')->ignore($model->pk,'pk')],
        ]);
        $model->designation_name = $validated['designation_name'];
        $model->save();
        if($request->ajax()) {
            return response()->json([
                'success'=>true,
                'action'=>'update',
                'data'=>[
                    'pk'=>$model->pk,
                    'encrypted_pk'=>encrypt($model->pk),
                    'designation_name'=>$model->designation_name,
                    'active_inactive'=>$model->active_inactive,
                ]
            ]);
        }
        return redirect()->route('admin.setup.designation_master.index')->with('success','Designation updated');
    }

    public function delete(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch(\Exception $e){ abort(404); }
        $model = DesignationMaster::findOrFail($pk);
        $model->delete();
        if($request->ajax()) { return response()->json(['success'=>true,'deleted'=>true]); }
        return redirect()->route('admin.setup.designation_master.index')->with('success','Deleted');
    }
}
