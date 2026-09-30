<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Admin\COE\Concerns\RendersSimpleMaster;
use App\Http\Controllers\Admin\COE\Concerns\SampleVenueData;
use App\Http\Controllers\Controller;

/**
 * COE → Master Data → Room Master (rooms on a floor of an examination building).
 *
 * Design-first screen, like the other COE screens: the rows are SAMPLE data.
 * There is no room master on feat/COE; the closest thing is venue_master, which
 * carries building_master_pk / floor_master_pk. All field names here are
 * placeholders until that is decided.
 */
class RoomController extends Controller
{
    use RendersSimpleMaster, SampleVenueData;

    /** Sample room types. */
    private const ROOM_TYPES = ['Classroom', 'Computer Lab', 'Examination Hall', 'Seminar Room'];

    public function index()
    {
        return $this->renderSimpleMaster([
            'title' => 'Room Master',
            'entity' => 'Room',
            'prefix' => 'exr',
            'colvisKey' => 'coeRoom:hiddenColumns:v1',
            'sno' => ['show' => false],
            'filters' => [
                ['key' => 'building', 'label' => 'Building Name', 'wide' => true],
                ['key' => 'floor', 'label' => 'Floor'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'labels' => [
                'createButton' => 'Add Room',
                'createTitle' => 'Add Room',
                'createSubmit' => 'Add Room',
                'editTitle' => 'Edit Room',
                'editSubmit' => 'Update Room',
            ],
        ], [
            ['key' => 'name', 'name' => 'room_name', 'label' => 'Room Name', 'column' => 'Room Name',
                'type' => 'text', 'placeholder' => 'eg. Room 01', 'required' => true, 'max' => 100,
                'unique' => true, 'uniqueWith' => ['building', 'floor']],
            ['key' => 'building', 'name' => 'building_master_pk', 'label' => 'Building Name', 'column' => 'Building',
                'type' => 'select', 'placeholder' => 'Select Building Name', 'required' => true,
                'options' => $this->sampleBuildings()],
            ['key' => 'floor', 'name' => 'floor_master_pk', 'label' => 'Floor Name', 'column' => 'Floor',
                'type' => 'select', 'placeholder' => 'Select Floor', 'required' => true,
                'dependsOn' => 'building', 'optionsBy' => $this->sampleFloorNamesByBuilding()],
            ['key' => 'type', 'name' => 'room_type', 'label' => 'Room Type', 'column' => 'Room Type',
                'type' => 'select', 'placeholder' => 'Select Type', 'required' => true, 'options' => self::ROOM_TYPES],
            ['key' => 'capacity', 'name' => 'total_capacity', 'label' => 'Total Capacity', 'column' => 'Total Capacity',
                'type' => 'number', 'placeholder' => 'Select Number', 'required' => true,
                'requiredMsg' => 'Enter the total capacity.',
                'min' => 1, 'max' => 1000, 'align' => 'center'],
        ], [
            ['Room 01', 'Adhar Shila', 'Ground Floor', 'Computer Lab', 30, 1],
            ['Room 02', 'Adhar Shila', 'Ground Floor', 'Seminar Room', 30, 1],
            ['Room 03', 'Adhar Shila', '2nd Floor', 'Examination Hall', 188, 0],
            ['Room 01', 'Academic Block', 'Ground Floor', 'Classroom', 45, 1],
        ]);
    }
}
