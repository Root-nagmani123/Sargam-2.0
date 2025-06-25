<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\HostelBuildingFloorMappingDataTable;
use App\Models\{
    HostelBuildingFloorMapping,
    HostelBuildingMaster,
    HostelFloorMaster
};

class HostelBuildingFloorMappingController extends Controller
{
    public function index(HostelBuildingFloorMappingDataTable $dataTable) 
    {
        return $dataTable->render('admin.building_floor_mapping.index');
    }

    public function create()
    {
        $hostelBuilding = HostelBuildingMaster::get()->pluck('hostel_building_name', 'pk')->toArray();
        $hostelFloor = HostelFloorMaster::get()->pluck('hostel_floor_name', 'pk')->toArray();
        return view('admin.building_floor_mapping.create', compact('hostelBuilding', 'hostelFloor'));
    }

    public function store(Request $request){
        
        $request->validate([
            'hostelbuilding' => 'required',
            'hostel_floor_name' => 'required'
        ]);
        
        if($request->pk) {
            $message = 'Hostel Building Floor mapping updated successfully.';
            $hostelBuildingFloor = HostelBuildingFloorMapping::findOrFail(decrypt($request->pk));
        }
        else {
            $message = 'Hostel Building Floor mapping created successfully.';
            $hostelBuildingFloor = new HostelBuildingFloorMapping();
        }
        $hostelBuildingFloor->hostel_building_master_pk = $request->hostelbuilding;
        $hostelBuildingFloor->hostel_floor_master_pk = $request->hostel_floor_name;
        $hostelBuildingFloor->active_inactive = 1;

        $hostelBuildingFloor->save();

        return redirect()->route('hostel.building.map.index')->with('success', $message);
    }

    public function edit($id){
        $id = decrypt($id);
        $hostelFloorMapping = HostelBuildingFloorMapping::findOrFail($id);
        $hostelBuilding = HostelBuildingMaster::get()->pluck('hostel_building_name', 'pk')->toArray();
        $hostelFloor = HostelFloorMaster::get()->pluck('hostel_floor_name', 'pk')->toArray();
        // dd($hostelBuilding);
        return view('admin.building_floor_mapping.create', compact('hostelFloorMapping', 'hostelBuilding', 'hostelFloor'));
    }
}
