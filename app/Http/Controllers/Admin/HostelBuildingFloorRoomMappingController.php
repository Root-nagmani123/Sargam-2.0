<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
    use \App\Http\Controllers\Concerns\ExportsMasterGrid;

    public $roomTypes;

    public function __construct()
    {
        $this->roomTypes = BuildingFloorRoomMapping::$roomTypes;
    }
    // public function index(HostelBuildingFloorRoomMappingDataTable $dataTable)
    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);

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

    /**
     * The grid's query: Building / Room Type / Status filters and the search
     * box, newest first. Shared by the grid and Print so a printout is exactly
     * the filtered list on screen (every page of it, not just the current one).
     */
    private function filteredQuery(Request $request)
    {
        $query = BuildingFloorRoomMapping::with(['building', 'floor'])->latest('pk');

        // Apply filters
        if (($buildingId = $this->filterValue($request, 'building_id')) !== null) {
            $query->where('building_master_pk', $buildingId);
        }
        if (($roomType = $this->filterValue($request, 'room_type')) !== null) {
            $query->where('room_type', $roomType);
        }
        if (($status = $this->filterValue($request, 'status')) !== null) {
            $query->where('active_inactive', $status);
        }
        if (($search = $this->filterValue($request, 'search')) !== null) {
            $query->where(function($q) use ($search) {
                $q->where('room_name', 'like', "%{$search}%")
                  ->orWhere('capacity', 'like', "%{$search}%")
                  ->orWhere('comment', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * A grid filter, or null when absent, blank or not a scalar: room_type[]=x
     * (or building_id[] / status[] / search[]) is ignored, not a 500.
     */
    private function filterValue(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    /**
     * Download / Print columns (ExportsMasterGrid). Keys are what the index
     * page sends as ?cols= (HR_EXPORT_COLUMN_KEYS there).
     */
    private function printColumnDefs(): array
    {
        return [
            'sno' => ['heading' => 'S. No.', 'width' => '6%', 'align' => 'center',
                'value' => fn ($row, int $index) => $index + 1],
            'building' => ['heading' => 'Building Name', 'width' => '16%', 'align' => 'left',
                'value' => fn ($row) => $row->building->building_name ?? '—'],
            'floor' => ['heading' => 'Floor Name', 'width' => '9%', 'align' => 'center',
                'value' => fn ($row) => $row->floor->floor_name ?? '—'],
            'room_name' => ['heading' => 'Room Name', 'width' => '16%', 'align' => 'left',
                'value' => fn ($row) => $row->room_name ?? '—'],
            'room_type' => ['heading' => 'Room Type', 'width' => '12%', 'align' => 'left',
                'value' => fn ($row) => $row->room_type ?? '—'],
            'capacity' => ['heading' => 'Capacity', 'width' => '9%', 'align' => 'center',
                'value' => fn ($row) => $row->capacity ?? '—'],
            'comment' => ['heading' => 'Comment', 'width' => '22%', 'align' => 'left',
                'value' => fn ($row) => $row->comment ?: '—'],
            'status' => ['heading' => 'Status', 'width' => '10%', 'align' => 'center',
                'value' => fn ($row) => ((int) $row->active_inactive === 1) ? 'Active' : 'Inactive'],
        ];
    }

    public function print(Request $request)
    {
        return $this->export($request, 'print');
    }

    /**
     * Download (csv | excel | pdf) and Print — one query, one column list
     * (ExportsMasterGrid), so every format matches the grid and each other:
     * same filters and search, only the columns left on in Columns.
     */
    public function export(Request $request, string $format = 'excel')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, self::$exportFormats, true), 404);

        $rows = $this->filteredQuery($request)->get();

        // Name the filters on the sheet the way the grid shows them.
        $filters = [];
        if (($buildingId = $this->filterValue($request, 'building_id')) !== null) {
            $filters[] = 'Building: ' . (BuildingMaster::find($buildingId)->building_name ?? $buildingId);
        }
        if (($roomType = $this->filterValue($request, 'room_type')) !== null) {
            $filters[] = 'Room Type: ' . $roomType;
        }
        if (($status = $this->filterValue($request, 'status')) !== null) {
            $filters[] = 'Status: ' . ($status === '1' ? 'Active' : 'Inactive');
        }
        if (($search = $this->filterValue($request, 'search')) !== null) {
            $filters[] = 'Search: ' . $search;
        }

        return $this->renderMasterExport(
            $format,
            $rows,
            $this->resolveExportColumns($request, $this->printColumnDefs()),
            'Hostel Floor Room Map',
            'HostelFloorRoomMap',
            $filters === [] ? null : implode('  |  ', $filters),
            'No rooms to export',
            'landscape'
        );
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
