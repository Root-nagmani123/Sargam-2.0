<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Admin\COE\Concerns\SampleResultData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * COE → Result Management → Merit & Ranking.
 *
 * Design-first (see SampleResultData). The fully evaluated OTs of one course,
 * ranked by Grand Total = written subjects (A) + Director Assessment (B).
 * ICT and Hindi are qualifying papers, shown but not added in.
 */
class MeritRankingController extends Controller
{
    use SampleResultData;

    public function index(Request $request)
    {
        $scope = $request->query('scope') === 'archived' ? 'archived' : 'active';
        $courses = $this->scopedCourses($scope);
        $courseKey = isset($courses[$request->query('course')]) ? $request->query('course') : array_key_first($courses);

        $subjects = array_filter($this->resultSubjects(), fn ($s) => ! $s['assessment']);

        return view('admin.coe.merit_ranking.index', [
            'scope' => $scope,
            'courses' => $courses,
            'courseKey' => $courseKey,
            'subjects' => array_map(fn ($s) => array_sum($s['components']), $subjects),
            'writtenMax' => array_sum(array_map(fn ($s) => array_sum($s['components']), $subjects)),
            'assessmentMax' => $this->subjectMax('Director Assessment'),
            'rows' => $this->marksheets($courseKey),
        ]);
    }
}
