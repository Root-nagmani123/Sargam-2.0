<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\DataTables\HostelBuildingFloorRoomMappingDataTable;
use App\DataTables\BuildingFloorRoomMappingDataTable;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FloorRoomMappingExport;
use App\Models\{
    HostelBuildingFloorMapping,
    HostelRoomMaster,
    HostelFloorRoomMapping,



    BuildingMaster,
    FloorMaster,
    BuildingFloorRoomMapping
};

class HostelBuildingFloorRoomMappingController extends Controller
{
    public $roomTypes;

    public function __construct()
    {
        $this->roomTypes = BuildingFloorRoomMapping::$roomTypes;
    }
    // public function index(HostelBuildingFloorRoomMappingDataTable $dataTable)
    public function index(BuildingFloorRoomMappingDataTable $dataTable)
    {
        return $dataTable->render('admin.building_floor_room_mapping.index');
    }

    public function create()
    {
        return view('admin.building_floor_room_mapping.create', $this->formData());
    }

    public function store(Request $request)
    {
        try{
            $request->validate([
                'building_master_pk' => 'required|exists:building_master,pk',
                'floor_master_pk' => 'required|exists:floor_master,pk',
                'capacity' => 'required|integer|min:1',
                'room_type' => [
                    'required',
                    \Illuminate\Validation\Rule::in(array_keys(BuildingFloorRoomMapping::$roomTypes))
                ],
            ]);

            $decryptedPk = decrypt($request->pk);
            $room_name = '';
            $building = BuildingMaster::where('pk', $request->building_master_pk)->first();
            $floor = FloorMaster::where('pk', $request->floor_master_pk)->first();
            $room_name = substr($building->building_name, 0, 4);
            $room_name .= '-' . $floor->floor_name.$request->room_name;

            if( $request->room_type != 'Room' ) {
                $room_name .= '-' . $request->room_type;
            }

            if($decryptedPk) {
                $mapping = BuildingFloorRoomMapping::findOrFail($decryptedPk);
            }
            else{
                $mapping = new BuildingFloorRoomMapping();
            }
            $mapping->building_master_pk = $request->building_master_pk;
            $mapping->floor_master_pk = $request->floor_master_pk;
            $mapping->room_name = $room_name;
            $mapping->room_type = $request->room_type;
            $mapping->capacity = $request->capacity;
            $mapping->save();

            return redirect()->route('hostel.building.floor.room.map.index')->with('success', 'Hostel Floor Room mapping created successfully.');
        }
        catch(\Exception $e) {

            return redirect()->route('hostel.building.floor.room.map.index')->with('error', 'Something went wrong');
        }
    }

    public function edit($encryptedId)
    {
        $id = safeDecrypt($encryptedId);
        $hostelFloorMappingRoom = BuildingFloorRoomMapping::findOrFail($id);
        
        return view('admin.building_floor_room_mapping.create', array_merge(
            $this->formData(),
            ['hostelFloorMappingRoom' => $hostelFloorMappingRoom]
        ));
    }

    /**
     * Get shared form data for create/edit views.
     */
    private function formData(): array
    {
        // $hostelBuilding = HostelBuildingFloorMapping::active()
        //     ->with(relations: [
        //         'building:pk,hostel_building_name',
        //         'floor:pk,hostel_floor_name'
        //     ])
        //     ->get()
        //     ->mapWithKeys(fn($item) => [
        //         $item->pk => "{$item->building->hostel_building_name}-{$item->floor->hostel_floor_name}"
        //     ])
        //     ->toArray();

        // $hostelRoom = HostelRoomMaster::active()
        //     ->pluck('hostel_room_name', 'pk')
        //     ->toArray();

        // return compact('hostelBuilding', 'hostelRoom');

        $building = BuildingMaster::active()
            ->pluck('building_name', 'pk')
            ->toArray();

        $floor = FloorMaster::active()
            ->pluck('floor_name', 'pk')
            ->toArray();

        $roomTypes = $this->roomTypes;
        return compact('building', 'floor', 'roomTypes');
    }

    function export(Request $request) {
        try {
            return \Excel::download(new \App\Exports\FloorRoomMappingExport($request->all()), 'floor_room_mapping.xlsx');
        } catch (\Exception $e) {
            return redirect()->route('hostel.building.floor.room.map.index')->with('error', 'Error exporting data: ' . $e->getMessage());
        }
    }

    function updateComment(Request $request) {
        $request->validate([
            'id' => 'required|exists:building_floor_room_mapping,pk',
            'comment' => 'nullable|string|max:255',
        ]);

        try {
            $mapping = BuildingFloorRoomMapping::findOrFail($request->id);
            $mapping->comment = $request->comment;
            $mapping->save();

            return response()->json(['success' => true, 'message' => 'Comment updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update comment.'], 500);
        }
    }
}
