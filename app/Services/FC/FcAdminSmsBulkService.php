<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use App\Models\PathPage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin bulk SMS for B1 (form step incomplete) and B2 (registration pending).
 *
 * B1 ≠ B2:
 * - B1: trainee started form (at least 1 step done) but still has pending steps → SMS for first pending step
 * - B2: trainee has login but has not completed any form step yet → overall registration-pending SMS
 *
 * No recipient picker. No send limit. Auto OTP/credentials/exemption/success flows unchanged.
 */
class FcAdminSmsBulkService
{
    public const TEMPLATE_B1 = 'b1';

    public const TEMPLATE_B2 = 'b2';

    public function __construct(
        private FcNotifyService $notify,
        private FcRegistrationFlowService $flow,
    ) {
    }

    /**
     * @return array{ok: bool, message: string, sent: int, skipped: int, failed: int}
     */
    public function send(string $template): array
    {
        $template = strtolower(trim($template));

        return match ($template) {
            self::TEMPLATE_B1 => $this->sendB1(),
            self::TEMPLATE_B2 => $this->sendB2(),
            default => [
                'ok' => false,
                'message' => 'Invalid template. Choose Form step incomplete or Registration pending.',
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
            ],
        };
    }

    /**
     * @return array{b1: int, b2: int, programme: string, last_date: string}
     */
    public function previewCounts(): array
    {
        return [
            'b1' => $this->b1Recipients()->count(),
            'b2' => $this->b2Recipients()->count(),
            'programme' => $this->programmeName(),
            'last_date' => $this->registrationDeadlineText(),
        ];
    }

    /**
     * @return Collection<int, array{pk: int, mobile: string, name: string, user_id: string, step_name: ?string}>
     */
    public function recipientList(string $template): Collection
    {
        $template = strtolower(trim($template));

        if ($template === self::TEMPLATE_B1) {
            return $this->b1Recipients()->map(fn (array $row) => [
                'pk' => $row['pk'],
                'name' => $row['name'],
                'mobile' => $row['mobile'],
                'user_id' => $row['user_id'] ?? '',
                'step_name' => $row['step_name'],
            ])->values();
        }

        if ($template === self::TEMPLATE_B2) {
            return $this->b2Recipients()->map(fn (array $row) => [
                'pk' => $row['pk'],
                'name' => $row['name'],
                'mobile' => $row['mobile'],
                'user_id' => $row['user_id'] ?? '',
                'step_name' => null,
            ])->values();
        }

        return collect();
    }

    /**
     * @return array{ok: bool, message: string, sent: int, skipped: int, failed: int}
     */
    protected function sendB1(): array
    {
        @set_time_limit(0);

        $recipients = $this->b1Recipients();
        if ($recipients->isEmpty()) {
            return [
                'ok' => true,
                'message' => 'No trainees found who started the form and still have a pending step.',
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
            ];
        }

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $row) {
            try {
                $this->notify->formStepIncomplete(
                    $row['mobile'],
                    $row['name'],
                    $row['step_name'],
                    $row['pk'],
                );
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        return [
            'ok' => true,
            'message' => "Form step incomplete SMS processed for {$sent} trainee(s).",
            'sent' => $sent,
            'skipped' => 0,
            'failed' => $failed,
        ];
    }

    /**
     * @return array{ok: bool, message: string, sent: int, skipped: int, failed: int}
     */
    protected function sendB2(): array
    {
        @set_time_limit(0);

        $recipients = $this->b2Recipients();
        if ($recipients->isEmpty()) {
            return [
                'ok' => true,
                'message' => 'No trainees found with registration pending (no form step started yet).',
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
            ];
        }

        $programme = $this->programmeName();
        $lastDate = $this->registrationDeadlineText();
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $row) {
            try {
                $this->notify->registrationPending(
                    $row['mobile'],
                    $row['name'],
                    $programme,
                    $lastDate,
                    $row['pk'],
                );
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        return [
            'ok' => true,
            'message' => "Registration pending SMS processed for {$sent} trainee(s).",
            'sent' => $sent,
            'skipped' => 0,
            'failed' => $failed,
        ];
    }

    /**
     * B1 — started form ( ≥1 step done ) + still has pending step(s).
     *
     * @return Collection<int, array{pk: int, mobile: string, name: string, user_id: string, step_name: string}>
     */
    protected function b1Recipients(): Collection
    {
        $out = collect();

        foreach ($this->eligibleRosterRows() as $row) {
            $classified = $this->classifyRosterRow($row);
            if ($classified === null || $classified['bucket'] !== self::TEMPLATE_B1) {
                continue;
            }

            $out->push([
                'pk' => $classified['pk'],
                'mobile' => $classified['mobile'],
                'name' => $classified['name'],
                'user_id' => $classified['user_id'],
                'step_name' => $classified['step_name'],
            ]);
        }

        return $out;
    }

    /**
     * B2 — login exists but no form step completed yet.
     *
     * @return Collection<int, array{pk: int, mobile: string, name: string, user_id: string}>
     */
    protected function b2Recipients(): Collection
    {
        $out = collect();

        foreach ($this->eligibleRosterRows() as $row) {
            $classified = $this->classifyRosterRow($row);
            if ($classified === null || $classified['bucket'] !== self::TEMPLATE_B2) {
                continue;
            }

            $out->push([
                'pk' => $classified['pk'],
                'mobile' => $classified['mobile'],
                'name' => $classified['name'],
                'user_id' => $classified['user_id'],
            ]);
        }

        return $out;
    }

    /**
     * Eligible roster: has mobile + username, not exemption. (May include is_registered=1
     * so later pending steps still get B1 incomplete SMS.)
     */
    protected function eligibleRosterRows(): Collection
    {
        if (! Schema::hasTable('fc_registration_master')) {
            return collect();
        }

        $query = DB::table('fc_registration_master')
            ->select('pk', 'display_name', 'contact_no', 'user_id')
            ->whereNotNull('contact_no')
            ->where('contact_no', '!=', '')
            ->whereNotNull('user_id')
            ->where('user_id', '!=', '');

        if (Schema::hasColumn('fc_registration_master', 'application_type')) {
            $query->where(function ($q) {
                $q->whereNull('application_type')
                    ->orWhere('application_type', '!=', FcRosterApplicationGuardService::APPLICATION_EXEMPTION);
            });
        }

        return $query->orderBy('pk')->get();
    }

    /**
     * @return array{bucket: string, pk: int, mobile: string, name: string, user_id: string, step_name: ?string}|null
     */
    protected function classifyRosterRow(object $row): ?array
    {
        $mobile = trim((string) ($row->contact_no ?? ''));
        if ($mobile === '') {
            return null;
        }

        $progressUserId = $this->resolveProgressUserId($row);
        if ($progressUserId === null) {
            return null;
        }

        $form = FcForm::resolveForUserId($progressUserId) ?? FcForm::activeRegistrationDynamicForm();
        $progress = $this->stepProgress($progressUserId, $form);

        // All done — neither B1 nor B2
        if ($progress['pending_step'] === null && $progress['done'] > 0) {
            return null;
        }

        $base = [
            'pk' => (int) $row->pk,
            'mobile' => $mobile,
            'name' => trim((string) ($row->display_name ?? '')),
            'user_id' => trim((string) ($row->user_id ?? '')),
            'step_name' => $progress['pending_step'],
        ];

        // Started (≥1 done) + still pending → Incomplete (B1)
        if ($progress['done'] >= 1 && $progress['pending_step'] !== null) {
            $base['bucket'] = self::TEMPLATE_B1;

            return $base;
        }

        // Not started (0 done) → Registration pending (B2)
        if ($progress['done'] === 0) {
            $base['bucket'] = self::TEMPLATE_B2;
            $base['step_name'] = null;

            return $base;
        }

        return null;
    }

    /**
     * @return array{done: int, pending_step: ?string}
     */
    protected function stepProgress(int $progressUserId, ?FcForm $form): array
    {
        if ($form) {
            $steps = $form->activeSteps()->get();
            $status = $this->flow->buildStepCompletionByStepId($form, $steps, $progressUserId);
            $done = 0;
            $pending = null;

            foreach ($steps as $step) {
                if ($this->flow->stepNotApplicable($step, $progressUserId)) {
                    continue;
                }
                if ($status[$step->id] ?? false) {
                    $done++;
                } elseif ($pending === null) {
                    $pending = trim((string) $step->step_name) ?: 'registration';
                }
            }

            if ($pending === null && ! $this->flow->isTravelComplete($progressUserId, $form)) {
                $pending = 'Travel Plan';
            }

            return ['done' => $done, 'pending_step' => $pending];
        }

        $progress = app(RegistrationService::class)->getProgress($progressUserId);
        $steps = $progress['steps'] ?? [];
        $labels = [
            'step1' => 'Basic Information',
            'step2' => 'Personal Details',
            'step3' => 'Other Details',
            'bank' => 'Bank Details',
            'travel' => 'Travel Plan',
            'documents' => 'Document Upload',
            'confirmed' => 'Declaration & Submit',
        ];

        $done = 0;
        $pending = null;
        foreach ($labels as $key => $label) {
            if (! empty($steps[$key])) {
                $done++;
            } elseif ($pending === null) {
                $pending = $label;
            }
        }

        return ['done' => $done, 'pending_step' => $pending];
    }

    protected function resolveProgressUserId(object $roster): ?int
    {
        $login = trim((string) ($roster->user_id ?? ''));
        if ($login === '') {
            return null;
        }

        $credPk = DB::table('user_credentials')->where('user_name', $login)->value('pk');
        if ($credPk) {
            return (int) $credPk;
        }

        return FcRosterAuthService::stagedUserId((int) $roster->pk);
    }

    protected function programmeName(): string
    {
        $form = FcForm::activeRegistrationDynamicForm();
        $name = trim((string) ($form?->form_name ?? ''));

        return $name !== ''
            ? $name
            : (string) config('gupshup.default_programme_name', 'Foundation Course');
    }

    protected function registrationDeadlineText(): string
    {
        try {
            $path = PathPage::query()->first();
            $end = $path->registration_end_date ?? null;
            if ($end) {
                return Carbon::parse($end)->format('d-M-Y');
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return now()->addDays(7)->format('d-M-Y');
    }
}
