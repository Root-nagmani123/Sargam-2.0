<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DesignationMaster;
use App\DataTables\DesignationMasterDataTable;
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
        return view('admin.master.designation.create');
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
            return redirect()->back()->with('error', 'Designation not found.');
        }

        $designation->designation_name = $request->designation_name;
        $designation->save();

        $message = $id ? 'Designation updated successfully.' : 'Designation created successfully.';

        return redirect()->route('master.designation.index')->with('success', $message);

    }
    function edit($id)
    {
        try {
            $designationMaster = DesignationMaster::find(decrypt($id));
            return view('admin.master.designation.create', compact('designationMaster'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to edit designation: ' . $e->getMessage());
        }
    }
    
}
