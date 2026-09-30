<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Admin\COE\Concerns\RendersSimpleMaster;
use App\Http\Controllers\Controller;

/**
 * COE → Master Data → Examination Term Master.
 *
 * Design-first screen, like the other COE screens: the rows are SAMPLE data.
 * The field name and rules match the backend already written on the
 * feature/coe_module branch (Admin\Master\TermMasterController, table
 * `term_master`: `term_name` required|max:100|unique, `active_inactive` 0/1),
 * so wiring this view to it is a data swap only.
 */
class ExaminationTermController extends Controller
{
    use RendersSimpleMaster;

    public function index()
    {
        return $this->renderSimpleMaster([
            'title' => 'Examination Term Master',
            'entity' => 'Examination Term',
            'prefix' => 'etr',
            'colvisKey' => 'coeExaminationTerm:hiddenColumns:v1',
        ], [
            $this->nameField('term_name', 'Examination Term', 'eg. Mid term'),
        ], [
            ['Annual Term', 1],
            ['Semester 3', 1],
            ['Refresher Term', 1],
            ['Phase I Term', 1],
            ['Post Term Assessment', 0],
            ['Second Term', 1],
            ['Summer Term', 1],
        ]);
    }
}
