<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Master\EmployeeTypeMasterDataTable;
use App\Models\EmployeeTypeMaster;
use Illuminate\Validation\Rule;

class EmployeeTypeMasterController extends Controller
{
    function index()
    {
        $employeeTypeMaster = new EmployeeTypeMasterDataTable;
        return $employeeTypeMaster->render('admin.master.employee_type.index');
        // return view('admin.master.employee_type.index');
    }
    function create()
    {
        return redirect()->route('master.employee.type.index', ['open_etm_modal' => 'add']);
    }
    function store(Request $request)
    {

        $id = $request->pk ? decrypt($request->pk) : null;

        $rules = [
            'employee_type_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employee_type_master', 'category_type_name')->ignore($id, 'pk'),
            ],
        ];

        $request->validate($rules);

        $employeeType = $id ? EmployeeTypeMaster::find($id) : new EmployeeTypeMaster();

        if ($id && !$employeeType) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Employee Type not found.'], 404);
            }

            return redirect()->back()->with('error', 'Employee Type not found.');
        }
        
        $employeeType->category_type_name = $request->employee_type_name;
        $employeeType->save();

        $message = $id ? 'Employee Type updated successfully.' : 'Employee Type created successfully.';

        EmployeeTypeMasterDataTable::bumpListingCacheEpoch();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('master.employee.type.index')->with('success', $message);

    }
    function edit($id)
    {
        try {
            $employeeTypeMaster = EmployeeTypeMaster::findOrFail(decrypt($id));

            return redirect()->route('master.employee.type.index', [
                'open_etm_modal' => 'edit',
                'etm_pk' => $id,
                'etm_name' => $employeeTypeMaster->category_type_name,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('master.employee.type.index')
                ->with('error', 'Failed to edit employee type: ' . $e->getMessage());
        }
    }
    function delete(Request $request, $id)
    {
        try {
            $employeeType = EmployeeTypeMaster::findOrFail(decrypt($id));
        } catch (\Exception $e) {
            return $this->deleteResponse($request, false, 'Employee Type not found.', 404);
        }

        if ((int) $employeeType->active_inactive === 1) {
            return $this->deleteResponse($request, false, 'Deactivate this Employee Type before deleting it.', 422);
        }

        try {
            $employeeType->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->deleteResponse($request, false, 'This Employee Type is in use and cannot be deleted.', 409);
        }

        EmployeeTypeMasterDataTable::bumpListingCacheEpoch();

        return $this->deleteResponse($request, true, 'Employee Type deleted successfully.');
    }

    private function deleteResponse(Request $request, bool $success, string $message, int $status = 200)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $success, 'message' => $message], $status);
        }

        return redirect()->route('master.employee.type.index')
            ->with($success ? 'success' : 'error', $message);
    }
}
