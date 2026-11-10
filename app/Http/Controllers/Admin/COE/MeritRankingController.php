<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Services\COE\ResultMarksService;
use Illuminate\Http\Request;

/**
 * COE → Result Management → Merit & Ranking.
 *
 * The fully evaluated OTs of one course, ranked by Grand Total (A+B): subjects (A)
 * plus Director Assessment (B), with ICT / Hindi shown as qualifying papers.
 * Rules: ResultMarksService::merit().
 */
class MeritRankingController extends Controller
{
    public function __construct(private ResultMarksService $results)
    {
    }

    /** ?scope=active|archived&course=<pk> */
    public function index(Request $request)
    {
        $scope = $request->query('scope') === 'archived' ? 'archived' : 'active';

        return view('admin.coe.merit_ranking.index', ['scope' => $scope]
            + $this->results->merit($scope, $request->integer('course') ?: null));
    }
}
