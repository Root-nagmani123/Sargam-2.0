<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\HostelBuildingFloorRoomMappingDataTable;
use App\Models\{
    HostelBuildingFloorMapping,
    HostelRoomMaster,
    HostelFloorRoomMapping
};

class HostelBuildingFloorRoomMappingController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:hostel.building.floor.room.map.index', ['only' => ['index']]);
        $this->middleware('permission:hostel.building.floor.room.map.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:hostel.building.floor.room.map.edit', ['only' => ['edit', 'store']]);
    }
    public function index(HostelBuildingFloorRoomMappingDataTable $dataTable)
    {
        return $dataTable->render('admin.building_floor_room_mapping.index');
    }

    public function create()
    {
        return view('admin.building_floor_room_mapping.create', $this->formData());
    }

    public function store(Request $request)
    {
        $request->validate([
            'hostel_building_floor' => 'required|exists:hostel_building_floor_mapping,pk',
            'hostel_room' => 'required|exists:hostel_room_master,pk',
        ]);

        $mapping = $request->filled('pk') 
            ? HostelFloorRoomMapping::findOrFail(safeDecrypt($request->pk)) 
            : new HostelFloorRoomMapping();

        $mapping->hostel_building_floor_mapping_pk = $request->hostel_building_floor;
        $mapping->hostel_room_master_pk = $request->hostel_room;
        $mapping->active_inactive = 1;
        $mapping->save();

        $message = $request->filled('pk') 
            ? 'Hostel Floor Room mapping updated successfully.' 
            : 'Hostel Floor Room mapping created successfully.';

        return redirect()->route('hostel.building.floor.room.map.index')->with('success', $message);
    }

    public function edit($encryptedId)
    {
        $id = safeDecrypt($encryptedId);
        $hostelFloorMappingRoom = HostelFloorRoomMapping::findOrFail($id);

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
        $hostelBuilding = HostelBuildingFloorMapping::active()
            ->with([
                'building:pk,hostel_building_name',
                'floor:pk,hostel_floor_name'
            ])
            ->get()
            ->mapWithKeys(fn($item) => [
                $item->pk => "{$item->building->hostel_building_name}-{$item->floor->hostel_floor_name}"
            ])
            ->toArray();

        $hostelRoom = HostelRoomMaster::active()
            ->pluck('hostel_room_name', 'pk')
            ->toArray();

        return compact('hostelBuilding', 'hostelRoom');
    }
}
