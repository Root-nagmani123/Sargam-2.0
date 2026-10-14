<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * COE → Examination Management → Evaluation & Marks.
 *
 * Design-first. Approval workflow: Pending → Approved / Returned for Recheck.
 */
class EvaluationMarksController extends Controller
{
    public const COURSES      = ['FC101', 'FC102', 'Phase IV 2025'];
    public const EXAMINATIONS = ['Written Test', 'Practical', 'Viva'];
    public const STATUSES     = ['Pending Review', 'Approved', 'Returned for Recheck'];
    public const MARK_SOURCES = ['Faculty', 'Sargam 2.0'];

    /** Faculty-end: enter/revise marks for assigned OTs. */
    public function facultyIndex(Request $request)
    {
        $tab = in_array($request->query('tab'), ['pending', 'completed', 'recheck'], true)
            ? $request->query('tab') : 'pending';

        $rows = $this->sampleRows();

        $pending   = $rows->where('approval_stage', 'Pending Review')->values();
        $completed = $rows->where('approval_stage', 'Approved')->values();
        $recheck   = $rows->where('approval_stage', 'Returned for Recheck')->values();

        return view('admin.coe.evaluation_marks.faculty_index', compact(
            'tab', 'pending', 'completed', 'recheck'
        ));
    }

    public function index(Request $request)
    {
        $rows = $this->sampleRows();

        return view('admin.coe.evaluation_marks.index', [
            'rows'        => $rows,
            'courses'     => self::COURSES,
            'examinations'=> self::EXAMINATIONS,
            'statuses'    => self::STATUSES,
            'pendingCount'  => $rows->where('approval_stage', 'Pending Review')->count(),
            'approvedCount' => $rows->where('approval_stage', 'Approved')->count(),
            'recheckCount'  => $rows->where('approval_stage', 'Returned for Recheck')->count(),
        ]);
    }

    /** @return Collection<int, array> */
    private function sampleRows(): Collection
    {
        $data = [
            // course, subjectComp, otCode, marksSource, faculty, booklet, attendance, assessMarks, examMarks, facultyRemarks, approval
            ['FC101', 'Law — Ethics & Integrity', 'UC01', 'Faculty',    'Cecil Obrien',   'PA-MT-1842', 'Present', '-',    42.00, '',                                 'Pending Review'],
            ['FC101', 'Law — Ethics & Integrity', 'UC02', '',           'GYAAN',          'PA-MT-1818', 'Present', '-',    23.00, '',                                 'Pending Review'],
            ['FC101', 'Law — Ethics & Integrity', 'UC03', 'Faculty',    'Janice Bailey',  'PA-MT-1835', 'Present', '-',    54.00, 'Strong analytical response',       'Pending Review'],
            ['FC101', 'Law — Ethics & Integrity', 'UC04', '',           'Sargam 2.0',     'PA-MT-1807', 'Absent',  'Not applicable', 'Not applicable', 'Attendance sheet verified', 'Pending Review'],
            ['FC101', 'Law — Ethics & Integrity', 'UC05', '',           'Sargam 2.0',     'PA-MT-1829', 'Present', '-',    40.00, '',                                 'Pending Review'],
            ['FC101', 'Law — Ethics & Integrity', 'UC06', 'Faculty',    'Traci Stevenson','PA-MT-1811', 'Present', '-',    24.00, '',                                 'Pending Review'],
            ['FC101', 'Law — Ethics & Integrity', 'UC07', 'Faculty',    'Claudia Byrd',   'PA-MT-1848', 'Present', '-',    53.00, '',                                 'Pending Review'],
            ['FC101', 'Law — Ethics & Integrity', 'UC08', 'Faculty',    'Sandra Calderon','PA-MT-1853', 'Present', '-',    38.00, '',                                 'Approved'],
            ['FC101', 'Law — Ethics & Integrity', 'UC09', 'Faculty',    'Adrian Harrington','PA-MT-1862','Present','-',   47.00, '',                                 'Approved'],
            ['FC101', 'Law — Ethics & Integrity', 'UC10', 'Faculty',    'John Doe',       'PA-MT-1871', 'Present', '-',    31.00, 'Needs improvement',                'Returned for Recheck'],
        ];

        return collect($data)->map(fn (array $r, int $i) => [
            'id'             => $i + 1,
            'course'         => $r[0],
            'subject_comp'   => $r[1],
            'ot_code'        => $r[2],
            'marks_source'   => $r[3],
            'faculty_name'   => $r[4],
            'booklet_code'   => $r[5],
            'attendance'     => $r[6],
            'assess_marks'   => $r[7],
            'exam_marks'     => $r[8],
            'faculty_remarks'=> $r[9],
            'admin_note'     => '',
            'approval_stage' => $r[10],
        ]);
    }
}
