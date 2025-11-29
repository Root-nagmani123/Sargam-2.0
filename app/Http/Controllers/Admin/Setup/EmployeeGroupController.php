<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeGroupMaster;
use Illuminate\Validation\Rule;

class EmployeeGroupController extends Controller
{
    public function index(Request $request)
    {
        $employeeGroups = EmployeeGroupMaster::orderBy('pk','desc')->paginate(10);
        return view('admin.setup.employee_group.index', compact('employeeGroups'));
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
