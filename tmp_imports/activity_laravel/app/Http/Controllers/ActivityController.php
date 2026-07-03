<?php

namespace App\Http\Controllers;

use App\Models\ActivityMaster;
use App\Models\CourseMaster;
use App\Models\OtActivity;
use App\Models\OtDetail;
use App\Services\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * ActivityController
 *
 * Converted from:
 *   upload.php      → store()   (AJAX POST)
 *   editrecord.php  → edit()    (GET page)
 *   updatedata.php  → update()  (AJAX GET → POST)
 *   deleterecord.php→ destroy() (GET redirect)
 *
 * Security fixes from original:
 *   - All inputs validated via Laravel validation (no raw $_GET injection)
 *   - Auth check via middleware instead of if($uname=='') echo "no"
 *   - Parameterised queries via Eloquent (no SQL injection risk)
 */
class ActivityController extends Controller
{
    public function __construct(private ActivityService $svc) {}

    // ── Store (upload.php) ────────────────────────────────────────────────────
    /**
     * Original upload.php logic:
     *   1. Get counter from countrecord table
     *   2. Build activityid = {ccode}Act{counter}
     *   3. Get OT username from otcode
     *   4. Check duplicate (same username + activity)
     *   5. Insert into otactivity_details
     *   6. Update countrecord
     *   Returns: 'ok' | 'al' | 'no'
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'otcode'    => 'required|string',
            'ccode'     => 'required|string',
            'uactivity' => 'required|string',
            'actvalue'  => 'required|string|max:500',
        ]);

        $result = $this->svc->store($request->only(['otcode','ccode','uactivity','actvalue']), Auth::user()->username);

        return response()->json(['status' => $result]);
    }

    // ── Edit form (editrecord.php) ────────────────────────────────────────────
    /**
     * Original editrecord.php:
     *   - Loads existing record by activityid
     *   - Pre-populates course dropdown (pre-selected)
     *   - Pre-populates OT dropdown for that course
     *   - Pre-populates activity dropdown for that course
     *   - Shows current activityval in textarea
     */
    public function edit(string $activityId): View
    {
        // Original: select * from otactivity_details where activityid='$activityid'
        $record = OtActivity::where('activityid', $activityId)->firstOrFail();

        // Original: select DISTINCT c_code,c_name from course_master where status=1
        $courses = CourseMaster::active()->select('c_code','c_name')->distinct()->get();

        // Original: select username,otname,otcode from ot_details where course='$row3[8]' AND status=1
        $ots = OtDetail::active()->byCourse($record->course)->select('username','otname','otcode')->get();

        // Original: select house from ot_details where username='$row3[2]'
        $house = OtDetail::where('username', $record->username)->value('house');

        // Original: select * from activity_master where ccode='$row3[8]' AND status=1
        $activities = ActivityMaster::active()->forCourse($record->course)->get();

        return view('home.edit', compact('record','courses','ots','house','activities'));
    }

    // ── Update (updatedata.php) ───────────────────────────────────────────────
    /**
     * Original updatedata.php:
     *   update otactivity_details set activity='$uactivity', activityval='$actvalue'
     *   where activityid='$activityid'
     */
    public function update(Request $request, string $activityId): JsonResponse
    {
        $request->validate([
            'uactivity' => 'required|string',
            'actvalue'  => 'required|string|max:500',
        ]);

        $ok = $this->svc->update($activityId, $request->uactivity, $request->actvalue);

        return response()->json(['status' => $ok ? 'ok' : 'error']);
    }

    // ── Delete (deleterecord.php) ─────────────────────────────────────────────
    /**
     * Original: delete from otactivity_details where activityid='$activityid'
     *           header("Location: home.php")
     */
    public function destroy(string $activityId): RedirectResponse
    {
        $this->svc->delete($activityId);
        return redirect()->route('home')->with('success', 'Record deleted.');
    }

    // ── AJAX: load_ote.php (for edit form) ────────────────────────────────────
    /** Returns OT list for a course with pre-selected OT marked */
    public function ajaxOtsForEdit(Request $request): JsonResponse
    {
        $course    = $request->query('course', '');
        $selectedOt = $request->query('ot', '');

        $ots = OtDetail::active()
            ->byCourse($course)
            ->select('username','otname','otcode')
            ->orderBy('otname')
            ->get()
            ->map(fn($o) => array_merge($o->toArray(), ['selected' => $o->username === $selectedOt]));

        return response()->json($ots);
    }
}
