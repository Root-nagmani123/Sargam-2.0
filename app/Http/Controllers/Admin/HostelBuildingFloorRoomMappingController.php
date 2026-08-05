<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HasBrandedExport;
use Illuminate\Http\Request;
// use App\DataTables\HostelBuildingFloorRoomMappingDataTable;
use App\DataTables\BuildingFloorRoomMappingDataTable;
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
    use HasBrandedExport;

    public $roomTypes;

    public function __construct()
    {
        $this->roomTypes = BuildingFloorRoomMapping::$roomTypes;
    }
    // public function index(HostelBuildingFloorRoomMappingDataTable $dataTable)
    public function index(Request $request)
    {
        $query = BuildingFloorRoomMapping::with(['building', 'floor'])->latest('pk');
        
        // Apply filters
        if ($request->filled('building_id')) {
            $query->where('building_master_pk', $request->building_id);
        }
        if ($request->filled('room_type')) {
            $query->where('room_type', $request->room_type);
        }
        if ($request->filled('status')) {
            $query->where('active_inactive', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('room_name', 'like', "%{$search}%")
                  ->orWhere('capacity', 'like', "%{$search}%")
                  ->orWhere('comment', 'like', "%{$search}%");
            });
        }
        
        $perPage = (int) $request->input('per_page', 10);
        if ($perPage < 1) {
            $perPage = 10;
        }

        $mappings = $query->paginate($perPage)->withQueryString();
        $buildings = BuildingMaster::active()->get();
        $floors = FloorMaster::active()->get();
        $roomTypes = $this->roomTypes;

        return view('admin.building_floor_room_mapping.index', compact('mappings', 'buildings', 'floors', 'roomTypes'));
    }

    public function create()
    {
        return view('admin.building_floor_room_mapping.create', $this->formData());
    }

    public function store(Request $request)
    {
        $request->validate([
            'building_master_pk' => 'required|exists:building_master,pk',
            'floor_master_pk' => 'required|exists:floor_master,pk',
            'capacity' => 'required|integer|min:1',
            'room_type' => [
                'required',
                \Illuminate\Validation\Rule::in(array_keys(BuildingFloorRoomMapping::$roomTypes))
            ],
            'comment' => 'nullable|string|max:255',
            'active_inactive' => 'nullable|in:0,1',
        ]);

        try{
            $room_name = '';
            $building = BuildingMaster::where('pk', $request->building_master_pk)->first();
            $floor = FloorMaster::where('pk', $request->floor_master_pk)->first();
            $room_name = substr($building->building_name, 0, 4);
            $room_name .= '-' . $floor->floor_name.$request->room_name;

            if( $request->room_type != 'Room' ) {
                $room_name .= '-' . $request->room_type;
            }

            if(isset($request->pk)){
                $decryptedPk = safeDecrypt($request->pk);
                $mapping = BuildingFloorRoomMapping::findOrFail($decryptedPk);
                $message = 'Hostel Floor Room mapping updated successfully.';
            }
            else{
                $mapping = new BuildingFloorRoomMapping();
                $message = 'Hostel Floor Room mapping created successfully.';
            }
            $mapping->building_master_pk = $request->building_master_pk;
            $mapping->floor_master_pk = $request->floor_master_pk;
            $mapping->room_name = $room_name;
            $mapping->room_type = $request->room_type;
            $mapping->capacity = $request->capacity;
            // Only touch these when the request actually carries them, so the
            // legacy full-page form (which omits them) keeps working unchanged.
            if ($request->has('comment')) {
                $mapping->comment = $request->comment;
            }
            if ($request->filled('active_inactive')) {
                $mapping->active_inactive = (int) $request->active_inactive;
            }
            $mapping->save();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => $message]);
            }

            return redirect()->route('hostel.building.floor.room.map.index')->with('success', $message);
        }
        catch(\Exception $e) {
            \Log::error($e->getMessage(), [
                'stack' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Something went wrong'], 500);
            }

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

    /** Branded CSV / PDF / Print (new-design-index-page.md §4b) — honours the same filters as the list. */
    public function export(Request $request, $format = 'pdf') {
        $query = BuildingFloorRoomMapping::with(['building', 'floor'])->latest('pk');
        if ($request->filled('building_id')) $query->where('building_master_pk', $request->building_id);
        if ($request->filled('room_type'))   $query->where('room_type', $request->room_type);
        if ($request->filled('status'))      $query->where('active_inactive', $request->status);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('room_name', 'like', "%{$search}%")
                  ->orWhere('capacity', 'like', "%{$search}%")
                  ->orWhere('comment', 'like', "%{$search}%");
            });
        }

        $rows = [];
        $i    = 1;
        foreach ($query->get() as $m) {
            $rows[] = [
                $i++,
                optional($m->building)->building_name ?? '—',
                optional($m->floor)->floor_name ?? '—',
                $m->room_name,
                $m->room_type,
                $m->capacity,
                $m->comment,
                $m->active_inactive == 1 ? 'Active' : 'Inactive',
            ];
        }
        return $this->brandedExport(
            $format,
            'Hostel Floor Room Map',
            ['S. No.', 'Building Name', 'Floor Name', 'Room Name', 'Room Type', 'Capacity', 'Comment', 'Status'],
            $rows,
            'hostel-floor-room-map'
        );
    }

    function destroy($id) {
        try {
            $id = safeDecrypt($id);
            $mapping = BuildingFloorRoomMapping::findOrFail($id);
            $mapping->delete();

            return redirect()->route('hostel.building.floor.room.map.index')->with('success', 'Hostel Floor Room mapping deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('hostel.building.floor.room.map.index')->with('error', 'Something went wrong while deleting.');
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
