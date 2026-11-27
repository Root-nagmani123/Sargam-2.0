<?php

namespace Modules\GuestTaClaim\Services;

use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\GuestTaClaim\Contracts\TimetableProvider;
use Modules\GuestTaClaim\Enums\ClaimStatus;
use Modules\GuestTaClaim\Enums\Permission;
use Modules\GuestTaClaim\Models\Claim;

/**
 * All status changes go through here, inside a transaction with a row lock,
 * and every change writes a history line.
 */
class ClaimWorkflowService
{
    public function __construct(
        protected ClaimCalculator $calculator,
        protected ClaimNumberGenerator $numbers,
        protected TimetableProvider $timetable,
        protected PermissionService $permissions,
    ) {}

    /**
     * Create or update a claim from the claim form.
     *
     * @param array $data validated header fields
     * @param array<int, array> $sessions validated session rows
     * @param array<int, UploadedFile> $files
     */
    public function save(
        ?Claim $claim,
        array $data,
        array $sessions,
        array $files,
        $user,
        bool $forward,
    ): Claim
    {
        return DB::transaction(function () use ($claim, $data, $sessions, $files, $user, $forward) {
            $isExisting = $claim !== null;
            if ($claim) {
                $claim = Claim::lockForUpdate()->findOrFail($claim->getKey());
                if (! $claim->status->isEditable()) {
                    throw new DomainException('This claim can no longer be edited.');
                }
            } else {
                $exists = Claim::where('course_id', $data['course_id'])
                    ->where('faculty_id', $data['faculty_id'])
                    ->whereDate('period_from', $data['period_from'])
                    ->whereDate('period_to', $data['period_to'])
                    ->first();
                if ($exists) {
                    throw new DomainException("Claim {$exists->claim_no} already exists for this faculty and period.");
                }

                $course = $this->timetable->course($data['course_id']) ?? throw new DomainException('Course not found.');
                $faculty = $this->timetable->faculty($data['faculty_id']) ?? throw new DomainException('Faculty not found.');

                $claim = new Claim([
                    'course_id' => (string) $course['id'],
                    'course_name' => $course['name'],
                    'coordinator_user_id' => $course['coordinator_user_id'],
                    'faculty_id' => (string) $faculty['id'],
                    'faculty_name' => $faculty['name'],
                    'faculty_designation' => $faculty['designation'] ?? null,
                    'faculty_organisation' => $faculty['organisation'] ?? null,
                    'faculty_official_address' => $data['faculty_official_address'] ?? $faculty['official_address'] ?? null,
                    'period_from' => $data['period_from'],
                    'period_to' => $data['period_to'],
                    'created_by' => $user->getKey(),
                ]);
                $claim->claim_no = $this->numbers->next();
                $claim->status = ClaimStatus::Draft;
            }

            $allowances = collect($data['travelling_allowances'] ?? [])
                ->map(fn ($row) => [
                    'departure_at' => $row['departure_at'] ?? null,
                    'departure_from' => trim((string) ($row['departure_from'] ?? '')) ?: null,
                    'arrival_at' => $row['arrival_at'] ?? null,
                    'arrival_to' => trim((string) ($row['arrival_to'] ?? '')) ?: null,
                    'travel_mode' => trim((string) ($row['travel_mode'] ?? '')) ?: null,
                    'distance_km' => $row['distance_km'] ?? null,
                    'fare_paid' => $row['fare_paid'] ?? 0,
                    'fare_admissible' => $row['fare_admissible'] ?? 0,
                    'accommodation' => $row['accommodation'] ?? 0,
                ])
                ->filter(fn ($row) => collect($row)->contains(fn ($value) => $value !== null && $value !== '' && $value !== 0 && $value !== '0'))
                ->values();

            $legacyAmounts = $isExisting
                && $allowances->isEmpty()
                && $claim->travellingAllowances()->doesntExist();
            $allowanceTotals = $this->calculator->travellingAllowanceTotals($allowances->all());
            $fareAdmissible = $legacyAmounts
                ? (float) $claim->travel_fare
                : $allowanceTotals['fare_admissible'];
            $accommodation = $legacyAmounts
                ? (float) $claim->local_conveyance
                : $allowanceTotals['accommodation'];
            $journeyDays = (int) ($data['da_journey_days'] ?? 0);
            $stayDays = (int) ($data['da_stay_days'] ?? 0);
            $daRate = (float) ($data['da_rate'] ?? 0);
            $daPercentage = (int) $data['da_percentage'];
            $firstAllowance = $allowances->first();

            $totals = $this->calculator->compute(
                $sessions,
                (float) $data['honorarium_rate'],
                $fareAdmissible,
                $accommodation,
                $journeyDays + $stayDays,
                $daRate,
                $daPercentage,
            );

            $claim->fill([
                'faculty_category' => $data['faculty_category'],
                'honorarium_rate' => $data['honorarium_rate'],
                'faculty_official_address' => $data['faculty_official_address'] ?? $claim->faculty_official_address,
                'basic_pay' => $data['basic_pay'] ?? null,
                'travel_mode' => $firstAllowance['travel_mode'] ?? ($legacyAmounts ? $claim->travel_mode : 'Not applicable'),
                'travel_from' => $firstAllowance['departure_from'] ?? ($legacyAmounts ? $claim->travel_from : null),
                'travel_fare' => $fareAdmissible,
                'local_conveyance' => $accommodation,
                'da_days' => $journeyDays + $stayDays,
                'da_journey_days' => $journeyDays,
                'da_stay_days' => $stayDays,
                'da_rate' => $daRate,
                'da_percentage' => $daPercentage,
                'da_rate_basis' => $data['da_rate_basis'] ?? null,
                'admissible_journey_numbers' => $data['admissible_journey_numbers'] ?? null,
                'higher_class_journey_numbers' => $data['higher_class_journey_numbers'] ?? null,
                'higher_class_reimbursement_basis' => $data['higher_class_reimbursement_basis'] ?? null,
                'remarks' => $data['remarks'] ?? null,
            ] + $totals);
            $claim->save();

            $claim->travellingAllowances()->delete();
            foreach ($allowances as $allowance) {
                $claim->travellingAllowances()->create($allowance);
            }

            $claim->sessions()->delete();
            foreach ($sessions as $s) {
                $claim->sessions()->create([
                    'source_session_id' => $s['source_session_id'] ?? null,
                    'session_date' => $s['session_date'],
                    'start_time' => $s['start_time'],
                    'end_time' => $s['end_time'],
                    'topic' => $s['topic'],
                    'venue' => $s['venue'] ?? null,
                    'status' => $s['status'],
                    'is_edited' => (bool) ($s['is_edited'] ?? false),
                ]);
            }

            $disk = config('guest-ta-claim.attachments.disk');
            foreach ($files as $file) {
                $path = $file->store(config('guest-ta-claim.attachments.directory').'/'.$claim->getKey(), $disk);
                $claim->attachments()->create([
                    'original_name' => $file->getClientOriginalName(),
                    'attachment_type' => 'supporting',
                    'path' => $path,
                    'disk' => $disk,
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $user->getKey(),
                ]);
            }

            if ($forward) {
                $this->transition($claim, $user, ClaimStatus::PendingCc, 'Claim forwarded to course coordinator', $data['remarks'] ?? null, ['forwarded_at' => now()]);
            } else {
                $this->log($claim, $user, 'Claim saved as draft', $claim->status, $claim->status);
            }

            return $claim;
        });
    }

    public function submitBillToAccounts(Claim $claim, UploadedFile $billPdf, $user): Claim
    {
        if (! $this->permissions->has($user, Permission::Forward)) {
            throw new DomainException('You do not have permission to submit claims directly to Accounts.');
        }

        return $this->guarded($claim, function (Claim $c) use ($billPdf, $user) {
            if (! $c->status->isEditable()) {
                throw new DomainException('This claim can no longer be submitted to Accounts.');
            }
            if ($c->sessions()->where('status', 'taken')->doesntExist()) {
                throw new DomainException('Confirm at least one session as taken before sending the claim to Accounts.');
            }

            $this->storeSubmittedBill($c, $billPdf, $user);
            $this->transition($c, $user, ClaimStatus::PendingAccounts, 'Signed bill submitted directly to Accounts', $c->remarks, [
                'forwarded_at' => now(),
            ]);
        });
    }

    public function forward(Claim $claim, $user): Claim
    {
        return $this->guarded($claim, function (Claim $c) use ($user) {
            if (! $c->status->isEditable()) {
                throw new DomainException('Only draft or returned claims can be forwarded.');
            }
            if ($c->sessions()->where('status', 'taken')->doesntExist()) {
                throw new DomainException('Mark at least one session as taken before forwarding.');
            }
            $this->transition($c, $user, ClaimStatus::PendingCc, 'Claim forwarded to course coordinator', null, ['forwarded_at' => now()]);
        });
    }

    public function approve(Claim $claim, $user, ?string $remark): Claim
    {
        return $this->guarded($claim, function (Claim $c) use ($user, $remark) {
            $this->assertCoordinator($c, $user);
            if ($c->status !== ClaimStatus::PendingCc) {
                throw new DomainException('This claim is not waiting for approval.');
            }
            $this->transition($c, $user, ClaimStatus::PendingAccounts, 'Approved by course coordinator', $remark, [
                'approved_by' => $user->getKey(), 'approved_at' => now(),
            ]);
        });
    }

    public function return(Claim $claim, $user, string $remark): Claim
    {
        return $this->guarded($claim, function (Claim $c) use ($user, $remark) {
            $this->assertCoordinator($c, $user);
            if ($c->status !== ClaimStatus::PendingCc) {
                throw new DomainException('This claim is not waiting for approval.');
            }
            $this->transition($c, $user, ClaimStatus::Returned, 'Returned for correction', $remark);
        });
    }

    public function markPaid(Claim $claim, $user, ?float $amount, ?string $remark): Claim
    {
        return $this->guarded($claim, function (Claim $c) use ($user, $amount, $remark) {
            if ($c->status !== ClaimStatus::PendingAccounts) {
                throw new DomainException('Only claims awaiting Accounts review can be marked paid.');
            }
            $this->transition($c, $user, ClaimStatus::Paid, 'Marked as paid', $remark, [
                'paid_amount' => $amount ?? $c->total_amount,
                'paid_remark' => $remark,
                'paid_by' => $user->getKey(),
                'paid_at' => now(),
            ]);
        });
    }

    public function markReportGenerated(Claim $claim, $user): void
    {
        if ($claim->status === ClaimStatus::Paid && ! $claim->report_generated_at) {
            $claim->forceFill(['report_generated_at' => now()])->save();
            $this->log($claim, $user, 'Claim report generated', $claim->status, $claim->status);
        }
    }

    public function delete(Claim $claim, $user): void
    {
        DB::transaction(function () use ($claim) {
            $c = Claim::lockForUpdate()->findOrFail($claim->getKey());
            if ($c->status !== ClaimStatus::Draft) {
                throw new DomainException('Only drafts can be deleted.');
            }
            foreach ($c->attachments as $a) {
                Storage::disk($a->disk)->delete($a->path);
            }
            $c->attachments()->delete();
            $c->sessions()->delete();
            $c->histories()->delete();
            $c->delete();
        });
    }

    public function canApprove(Claim $claim, $user): bool
    {
        if (! $this->permissions->has($user, Permission::Approve)) {
            return false;
        }

        return ! config('guest-ta-claim.restrict_approval_to_course_coordinator')
            || $this->permissions->isSuperAdmin($user)
            || $claim->coordinator_user_id === null
            || (int) $claim->coordinator_user_id === (int) $user->getKey();
    }

    protected function assertCoordinator(Claim $c, $user): void
    {
        if (! $this->canApprove($c, $user)) {
            throw new DomainException('Only the course coordinator of this course can decide on this claim.');
        }
    }

    protected function guarded(Claim $claim, callable $fn): Claim
    {
        return DB::transaction(function () use ($claim, $fn) {
            $c = Claim::lockForUpdate()->findOrFail($claim->getKey());
            $fn($c);

            return $c->refresh();
        });
    }

    protected function transition(Claim $c, $user, ClaimStatus $to, string $action, ?string $remark = null, array $extra = []): void
    {
        $from = $c->status;
        $c->forceFill($extra);
        $c->status = $to;
        $c->save();
        $this->log($c, $user, $action, $from, $to, $remark);
    }

    protected function log(Claim $c, $user, string $action, ?ClaimStatus $from, ClaimStatus $to, ?string $remark = null): void
    {
        $c->histories()->create([
            'user_id' => $user->getKey(),
            'user_name' => $user->{config('guest-ta-claim.user_name_column', 'name')} ?? 'User '.$user->getKey(),
            'action' => $action,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'remark' => $remark,
        ]);
    }

    protected function storeSubmittedBill(Claim $claim, UploadedFile $billPdf, $user): void
    {
        $disk = config('guest-ta-claim.attachments.disk');
        $path = $billPdf->store(config('guest-ta-claim.attachments.directory').'/'.$claim->getKey(), $disk);

        $claim->attachments()->create([
            'original_name' => $billPdf->getClientOriginalName(),
            'attachment_type' => 'submitted_bill',
            'path' => $path,
            'disk' => $disk,
            'mime' => $billPdf->getClientMimeType(),
            'size' => $billPdf->getSize(),
            'uploaded_by' => $user->getKey(),
        ]);
    }
}
