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
}
