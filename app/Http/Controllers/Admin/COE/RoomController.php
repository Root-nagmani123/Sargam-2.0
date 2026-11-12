<?php

namespace App\Http\Controllers\Admin\COE;

use App\DataTables\CoeRoomMasterDataTable;
use App\Http\Controllers\Admin\COE\Concerns\ExportsCoeMaster;
use App\Http\Controllers\Controller;
use App\Models\BuildingFloorRoomMapping;
use App\Models\BuildingMaster;
use App\Models\FloorMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * COE → Master Data → Room Master.
 *
 * The shared building_floor_room_mapping, rooms of Classroom buildings only: the
 * building must be a Classroom building_master row, the floor any floor_master
 * row (floors are universal). The room is picked from Venue Master and stored by
 * its venue_short_name in `room_name`, unique across the table; `room_type` one of
 * the table's enum; `capacity` 1..9999; `laptop_count` / `scriber_count` optional,
 * each at most the capacity. Once the DBA adds faulty_laptop_count (faultyEnabled()),
 * a room also records its faulty laptops (at most its laptop count) and the grid shows
 * Available = laptop count − faulty. Status is stored as the table does: 1 active, 2 inactive.
 */
class RoomController extends Controller
{
    use ExportsCoeMaster;

    public function index(CoeRoomMasterDataTable $dataTable)
    {
        $master = [
            'title' => 'Room Master',
            'entity' => 'Room',
            'prefix' => 'exr',
            'colvisKey' => 'coeRoom:hiddenColumns:v3',
            'labels' => [
                'createButton' => 'Add Room',
                'createTitle' => 'Add Room',
                'editTitle' => 'Edit Room',
                'createSubmit' => 'Add Room',
                'editSubmit' => 'Update Room',
            ],
            'sno' => ['show' => true, 'label' => 'S. No.'],
            'filters' => [],
            'statusFilter' => true,
            'pinActions' => true,
            'fields' => [
                // A Venue Master venue, saved by its short name.
                ['key' => 'name', 'name' => 'room_name', 'label' => 'Room Name', 'column' => 'Room Name',
                    'type' => 'select', 'placeholder' => 'Select Room', 'required' => true,
                    'options' => $this->venueOptions()],
                ['key' => 'building', 'name' => 'building_master_pk', 'label' => 'Building Name', 'column' => 'Building',
                    'type' => 'select', 'placeholder' => 'Select Building Name', 'required' => true,
                    'options' => $this->buildingOptions(), 'inactiveOptions' => $this->inactiveBuildingOptions()],
                ['key' => 'floor', 'name' => 'floor_master_pk', 'label' => 'Floor Name', 'column' => 'Floor',
                    'type' => 'select', 'placeholder' => 'Select Floor', 'required' => true,
                    'options' => $this->floorOptions()],
                ['key' => 'type', 'name' => 'room_type', 'label' => 'Room Type', 'column' => 'Room Type',
                    'type' => 'select', 'placeholder' => 'Select Type', 'required' => true,
                    'options' => BuildingFloorRoomMapping::$roomTypes],
                ['key' => 'capacity', 'name' => 'capacity', 'label' => 'Total Capacity', 'column' => 'Total Capacity',
                    'type' => 'number', 'placeholder' => 'eg. 100', 'required' => true, 'maxValue' => 9999, 'step' => 1, 'align' => 'center'],
                ['key' => 'laptops', 'name' => 'laptop_count', 'label' => 'Laptop Count', 'column' => 'Laptop Count',
                    'type' => 'number', 'placeholder' => 'eg. 5', 'maxValue' => 9999, 'step' => 1, 'align' => 'center'],
                ['key' => 'scribers', 'name' => 'scriber_count', 'label' => 'Scriber Count', 'column' => 'Scriber Count',
                    'type' => 'number', 'placeholder' => 'eg. 2', 'maxValue' => 9999, 'step' => 1, 'align' => 'center'],
            ],
            'endpoints' => [
                'list' => route('admin.coe.room.index'),
                'export' => route('admin.coe.room.export', ['format' => '__FORMAT__']),
                'store' => route('admin.coe.room.store'),
                'destroy' => route('admin.coe.room.destroy', ['id' => '__ID__']),
                'toggle' => route('admin.coe.room.toggle-status', ['id' => '__ID__']),
            ],
        ];
        $master['formOrder'] = ['name', 'building', 'floor', 'type', 'capacity', 'laptops', 'scribers'];
        if (self::faultyEnabled()) {
            // Faulty sits after Laptop Count; Available is worked out, a grid column only.
            array_splice($master['fields'], 6, 0, [
                ['key' => 'faulty', 'name' => 'faulty_laptop_count', 'label' => 'Faulty Laptops', 'column' => 'Faulty Laptops',
                    'type' => 'number', 'placeholder' => 'eg. 1', 'maxValue' => 9999, 'step' => 1, 'align' => 'center'],
                ['key' => 'available', 'name' => 'available_laptops', 'label' => 'Available Laptops', 'column' => 'Available Laptops',
                    'computed' => true, 'align' => 'center'],
            ]);
            array_splice($master['formOrder'], 6, 0, ['faulty']);
            $master['colvisKey'] = 'coeRoom:hiddenColumns:v4';
        }

        return $dataTable->render('admin.coe.examination_term.index', ['master' => $master]);
    }

    public function store(Request $request): JsonResponse
    {
        $id = $request->filled('pk') ? decrypt($request->input('pk')) : null;

        $validated = $request->validate([
            'room_name' => [
                'required', 'string', 'max:255',
                Rule::exists('venue_master', 'venue_short_name')->where('active_inactive', 1),
                Rule::unique('building_floor_room_mapping', 'room_name')->ignore($id, 'pk'),
            ],
            'building_master_pk' => ['required', 'integer',
                Rule::exists('building_master', 'pk')->where('building_type', BuildingMaster::TYPE_CLASSROOM),
                // An inactive building only stays on a room that is already on it.
                function ($attribute, $value, $fail) use ($id) {
                    $active = BuildingMaster::whereKey($value)->where('active_inactive', 1)->exists();
                    $current = $id && (int) BuildingFloorRoomMapping::whereKey($id)->value('building_master_pk') === (int) $value;
                    if (! $active && ! $current) {
                        $fail('This building is inactive. Select an active building.');
                    }
                }],
            'floor_master_pk' => ['required', 'integer', Rule::exists('floor_master', 'pk')],
            'room_type' => ['required', Rule::in(array_keys(BuildingFloorRoomMapping::$roomTypes))],
            'capacity' => ['required', 'integer', 'min:1', 'max:9999'],
            'laptop_count' => ['nullable', 'integer', 'min:0', 'lte:capacity'],
            'scriber_count' => ['nullable', 'integer', 'min:0', 'lte:capacity'],
            'faulty_laptop_count' => ['nullable', 'integer', 'min:0', function ($attribute, $value, $fail) use ($request) {
                $laptops = $request->input('laptop_count');
                if ($laptops === null || $laptops === '') {
                    $fail('Enter the laptop count first.');
                } elseif ((int) $value > (int) $laptops) {
                    $fail('Faulty laptops cannot be more than the laptop count.');
                }
            }],
            'active_inactive' => ['required', 'in:0,1'],
        ], [
            'room_name.unique' => 'This room is already mapped.',
            'room_name.exists' => 'Select a room from Venue Master.',
            'laptop_count.lte' => 'Laptop count cannot be more than the total capacity.',
            'scriber_count.lte' => 'Scriber count cannot be more than the total capacity.',
            'building_master_pk.exists' => 'Select a Classroom building.',
        ], [
            'room_name' => 'room name',
            'building_master_pk' => 'building',
            'floor_master_pk' => 'floor',
            'room_type' => 'room type',
            'capacity' => 'total capacity',
            'laptop_count' => 'laptop count',
            'scriber_count' => 'scriber count',
            'faulty_laptop_count' => 'faulty laptops',
        ]);

        $model = $id ? $this->classroomRoom($id) : new BuildingFloorRoomMapping();
        $model->room_name = trim($validated['room_name']);
        $model->building_master_pk = (int) $validated['building_master_pk'];
        $model->floor_master_pk = (int) $validated['floor_master_pk'];
        $model->room_type = $validated['room_type'];
        $model->capacity = (int) $validated['capacity'];
        $model->laptop_count = $validated['laptop_count'] ?? null;
        $model->scriber_count = $validated['scriber_count'] ?? null;
        if (self::faultyEnabled()) {
            $model->faulty_laptop_count = $validated['faulty_laptop_count'] ?? null;
        }
        $model->active_inactive = $validated['active_inactive'] === '1' ? 1 : 2;
        if (! $id) {
            $model->created_date = now();
        }
        $model->updated_date = now();
        $model->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Room ' . ($id ? 'updated' : 'created') . ' successfully.',
        ]);
    }

    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $model = $this->classroomRoom(decrypt($id));
        $model->active_inactive = $request->boolean('active') ? 1 : 2;
        $model->updated_date = now();
        $model->save();

        return response()->json(['status' => 'success']);
    }

    /** Only an inactive room can be deleted. */
    public function destroy(string $id): JsonResponse
    {
        $model = $this->classroomRoom(decrypt($id));

        if ((int) $model->active_inactive === 1) {
            return response()->json(['status' => 'error', 'message' => 'An active room cannot be deleted. Deactivate it first.'], 422);
        }

        $model->delete();

        return response()->json(['status' => 'success', 'message' => 'Room deleted successfully.']);
    }

    /** A room of a Classroom building — the COE may not touch hostel rooms. */
    private function classroomRoom($id): BuildingFloorRoomMapping
    {
        return BuildingFloorRoomMapping::whereKey($id)
            ->whereHas('building', fn ($q) => $q->classroom())
            ->firstOrFail();
    }

    /**
     * Faulty laptops live in building_floor_room_mapping.faulty_laptop_count, added by
     * DBA DDL. Until the column exists Room Master has no Faulty / Available columns.
     */
    public static function faultyEnabled(): bool
    {
        static $enabled;

        return $enabled ??= \Illuminate\Support\Facades\Schema::hasColumn('building_floor_room_mapping', 'faulty_laptop_count');
    }

    /** Laptops that can be used: laptop count less the faulty ones (null without a laptop count). */
    public static function availableLaptops(object $row): ?int
    {
        return $row->laptop_count === null ? null : max(0, (int) $row->laptop_count - (int) ($row->faulty_laptop_count ?? 0));
    }

    /** Active Venue Master venues as [short name => "Name (short name)"], the short name being what is stored. */
    private function venueOptions(): array
    {
        return DB::table('venue_master')
            ->where('active_inactive', 1)
            ->whereRaw("TRIM(COALESCE(venue_short_name, '')) <> ''")
            ->orderBy('venue_name')
            ->get(['venue_name', 'venue_short_name'])
            ->mapWithKeys(fn ($v) => [trim($v->venue_short_name) => trim($v->venue_name).' ('.trim($v->venue_short_name).')'])
            ->all();
    }

    /** Active Classroom buildings, the only ones Add Room offers. [pk => name] */
    private function buildingOptions(): array
    {
        return BuildingMaster::classroom()
            ->where('active_inactive', 1)
            ->orderBy('building_name')
            ->pluck('building_name', 'pk')
            ->map(fn ($name) => trim((string) $name))
            ->all();
    }

    /** Inactive Classroom buildings a room is on; Edit shows only the room's own one. [pk => name] */
    private function inactiveBuildingOptions(): array
    {
        return BuildingMaster::classroom()
            ->where('active_inactive', '!=', 1)
            ->whereIn('pk', BuildingFloorRoomMapping::select('building_master_pk'))
            ->orderBy('building_name')
            ->pluck('building_name', 'pk')
            ->map(fn ($name) => trim((string) $name) . ' (Inactive)')
            ->all();
    }

    /** Every active floor, plus any inactive one a Classroom room is on. [pk => name] */
    private function floorOptions(): array
    {
        $used = CoeRoomMasterDataTable::classroomRooms(BuildingFloorRoomMapping::query())
            ->pluck('building_floor_room_mapping.floor_master_pk');

        return FloorMaster::where('active_inactive', 1)
            ->orWhereIn('pk', $used)
            ->orderBy('pk')
            ->pluck('floor_name', 'pk')
            ->map(fn ($name) => trim((string) $name))
            ->all();
    }

    /** CSV / Excel / PDF / Print of the grid as filtered on screen (ExportsCoeMaster). */
    public function export(Request $request, string $format)
    {
        return $this->exportCoeMaster(
            $request,
            $format,
            // As a subquery, so the status filter's active_inactive is the room's (building_master has one too).
            BuildingFloorRoomMapping::query()
                ->fromSub(CoeRoomMasterDataTable::classroomRooms(BuildingFloorRoomMapping::query()), 'r')
                ->orderBy('building_name')->orderBy('floor_master_pk')->orderBy('room_name'),
            [
                'sno' => $this->coeSnoColumn(),
                'name' => ['heading' => 'Room Name', 'width' => '18%', 'align' => 'left', 'value' => fn ($row) => trim((string) $row->room_name)],
                'building' => ['heading' => 'Building', 'width' => '20%', 'align' => 'left', 'value' => fn ($row) => trim((string) $row->building_name)],
                'floor' => ['heading' => 'Floor', 'width' => '10%', 'align' => 'center', 'value' => fn ($row) => trim((string) $row->floor_name)],
                'type' => ['heading' => 'Room Type', 'width' => '16%', 'align' => 'left', 'value' => fn ($row) => (string) $row->room_type],
                'capacity' => ['heading' => 'Total Capacity', 'width' => '10%', 'align' => 'center', 'value' => fn ($row) => (string) ($row->capacity ?? '')],
                'laptops' => ['heading' => 'Laptop Count', 'width' => '8%', 'align' => 'center', 'value' => fn ($row) => (string) ($row->laptop_count ?? '')],
                'scribers' => ['heading' => 'Scriber Count', 'width' => '8%', 'align' => 'center', 'value' => fn ($row) => (string) ($row->scriber_count ?? '')],
            ] + (self::faultyEnabled() ? [
                'faulty' => ['heading' => 'Faulty Laptops', 'width' => '8%', 'align' => 'center', 'value' => fn ($row) => (string) ($row->faulty_laptop_count ?? '')],
                'available' => ['heading' => 'Available Laptops', 'width' => '8%', 'align' => 'center', 'value' => fn ($row) => (string) (self::availableLaptops($row) ?? '')],
            ] : []) + [
                'status' => $this->coeStatusColumn(),
            ],
            fn ($query, string $term) => $query->where(fn ($q) => $q
                ->where('room_name', 'like', '%' . $term . '%')
                ->orWhere('building_name', 'like', '%' . $term . '%')
                ->orWhere('room_type', 'like', '%' . $term . '%')),
            'Room Master',
            'RoomMaster'
        );
    }
}
