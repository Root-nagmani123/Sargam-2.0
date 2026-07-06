<?php

namespace App\Services;

use App\Models\OtActivity;
use App\Models\OtDetail;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * ActivityService
 *
 * Extracted from original procedural files:
 *   upload.php      → store()
 *   updatedata.php  → update()
 *   deleterecord.php→ delete()
 *
 * Key logic preserved:
 *  1. Duplicate check: same OT + same activity = reject with 'al'
 *  2. activityid generated as: {ccode}Act{counter}  (replaced counter with UUID fragment)
 *  3. activitydt stored as "d-m-Y/ h:i:s" format (kept for backward compat)
 */
class ActivityService
{
    /**
     * Store a new activity record.
     * Returns: 'ok' | 'al' (already exists) | 'no' (not authenticated)
     */
    public function store(array $data, string $staffUsername): string
    {
        if (empty($staffUsername)) {
            return 'no';
        }

        // Resolve OT username from otcode (original: select username from ot_details where otcode='$otcode')
        $ot = OtDetail::where('otcode', $data['otcode'])->first();
        if (!$ot) {
            return 'no';
        }

        // Duplicate check (original: select * from otactivity_details where username=... AND activity=...)
        $exists = OtActivity::where('username', $ot->username)
            ->where('activity', $data['uactivity'])
            ->exists();

        if ($exists) {
            return 'al';
        }

        // Generate activityid: {ccode}Act{unique} (original used countrecord table counter)
        $activityId = $data['ccode'] . 'Act' . strtoupper(Str::random(8));

        OtActivity::create([
            'activityid'  => $activityId,
            'username'    => $ot->username,
            'activity'    => $data['uactivity'],
            'activityval' => $data['actvalue'],
            'activitydt'  => now()->format('d-m-Y/ h:i:s'),
            'submitedby'  => $staffUsername,
            'course'      => $data['ccode'],
            'status'      => 1,
        ]);

        return 'ok';
    }

    /**
     * Update an existing activity record.
     * Original: updatedata.php — update otactivity_details set activity=... activityval=... where activityid=...
     */
    public function update(string $activityId, string $activity, string $activityVal): bool
    {
        return (bool) OtActivity::where('activityid', $activityId)
            ->update([
                'activity'    => $activity,
                'activityval' => $activityVal,
            ]);
    }

    /**
     * Delete an activity record.
     * Original: deleterecord.php — delete from otactivity_details where activityid='$activityid'
     */
    public function delete(string $activityId): bool
    {
        return (bool) OtActivity::where('activityid', $activityId)->delete();
    }

    /**
     * Get department-wise activity counts for showreportall.php.
     * Original: 6 separate COUNT queries, one per department.
     * Optimised: single query grouped by activity.
     */
    public function getDeptCounts(): array
    {
        $counts = OtActivity::selectRaw('activity, COUNT(*) as total')
            ->whereIn('activity', array_values(OtActivity::DEPT_ACTIVITY))
            ->groupBy('activity')
            ->pluck('total', 'activity')
            ->toArray();

        $result = [];
        foreach (OtActivity::DEPT_ACTIVITY as $dept => $actCode) {
            $result[$dept] = $counts[$actCode] ?? 0;
        }
        return $result;
    }

    /**
     * Get service-wise joined counts.
     * Original servicewisest.php: nested loop comparing each service — O(N²).
     * Optimised: single JOIN query.
     */
    public function getServiceWiseCounts(): array
    {
        return OtActivity::join('ot_details', 'otactivity_details.username', '=', 'ot_details.username')
            ->where('otactivity_details.activity', 'joined')
            ->selectRaw('ot_details.service, COUNT(*) as total')
            ->groupBy('ot_details.service')
            ->pluck('total', 'service')
            ->toArray();
    }
}
