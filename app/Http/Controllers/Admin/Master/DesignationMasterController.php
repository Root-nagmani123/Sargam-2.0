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
        $designationMaster = null;
        
        // Return only form HTML for AJAX requests
        if (request()->ajax()) {
            return view('admin.master.designation._form', compact('designationMaster'))->render();
        }
        
        return view('admin.master.designation.create', compact('designationMaster'));
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
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Designation not found.'
                ], 404);
            }
            return redirect()->back()->with('error', 'Designation not found.');
        }

        $designation->designation_name = $request->designation_name;
        $designation->save();

        $message = $id ? 'Designation updated successfully.' : 'Designation created successfully.';

        // Return JSON response for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return redirect()->route('master.designation.index')->with('success', $message);

    }
    function edit($id)
    {
        try {
            $designationMaster = DesignationMaster::findOrFail(decrypt($id));
            
            // Return only form HTML for AJAX requests
            if (request()->ajax()) {
                return view('admin.master.designation._form', compact('designationMaster'))->render();
            }
            
            return view('admin.master.designation.create', compact('designationMaster'));
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to edit designation: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to edit designation: ' . $e->getMessage());
        }
    }

    public function delete(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
            $designation = DesignationMaster::findOrFail($pk);
            
            // Check if designation is active
            if ($designation->active_inactive == 1) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete an active designation. Please deactivate it first.'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Cannot delete an active designation. Please deactivate it first.');
            }
            
            $designation->delete();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'deleted' => true,
                    'message' => 'Designation deleted successfully.'
                ]);
            }
            
            return redirect()->route('master.designation.index')->with('success', 'Designation deleted successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete designation: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to delete designation: ' . $e->getMessage());
        }
    }
    
}
