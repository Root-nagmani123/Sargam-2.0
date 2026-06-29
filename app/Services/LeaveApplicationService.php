<?php

namespace App\Services;

use App\Models\CourseMaster;
use App\Models\ExemptionMaster;
use App\Models\LeaveApplication;
use App\Models\StationedLeaveFacultyApprover;
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

        $course = $this->findStudentCourse((int) $student->pk);

        if (! $course) {
            abort(403, 'No course enrollment found for your account.');
        }

        $today = now()->toDateString();
        $courseIsRunning = $course->end_date === null
            || $course->end_date >= $today;

        return [
            'student' => $student,
            'course' => $course,
            'student_pk' => (int) $student->pk,
            'course_pk' => (int) $course->pk,
            'course_is_running' => $courseIsRunning,
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

    /**
     * Latest active PT exemption config for a course and gender as of a given date
     * (defaults to today). Uses the leave start date when validating applications.
     */
    public function getActivePtExemptionConfig(int $coursePk, ?string $gender, ?string $asOfDate = null): ?ExemptionMaster
    {
        $genderLabel = $this->normalizeGender($gender);
        if (! $genderLabel) {
            return null;
        }

        $asOfDate = $asOfDate ?? now()->toDateString();

        return ExemptionMaster::query()
            ->where('course_master_pk', $coursePk)
            ->where('gender', $genderLabel)
            ->where('active_inactive', 1)
            ->whereDate('effective_from', '<=', $asOfDate)
            ->orderByDesc('effective_from')
            ->first();
    }

    public function ptExemptionConfigured(int $coursePk, ?string $gender, ?string $asOfDate = null): bool
    {
        return $this->getActivePtExemptionConfig($coursePk, $gender, $asOfDate) !== null;
    }

    /**
     * Next PT exemption config that is active but not yet effective as of a given date
     * (defaults to today). Pass the leave start date when validating applications.
     */
    public function getUpcomingPtExemptionConfig(int $coursePk, ?string $gender, ?string $asOfDate = null): ?ExemptionMaster
    {
        $genderLabel = $this->normalizeGender($gender);
        if (! $genderLabel) {
            return null;
        }

        $asOfDate = $asOfDate ?? now()->toDateString();

        return ExemptionMaster::query()
            ->where('course_master_pk', $coursePk)
            ->where('gender', $genderLabel)
            ->where('active_inactive', 1)
            ->whereDate('effective_from', '>', $asOfDate)
            ->orderBy('effective_from')
            ->first();
    }

    public function getPtBalance(int $studentPk, int $coursePk, ?string $gender): array
    {
        $genderLabel = $this->normalizeGender($gender);
        $allocated = 0.0;

        if ($genderLabel) {
            $config = $this->getActivePtExemptionConfig($coursePk, $gender);
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

    /**
     * Latest active stationed-leave config for a course as of a given date
     * (defaults to today). Uses the leave start date when validating applications.
     */
    public function getActiveStationedLeaveConfig(int $coursePk, ?string $asOfDate = null): ?StationedLeaveMaster
    {
        $asOfDate = $asOfDate ?? now()->toDateString();

        return StationedLeaveMaster::query()
            ->where('course_master_pk', $coursePk)
            ->where('active_inactive', 1)
            ->whereDate('effective_from', '<=', $asOfDate)
            ->orderByDesc('effective_from')
            ->first();
    }

    public function stationedLeaveConfigured(int $coursePk, ?string $asOfDate = null): bool
    {
        return $this->getActiveStationedLeaveConfig($coursePk, $asOfDate) !== null;
    }

    /**
     * Whether a submitted stationed-leave application must wait for faculty approval.
     */
    public function stationedLeaveRequiresFacultyApproval(int $coursePk, ?string $asOfDate = null): bool
    {
        $config = $this->getActiveStationedLeaveConfig($coursePk, $asOfDate);

        if (! $config || ! (int) $config->is_faculty_approval_required) {
            return false;
        }

        return StationedLeaveFacultyApprover::query()
            ->where('stationed_leave_master_pk', $config->pk)
            ->where('is_approval_authority', 1)
            ->exists();
    }

    /**
     * Next stationed-leave config that is active but not yet effective as of a given date
     * (defaults to today). Pass the leave start date when validating applications.
     */
    public function getUpcomingStationedLeaveConfig(int $coursePk, ?string $asOfDate = null): ?StationedLeaveMaster
    {
        $asOfDate = $asOfDate ?? now()->toDateString();

        return StationedLeaveMaster::query()
            ->where('course_master_pk', $coursePk)
            ->where('active_inactive', 1)
            ->whereDate('effective_from', '>', $asOfDate)
            ->orderBy('effective_from')
            ->first();
    }

    public function findOverlappingApplication(
        int $studentPk,
        string $fromDate,
        string $toDate,
        ?int $ignoreApplicationPk = null
    ): ?LeaveApplication {
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

        return $query->orderByDesc('from_date')->first();
    }

    public function overlapErrorMessage(LeaveApplication $existing): string
    {
        $from = $existing->from_date?->format('d-m-Y') ?? '';
        $to = $existing->to_date?->format('d-m-Y') ?? '';

        return 'Leave dates overlap with an existing '
            . $existing->leave_type_label
            . ' application (' . $from . ' to ' . $to . ', '
            . $existing->status_label
            . '). Please choose dates that do not conflict.';
    }

    public function assertNoOverlap(
        int $studentPk,
        string $fromDate,
        string $toDate,
        ?int $ignoreApplicationPk = null
    ): void {
        $existing = $this->findOverlappingApplication($studentPk, $fromDate, $toDate, $ignoreApplicationPk);

        if ($existing) {
            throw new \InvalidArgumentException($this->overlapErrorMessage($existing));
        }
    }

    /**
     * Officer trainees may apply for leave starting today only before the configured PT/cutoff time.
     */
    public function isLeaveStartDateAllowedForApply(?string $cutoffTime, string $fromDate, ?Carbon $now = null): bool
    {
        if (blank($cutoffTime)) {
            return true;
        }

        $now = $now ?? now();
        $from = Carbon::parse($fromDate)->startOfDay();
        $today = $now->copy()->startOfDay();

        if (! $from->equalTo($today)) {
            return true;
        }

        $cutoff = Carbon::parse($today->toDateString() . ' ' . $cutoffTime);

        return $now->lt($cutoff);
    }

    public function applyCutoffErrorMessage(string $leaveTypeLabel, ?string $cutoffTime): string
    {
        $timeDisplay = $cutoffTime
            ? Carbon::parse($cutoffTime)->format('h:i A')
            : '';

        return 'You cannot apply for ' . $leaveTypeLabel . ' starting today after PT timing ('
            . $timeDisplay . '). Please select a future start date.';
    }

    /**
     * Earliest selectable start date considering effective-from and same-day apply cutoff.
     */
    public function resolveEarliestFromDate(?string $configMinDate, ?string $cutoffTime): ?string
    {
        $candidates = array_filter([
            $configMinDate,
            now()->toDateString(),
        ]);

        if ($candidates === []) {
            return null;
        }

        $min = max($candidates);

        if (! $this->isLeaveStartDateAllowedForApply($cutoffTime, now()->toDateString())) {
            $min = max($min, now()->addDay()->toDateString());
        }

        return $min;
    }

    public function formatCutoffTimeDisplay(?string $cutoffTime): ?string
    {
        if (blank($cutoffTime)) {
            return null;
        }

        return Carbon::parse($cutoffTime)->format('h:i A');
    }

    /**
     * Prefer a currently running course; otherwise use the latest active enrollment.
     * Matches enrollment checks used elsewhere (e.g. EnrollementController::hasActiveCourse).
     */
    protected function findStudentCourse(int $studentPk): ?CourseMaster
    {
        $today = now()->toDateString();

        $baseQuery = CourseMaster::query()
            ->join('student_master_course__map as smcm', 'smcm.course_master_pk', '=', 'course_master.pk')
            ->where('smcm.student_master_pk', $studentPk)
            ->where('smcm.active_inactive', 1)
            ->where('course_master.active_inactive', 1)
            ->select('course_master.*');

        $runningCourse = (clone $baseQuery)
            ->where(function ($q) use ($today) {
                $q->whereNull('course_master.end_date')
                    ->orWhereDate('course_master.end_date', '>=', $today);
            })
            ->orderByDesc('smcm.pk')
            ->first();

        if ($runningCourse) {
            return $runningCourse;
        }

        return $baseQuery->orderByDesc('smcm.pk')->first();
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
