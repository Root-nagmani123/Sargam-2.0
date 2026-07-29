<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use App\Models\FC\FcFormStep;
use App\Models\PathPage;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Admin bulk SMS + Email for B1 / B2.
 *
 * B1 — form started (1+ trackable step done) but still has pending steps.
 * B2 — registration not completed and form not started (no tracker / 0 steps done).
 *
 * DB practices: select required columns, chunkById for scans/sends,
 * batch credential + tracker lookups (no per-row queries in loops),
 * paginated recipient lists. Requirements unchanged (no send limit).
 */
class FcAdminSmsBulkService
{
    public const TEMPLATE_B1 = 'b1';

    public const TEMPLATE_B2 = 'b2';

    public const CHUNK_SIZE = 100;

    public const LIST_PER_PAGE = 20;

    public function __construct(
        private FcNotifyService $notify,
        private FcStepApplicabilityService $applicability,
    ) {
    }

    /**
     * @param  list<int>|null  $registrationPks  When set, send only to these roster pk values.
     * @return array{ok: bool, message: string, sent: int, skipped: int, failed: int}
     */
    public function send(string $template, ?array $registrationPks = null, ?int $formId = null): array
    {
        $template = strtolower(trim($template));

        return match ($template) {
            self::TEMPLATE_B1 => $this->sendByTemplate(self::TEMPLATE_B1, $registrationPks, $formId),
            self::TEMPLATE_B2 => $this->sendByTemplate(self::TEMPLATE_B2, $registrationPks, $formId),
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
     * Counts only (chunked — does not keep full recipient lists in memory).
     *
     * @return array{b1: int, b2: int, programme: string, last_date: string}
     */
    public function previewCounts(?int $formId = null): array
    {
        $payload = $this->previewForIndex(1, 1, self::LIST_PER_PAGE, $formId);

        return [
            'b1' => $payload['b1'],
            'b2' => $payload['b2'],
            'programme' => $payload['programme'],
            'last_date' => $payload['last_date'],
        ];
    }

    /**
     * Full recipient list for one template (chunked scan — used by DataTables AJAX).
     *
     * @return list<array{pk: int, name: string, mobile: string, user_id: string, step_name: ?string}>
     */
    public function allRecipientsForTemplate(string $template, ?int $formId = null): array
    {
        $template = strtolower(trim($template));
        if ($template !== self::TEMPLATE_B1 && $template !== self::TEMPLATE_B2) {
            return [];
        }

        $out = [];
        $this->eachClassifiedRecipient(function (array $item) use (&$out, $template) {
            if (($item['bucket'] ?? null) !== $template) {
                return;
            }

            $out[] = [
                'pk' => (int) $item['pk'],
                'name' => trim((string) ($item['name'] ?? '')),
                'mobile' => trim((string) ($item['mobile'] ?? '')),
                'user_id' => trim((string) ($item['user_id'] ?? '')),
                'step_name' => $template === self::TEMPLATE_B1
                    ? trim((string) ($item['step_name'] ?? ''))
                    : null,
            ];
        }, $this->resolveFormForScope($formId));

        return $out;
    }

    /**
     * One chunked pass: counts + one page of each list (no full dataset in memory).
     *
     * @return array{
     *   b1: int,
     *   b2: int,
     *   programme: string,
     *   last_date: string,
     *   lists: array{b1: LengthAwarePaginator, b2: LengthAwarePaginator}
     * }
     */
    public function previewForIndex(int $b1Page = 1, int $b2Page = 1, int $perPage = self::LIST_PER_PAGE, ?int $formId = null): array
    {
        $b1Page = max(1, $b1Page);
        $b2Page = max(1, $b2Page);
        $perPage = max(1, min(100, $perPage));

        $counts = [self::TEMPLATE_B1 => 0, self::TEMPLATE_B2 => 0];
        $pageItems = [self::TEMPLATE_B1 => [], self::TEMPLATE_B2 => []];
        $offsets = [
            self::TEMPLATE_B1 => ($b1Page - 1) * $perPage,
            self::TEMPLATE_B2 => ($b2Page - 1) * $perPage,
        ];

        $form = $this->resolveFormForScope($formId);

        $this->eachClassifiedRecipient(function (array $item) use (&$counts, &$pageItems, $offsets, $perPage) {
            $bucket = $item['bucket'] ?? null;
            if ($bucket !== self::TEMPLATE_B1 && $bucket !== self::TEMPLATE_B2) {
                return;
            }

            $idx = $counts[$bucket];
            if ($idx >= $offsets[$bucket] && count($pageItems[$bucket]) < $perPage) {
                $pageItems[$bucket][] = [
                    'pk' => $item['pk'],
                    'name' => $item['name'],
                    'mobile' => $item['mobile'],
                    'user_id' => $item['user_id'] ?? '',
                    'step_name' => $bucket === self::TEMPLATE_B1 ? ($item['step_name'] ?? null) : null,
                ];
            }
            $counts[$bucket]++;
        }, $form);

        return [
            'b1' => $counts[self::TEMPLATE_B1],
            'b2' => $counts[self::TEMPLATE_B2],
            'programme' => $this->programmeName($form),
            'last_date' => $this->registrationDeadlineText(),
            'lists' => [
                self::TEMPLATE_B1 => new Paginator(
                    $pageItems[self::TEMPLATE_B1],
                    $counts[self::TEMPLATE_B1],
                    $perPage,
                    $b1Page,
                    ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'b1_page']
                ),
                self::TEMPLATE_B2 => new Paginator(
                    $pageItems[self::TEMPLATE_B2],
                    $counts[self::TEMPLATE_B2],
                    $perPage,
                    $b2Page,
                    ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'b2_page']
                ),
            ],
        ];
    }

    /**
     * Paginated recipient list for one template (chunked scan; only one page kept).
     */
    public function paginateRecipients(string $template, int $page = 1, int $perPage = self::LIST_PER_PAGE, ?int $formId = null): LengthAwarePaginator
    {
        $template = strtolower(trim($template));
        $payload = $this->previewForIndex(
            $template === self::TEMPLATE_B1 ? $page : 1,
            $template === self::TEMPLATE_B2 ? $page : 1,
            $perPage,
            $formId
        );

        return $payload['lists'][$template] ?? new Paginator([], 0, $perPage, $page);
    }

    /**
     * @param  list<int>|null  $registrationPks
     * @return array{ok: bool, message: string, sent: int, skipped: int, failed: int}
     */
    protected function sendByTemplate(string $template, ?array $registrationPks = null, ?int $formId = null): array
    {
        @set_time_limit(0);

        $registrationPks = $registrationPks !== null
            ? array_values(array_unique(array_filter(array_map('intval', $registrationPks))))
            : null;

        if ($registrationPks !== null && $registrationPks === []) {
            return [
                'ok' => false,
                'message' => 'Select at least one trainee to send.',
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
            ];
        }

        // Fail fast on bad SMTP so large B2 cohorts are not blocked ~2s each.
        config(['mail.mailers.smtp.timeout' => 5]);
        $this->notify->resetEmailCircuit();

        Log::info('FC admin bulk send started.', [
            'template' => $template,
            'sms_driver' => config('gupshup.driver'),
            'mail_host' => config('mail.mailers.smtp.host'),
            'selected_only' => $registrationPks !== null,
            'selected_count' => $registrationPks !== null ? count($registrationPks) : null,
        ]);

        $form = $this->resolveFormForScope($formId);
        if (! $form) {
            return [
                'ok' => false,
                'message' => 'Selected template is not available.',
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
            ];
        }

        $programme = $this->programmeName($form);
        $lastDate = $this->registrationDeadlineText();

        if ($registrationPks !== null) {
            return $this->sendByTemplateToSelectedPks($template, $registrationPks, $form, $programme, $lastDate);
        }

        $sent = 0;
        $failed = 0;
        $matched = 0;
        $emailSkipped = false;

        $this->eachClassifiedRecipient(function (array $row) use (
            $template,
            $programme,
            $lastDate,
            $registrationPks,
            &$sent,
            &$failed,
            &$matched,
            &$emailSkipped
        ) {
            if (($row['bucket'] ?? null) !== $template) {
                return;
            }

            if ($registrationPks !== null && ! in_array((int) ($row['pk'] ?? 0), $registrationPks, true)) {
                return;
            }

            $matched++;

            try {
                if ($template === self::TEMPLATE_B1) {
                    $this->notify->formStepIncomplete(
                        $row['mobile'],
                        $row['name'],
                        $row['step_name'] ?? 'registration',
                        $row['pk'],
                        $row['email'] ?? null,
                    );
                } else {
                    $this->notify->registrationPending(
                        $row['mobile'],
                        $row['name'],
                        $programme,
                        $lastDate,
                        $row['pk'],
                        $row['email'] ?? null,
                        $row['pending_steps'] ?? null,
                    );
                }
                $sent++;

                if ($matched === 1 || $matched % 50 === 0) {
                    Log::info('FC admin bulk send progress.', [
                        'template' => $template,
                        'matched' => $matched,
                        'sent' => $sent,
                        'email_circuit_open' => $this->notify->isEmailCircuitOpen(),
                    ]);
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error('FC admin bulk send recipient failed: '.$e->getMessage(), [
                    'template' => $template,
                    'pk' => $row['pk'] ?? null,
                ]);
            }

            if ($this->notify->isEmailCircuitOpen()) {
                $emailSkipped = true;
            }
        }, $form);

        if ($matched === 0) {
            $emptyMsg = $registrationPks !== null
                ? 'None of the selected trainees are eligible for this template.'
                : ($template === self::TEMPLATE_B1
                    ? 'No trainees found who started the form and still have a pending step.'
                    : 'No trainees found with registration pending (form not started / not registered).');

            return [
                'ok' => $registrationPks === null,
                'message' => $emptyMsg,
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
            ];
        }

        $label = $template === self::TEMPLATE_B1 ? 'Form step incomplete' : 'Registration pending';
        $message = $registrationPks !== null
            ? "{$label} sent to {$sent} selected trainee(s)."
            : "{$label} processed for {$sent} trainee(s).";

        if (strtolower((string) config('gupshup.driver')) === 'log') {
            $message .= ' SMS_DRIVER=log (SMS written to laravel.log only — not sent to phones).';
        }
        if ($emailSkipped) {
            $message .= ' Email stopped early after SMTP failures (check MAIL_* / App Password).';
        }

        Log::info('FC admin bulk send finished.', [
            'template' => $template,
            'matched' => $matched,
            'sent' => $sent,
            'failed' => $failed,
            'email_circuit_open' => $emailSkipped,
        ]);

        return [
            'ok' => true,
            'message' => $message,
            'sent' => $sent,
            'skipped' => 0,
            'failed' => $failed,
        ];
    }

    /**
     * Fast path: send only to explicitly selected roster PKs (no full-cohort scan).
     *
     * @param  list<int>  $registrationPks
     * @return array{ok: bool, message: string, sent: int, skipped: int, failed: int}
     */
    protected function sendByTemplateToSelectedPks(
        string $template,
        array $registrationPks,
        FcForm $form,
        string $programme,
        string $lastDate,
    ): array {
        $steps = $this->trackableSteps($form);
        $pendingStepsLabel = $steps
            ->map(fn ($s) => trim((string) ($s->step_name ?? '')))
            ->filter()
            ->implode(', ') ?: 'pending steps';

        $rosters = $this->eligibleRosterQuery($form)
            ->whereIn('pk', $registrationPks)
            ->get();

        if ($rosters->isEmpty()) {
            return [
                'ok' => false,
                'message' => 'None of the selected trainees are eligible for this template.',
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
            ];
        }

        $trackerTable = $form->trackerStorageTable();
        $userCol = fc_user_col($trackerTable);
        $trackerQuery = DB::table($trackerTable);
        if (Schema::hasColumn($trackerTable, 'form_id')) {
            $trackerQuery->where('form_id', $form->id);
        }
        $trackers = $trackerQuery
            ->whereIn($userCol, $registrationPks)
            ->get()
            ->keyBy(fn ($row) => (string) ($row->{$userCol} ?? ''));

        $sent = 0;
        $failed = 0;
        $matched = 0;
        $emailSkipped = false;

        foreach ($rosters as $roster) {
            $classified = $this->classifySelectedRosterRow($roster, $form, $steps, $trackers, $pendingStepsLabel);
            if ($classified === null || ($classified['bucket'] ?? null) !== $template) {
                continue;
            }

            $matched++;
            $result = $this->dispatchTemplateNotification($template, $classified, $programme, $lastDate);
            if ($result['sent']) {
                $sent++;
            }
            if ($result['failed']) {
                $failed++;
            }
            if ($result['email_skipped']) {
                $emailSkipped = true;
            }
        }

        if ($matched === 0) {
            return [
                'ok' => false,
                'message' => 'None of the selected trainees are eligible for this template.',
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
            ];
        }

        $label = $template === self::TEMPLATE_B1 ? 'Form step incomplete' : 'Registration pending';
        $message = "{$label} sent to {$sent} selected trainee(s).";

        if (strtolower((string) config('gupshup.driver')) === 'log') {
            $message .= ' SMS_DRIVER=log (SMS written to laravel.log only — not sent to phones).';
        }
        if ($emailSkipped) {
            $message .= ' Email stopped early after SMTP failures (check MAIL_* / App Password).';
        }

        return [
            'ok' => $sent > 0,
            'message' => $message,
            'sent' => $sent,
            'skipped' => 0,
            'failed' => $failed,
        ];
    }

    /**
     * @param  Collection<string, object>  $trackers
     * @return array<string, mixed>|null
     */
    protected function classifySelectedRosterRow(
        object $roster,
        FcForm $form,
        Collection $steps,
        Collection $trackers,
        string $pendingStepsLabel,
    ): ?array {
        $pk = (int) ($roster->pk ?? 0);
        if ($pk <= 0) {
            return null;
        }

        $mobile = trim((string) ($roster->contact_no ?? ''));
        $email = trim((string) ($roster->email ?? ''));
        $login = trim((string) ($roster->user_id ?? ''));

        $tracker = $trackers->get((string) $pk);
        if (! $tracker && $login !== '') {
            $tracker = $trackers->get($login);
        }

        $bucket = self::TEMPLATE_B2;
        $stepName = null;

        if ($tracker) {
            $userId = $this->resolveApplicabilityUserId($roster, $tracker, $form);
            $progress = $this->stepProgressFromTracker($form, $steps, $tracker, $userId);
            if ($progress['pending_step'] === null) {
                return null;
            }
            if ($progress['done'] >= 1) {
                $bucket = self::TEMPLATE_B1;
                $stepName = $progress['pending_step'];
            }
        }

        return [
            'bucket' => $bucket,
            'pk' => $pk,
            'mobile' => $mobile,
            'name' => trim((string) ($roster->display_name ?? '')),
            'user_id' => $login,
            'email' => $email !== '' ? $email : null,
            'step_name' => $stepName,
            'pending_steps' => $pendingStepsLabel,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{sent: bool, failed: bool, email_skipped: bool}
     */
    protected function dispatchTemplateNotification(
        string $template,
        array $row,
        string $programme,
        string $lastDate,
    ): array {
        try {
            if ($template === self::TEMPLATE_B1) {
                $this->notify->formStepIncomplete(
                    $row['mobile'],
                    $row['name'],
                    $row['step_name'] ?? 'registration',
                    $row['pk'],
                    $row['email'] ?? null,
                );
            } else {
                $this->notify->registrationPending(
                    $row['mobile'],
                    $row['name'],
                    $programme,
                    $lastDate,
                    $row['pk'],
                    $row['email'] ?? null,
                    $row['pending_steps'] ?? null,
                );
            }

            return [
                'sent' => true,
                'failed' => false,
                'email_skipped' => $this->notify->isEmailCircuitOpen(),
            ];
        } catch (\Throwable $e) {
            Log::error('FC admin bulk send recipient failed: '.$e->getMessage(), [
                'template' => $template,
                'pk' => $row['pk'] ?? null,
            ]);

            return [
                'sent' => false,
                'failed' => true,
                'email_skipped' => $this->notify->isEmailCircuitOpen(),
            ];
        }
    }

    /**
     * Stream classified B1/B2 recipients without keeping the full list in memory.
     *
     * @param  callable(array<string, mixed>): void  $callback
     */
    protected function eachClassifiedRecipient(callable $callback, ?FcForm $form = null): void
    {
        $form = $form ?? FcForm::activeRegistrationDynamicForm();
        $steps = $this->trackableSteps($form);

        /** @var array<int, true> $skipB2 Roster pks already in B1 bucket. */
        $skipB2 = [];
        /** @var array<int, true> $emitted Already yielded (avoid double B2). */
        $emitted = [];

        $chunkCol = $this->trackerChunkColumn($form);
        $this->eligibleTrackerQuery($form)->orderBy($chunkCol)->chunkById(self::CHUNK_SIZE, function ($rows) use (
            $callback,
            $form,
            $steps,
            &$skipB2,
            &$emitted
        ) {
            foreach ($this->classifyTrackerChunk($rows, $form, $steps, $skipB2) as $item) {
                $pk = (int) ($item['pk'] ?? 0);
                if ($pk <= 0) {
                    continue;
                }

                $bucket = $item['bucket'] ?? null;
                if ($bucket === self::TEMPLATE_B1) {
                    $skipB2[$pk] = true;
                }

                $emitted[$pk] = true;
                $callback($item);
            }
        }, $chunkCol);

        $pendingStepsLabel = $steps
            ->map(fn ($s) => trim((string) ($s->step_name ?? '')))
            ->filter()
            ->implode(', ');
        if ($pendingStepsLabel === '') {
            $pendingStepsLabel = 'pending steps';
        }

        $this->eligibleRosterQuery($form)->orderBy('pk')->chunkById(self::CHUNK_SIZE, function ($rows) use (
            $callback,
            &$skipB2,
            &$emitted,
            $pendingStepsLabel
        ) {
            foreach ($rows as $roster) {
                $pk = (int) ($roster->pk ?? 0);
                if ($pk <= 0 || isset($skipB2[$pk]) || isset($emitted[$pk])) {
                    continue;
                }

                $mobile = trim((string) ($roster->contact_no ?? ''));

                $email = trim((string) ($roster->email ?? ''));
                $emitted[$pk] = true;
                $callback([
                    'bucket' => self::TEMPLATE_B2,
                    'pk' => $pk,
                    'mobile' => $mobile,
                    'name' => trim((string) ($roster->display_name ?? '')),
                    'user_id' => trim((string) ($roster->user_id ?? '')),
                    'email' => $email !== '' ? $email : null,
                    'step_name' => null,
                    'pending_steps' => $pendingStepsLabel,
                ]);
            }
        }, 'pk');
    }

    /**
     * Incomplete (not registered) roster for the active form's course — same scope as
     * Registration Master when that Programme/Course + Active is selected.
     * Excludes exemption applications.
     */
    protected function eligibleRosterQuery(?FcForm $form = null)
    {
        if (! Schema::hasTable('fc_registration_master')) {
            return DB::table('fc_registration_master')->whereRaw('1 = 0');
        }

        $coursePk = (int) ($form?->course_master_pk ?? 0);
        // Never fall back to "all FC courses" — require the form's course (e.g. FC-101 2026).
        if ($coursePk <= 0 || ! Schema::hasColumn('fc_registration_master', 'course_master_pk')) {
            return DB::table('fc_registration_master')->whereRaw('1 = 0');
        }

        $query = DB::table('fc_registration_master')
            ->select(['pk', 'display_name', 'contact_no', 'user_id', 'email', 'is_registered', 'application_type'])
            ->where('course_master_pk', $coursePk);

        // Same as Registration Master "Active" tab.
        if (Schema::hasColumn('fc_registration_master', 'active_inactive')) {
            $query->where('active_inactive', 1);
        }

        $this->applyExemptionExclusion($query);

        return $query;
    }

    /**
     * Steps with tracker columns — same set as FcFormOverviewDataTable / report incomplete filter.
     */
    protected function trackableSteps(?FcForm $form): Collection
    {
        if (! $form) {
            return collect();
        }

        return $form->activeSteps()
            ->whereNotNull('tracker_column')
            ->orderBy('step_number')
            ->get(FcStepApplicabilityService::stepColumns())
            ->filter(fn ($s) => preg_match('/^[a-zA-Z0-9_]+$/', (string) $s->tracker_column))
            ->values();
    }

    protected function trackerChunkColumn(?FcForm $form): string
    {
        $table = $form?->trackerStorageTable() ?? 'student_masters';

        if (Schema::hasColumn($table, 'id')) {
            return 'id';
        }

        if (Schema::hasColumn($table, 'pk')) {
            return 'pk';
        }

        return 'id';
    }

    /**
     * Tracker rows for the active FC form (same scope as the form overview report).
     */
    protected function eligibleTrackerQuery(?FcForm $form)
    {
        if (! $form) {
            return DB::table('student_masters')->whereRaw('1 = 0');
        }

        $table = $form->trackerStorageTable();
        if (! fc_schema_has_table($table)) {
            return DB::table($table)->whereRaw('1 = 0');
        }

        $query = DB::table($table);

        if (fc_schema_has_column($table, 'form_id')) {
            $query->where('form_id', $form->id);
        }

        return $query;
    }

    /**
     * Classify tracker rows for the active form (batch roster lookup — no N+1).
     *
     * @param  Collection<int, object>  $trackerRows
     * @param  Collection<int, FcFormStep>  $steps
     * @param  array<int, true>  $skipB2  Roster pks with a fully completed form (filled by this method).
     * @return list<array<string, mixed>>
     */
    protected function classifyTrackerChunk(
        Collection $trackerRows,
        ?FcForm $form,
        Collection $steps,
        array &$skipB2 = [],
    ): array {
        if ($trackerRows->isEmpty() || ! $form || $steps->isEmpty()) {
            return [];
        }

        $userCol = fc_user_col($form->trackerStorageTable());
        $trackerKeys = $trackerRows
            ->map(fn ($r) => $r->{$userCol} ?? null)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->unique()
            ->values();

        $rosterByPk = collect();
        if (Schema::hasTable('fc_registration_master') && $trackerKeys->isNotEmpty()) {
            $rosterByPkQuery = DB::table('fc_registration_master')
                ->whereIn('pk', $trackerKeys->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->all())
                ->when(
                    Schema::hasColumn('fc_registration_master', 'course_master_pk') && (int) ($form?->course_master_pk ?? 0) > 0,
                    fn ($q) => $q->where('course_master_pk', (int) $form->course_master_pk)
                );
            if (Schema::hasColumn('fc_registration_master', 'active_inactive')) {
                $rosterByPkQuery->where('active_inactive', 1);
            }
            $this->applyExemptionExclusion($rosterByPkQuery);
            $rosterByPk = $rosterByPkQuery
                ->get()
                ->keyBy('pk');
        }

        $rosterByLogin = collect();
        if (Schema::hasTable('fc_registration_master') && Schema::hasTable('user_credentials')) {
            $credLogins = DB::table('user_credentials')
                ->whereIn('pk', $trackerKeys->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->all())
                ->pluck('user_name')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($credLogins !== []) {
                $rosterByLoginQuery = DB::table('fc_registration_master')
                    ->whereIn('user_id', $credLogins)
                    ->when(
                        Schema::hasColumn('fc_registration_master', 'course_master_pk') && (int) ($form?->course_master_pk ?? 0) > 0,
                        fn ($q) => $q->where('course_master_pk', (int) $form->course_master_pk)
                    );
                if (Schema::hasColumn('fc_registration_master', 'active_inactive')) {
                    $rosterByLoginQuery->where('active_inactive', 1);
                }
                $this->applyExemptionExclusion($rosterByLoginQuery);
                $rosterByLogin = $rosterByLoginQuery
                    ->get()
                    ->keyBy(fn ($r) => trim((string) $r->user_id));
            }
        }

        $out = [];
        foreach ($trackerRows as $trackerRow) {
            $classified = $this->classifyTrackerRow(
                $trackerRow,
                $form,
                $steps,
                $userCol,
                $rosterByPk,
                $rosterByLogin,
                $skipB2,
            );
            if ($classified !== null) {
                $out[] = $classified;
            }
        }

        return $out;
    }

    /**
     * @param  Collection<int|string, object>  $rosterByPk
     * @param  Collection<string, object>  $rosterByLogin
     * @param  array<int, true>  $skipB2
     * @return array<string, mixed>|null
     */
    protected function classifyTrackerRow(
        object $trackerRow,
        FcForm $form,
        Collection $steps,
        string $userCol,
        Collection $rosterByPk,
        Collection $rosterByLogin,
        array &$skipB2 = [],
    ): ?array {
        $trackerUserKey = $trackerRow->{$userCol} ?? null;
        if ($trackerUserKey === null || $trackerUserKey === '') {
            return null;
        }

        $roster = $rosterByPk->get((int) $trackerUserKey);
        if (! $roster && is_numeric($trackerUserKey)) {
            $login = fc_user_name_for_id((int) $trackerUserKey);
            if ($login) {
                $roster = $rosterByLogin->get(trim($login));
            }
        }

        if (! $roster) {
            return null;
        }

        // Same course + Active as Registration Master for this programme.
        $expectedCoursePk = (int) ($form->course_master_pk ?? 0);
        if ($expectedCoursePk > 0 && Schema::hasColumn('fc_registration_master', 'course_master_pk')) {
            if ((int) ($roster->course_master_pk ?? 0) !== $expectedCoursePk) {
                return null;
            }
        }
        if (Schema::hasColumn('fc_registration_master', 'active_inactive')
            && (int) ($roster->active_inactive ?? 0) !== 1) {
            return null;
        }

        $mobile = trim((string) ($roster->contact_no ?? ''));
        $login = trim((string) ($roster->user_id ?? ''));
        if ($mobile === '') {
            return null;
        }

        $userId = $this->resolveApplicabilityUserId($roster, $trackerRow, $form);
        $progress = $this->stepProgressFromTracker($form, $steps, $trackerRow, $userId);
        $rosterPk = (int) $roster->pk;

        // Form fully submitted — not B1. It will still appear in the fallback
        // roster pass (B2 bucket) so the visible pool stays aligned with
        // selected template course + active roster query.
        if ($progress['pending_step'] === null) {
            return null;
        }

        $email = trim((string) ($roster->email ?? ''));
        $base = [
            'pk' => $rosterPk,
            'mobile' => $mobile,
            'name' => trim((string) ($roster->display_name ?? '')),
            'user_id' => $login,
            'email' => $email !== '' ? $email : null,
            'step_name' => $progress['pending_step'],
            'pending_steps' => $progress['pending_steps'] ?? $progress['pending_step'],
        ];

        // B1: started form (1+ step done), still incomplete.
        if ($progress['done'] >= 1) {
            $base['bucket'] = self::TEMPLATE_B1;

            return $base;
        }

        // B2: tracker exists but no step completed yet (and still not registered).
        if ($progress['done'] === 0) {
            if (Schema::hasColumn('fc_registration_master', 'is_registered')) {
                $registered = $roster->is_registered ?? null;
                if ((int) $registered === 1) {
                    return null;
                }
            }

            $base['bucket'] = self::TEMPLATE_B2;
            $base['step_name'] = null;

            return $base;
        }

        return null;
    }

    /**
     * Resolve the auth id used by FcStepApplicabilityService (credentials pk, or -roster pk).
     */
    protected function resolveApplicabilityUserId(object $roster, object $trackerRow, FcForm $form): ?int
    {
        $login = trim((string) ($roster->user_id ?? ''));
        if ($login !== '' && Schema::hasTable('user_credentials')) {
            $credPk = DB::table('user_credentials')->where('user_name', $login)->value('pk');
            if ($credPk) {
                return (int) $credPk;
            }
        }

        $rosterPk = (int) ($roster->pk ?? 0);
        if ($rosterPk > 0) {
            return -$rosterPk;
        }

        $userCol = fc_user_col($form->trackerStorageTable());
        $key = $trackerRow->{$userCol} ?? null;
        if (is_numeric($key) && (int) $key > 0) {
            return (int) $key;
        }

        return null;
    }

    /**
     * @return array{done: int, pending_step: ?string, pending_steps: ?string}
     */
    protected function stepProgressFromTracker(
        ?FcForm $form,
        Collection $steps,
        ?object $trackerRow,
        ?int $userId = null,
    ): array {
        if (! $form || $steps->isEmpty()) {
            return ['done' => 0, 'pending_step' => 'Basic Information', 'pending_steps' => 'Basic Information'];
        }

        $done = 0;
        $pending = [];

        foreach ($steps as $step) {
            $col = $step->tracker_column ?? null;
            $complete = false;
            if ($col && $trackerRow !== null && isset($trackerRow->{$col})) {
                $complete = (bool) $trackerRow->{$col};
            }

            // Same waiver rule as the form overview report: a step that does not apply
            // to this trainee (e.g. Special Assistant without ph_value) is ignored when
            // incomplete, so they are not listed as "Form step incomplete".
            if (! $complete && $userId !== null && $this->applicability->notApplicable($step, $userId)) {
                continue;
            }

            if ($complete) {
                $done++;
            } else {
                $pending[] = trim((string) $step->step_name) ?: 'registration';
            }
        }

        return [
            'done' => $done,
            'pending_step' => $pending[0] ?? null,
            'pending_steps' => $pending !== [] ? implode(', ', $pending) : null,
        ];
    }

    protected function programmeName(?FcForm $form = null): string
    {
        $form = $form ?? FcForm::activeRegistrationDynamicForm();

        // Prefer dynamic form name (same as Form Management cards, e.g. Foundation Course -101st).
        $formName = trim((string) ($form?->form_name ?? ''));
        if ($formName !== '') {
            return $formName;
        }

        $coursePk = (int) ($form?->course_master_pk ?? 0);
        if ($coursePk > 0 && Schema::hasTable('course_master')) {
            $courseName = trim((string) (DB::table('course_master')->where('pk', $coursePk)->value('course_name') ?? ''));
            if ($courseName !== '') {
                return $courseName;
            }
        }

        return (string) config('gupshup.default_programme_name', 'Foundation Course');
    }

    protected function registrationDeadlineText(): string
    {
        try {
            $path = PathPage::query()->select(['registration_end_date'])->first();
            $end = $path->registration_end_date ?? null;
            if ($end) {
                return Carbon::parse($end)->format('d-M-Y');
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return now()->addDays(7)->format('d-M-Y');
    }

    protected function resolveFormForScope(?int $formId = null): ?FcForm
    {
        if ($formId && $formId > 0) {
            return FcForm::query()->where('is_active', true)->find($formId);
        }

        return FcForm::activeRegistrationDynamicForm();
    }

    protected function applyExemptionExclusion($query): void
    {
        $hasApplicationType = Schema::hasColumn('fc_registration_master', 'application_type');
        $hasExemptionPk = Schema::hasColumn('fc_registration_master', 'fc_exemption_master_pk');

        if (! $hasApplicationType && ! $hasExemptionPk) {
            return;
        }

        $query->where(function ($sub) use ($hasApplicationType, $hasExemptionPk) {
            if ($hasApplicationType) {
                $sub->where(function ($q) {
                    $q->whereNull('application_type')
                        ->orWhere('application_type', '!=', 2);
                });
            }

            if ($hasExemptionPk) {
                $sub->where(function ($q) {
                    $q->whereNull('fc_exemption_master_pk')
                        ->orWhere('fc_exemption_master_pk', 0);
                });
            }
        });
    }
}
