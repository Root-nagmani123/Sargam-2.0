<?php

namespace App\Services\FC;

use App\Models\FC\FcOtActivity;
use App\Models\FC\FcOtDetail;
use Illuminate\Support\Str;

class FcActivityService
{
    public function store(array $data, string $staffUsername): string
    {
        if (empty($staffUsername)) {
            return 'no';
        }

        $ot = FcOtDetail::where('otcode', $data['otcode'])->first();
        if (! $ot) {
            return 'no';
        }

        $exists = FcOtActivity::where('username', $ot->username)
            ->where('activity', $data['uactivity'])
            ->exists();

        if ($exists) {
            return 'al';
        }

        $activityId = $data['ccode'] . 'Act' . strtoupper(Str::random(8));

        FcOtActivity::create([
            'activityid' => $activityId,
            'username' => $ot->username,
            'activity' => $data['uactivity'],
            'activityval' => $data['actvalue'],
            'activitydt' => now()->format('d-m-Y/ h:i:s'),
            'submitedby' => $staffUsername,
            'course' => $data['ccode'],
            'status' => 1,
        ]);

        return 'ok';
    }

    public function update(string $activityId, string $activity, string $activityVal): bool
    {
        return (bool) FcOtActivity::where('activityid', $activityId)->update([
            'activity' => $activity,
            'activityval' => $activityVal,
        ]);
    }

    public function delete(string $activityId): bool
    {
        return (bool) FcOtActivity::where('activityid', $activityId)->delete();
    }

    public function getDeptCounts(): array
    {
        $counts = FcOtActivity::selectRaw('activity, COUNT(*) as total')
            ->whereIn('activity', array_values(FcOtActivity::DEPT_ACTIVITY))
            ->groupBy('activity')
            ->pluck('total', 'activity')
            ->toArray();

        $result = [];
        foreach (FcOtActivity::DEPT_ACTIVITY as $dept => $actCode) {
            $result[$dept] = $counts[$actCode] ?? 0;
        }
        return $result;
    }

    public function getServiceWiseCounts(): array
    {
        return FcOtActivity::join('fc_ot_details', 'fc_otactivity_details.username', '=', 'fc_ot_details.username')
            ->where('fc_otactivity_details.activity', 'joined')
            ->selectRaw('fc_ot_details.service, COUNT(*) as total')
            ->groupBy('fc_ot_details.service')
            ->pluck('total', 'service')
            ->toArray();
    }
}
