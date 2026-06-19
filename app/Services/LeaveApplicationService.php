<?php

namespace App\Services;

use App\Models\CourseMaster;
use App\Models\ExemptionMaster;
use App\Models\LeaveApplication;
use App\Models\StationedLeaveMaster;
use App\Models\StudentMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveApplicationService
{
    public function resolveStudentContext(int $userPk): array
    {
        $user = DB::table('user_credentials')->where('pk', $userPk)->first();

        if (! $user || ($user->user_category ?? '') !== 'S' || empty($user->user_id)) {
            abort(403, 'Only officer trainees can access leave applications.');
        }

        $student = StudentMaster::find($user->user_id);
        if (! $student) {
            abort(404, 'Student record not found.');
        }

        $course = CourseMaster::query()
            ->join('student_master_course__map as smcm', 'smcm.course_master_pk', '=', 'course_master.pk')
            ->where('smcm.student_master_pk', $student->pk)
            ->where('smcm.active_inactive', 1)
            ->where('course_master.active_inactive', 1)
            ->whereDate('course_master.end_date', '>=', now()->toDateString())
            ->orderByDesc('course_master.end_date')
            ->select('course_master.*')
            ->first();

        if (! $course) {
            abort(403, 'No active course found for your account.');
        }

        return [
            'student' => $student,
            'course' => $course,
            'student_pk' => (int) $student->pk,
            'course_pk' => (int) $course->pk,
        ];
    }

    public function calculateTotalDays(string $fromDate, string $toDate): float
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->startOfDay();

        if ($to->lt($from)) {
            return 0;
        }

        return (float) ($from->diffInDays($to) + 1);
    }

    public function getPtBalance(int $studentPk, int $coursePk, ?string $gender): array
    {
        $genderLabel = $this->normalizeGender($gender);
        $allocated = 0.0;

        if ($genderLabel) {
            $config = ExemptionMaster::query()
                ->where('course_master_pk', $coursePk)
                ->where('gender', $genderLabel)
                ->where('active_inactive', 1)
                ->whereDate('effective_from', '<=', now()->toDateString())
                ->orderByDesc('effective_from')
                ->first();

            $allocated = $config ? (float) $config->exemption_days : 0.0;
        }

        $used = (float) LeaveApplication::query()
            ->where('student_master_pk', $studentPk)
            ->where('course_master_pk', $coursePk)
            ->where('leave_type', LeaveApplication::TYPE_PT_EXEMPTION)
            ->where('status', LeaveApplication::STATUS_APPROVED)
            ->sum('total_days');

        $pending = (float) LeaveApplication::query()
            ->where('student_master_pk', $studentPk)
            ->where('course_master_pk', $coursePk)
            ->where('leave_type', LeaveApplication::TYPE_PT_EXEMPTION)
            ->where('status', LeaveApplication::STATUS_PENDING)
            ->sum('total_days');

        $remaining = max(0, $allocated - $used - $pending);

        return [
            'allocated' => $allocated,
            'used' => $used,
            'pending' => $pending,
            'remaining' => $remaining,
            'as_on' => now()->format('d M Y'),
        ];
    }

    public function stationedLeaveConfigured(int $coursePk): bool
    {
        return StationedLeaveMaster::query()
            ->where('course_master_pk', $coursePk)
            ->where('active_inactive', 1)
            ->whereDate('effective_from', '<=', now()->toDateString())
            ->exists();
    }

    public function assertNoOverlap(
        int $studentPk,
        string $fromDate,
        string $toDate,
        ?int $ignoreApplicationPk = null
    ): void {
        $query = LeaveApplication::query()
            ->where('student_master_pk', $studentPk)
            ->whereIn('status', [
                LeaveApplication::STATUS_DRAFT,
                LeaveApplication::STATUS_PENDING,
                LeaveApplication::STATUS_APPROVED,
            ])
            ->where(function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('from_date', [$fromDate, $toDate])
                    ->orWhereBetween('to_date', [$fromDate, $toDate])
                    ->orWhere(function ($inner) use ($fromDate, $toDate) {
                        $inner->whereDate('from_date', '<=', $fromDate)
                            ->whereDate('to_date', '>=', $toDate);
                    });
            });

        if ($ignoreApplicationPk) {
            $query->where('pk', '!=', $ignoreApplicationPk);
        }

        if ($query->exists()) {
            throw new \InvalidArgumentException('Leave dates overlap with an existing application.');
        }
    }

    protected function normalizeGender(?string $gender): ?string
    {
        $gender = strtolower(trim((string) $gender));

        if (in_array($gender, ['male', 'm'], true)) {
            return 'Male';
        }

        if (in_array($gender, ['female', 'f'], true)) {
            return 'Female';
        }

        return null;
    }
}
