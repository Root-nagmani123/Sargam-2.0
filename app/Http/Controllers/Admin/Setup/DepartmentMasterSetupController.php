<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DepartmentMaster;
use Illuminate\Validation\Rule;

class DepartmentMasterSetupController extends Controller
{
    public function index(Request $request)
    {
        $departments = DepartmentMaster::orderBy('pk','desc')->paginate(10);
        return view('admin.setup.department_master.index', compact('departments'));
    }

    public function create(Request $request)
    {
        if($request->ajax()) {
            return view('admin.setup.department_master._form');
        }
        return redirect()->route('admin.setup.department_master.index');
    }

    public function edit(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch(\Exception $e){ abort(404); }
        $department = DepartmentMaster::findOrFail($pk);
        if($request->ajax()) {
            return view('admin.setup.department_master._form', compact('department'));
        }
        return redirect()->route('admin.setup.department_master.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_name' => ['required','string','max:150', Rule::unique('department_master','department_name')],
        ]);
        $model = new DepartmentMaster();
        $model->department_name = $validated['department_name'];
        $model->active_inactive = 1;
        $model->save();
        if($request->ajax()) {
            return response()->json([
                'success'=>true,
                'action'=>'create',
                'data'=>[
                    'pk'=>$model->pk,
                    'encrypted_pk'=>encrypt($model->pk),
                    'department_name'=>$model->department_name,
                    'active_inactive'=>$model->active_inactive,
                ]
            ]);
        }
        return redirect()->route('admin.setup.department_master.index')->with('success','Department created');
    }

    public function update(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch(\Exception $e){ abort(404); }
        $model = DepartmentMaster::findOrFail($pk);
        $validated = $request->validate([
            'department_name' => ['required','string','max:150', Rule::unique('department_master','department_name')->ignore($model->pk,'pk')],
        ]);
        $model->department_name = $validated['department_name'];
        $model->save();
        if($request->ajax()) {
            return response()->json([
                'success'=>true,
                'action'=>'update',
                'data'=>[
                    'pk'=>$model->pk,
                    'encrypted_pk'=>encrypt($model->pk),
                    'department_name'=>$model->department_name,
                    'active_inactive'=>$model->active_inactive,
                ]
            ]);
        }
        return redirect()->route('admin.setup.department_master.index')->with('success','Department updated');
    }

    public function delete(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch(\Exception $e){ abort(404); }
        $model = DepartmentMaster::findOrFail($pk);
        $model->delete();
        if($request->ajax()) { return response()->json(['success'=>true,'deleted'=>true]); }
        return redirect()->route('admin.setup.department_master.index')->with('success','Deleted');
    }
}
