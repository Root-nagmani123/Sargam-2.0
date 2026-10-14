<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * COE → Master Data → Unique Participant Code.
 *
 * Design-first screen with sample rows. Columns: Course Name, OT Name,
 * DT Name, Unique Code, Status (Pending / Frozen).
 *
 * Two scopes:
 *   default        active / current courses
 *   ?scope=archived  archived courses
 */
class UniqueParticipantCodeController extends Controller
{
    public const SCOPES = ['active', 'archived'];

    public function index(Request $request)
    {
        $scope = in_array($request->query('scope'), self::SCOPES, true)
            ? $request->query('scope')
            : 'active';

        $rows = $this->sampleRows()->where('archived', $scope === 'archived')->values();

        return view('admin.coe.unique_participant_code.index', [
            'scope'   => $scope,
            'rows'    => $rows,
            'courses' => $rows->pluck('course_name')->unique()->sort()->values(),
        ]);
    }

    /** @return Collection<int, array{id:int, course_name:string, ot_name:string, dt_name:string, unique_code:string, status:string, archived:bool}> */
    private function sampleRows(): Collection
    {
        $rows = [
            // course,   ot_name,          dt_name,        unique_code, status,   archived
            ['FC101', 'Dana Arnold',    'danaarnold',    'UC-234', 'Pending', false],
            ['FC101', 'Carlos Jordan',  'carlosjordan',  'UC-234', 'Pending', false],
            ['FC101', 'Kelly Mack',     'kellymack',     'UC-235', 'Pending', false],
            ['FC101', 'Becky Blair',    'beckyblair',    'UC-236', 'Pending', false],
            ['FC101', 'Shannon Robbins','shannonrobbins','UC-237', 'Pending', false],
            ['FC102', 'Amit Sharma',    'amitsharma',   'UC-301', 'Frozen',  false],
            ['FC102', 'Neha Gupta',     'nehagupta',    'UC-302', 'Frozen',  false],
            ['FC099', 'Arjun Das',      'arjundas',     'UC-101', 'Frozen',  true],
            ['FC099', 'Meera Iyer',     'meeraiyer',    'UC-102', 'Pending', true],
        ];

        return collect($rows)->values()->map(fn (array $r, int $i) => [
            'id'          => $i + 1,
            'course_name' => $r[0],
            'ot_name'     => $r[1],
            'dt_name'     => $r[2],
            'unique_code' => $r[3],
            'status'      => $r[4],
            'archived'    => $r[5],
        ]);
    }
}
