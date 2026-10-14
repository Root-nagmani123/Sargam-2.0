<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Admin\COE\Concerns\SampleVenueData;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;

/**
 * COE → Master Data → Laptop & Scriber Seats Master.
 *
 * Design-first screen. Each row maps a room to a count of laptop seats and
 * scriber seats (selected from an interactive seat-map in the Add modal).
 * Fields: Building Name, Floor, Room Name, Room Type, Total Capacity,
 *         Laptop Seats (count), Scriber Seats (count).
 */
class LaptopScriberSeatsController extends Controller
{
    use SampleVenueData;

    public function index()
    {
        $rows = $this->sampleRows();

        return view('admin.coe.laptop_scriber_seats.index', [
            'rows'      => $rows,
            'buildings' => $this->sampleBuildings(),
            'floorsByBuilding' => $this->sampleFloorNamesByBuilding(),
            'roomsByBuildingFloor' => $this->sampleRoomsByBuildingFloor(),
            'buildingFilters' => $rows->pluck('building')->unique()->sort()->values(),
            'floorFilters'    => $rows->pluck('floor')->unique()->sort()->values(),
        ]);
    }

    /**
     * @return Collection<int, array{id:int, room:string, building:string, floor:string,
     *     type:string, capacity:int, laptop_seats:int, scriber_seats:int}>
     */
    private function sampleRows(): Collection
    {
        $rows = [
            // room,     building,        floor,          type,               capacity, laptop, scriber
            ['Room 01', 'Adhar Shila', 'Ground Floor', 'Computer Lab',      30, 10,  0],
            ['Room 02', 'Adhar Shila', 'Ground Floor', 'Seminar Room',      30, 10,  0],
            ['Room 03', 'Adhar Shila', '2nd Floor',    'Examination Hall', 188, 68,  0],
            ['Room 01', 'Academic Block', 'Ground Floor', 'Classroom',       45,  0,  0],
        ];

        return collect($rows)->values()->map(fn (array $r, int $i) => [
            'id'           => $i + 1,
            'room'         => $r[0],
            'building'     => $r[1],
            'floor'        => $r[2],
            'type'         => $r[3],
            'capacity'     => $r[4],
            'laptop_seats' => $r[5],
            'scriber_seats'=> $r[6],
        ]);
    }

    /**
     * Rooms per building+floor: [ building => [ floor => [ [name, type, capacity], … ] ] ]
     *
     * @return array<string, array<string, list<array{0:string, 1:string, 2:int}>>>
     */
    private function sampleRoomsByBuildingFloor(): array
    {
        return [
            'Adhar Shila' => [
                'Ground Floor' => [
                    ['Room 01', 'Computer Lab',    30],
                    ['Room 02', 'Seminar Room',    30],
                ],
                '1st Floor' => [
                    ['Room 01', 'Classroom',       40],
                ],
                '2nd Floor' => [
                    ['Room 03', 'Examination Hall', 188],
                ],
                '3rd Floor' => [
                    ['Room 04', 'Examination Hall', 200],
                ],
            ],
            'Academic Block' => [
                'Ground Floor' => [
                    ['Room 01', 'Classroom',        45],
                    ['Room 02', 'Seminar Room',     60],
                ],
            ],
            'Main Campus' => [
                'Ground Floor' => [
                    ['Hall 01', 'Examination Hall', 300],
                ],
            ],
        ];
    }
}
