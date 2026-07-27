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
    ) {
    }

    /**
     * @param  list<int>|null  $registrationPks  When set, send only to these roster pk values.
     * @return array{ok: bool, message: string, sent: int, skipped: int, failed: int}
     */
    public function send(string $template, ?array $registrationPks = null): array
    {
        $template = strtolower(trim($template));

        return match ($template) {
            self::TEMPLATE_B1 => $this->sendByTemplate(self::TEMPLATE_B1, $registrationPks),
            self::TEMPLATE_B2 => $this->sendByTemplate(self::TEMPLATE_B2, $registrationPks),
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
    public function previewCounts(): array
    {
        $payload = $this->previewForIndex(1, 1);

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
    public function allRecipientsForTemplate(string $template): array
    {
        $template = strtolower(trim($template));
        if ($template !== self::TEMPLATE_B1 && $template !== self::TEMPLATE_B2) {
            return [];
        }

        $form = FcForm::activeRegistrationDynamicForm();
        $steps = $this->trackableSteps($form);
        $out = [];
        $chunkCol = $this->trackerChunkColumn($form);

        $this->eligibleTrackerQuery($form)->orderBy($chunkCol)->chunkById(self::CHUNK_SIZE, function ($rows) use (
            &$out,
            $template,
            $form,
            $steps
        ) {
            foreach ($this->classifyTrackerChunk($rows, $form, $steps) as $item) {
                if (($item['bucket'] ?? null) !== $template) {
                    continue;
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
            }
        }, $chunkCol);

        return $out;
    }

    /**
     * One chunked roster pass: counts + one page of each list (no full dataset in memory).
     *
     * @return array{
     *   b1: int,
     *   b2: int,
     *   programme: string,
     *   last_date: string,
     *   lists: array{b1: LengthAwarePaginator, b2: LengthAwarePaginator}
     * }
     */
    public function previewForIndex(int $b1Page = 1, int $b2Page = 1, int $perPage = self::LIST_PER_PAGE): array
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

        $form = FcForm::activeRegistrationDynamicForm();
        $steps = $this->trackableSteps($form);
        $chunkCol = $this->trackerChunkColumn($form);

        $this->eligibleTrackerQuery($form)->orderBy($chunkCol)->chunkById(self::CHUNK_SIZE, function ($rows) use (
            &$counts,
            &$pageItems,
            $offsets,
            $perPage,
            $form,
            $steps
        ) {
            foreach ($this->classifyTrackerChunk($rows, $form, $steps) as $item) {
                $bucket = $item['bucket'] ?? null;
                if ($bucket !== self::TEMPLATE_B1 && $bucket !== self::TEMPLATE_B2) {
                    continue;
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
            }
        }, $chunkCol);

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
    public function paginateRecipients(string $template, int $page = 1, int $perPage = self::LIST_PER_PAGE): LengthAwarePaginator
    {
        $template = strtolower(trim($template));
        $payload = $this->previewForIndex(
            $template === self::TEMPLATE_B1 ? $page : 1,
            $template === self::TEMPLATE_B2 ? $page : 1,
            $perPage
        );

        return $payload['lists'][$template] ?? new Paginator([], 0, $perPage, $page);
    }

    /**
     * @param  list<int>|null  $registrationPks
     * @return array{ok: bool, message: string, sent: int, skipped: int, failed: int}
     */
    protected function sendByTemplate(string $template, ?array $registrationPks = null): array
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

        // Fail fast on bad SMTP so 484 recipients are not blocked ~2s each.
        config(['mail.mailers.smtp.timeout' => 5]);
        $this->notify->resetEmailCircuit();

        Log::info('FC admin bulk send started.', [
            'template' => $template,
            'sms_driver' => config('gupshup.driver'),
            'mail_host' => config('mail.mailers.smtp.host'),
            'selected_only' => $registrationPks !== null,
            'selected_count' => $registrationPks !== null ? count($registrationPks) : null,
        ]);

        $form = FcForm::activeRegistrationDynamicForm();
        $steps = $this->trackableSteps($form);
        $programme = $this->programmeName($form);
        $lastDate = $this->registrationDeadlineText();
        $chunkCol = $this->trackerChunkColumn($form);

        $sent = 0;
        $failed = 0;
        $matched = 0;
        $emailSkipped = false;

        $this->eligibleTrackerQuery($form)->orderBy($chunkCol)->chunkById(self::CHUNK_SIZE, function ($rows) use (
            $template,
            $form,
            $steps,
            $programme,
            $lastDate,
            $registrationPks,
            &$sent,
            &$failed,
            &$matched,
            &$emailSkipped
        ) {
            foreach ($this->classifyTrackerChunk($rows, $form, $steps) as $row) {
                if (($row['bucket'] ?? null) !== $template) {
                    continue;
                }

                if ($registrationPks !== null && ! in_array((int) ($row['pk'] ?? 0), $registrationPks, true)) {
                    continue;
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
            }
        }, $chunkCol);

        if ($matched === 0) {
            $emptyMsg = $registrationPks !== null
                ? 'None of the selected trainees are eligible for this template.'
                : ($template === self::TEMPLATE_B1
                    ? 'No trainees found who started the form and still have a pending step.'
                    : 'No trainees found with registration pending (no form step started yet).');

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
            ->get()
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
     * @return list<array<string, mixed>>
     */
    protected function classifyTrackerChunk(Collection $trackerRows, ?FcForm $form, Collection $steps): array
    {
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
            $rosterByPk = DB::table('fc_registration_master')
                ->whereIn('pk', $trackerKeys->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0)->all())
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
                $rosterByLogin = DB::table('fc_registration_master')
                    ->whereIn('user_id', $credLogins)
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
     * @return array<string, mixed>|null
     */
    protected function classifyTrackerRow(
        object $trackerRow,
        FcForm $form,
        Collection $steps,
        string $userCol,
        Collection $rosterByPk,
        Collection $rosterByLogin,
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

        if (Schema::hasColumn('fc_registration_master', 'application_type')) {
            $appType = $roster->application_type ?? null;
            if ($appType === FcRosterApplicationGuardService::APPLICATION_EXEMPTION) {
                return null;
            }
        }

        $mobile = trim((string) ($roster->contact_no ?? ''));
        $login = trim((string) ($roster->user_id ?? ''));
        if ($mobile === '' || $login === '') {
            return null;
        }

        $progress = $this->stepProgressFromTracker($form, $steps, $trackerRow);

        if ($progress['pending_step'] === null) {
            return null;
        }

        $email = trim((string) ($roster->email ?? ''));
        $base = [
            'pk' => (int) $roster->pk,
            'mobile' => $mobile,
            'name' => trim((string) ($roster->display_name ?? '')),
            'user_id' => $login,
            'email' => $email !== '' ? $email : null,
            'step_name' => $progress['pending_step'],
            'pending_steps' => $progress['pending_steps'] ?? $progress['pending_step'],
        ];

        if ($progress['done'] >= 1) {
            $base['bucket'] = self::TEMPLATE_B1;

            return $base;
        }

        if ($progress['done'] === 0) {
            $base['bucket'] = self::TEMPLATE_B2;
            $base['step_name'] = null;

            return $base;
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
        $name = trim((string) ($form?->form_name ?? ''));

        return $name !== ''
            ? $name
            : (string) config('gupshup.default_programme_name', 'Foundation Course');
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
}
