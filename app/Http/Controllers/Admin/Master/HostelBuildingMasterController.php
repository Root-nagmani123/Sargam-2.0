<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HasBrandedExport;
use Illuminate\Http\Request;
// use App\DataTables\Master\HostelBuildingMasterDataTable;
use App\DataTables\Master\BuildingMasterDataTable;
// use App\Models\HostelBuildingMaster;
use App\Models\BuildingMaster;

class HostelBuildingMasterController extends Controller
{
    use HasBrandedExport;

    protected $buildingType;
    public function __construct(){
        $this->buildingType = BuildingMaster::$buildingType;
    }
    // public function index(HostelBuildingMasterDataTable $dataTable){
    public function index(BuildingMasterDataTable $dataTable){
        return $dataTable->render('admin.master.hostel_building.index', ['buildingType' => $this->buildingType]);
    }

    public function create(){
        return view('admin.master.hostel_building.create', ['buildingType' => $this->buildingType]);
    }

    public function store(Request $request){

        $request->validate([
            'building_name'  => 'required|string|max:255|unique:building_master,building_name,' . ($request->pk ? decrypt($request->pk) : 'null').',pk',
            'no_of_floors'   => 'required|integer|min:0',
            'no_of_rooms'    => 'required|integer|min:0',
            'building_type'  => 'required|string|max:255',
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
        // Preserve existing behaviour (default active) while allowing the modal's
        // Building Status field to set it when provided.
        $buildingMaster->active_inactive = $request->filled('active_inactive')
            ? (int) $request->active_inactive
            : ($buildingMaster->active_inactive ?? 1);

        $buildingMaster->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.hostel.building.index')->with('success', $message);
    }

    public function edit($id){
        $id = decrypt($id);
        $hostelBuildingMaster = BuildingMaster::findOrFail($id);
        return view('admin.master.hostel_building.create', compact('hostelBuildingMaster'), ['buildingType' => $this->buildingType]);
    }

    /** Branded CSV / PDF / Print (new-design-index-page.md §4b) via the shared trait. */
    public function export($format = 'pdf') {
        $rows = [];
        $i    = 1;
        foreach (BuildingMaster::orderBy('building_name')->get() as $b) {
            $rows[] = [
                $i++,
                $b->building_name,
                $b->no_of_floors,
                $b->no_of_rooms,
                $b->building_type,
                $b->active_inactive == 1 ? 'Active' : 'Inactive',
            ];
        }
        return $this->brandedExport(
            $format,
            'Building Master',
            ['S. No.', 'Building Name', 'No. of Floors', 'No. of Rooms', 'Building Type', 'Status'],
            $rows,
            'building-master'
        );
    }

    function destroy($id){
        $id = decrypt($id);
        $buildingMaster = BuildingMaster::findOrFail($id);
        $buildingMaster->delete();
        return redirect()->route('master.hostel.building.index')->with('success', 'Building deleted successfully.');
    }
}
