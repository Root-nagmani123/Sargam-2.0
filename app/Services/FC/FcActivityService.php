<?php

namespace App\Services\FC;

use App\Models\FC\FcActivityMaster;
use App\Models\FC\FcOtActivity;
use App\Models\FC\FcOtDetail;
use Illuminate\Support\Str;

class FcActivityService
{
    public function __construct(private FcPostArrivalAccessService $access)
    {
    }

    public function store(array $data, string $staffUsername): string
    {
        if ($staffUsername === '') {
            return 'no';
        }

        $ot = FcOtDetail::where('otcode', $data['otcode'])->first();
        if (! $ot) {
            return 'no';
        }

        $master = FcActivityMaster::query()
            ->where('menuid', $data['uactivity'])
            ->where('status', 1)
            ->first();
        if (! $master) {
            return 'no';
        }

        $this->access->assertMenuidAllowedForOtEntry($data['uactivity']);

        // Medical department: always append a new row (history) so vitals (height, weight, etc.) are not
        // overwritten by upsert — even if the master is still set to "upsert" in Activity setup.
        if ($this->isMedicalDepartmentActivity($master)) {
            return $this->insertRow($ot->user_id, $data, $staffUsername) ? 'ok' : 'no';
        }

        if ($master->entry_policy === 'repeat') {
            return $this->insertRow($ot->user_id, $data, $staffUsername) ? 'ok' : 'no';
        }

        if ($master->entry_policy === 'upsert') {
            return $this->upsertRow($ot->user_id, $data, $staffUsername) ? 'ok' : 'no';
        }

        $exists = FcOtActivity::where(fc_user_col('fc_otactivity_details'), $ot->user_id)
            ->where('activity', $data['uactivity'])
            ->where(function ($q) use ($data) {
                $q->where('course', $data['ccode'])
                    ->orWhereRaw('TRIM(course) = ?', [trim((string) $data['ccode'])]);
            })
            ->exists();
        if ($exists) {
            return 'al';
        }

        return $this->insertRow($ot->user_id, $data, $staffUsername) ? 'ok' : 'no';
    }

    private function isMedicalDepartmentActivity(FcActivityMaster $master): bool
    {
        $medId = $this->access->medicalDepartmentId();
        if ($medId === null) {
            return false;
        }

        return (int) $master->department_id === $medId;
    }

    private function insertRow(int $userId, array $data, string $staffUsername): bool
    {
        $activityId = $data['ccode'] . 'Act' . strtoupper(Str::random(8));

        try {
            FcOtActivity::create([
                'activityid' => $activityId,
                fc_user_col('fc_otactivity_details') => fc_user_val('fc_otactivity_details', $userId),
                'activity' => $data['uactivity'],
                'activityval' => $data['actvalue'],
                'activitydt' => now()->format('d-m-Y/ h:i:s'),
                'submitedby' => $staffUsername,
                'course' => $data['ccode'],
                'status' => 1,
            ]);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    private function upsertRow(int $userId, array $data, string $staffUsername): bool
    {
        $existing = FcOtActivity::query()
            ->where(fc_user_col('fc_otactivity_details'), fc_user_val('fc_otactivity_details', $userId))
            ->where('activity', $data['uactivity'])
            ->where(function ($q) use ($data) {
                $q->where('course', $data['ccode'])
                    ->orWhereRaw('TRIM(course) = ?', [trim((string) $data['ccode'])]);
            })
            ->first();

        $payload = [
            'activityval' => $data['actvalue'],
            'activitydt' => now()->format('d-m-Y/ h:i:s'),
            'submitedby' => $staffUsername,
            'course' => $data['ccode'],
            'status' => 1,
        ];

        try {
            if ($existing) {
                $existing->update($payload);

                return true;
            }

            $payload['activityid'] = $data['ccode'] . 'Act' . strtoupper(Str::random(8));
            $payload[fc_user_col('fc_otactivity_details')] = fc_user_val('fc_otactivity_details', $userId);
            $payload['activity'] = $data['uactivity'];

            FcOtActivity::create($payload);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    public function update(string $activityId, string $activity, string $activityVal): bool
    {
        $this->access->assertMenuidAllowedForOtEntry($activity);

        return (bool) FcOtActivity::where('activityid', $activityId)->update([
            'activity' => $activity,
            'activityval' => $activityVal,
        ]);
    }

    public function delete(string $activityId): bool
    {
        $row = FcOtActivity::where('activityid', $activityId)->first();
        if (! $row) {
            return false;
        }
        $this->access->assertMenuidAllowedForOtEntry($row->activity);

        return (bool) $row->delete();
    }

    /**
     * Summary counts: per active activity that the current user is allowed to view.
     *
     * @return array<string, int> menuid => distinct OT count
     */
    public function getVisibleActivityCounts(): array
    {
        $masters = FcActivityMaster::query()
            ->active()
            ->ordered()
            ->get(['menuid', 'department_id']);

        $menuids = $masters->pluck('menuid')->all();
        if ($menuids === []) {
            return [];
        }

        $rows = FcOtActivity::query()
            ->where('status', 1)
            ->whereIn('activity', $menuids)
            ->select(['activity'])
            ->selectRaw('COUNT(DISTINCT ' . fc_user_col('fc_otactivity_details') . ') as c')
            ->groupBy('activity')
            ->pluck('c', 'activity')
            ->toArray();

        return $rows;
    }

    /**
     * @return array<string, float|int|string|null>
     */
    public function getServiceWiseJoinedCounts(?string $joinedMenuid): array
    {
        if (! $joinedMenuid) {
            return [];
        }

        return FcOtActivity::query()
            ->join('fc_ot_details', 'fc_otactivity_details.' . fc_user_col('fc_otactivity_details'), '=', 'fc_ot_details.' . fc_user_col('fc_ot_details'))
            ->where('fc_otactivity_details.activity', $joinedMenuid)
            ->where('fc_otactivity_details.status', 1)
            ->where('fc_ot_details.status', 1)
            ->selectRaw('fc_ot_details.service as svc, COUNT(DISTINCT fc_ot_details.' . fc_user_col('fc_ot_details') . ') as total')
            ->groupBy('fc_ot_details.service')
            ->pluck('total', 'svc')
            ->toArray();
    }
}
