<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Admin\COE\Concerns\RendersSimpleMaster;
use App\Http\Controllers\Controller;

/**
 * COE → Master Data → Building Master (examination buildings).
 *
 * Design-first screen, like the other COE screens: the rows are SAMPLE data.
 *
 * ⚠ The design and the backend already on feat/COE disagree
 * (Admin\Master\ExamBuildingMasterController, table `exam_building_master`):
 *   - Name  → `building_name`        required|max:150|unique      (matches)
 *   - Code  → `building_short_name`  nullable|max:50              (design makes it required)
 *   - Floors / Rooms counts          no such columns (floors are their own master, exam_floor_master)
 *   - Status `active_inactive`       stored 1 / 2 there, 1 / 0 here
 * The field names below follow the backend where a column exists; total_floors /
 * total_rooms are placeholders until that is settled.
 */
class BuildingController extends Controller
{
    use RendersSimpleMaster;

    public function index()
    {
        return $this->renderSimpleMaster([
            'title' => 'Building Master',
            'entity' => 'Building',
            'prefix' => 'ebm',
            'colvisKey' => 'coeBuilding:hiddenColumns:v1',
            'filters' => [['key' => 'status', 'label' => 'Status']],
            'labels' => [
                'createButton' => 'Add Building',
                'createTitle' => 'Add Building',
                'createSubmit' => 'Add Building',
                'editSubmit' => 'Save',
            ],
        ], [
            $this->nameField('building_name', 'Building Name', 'eg. Gyanshila', 150),
            [
                'key' => 'code', 'name' => 'building_short_name', 'label' => 'Building Code', 'column' => 'Building Code',
                'type' => 'text', 'placeholder' => 'eg. GS-01', 'required' => true, 'max' => 50, 'unique' => true,
            ],
            [
                'key' => 'floors', 'name' => 'total_floors', 'label' => 'Floors', 'column' => 'Total Floors',
                'type' => 'number', 'placeholder' => 'Select Number of Floors', 'required' => true,
                'min' => 1, 'max' => 50, 'half' => true, 'align' => 'center',
            ],
            [
                'key' => 'rooms', 'name' => 'total_rooms', 'label' => 'Rooms', 'column' => 'Total Rooms',
                'type' => 'number', 'placeholder' => 'Select Number of Rooms', 'required' => true,
                'min' => 1, 'max' => 999, 'half' => true, 'align' => 'center',
            ],
        ], [
            ['Adhar Shila', 'AS-01', 4, 12, 1],
            ['Academic Block', 'AB-01', 3, 8, 1],
            ['Main Campus', 'MC-01', 5, 15, 0],
        ]);
    }
}
