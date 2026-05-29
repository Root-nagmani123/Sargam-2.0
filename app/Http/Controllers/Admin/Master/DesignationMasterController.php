<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DesignationMaster;
use App\DataTables\Master\DesignationMasterDataTable;
use Illuminate\Validation\Rule; 
class DesignationMasterController extends Controller
{
    function index()
    {
        $designationMaster = new DesignationMasterDataTable;
        return $designationMaster->render('admin.master.designation.index');
        // return view('admin.master.designation.index');
    }
    function create()
    {
        return redirect()->route('master.designation.index', ['open_dsn_modal' => 'add']);
    }
    function store(Request $request)
    {


        $id = $request->pk ? decrypt($request->pk) : null;

        $rules = [
            'designation_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('designation_master', 'designation_name')->ignore($id, 'pk'),
            ],
        ];

        $request->validate($rules);

        $designation = $id ? DesignationMaster::find($id) : new DesignationMaster();

        if ($id && !$designation) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Designation not found.'], 404);
            }

            return redirect()->back()->with('error', 'Designation not found.');
        }

        $designation->designation_name = $request->designation_name;
        $designation->save();

        $message = $id ? 'Designation updated successfully.' : 'Designation created successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('master.designation.index')->with('success', $message);

    }
    function edit($id)
    {
        try {
            $designationMaster = DesignationMaster::findOrFail(decrypt($id));

            return redirect()->route('master.designation.index', [
                'open_dsn_modal' => 'edit',
                'dsn_pk' => $id,
                'dsn_name' => $designationMaster->designation_name,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('master.designation.index')
                ->with('error', 'Failed to edit designation: ' . $e->getMessage());
        }
    }
    
}
