<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Admin\COE\Concerns\RendersSimpleMaster;
use App\Http\Controllers\Controller;

/**
 * COE → Master Data → Examination Type Master.
 *
 * Design-first screen, like the other COE screens: the rows are SAMPLE data.
 * The field name and rules match the backend already written on the
 * feature/coe_module branch (Admin\Master\ExaminationTypeMasterController,
 * table `examination_type_master`: `exam_type_name` required|max:100|unique,
 * `active_inactive` 0/1), so wiring this view to it is a data swap only.
 */
class ExaminationTypeController extends Controller
{
    use RendersSimpleMaster;

    public function index()
    {
        return $this->renderSimpleMaster([
            'title' => 'Examination Type Master',
            'entity' => 'Examination Type',
            'prefix' => 'ext',
            'colvisKey' => 'coeExaminationType:hiddenColumns:v1',
        ], [
            $this->nameField('exam_type_name', 'Examination Type', 'eg. Written Test'),
        ], [
            ['Written Test', 1],
            ['Computer Proficiency Test', 1],
            ['Presentation Assessment', 1],
            ['Director Assessment', 1],
            ['Viva Voce', 0],
            ['Leadership Assessment', 1],
            ['Ethics Assessment', 1],
        ]);
    }
}
