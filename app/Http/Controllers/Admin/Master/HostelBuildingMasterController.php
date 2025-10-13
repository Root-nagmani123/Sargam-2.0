<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\DataTables\Master\HostelBuildingMasterDataTable;
use App\DataTables\Master\BuildingMasterDataTable;
// use App\Models\HostelBuildingMaster;
use App\Models\BuildingMaster;

class HostelBuildingMasterController extends Controller
{
    protected $buildingType;
    public function __construct(){
        $this->buildingType = BuildingMaster::$buildingType;
    }
    // public function index(HostelBuildingMasterDataTable $dataTable){
    public function index(BuildingMasterDataTable $dataTable){
        return $dataTable->render('admin.master.hostel_building.index');
    }

    public function create(){
        return view('admin.master.hostel_building.create', ['buildingType' => $this->buildingType]);
    }

    public function store(Request $request){
        
        $request->validate([
            'building_name' => 'required|string|max:255|unique:building_master,building_name,' . ($request->pk ? decrypt($request->pk) : 'null').',pk',
        ]);
        
        if($request->pk) {
            $message = 'Building updated successfully.';
            $buildingMaster = BuildingMaster::findOrFail(decrypt($request->pk));
        }
        else {
            $message = 'Building created successfully.';
            $buildingMaster = new BuildingMaster();
        }
        $buildingMaster->building_name = $request->building_name;
        $buildingMaster->no_of_floors = $request->no_of_floors;
        $buildingMaster->no_of_rooms = $request->no_of_rooms;
        $buildingMaster->building_type = $request->building_type;
        $buildingMaster->active_inactive = 1;

        $buildingMaster->save();

        return redirect()->route('master.hostel.building.index')->with('success', $message);
    }

    public function edit($id){
        $id = decrypt($id);
        $hostelBuildingMaster = BuildingMaster::findOrFail($id);
        return view('admin.master.hostel_building.create', compact('hostelBuildingMaster'), ['buildingType' => $this->buildingType]);
    }
}
