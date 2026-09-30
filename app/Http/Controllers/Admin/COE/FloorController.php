<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Admin\COE\Concerns\RendersSimpleMaster;
use App\Http\Controllers\Admin\COE\Concerns\SampleVenueData;
use App\Http\Controllers\Controller;

/**
 * COE → Master Data → Floor Master (floors of an examination building).
 *
 * Design-first screen, like the other COE screens: the rows are SAMPLE data.
 *
 * ⚠ The design and the backend already on feat/COE disagree
 * (Admin\Master\ExamFloorMasterController, table `exam_floor_master`):
 *   - that table has no building link, no floor code and no room count;
 *   - `floor_name` is unique across ALL buildings there, but the design has a
 *     "Ground Floor" in every building — here it is unique per building;
 *   - status is stored 1 / 2 there, 1 / 0 here.
 * floor_name follows the backend; the other field names are placeholders.
 */
class FloorController extends Controller
{
    use RendersSimpleMaster, SampleVenueData;

    public function index()
    {
        $rows = [];
        foreach ($this->sampleFloors() as $building => $floors) {
            foreach ($floors as [$name, $code, $rooms, $active]) {
                $rows[] = [$name, $code, $building, $rooms, $active];
            }
        }

        return $this->renderSimpleMaster([
            'title' => 'Floor Master',
            'entity' => 'Floor',
            'prefix' => 'efm',
            'colvisKey' => 'coeFloor:hiddenColumns:v1',
            'sno' => ['label' => 'Floor No.'],
            'filters' => [
                ['key' => 'building', 'label' => 'Building Name', 'wide' => true],
                ['key' => 'rooms', 'label' => 'Number of Rooms', 'wide' => true],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'formOrder' => ['building', 'name', 'code', 'rooms'],
            'labels' => [
                'createButton' => 'Add Floor',
                'createTitle' => 'Add Floor',
                'createSubmit' => 'Add Floor',
                'editSubmit' => 'Save',
            ],
        ], [
            ['key' => 'name', 'name' => 'floor_name', 'label' => 'Floor Name', 'column' => 'Floor Name',
                'type' => 'text', 'placeholder' => 'eg. Ground Floor', 'required' => true, 'max' => 100,
                'unique' => true, 'uniqueWith' => ['building']],
            ['key' => 'code', 'name' => 'floor_code', 'label' => 'Floor Code', 'column' => 'Floor Code',
                'type' => 'text', 'placeholder' => 'eg. GF-AS', 'required' => true, 'max' => 50, 'unique' => true],
            ['key' => 'building', 'name' => 'building_master_pk', 'label' => 'Building Name', 'column' => 'Building Name',
                'type' => 'select', 'placeholder' => 'Select Building Name', 'required' => true,
                'options' => $this->sampleBuildings()],
            ['key' => 'rooms', 'name' => 'total_rooms', 'label' => 'Rooms', 'column' => 'Total Rooms',
                'type' => 'number', 'placeholder' => 'Select Number of Rooms', 'required' => true,
                'min' => 1, 'max' => 999],
        ], $rows);
    }
}
