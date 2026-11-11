<?php

namespace App\Services\COE;

use App\Http\Controllers\Admin\COE\RoomController;
use App\Models\BuildingMaster;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Examination rooms as COE → Master Data → Room Master defines them.
 *
 * Room Master keeps a room in building_floor_room_mapping (Classroom buildings of
 * building_master, floors of floor_master) and names it by venue_master.venue_short_name.
 * Seat plans, seat allocations and seat layouts are keyed by venue_master.venue_id, so
 * every query here starts from venue_master and joins its Room Master row by that name.
 */
final class ExamRooms
{
    /** The two name columns were created with different collations; compare them under one. */
    private const COLLATION = 'utf8mb4_general_ci';

    /**
     * venue_id → building, floor, capacity and laptops from Room Master, active or not.
     * Columns: venue_id, building_pk, building_name, floor_pk, floor_name, room_capacity,
     * room_laptops, is_active (room, building, floor and venue all active).
     */
    public static function placements(): Builder
    {
        $laptops = RoomController::faultyEnabled()
            ? 'GREATEST(0, rm.laptop_count - COALESCE(rm.faulty_laptop_count, 0))'
            : 'rm.laptop_count';

        return DB::table('venue_master as vm')
            ->join(
                'building_floor_room_mapping as rm',
                DB::raw('TRIM(rm.room_name) COLLATE ' . self::COLLATION),
                '=',
                DB::raw('TRIM(vm.venue_short_name) COLLATE ' . self::COLLATION)
            )
            ->join('building_master as b', fn ($j) => $j->on('b.pk', '=', 'rm.building_master_pk')
                ->where('b.building_type', BuildingMaster::TYPE_CLASSROOM))
            ->join('floor_master as f', 'f.pk', '=', 'rm.floor_master_pk')
            ->select([
                'vm.venue_id',
                'b.pk as building_pk',
                'b.building_name',
                'f.pk as floor_pk',
                'f.floor_name',
                'rm.capacity as room_capacity',
                DB::raw("{$laptops} as room_laptops"),
                DB::raw('(vm.active_inactive = 1 AND rm.active_inactive = 1 AND b.active_inactive = 1 AND f.active_inactive = 1) as is_active'),
            ]);
    }

    /** Rooms a new seating plan or seat layout may use: active, with a capacity. */
    public static function active(): Builder
    {
        return DB::query()->fromSub(self::placements(), 'rp')
            ->where('rp.is_active', 1)
            ->where('rp.room_capacity', '>', 0);
    }
}
