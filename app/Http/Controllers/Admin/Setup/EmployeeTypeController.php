<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeTypeMaster;
use Illuminate\Validation\Rule;

class EmployeeTypeController extends Controller
{
    public function index(Request $request)
    {
        $employeeTypes = EmployeeTypeMaster::orderBy('pk','desc')->paginate(10);
        return view('admin.setup.employee_type.index', compact('employeeTypes'));
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
        if ($request->ajax()) {
            return response()->json(['success'=>true,'deleted'=>true]);
        }
        return redirect()->route('admin.setup.employee_type.index')->with('success','Deleted');
    }
}
