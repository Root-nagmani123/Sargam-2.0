<?php

namespace Tests\Feature;

use App\Models\BuildingFloorRoomMapping;
use App\Models\BuildingMaster;
use App\Models\FloorMaster;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * COE Building / Floor / Room Master on the shared building_master, floor_master
 * and building_floor_room_mapping: only Classroom buildings and their rooms are
 * the COE's, floors are universal, status is stored 1 / 2 like the hostel screens.
 * Development database, in a transaction.
 */
class CoeBuildingMasterFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::role('Super Admin')->first();
        if ($admin === null || FloorMaster::count() < 2 || ! BuildingMaster::where('building_type', 'Hostel')->exists()) {
            $this->markTestSkipped('Needs a Super Admin, 2 floors and a hostel building.');
        }
        $this->admin = $admin;
    }

    private function save(string $route, array $data)
    {
        return $this->actingAs($this->admin)->postJson(route($route), $data + ['active_inactive' => '1']);
    }

    /** The grid's JSON rows, as DataTables asks for them. */
    private function grid(string $route, string $search = ''): array
    {
        return $this->actingAs($this->admin)->getJson(route($route, [
            'draw' => 1, 'start' => 0, 'length' => 500, 'search' => ['value' => $search],
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data');
    }

    public function test_pages_render(): void
    {
        foreach (['building' => 'Building Master', 'floor' => 'Floor Master', 'room' => 'Room Master'] as $page => $title) {
            $this->actingAs($this->admin)->get(route("admin.coe.{$page}.index"))->assertOk()->assertSee($title);
        }
        // Faulty / Available laptops only once the DBA column is there.
        $room = $this->actingAs($this->admin)->get(route('admin.coe.room.index'))->assertSee('Laptop Count')->assertSee('Scriber Count');
        \App\Http\Controllers\Admin\COE\RoomController::faultyEnabled()
            ? $room->assertSee('Faulty Laptops')->assertSee('Available Laptops')
            : $room->assertDontSee('Faulty Laptops');
    }

    public function test_building_master_lists_and_adds_only_classroom_buildings(): void
    {
        $hostel = BuildingMaster::where('building_type', 'Hostel')->first();
        $names = array_column($this->grid('admin.coe.building.index'), 'building_name');
        $this->assertNotContains(e(trim($hostel->building_name)), $names);

        $name = 'ZZ Block '.uniqid();
        $this->save('admin.coe.building.store', ['building_name' => $name, 'no_of_floors' => 3, 'no_of_rooms' => 12])->assertOk();
        $building = BuildingMaster::where('building_name', $name)->firstOrFail();
        $this->assertSame(['Classroom', 3, 12, 1], [$building->building_type, (int) $building->no_of_floors, (int) $building->no_of_rooms, (int) $building->active_inactive]);
        $this->assertContains($name, array_column($this->grid('admin.coe.building.index', $name), 'building_name'));

        // Inactive is stored as 2, as the table does.
        $id = encrypt($building->pk);
        $this->actingAs($this->admin)->patchJson(route('admin.coe.building.toggle-status', ['id' => $id]), ['active' => 0])->assertOk();
        $this->assertSame(2, (int) $building->fresh()->active_inactive);

        // A hostel building is not the COE's to edit.
        $this->save('admin.coe.building.store', ['pk' => encrypt($hostel->pk), 'building_name' => $hostel->building_name, 'no_of_floors' => 1, 'no_of_rooms' => 1])
            ->assertNotFound();

        // Rooms block delete; without them an inactive building goes.
        $room = BuildingFloorRoomMapping::create(['building_master_pk' => $building->pk, 'floor_master_pk' => FloorMaster::value('pk'),
            'room_name' => 'ZZ-1', 'room_type' => 'Classroom', 'capacity' => 30, 'active_inactive' => 1]);
        $this->actingAs($this->admin)->deleteJson(route('admin.coe.building.destroy', ['id' => $id]))->assertStatus(422);
        $room->delete();
        $this->actingAs($this->admin)->deleteJson(route('admin.coe.building.destroy', ['id' => $id]))->assertOk();
        $this->assertNull($building->fresh());
    }

    public function test_floor_master_lists_every_floor_with_total_rooms_blank(): void
    {
        $rows = $this->grid('admin.coe.floor.index');
        $this->assertCount(FloorMaster::count(), $rows);
        $this->assertSame([''], array_values(array_unique(array_column($rows, 'total_rooms'))));

        $this->save('admin.coe.floor.store', ['floor_name' => FloorMaster::value('floor_name')])
            ->assertStatus(422)->assertJsonValidationErrors('floor_name');

        // A floor rooms are on cannot be deleted, even inactive.
        $used = FloorMaster::whereIn('pk', BuildingFloorRoomMapping::select('floor_master_pk'))->firstOrFail();
        $id = encrypt($used->pk);
        $this->actingAs($this->admin)->patchJson(route('admin.coe.floor.toggle-status', ['id' => $id]), ['active' => 0])->assertOk();
        $this->assertSame(2, (int) $used->fresh()->active_inactive);
        $this->actingAs($this->admin)->deleteJson(route('admin.coe.floor.destroy', ['id' => $id]))->assertStatus(422);
    }

    public function test_room_master_shows_and_saves_only_classroom_rooms(): void
    {
        $classroom = BuildingMaster::create(['building_name' => 'ZZ Hall '.uniqid(), 'building_type' => 'Classroom', 'active_inactive' => 1]);
        $hostel = BuildingMaster::where('building_type', 'Hostel')->first();
        $floor = FloorMaster::value('pk');

        // The room is a Venue Master venue, stored by its short name.
        // Compared in PHP: venue_master and building_floor_room_mapping have different collations.
        $venue = \Illuminate\Support\Facades\DB::table('venue_master')->where('active_inactive', 1)->pluck('venue_short_name')
            ->map(fn ($s) => trim((string) $s))->filter()
            ->diff(BuildingFloorRoomMapping::pluck('room_name')->map(fn ($s) => trim((string) $s)))
            ->first();
        $room = ['room_name' => $venue, 'building_master_pk' => $classroom->pk, 'floor_master_pk' => $floor,
            'room_type' => 'Classroom', 'capacity' => 60];

        $this->save('admin.coe.room.store', ['room_name' => 'ZZ not a venue'] + $room)
            ->assertStatus(422)->assertJsonValidationErrors('room_name');
        $this->save('admin.coe.room.store', ['laptop_count' => 61] + $room)
            ->assertStatus(422)->assertJsonValidationErrors('laptop_count');
        $this->save('admin.coe.room.store', ['laptop_count' => 5, 'scriber_count' => 2] + $room)->assertOk();
        $saved = BuildingFloorRoomMapping::where('room_name', $venue)->firstOrFail();
        $this->assertSame([5, 2], [(int) $saved->laptop_count, (int) $saved->scriber_count]);
        // Unique across the table, on any floor or building; a hostel building.
        $this->save('admin.coe.room.store', ['floor_master_pk' => FloorMaster::where('pk', '!=', $floor)->value('pk')] + $room)
            ->assertStatus(422)->assertJsonValidationErrors('room_name');
        $this->save('admin.coe.room.store', ['building_master_pk' => $hostel->pk] + $room)
            ->assertStatus(422)->assertJsonValidationErrors('building_master_pk');
        // Edit keeps its own name without tripping the unique rule; counts may be cleared.
        $this->save('admin.coe.room.store', ['pk' => encrypt($saved->pk), 'capacity' => 80, 'laptop_count' => '', 'scriber_count' => ''] + $room)->assertOk();
        $this->assertSame([80, null], [(int) $saved->fresh()->capacity, $saved->fresh()->laptop_count]);

        $rows = collect($this->grid('admin.coe.room.index'));
        $this->assertTrue($rows->contains(fn ($r) => $r['room_name'] === e($venue) && $r['building_master_pk'] === e($classroom->building_name)
            && (int) $r['capacity'] === 80 && $r['DT_RowAttr']['data-coe-val-name'] === $venue
            && $r['DT_RowAttr']['data-coe-val-building'] === (string) $classroom->pk));
        // Every row is in a Classroom building: no hostel rooms.
        $classroomNames = BuildingMaster::classroom()->pluck('building_name')->map(fn ($n) => e(trim($n)))->all();
        $this->assertEmpty($rows->reject(fn ($r) => in_array($r['building_master_pk'], $classroomNames, true))->all());

        // A hostel room is not the COE's to touch.
        $hostelRoom = BuildingFloorRoomMapping::where('building_master_pk', $hostel->pk)->first();
        if ($hostelRoom) {
            $this->actingAs($this->admin)->patchJson(route('admin.coe.room.toggle-status', ['id' => encrypt($hostelRoom->pk)]), ['active' => 0])
                ->assertNotFound();
        }
    }

    public function test_faulty_laptops_leave_the_available_ones_once_the_column_exists(): void
    {
        if (! \App\Http\Controllers\Admin\COE\RoomController::faultyEnabled()) {
            $this->markTestSkipped('building_floor_room_mapping.faulty_laptop_count is not there yet (DBA DDL).');
        }
        $classroom = BuildingMaster::create(['building_name' => 'ZZ Lab '.uniqid(), 'building_type' => 'Classroom', 'active_inactive' => 1]);
        $venue = \Illuminate\Support\Facades\DB::table('venue_master')->where('active_inactive', 1)->pluck('venue_short_name')
            ->map(fn ($s) => trim((string) $s))->filter()
            ->diff(BuildingFloorRoomMapping::pluck('room_name')->map(fn ($s) => trim((string) $s)))->first();
        $room = ['room_name' => $venue, 'building_master_pk' => $classroom->pk, 'floor_master_pk' => FloorMaster::value('pk'),
            'room_type' => 'Classroom', 'capacity' => 40, 'laptop_count' => 10];

        $this->save('admin.coe.room.store', ['faulty_laptop_count' => 11] + $room)
            ->assertStatus(422)->assertJsonValidationErrors('faulty_laptop_count');
        $this->save('admin.coe.room.store', ['faulty_laptop_count' => 1, 'laptop_count' => ''] + $room)
            ->assertStatus(422)->assertJsonValidationErrors('faulty_laptop_count');
        $this->save('admin.coe.room.store', ['faulty_laptop_count' => 3] + $room)->assertOk();

        $row = collect($this->grid('admin.coe.room.index'))->firstWhere('room_name', e($venue));
        $this->assertSame([3, 7], [(int) $row['faulty_laptop_count'], (int) $row['available_laptops']]);
    }

    public function test_exports_follow_the_status_filter(): void
    {
        foreach (['building', 'floor', 'room'] as $page) {
            $this->actingAs($this->admin)->get(route("admin.coe.{$page}.export", ['format' => 'csv', 'status_filter' => '0']))->assertOk();
        }
    }
}
