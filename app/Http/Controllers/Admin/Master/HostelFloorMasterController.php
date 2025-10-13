<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Master\HostelFloorMasterDataTable;
// use App\Models\HostelFloorMaster;
use App\Models\FloorMaster;

class HostelFloorMasterController extends Controller
{
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
            'floor_name' => 'required|string|max:255|unique:floor_master,floor_name,' . ($request->pk ? decrypt($request->pk) : 'null').',pk',
        ]);
        
        if($request->pk) {
            $message = 'Floor updated successfully.';
            $floorMaster = FloorMaster::findOrFail(decrypt($request->pk));
        }
        else {
            $message = 'Floor created successfully.';
            $floorMaster = new FloorMaster();
        }
        $floorMaster->floor_name = $request->floor_name;
        $floorMaster->active_inactive = 1;

        $floorMaster->save();

        return redirect()->route('master.hostel.floor.index')->with('success', $message);
    }

    public function edit($id){
        $id = decrypt($id);
        $hostelFloorMaster = FloorMaster::findOrFail($id);
        return view('admin.master.hostel_floor.create', compact('hostelFloorMaster'));
    }
}
