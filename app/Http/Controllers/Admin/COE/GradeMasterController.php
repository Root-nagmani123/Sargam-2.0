<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Admin\COE\Concerns\RendersSimpleMaster;
use App\Http\Controllers\Controller;

/**
 * COE → Master Data → Grade Master.
 *
 * Design-first screen with sample rows. Fields: grade name, classification,
 * minimum %, maximum %, result status (Pass / Not Qualified), active/inactive.
 */
class GradeMasterController extends Controller
{
    use RendersSimpleMaster;

    public function index()
    {
        return $this->renderSimpleMaster([
            'title'     => 'Grade Master',
            'entity'    => 'Grade',
            'prefix'    => 'cgm',
            'colvisKey' => 'coeGradeMaster:hiddenColumns:v1',
            'filters'   => [
                ['key' => 'result_status', 'label' => 'Result Status'],
                ['key' => 'status',        'label' => 'Status'],
            ],
        ], [
            ['key' => 'grade',         'name' => 'grade',         'label' => 'Grade',         'column' => 'Grade',         'type' => 'text',   'placeholder' => 'eg. A+',         'required' => true,  'max' => 10,  'unique' => true],
            ['key' => 'classification','name' => 'classification','label' => 'Classification','column' => 'Classification','type' => 'text',   'placeholder' => 'eg. Outstanding',  'required' => true,  'max' => 100],
            ['key' => 'min_percent',   'name' => 'min_percent',   'label' => 'Minimum %',     'column' => 'Minimum %',     'type' => 'number', 'placeholder' => '',               'required' => true,  'min' => 0, 'max' => 100, 'half' => true],
            ['key' => 'max_percent',   'name' => 'max_percent',   'label' => 'Maximum %',     'column' => 'Maximum %',     'type' => 'number', 'placeholder' => '',               'required' => true,  'min' => 0, 'max' => 100, 'half' => true],
            ['key' => 'result_status', 'name' => 'result_status', 'label' => 'Result Status', 'column' => 'Result Status', 'type' => 'select', 'required' => true,
             'options' => ['Pass', 'Not Qualified']],
        ], [
            //  grade          classification   min  max   result_status    active
            ['A+', 'Outstanding',   85, 100, 'Pass',          true],
            ['A',  'Excellent',     75,  84, 'Pass',          true],
            ['B+', 'Very Good',     65,  74, 'Pass',          true],
            ['B',  'Good',          55,  64, 'Pass',          true],
            ['C',  'Satisfactory',  50,  54, 'Pass',          true],
            ['F',  'Not Qualified',  0,  49, 'Not Qualified', false],
        ]);
    }
}
