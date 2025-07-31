<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Master\HostelBuildingMasterDataTable;
use App\Models\HostelBuildingMaster;

class HostelBuildingMasterController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:master.hostel-building-master.index', ['only' => ['index']]);
        $this->middleware('permission:master.hostel-building-master.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:master.hostel-building-master.edit', ['only' => ['edit', 'store']]);
    }

    public function index(HostelBuildingMasterDataTable $dataTable){
        return $dataTable->render('admin.master.hostel_building.index');
    }

    public function create(){
        return view('admin.master.hostel_building.create');
    }

    public function store(Request $request){
        
        $request->validate([
            'hostel_building_name' => 'required|string|max:255|unique:hostel_building_master,hostel_building_name,' . ($request->pk ? decrypt($request->pk) : 'null').',pk',
        ]);
        
        if($request->pk) {
            $message = 'Hostel Building updated successfully.';
            $hostelBuildingMaster = HostelBuildingMaster::findOrFail(decrypt($request->pk));
        }
        else {
            $message = 'Hostel Building created successfully.';
            $hostelBuildingMaster = new HostelBuildingMaster();
        }
        $hostelBuildingMaster->hostel_building_name = $request->hostel_building_name;
        $hostelBuildingMaster->active_inactive = 1;

        $hostelBuildingMaster->save();

        return redirect()->route('master.hostel.building.index')->with('success', $message);
    }

    public function edit($id){
        $id = decrypt($id);
        $hostelBuildingMaster = HostelBuildingMaster::findOrFail($id);
        return view('admin.master.hostel_building.create', compact('hostelBuildingMaster'));
    }
}
