<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Master\HostelFloorMasterDataTable;
use App\Models\HostelFloorMaster;

class HostelFloorMasterController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:master.hostel-floor-master.index', ['only' => ['index']]);
        $this->middleware('permission:master.hostel-floor-master.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:master.hostel-floor-master.edit', ['only' => ['edit', 'store']]);
    }
    public function index(HostelFloorMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.hostel_floor.index');
    }

    public function create()
    {
        return view('admin.master.hostel_floor.create');
    }

    public function store(Request $request){
        
        $request->validate([
            'hostel_floor_name' => 'required|string|max:255|unique:hostel_floor_master,hostel_floor_name,' . ($request->pk ? decrypt($request->pk) : 'null').',pk',
        ]);
        
        if($request->pk) {
            $message = 'Hostel Floor updated successfully.';
            $hostelFloorMaster = HostelFloorMaster::findOrFail(decrypt($request->pk));
        }
        else {
            $message = 'Hostel Floor created successfully.';
            $hostelFloorMaster = new HostelFloorMaster();
        }
        $hostelFloorMaster->hostel_floor_name = $request->hostel_floor_name;
        $hostelFloorMaster->active_inactive = 1;

        $hostelFloorMaster->save();

        return redirect()->route('master.hostel.floor.index')->with('success', $message);
    }

    public function edit($id){
        $id = decrypt($id);
        $hostelFloorMaster = HostelFloorMaster::findOrFail($id);
        return view('admin.master.hostel_floor.create', compact('hostelFloorMaster'));
    }
}
