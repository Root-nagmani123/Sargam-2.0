<?php

namespace App\Http\Controllers\Admin\COE\Concerns;

/**
 * SAMPLE buildings and floors shared by the COE Building / Floor / Room
 * design-first screens, so their dropdowns agree with each other. Delete this
 * when those screens read the real exam venue masters.
 */
trait SampleVenueData
{
    /** @return list<string> */
    protected function sampleBuildings(): array
    {
        return ['Adhar Shila', 'Academic Block', 'Main Campus'];
    }

    /**
     * Floors per building: [building => [[floor name, code, rooms, active], …]].
     *
     * @return array<string, list<array{0:string, 1:string, 2:int, 3:int}>>
     */
    protected function sampleFloors(): array
    {
        return [
            'Adhar Shila' => [
                ['Ground Floor', 'GF-AS', 4, 1],
                ['1st Floor', '1F-AS', 3, 1],
                ['2nd Floor', '2F-AS', 3, 0],
                ['3rd Floor', '3F-AS', 2, 0],
            ],
            'Academic Block' => [
                ['Ground Floor', 'GF-AB', 7, 0],
            ],
            'Main Campus' => [
                ['Ground Floor', 'GF-MC', 5, 1],
            ],
        ];
    }

    /** @return array<string, list<string>> building => floor names */
    protected function sampleFloorNamesByBuilding(): array
    {
        return array_map(fn (array $floors) => array_column($floors, 0), $this->sampleFloors());
    }

    /**
     * Rooms per building+floor: ["Building||Floor" => [room name => [capacity, laptop_available]]].
     *
     * @return array<string, array<string, array{capacity:int, laptop:int}>>
     */
    protected function sampleRoomsByBuildingFloor(): array
    {
        return [
            'Adhar Shila||Ground Floor' => [
                'Room 101 – GF-AS-R1' => ['capacity' => 40, 'laptop' => 20],
                'Room 102 – GF-AS-R2' => ['capacity' => 30, 'laptop' => 15],
            ],
            'Adhar Shila||1st Floor' => [
                'Exam Hall – 1F-AS-EH1' => ['capacity' => 80, 'laptop' => 40],
                'Room 201 – 1F-AS-R1'   => ['capacity' => 35, 'laptop' => 18],
            ],
            'Adhar Shila||2nd Floor' => [
                'First Room – AS-SF02-R1' => ['capacity' => 100, 'laptop' => 22],
                'Exam Hall – AS-SF02-EH'  => ['capacity' =>  60, 'laptop' => 30],
            ],
            'Academic Block||Ground Floor' => [
                'Lecture Hall – AB-GF-LH1' => ['capacity' => 120, 'laptop' => 60],
                'Seminar Room – AB-GF-SR1' => ['capacity' =>  50, 'laptop' => 25],
            ],
            'Main Campus||Ground Floor' => [
                'Auditorium – MC-GF-AU' => ['capacity' => 200, 'laptop' => 0],
                'Conference Room – MC-GF-CR1' => ['capacity' => 30, 'laptop' => 30],
            ],
        ];
    }
}
