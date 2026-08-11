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
 * Admin bulk SMS + Email for B1 / B2 / B3.
 *
 * B1 — form started: either 1+ trackable step done, or credentials are staged
 *      (user_id + password set) even with 0 steps done — staging credentials is
 *      itself the first action of registration, so the trainee is "started, not
 *      not-started".
 * B2 — registration not completed and truly not started: no staged credentials
 *      and no tracker / 0 steps done.
 * B3 — all fc_form_steps complete but the (non-tracker-column) travel step is not
 *      submitted yet (tracker.travel_done is falsy). Email only — no DLT-approved
 *      SMS template exists for this reminder yet.
 *
 * DB practices: select required columns, chunkById for scans/sends,
 * batch credential + tracker lookups (no per-row queries in loops),
 * paginated recipient lists. Requirements unchanged (no send limit).
 */
class FcAdminSmsBulkService
{
    public const TEMPLATE_B1 = 'b1';

    public const TEMPLATE_B2 = 'b2';

    public const TEMPLATE_B3 = 'b3';

    public const VALID_TEMPLATES = [self::TEMPLATE_B1, self::TEMPLATE_B2, self::TEMPLATE_B3];

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
            self::TEMPLATE_B3 => $this->sendByTemplate(self::TEMPLATE_B3, $registrationPks, $formId),
            default => [
                'ok' => false,
                'message' => 'Invalid template. Choose Form step incomplete, Registration pending, or Travel pending.',
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
            ],
        };
    }

    /**
     * Counts only (chunked — does not keep full recipient lists in memory).
     *
     * @return array{b1: int, b2: int, b3: int, programme: string, last_date: string}
     */
    public function previewCounts(?int $formId = null): array
    {
        $payload = $this->previewForIndex(1, 1, 1, self::LIST_PER_PAGE, $formId);

        return [
            'b1' => $payload['b1'],
            'b2' => $payload['b2'],
            'b3' => $payload['b3'],
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
        if (! in_array($template, self::VALID_TEMPLATES, true)) {
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
     * One page of recipients for a template, stopping the underlying scan as soon as
     * enough matches are collected — used for the DataTables first load / page turns
     * so the admin isn't waiting on a full-roster scan for a 25-row page.
     *
     * @return list<array{pk: int, name: string, mobile: string, user_id: string, step_name: ?string}>
     */
    public function recipientsPage(string $template, int $offset, int $length, ?int $formId = null): array
    {
        $template = strtolower(trim($template));
        if (! in_array($template, self::VALID_TEMPLATES, true)) {
            return [];
        }

        $offset = max(0, $offset);
        $length = max(1, $length);
        $needed = $offset + $length;

        $out = [];
        $seen = 0;
        $this->eachClassifiedRecipient(function (array $item) use (&$out, &$seen, $template, $offset, $needed) {
            if (($item['bucket'] ?? null) !== $template) {
                return;
            }

            if ($seen >= $offset) {
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
            $seen++;

            return $seen < $needed;
        }, $this->resolveFormForScope($formId));

        return $out;
    }

    /**
     * Count of matching recipients for one template (same chunked scan, no rows kept).
     */
    public function countRecipientsForTemplate(string $template, ?int $formId = null): int
    {
        $template = strtolower(trim($template));
        if (! in_array($template, self::VALID_TEMPLATES, true)) {
            return 0;
        }

        $count = 0;
        $this->eachClassifiedRecipient(function (array $item) use (&$count, $template) {
            if (($item['bucket'] ?? null) === $template) {
                $count++;
            }
        }, $this->resolveFormForScope($formId));

        return $count;
    }

    /**
     * One chunked pass: counts + one page of each list (no full dataset in memory).
     *
     * @return array{
     *   b1: int,
     *   b2: int,
     *   b3: int,
     *   programme: string,
     *   last_date: string,
     *   lists: array{b1: LengthAwarePaginator, b2: LengthAwarePaginator, b3: LengthAwarePaginator}
     * }
     */
    public function previewForIndex(int $b1Page = 1, int $b2Page = 1, int $b3Page = 1, int $perPage = self::LIST_PER_PAGE, ?int $formId = null): array
    {
        $b1Page = max(1, $b1Page);
        $b2Page = max(1, $b2Page);
        $b3Page = max(1, $b3Page);
        $perPage = max(1, min(100, $perPage));

        $counts = [self::TEMPLATE_B1 => 0, self::TEMPLATE_B2 => 0, self::TEMPLATE_B3 => 0];
        $pageItems = [self::TEMPLATE_B1 => [], self::TEMPLATE_B2 => [], self::TEMPLATE_B3 => []];
        $offsets = [
            self::TEMPLATE_B1 => ($b1Page - 1) * $perPage,
            self::TEMPLATE_B2 => ($b2Page - 1) * $perPage,
            self::TEMPLATE_B3 => ($b3Page - 1) * $perPage,
        ];

        $form = $this->resolveFormForScope($formId);

        $this->eachClassifiedRecipient(function (array $item) use (&$counts, &$pageItems, $offsets, $perPage) {
            $bucket = $item['bucket'] ?? null;
            if (! in_array($bucket, self::VALID_TEMPLATES, true)) {
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
            'b3' => $counts[self::TEMPLATE_B3],
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
                self::TEMPLATE_B3 => new Paginator(
                    $pageItems[self::TEMPLATE_B3],
                    $counts[self::TEMPLATE_B3],
                    $perPage,
                    $b3Page,
                    ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'b3_page']
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
            $template === self::TEMPLATE_B3 ? $page : 1,
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
                $delivered = $this->dispatchTemplateNotification($template, $row, $programme, $lastDate)['sent'];

                if ($delivered) {
                    $sent++;
                } else {
                    $failed++;
                }

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
                : match ($template) {
                    self::TEMPLATE_B1 => 'No trainees found who started the form and still have a pending step.',
                    self::TEMPLATE_B3 => 'No trainees found with all form steps complete but travel plan still pending.',
                    default => 'No trainees found with registration pending (form not started / not registered).',
                };

            return [
                'ok' => $registrationPks === null,
                'message' => $emptyMsg,
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
            ];
        }

        $label = $this->templateLabel($template);
        $message = $registrationPks !== null
            ? "{$label} delivered to {$sent} of {$matched} selected trainee(s)."
            : "{$label} delivered to {$sent} of {$matched} trainee(s).";
        if ($failed > 0) {
            $message .= " {$failed} failed to deliver.";
        }

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

        $label = $this->templateLabel($template);
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

        $bucket = $this->hasStagedCredentials($roster) ? self::TEMPLATE_B1 : self::TEMPLATE_B2;
        $stepName = null;
        $rowPendingSteps = $pendingStepsLabel;

        if ($tracker) {
            $progress = $this->stepProgressFromTracker($form, $steps, $tracker, $roster);
            if ($progress['pending_step'] === null) {
                return $this->isTravelPending($tracker)
                    ? [
                        'bucket' => self::TEMPLATE_B3,
                        'pk' => $pk,
                        'mobile' => $mobile,
                        'name' => trim((string) ($roster->display_name ?? '')),
                        'user_id' => $login,
                        'email' => $email !== '' ? $email : null,
                        'step_name' => null,
                        'pending_steps' => null,
                    ]
                    : null;
            }
            if ($progress['done'] >= 1) {
                $bucket = self::TEMPLATE_B1;
            }
            $stepName = $progress['pending_step'];
            $rowPendingSteps = $progress['pending_steps'] ?? $progress['pending_step'];
        }

        // No tracker row at all, but credentials are staged: still B1 (see class
        // doc-comment). Nothing is done yet, so every applicable trackable step is
        // pending — the SMS names the first one, the email lists them all.
        if ($bucket === self::TEMPLATE_B1 && $stepName === null) {
            $progress = $this->stepProgressFromTracker($form, $steps, null, $roster);
            $stepName = $progress['pending_step']
                ?: (trim((string) ($steps->first()?->step_name ?? '')) ?: 'Basic Information');
            $rowPendingSteps = $progress['pending_steps'] ?: $stepName;
        }

        return [
            'bucket' => $bucket,
            'pk' => $pk,
            'mobile' => $mobile,
            'name' => trim((string) ($roster->display_name ?? '')),
            'user_id' => $login,
            'email' => $email !== '' ? $email : null,
            'step_name' => $stepName,
            'pending_steps' => $rowPendingSteps,
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
            $delivered = match ($template) {
                self::TEMPLATE_B1 => $this->notify->formStepIncomplete(
                    $row['mobile'],
                    $row['name'],
                    $row['step_name'] ?? 'registration',
                    $row['pk'],
                    $row['email'] ?? null,
                    $programme,
                    $row['pending_steps'] ?? null,
                ),
                self::TEMPLATE_B3 => $this->notify->travelPending(
                    $row['mobile'],
                    $row['name'],
                    $programme,
                    $row['pk'],
                    $row['email'] ?? null,
                ),
                default => $this->notify->registrationPending(
                    $row['mobile'],
                    $row['name'],
                    $programme,
                    $lastDate,
                    $row['pk'],
                    $row['email'] ?? null,
                    $row['pending_steps'] ?? null,
                ),
            };

            return [
                'sent' => $delivered,
                'failed' => ! $delivered,
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
     * @param  callable(array<string, mixed>): (bool|void)  $callback  Return false to stop scanning early
     *         (e.g. once a bounded page of matches has been collected).
     */
    protected function eachClassifiedRecipient(callable $callback, ?FcForm $form = null): void
    {
        $form = $form ?? FcForm::activeRegistrationDynamicForm();
        $steps = $this->trackableSteps($form);

        /** @var array<int, true> $skipB2 Roster pks excluded from B2 (B1, form complete, or started). */
        $skipB2 = [];
        /** @var array<int, true> $emitted Already yielded (avoid double B2). */
        $emitted = [];
        $stopped = false;

        $chunkCol = $this->trackerChunkColumn($form);
        $this->eligibleTrackerQuery($form)->orderBy($chunkCol)->chunkById(self::CHUNK_SIZE, function ($rows) use (
            $callback,
            $form,
            $steps,
            &$skipB2,
            &$emitted,
            &$stopped
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
                if ($callback($item) === false) {
                    $stopped = true;
                }
            }

            // B1 comes only from the (small) tracker table, so it always needs a full pass
            // here to keep $skipB2 correct for phase two below — only stop once that pass
            // itself is done producing rows.
            return $stopped ? false : null;
        }, $chunkCol);

        if ($stopped) {
            return;
        }

        $pendingStepsLabel = $steps
            ->map(fn ($s) => trim((string) ($s->step_name ?? '')))
            ->filter()
            ->implode(', ');
        if ($pendingStepsLabel === '') {
            $pendingStepsLabel = 'pending steps';
        }

        // Hoisted out of the per-row loop below — was previously re-checked via an
        // uncached information_schema query for every one of the ~10k roster rows.
        $hasIsRegistered = Schema::hasColumn('fc_registration_master', 'is_registered');

        $this->eligibleRosterQuery($form)->orderBy('pk')->chunkById(self::CHUNK_SIZE, function ($rows) use (
            $callback,
            $form,
            $steps,
            &$skipB2,
            &$emitted,
            $pendingStepsLabel,
            $hasIsRegistered
        ) {
            $trackersByRosterPk = $this->loadTrackersForRosters(collect($rows), $form);
            $stop = false;

            foreach ($rows as $roster) {
                $pk = (int) ($roster->pk ?? 0);
                if ($pk <= 0 || isset($skipB2[$pk]) || isset($emitted[$pk])) {
                    continue;
                }

                if ($hasIsRegistered && (int) ($roster->is_registered ?? 0) === 1) {
                    continue;
                }

                $tracker = $trackersByRosterPk->get($pk);
                $stepName = null;
                $rowPendingSteps = $pendingStepsLabel;
                if ($tracker) {
                    $progress = $this->stepProgressFromTracker($form, $steps, $tracker, $roster);
                    if ($progress['pending_step'] === null || $progress['done'] >= 1) {
                        continue;
                    }
                    $stepName = $progress['pending_step'];
                    $rowPendingSteps = $progress['pending_steps'] ?? $progress['pending_step'];
                }

                // Credentials staged (user_id + password set) counts as "started" even
                // with 0 trackable steps done — see class doc-comment. Since there is no
                // tracker row yet in that case, $stepName was never set above: every
                // applicable trackable step is still pending, so name the first one for
                // the SMS and keep the whole list for the email.
                $hasStagedCredentials = $this->hasStagedCredentials($roster);
                $bucket = $hasStagedCredentials ? self::TEMPLATE_B1 : self::TEMPLATE_B2;
                if ($hasStagedCredentials && $stepName === null) {
                    $progress = $this->stepProgressFromTracker($form, $steps, null, $roster);
                    $stepName = $progress['pending_step']
                        ?: (trim((string) ($steps->first()?->step_name ?? '')) ?: 'Basic Information');
                    $rowPendingSteps = $progress['pending_steps'] ?: $stepName;
                }

                $mobile = trim((string) ($roster->contact_no ?? ''));

                $email = trim((string) ($roster->email ?? ''));
                $emitted[$pk] = true;
                if ($callback([
                    'bucket' => $bucket,
                    'pk' => $pk,
                    'mobile' => $mobile,
                    'name' => trim((string) ($roster->display_name ?? '')),
                    'user_id' => trim((string) ($roster->user_id ?? '')),
                    'email' => $email !== '' ? $email : null,
                    'step_name' => $stepName,
                    'pending_steps' => $rowPendingSteps,
                ]) === false) {
                    $stop = true;
                    break;
                }
            }

            return $stop ? false : null;
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
            ->select(['pk', 'display_name', 'contact_no', 'user_id', 'password', 'email', 'is_registered', 'application_type', 'ph_value'])
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

        $progress = $this->stepProgressFromTracker($form, $steps, $trackerRow, $roster);
        $rosterPk = (int) $roster->pk;
        $email = trim((string) ($roster->email ?? ''));

        // All fc_form_steps complete — exclude from B1/B2 either way. If the
        // (separately tracked) travel step is also pending, bucket as B3 instead
        // of dropping the trainee entirely.
        if ($progress['pending_step'] === null) {
            $skipB2[$rosterPk] = true;

            if ($this->isTravelPending($trackerRow)) {
                return [
                    'bucket' => self::TEMPLATE_B3,
                    'pk' => $rosterPk,
                    'mobile' => $mobile,
                    'name' => trim((string) ($roster->display_name ?? '')),
                    'user_id' => $login,
                    'email' => $email !== '' ? $email : null,
                    'step_name' => null,
                    'pending_steps' => null,
                ];
            }

            return null;
        }

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

        // B2: tracker exists but no step completed yet (and still not registered) —
        // unless credentials are already staged, which counts as "started" (B1), same
        // signal as classifySelectedRosterRow() and the phase-two roster pass below.
        if ($progress['done'] === 0) {
            if (Schema::hasColumn('fc_registration_master', 'is_registered')) {
                $registered = $roster->is_registered ?? null;
                if ((int) $registered === 1) {
                    return null;
                }
            }

            if ($this->hasStagedCredentials($roster)) {
                $base['bucket'] = self::TEMPLATE_B1;
                if (trim((string) $base['step_name']) === '') {
                    $base['step_name'] = trim((string) ($steps->first()?->step_name ?? '')) ?: 'Basic Information';
                }
                if (trim((string) $base['pending_steps']) === '') {
                    $base['pending_steps'] = $base['step_name'];
                }

                return $base;
            }

            $base['bucket'] = self::TEMPLATE_B2;
            $base['step_name'] = null;

            return $base;
        }

        return null;
    }

    /**
     * Batch-load tracker rows keyed by roster pk (roster pk, login, or credentials pk).
     *
     * @param  Collection<int, object>  $rosters
     * @return Collection<int, object>
     */
    protected function loadTrackersForRosters(Collection $rosters, FcForm $form): Collection
    {
        if ($rosters->isEmpty()) {
            return collect();
        }

        $table = $form->trackerStorageTable();
        if (! fc_schema_has_table($table)) {
            return collect();
        }

        $userCol = fc_user_col($table);
        $hasUserCredentials = Schema::hasTable('user_credentials');

        $logins = $rosters
            ->map(fn ($roster) => trim((string) ($roster->user_id ?? '')))
            ->filter()
            ->unique()
            ->values();

        // One batched login -> credentials-pk lookup instead of one query per roster.
        $credPkByLogin = ($hasUserCredentials && $logins->isNotEmpty())
            ? DB::table('user_credentials')
                ->whereIn('user_name', $logins->all())
                ->pluck('pk', 'user_name')
            : collect();

        $keys = [];
        foreach ($rosters as $roster) {
            $pk = (int) ($roster->pk ?? 0);
            if ($pk > 0) {
                $keys[] = $pk;
            }

            $login = trim((string) ($roster->user_id ?? ''));
            if ($login === '') {
                continue;
            }

            $keys[] = $login;

            $credPk = $credPkByLogin->get($login);
            if ($credPk) {
                $keys[] = (int) $credPk;
            }
        }

        $keys = array_values(array_unique(array_filter($keys, fn ($v) => $v !== '' && $v !== null)));
        if ($keys === []) {
            return collect();
        }

        $query = DB::table($table)->whereIn($userCol, $keys);
        if (fc_schema_has_column($table, 'form_id')) {
            $query->where('form_id', $form->id);
        }

        $byKey = $query->get()->keyBy(fn ($row) => (string) ($row->{$userCol} ?? ''));

        $result = collect();
        foreach ($rosters as $roster) {
            $pk = (int) ($roster->pk ?? 0);
            if ($pk <= 0) {
                continue;
            }

            $tracker = $byKey->get((string) $pk);
            $login = trim((string) ($roster->user_id ?? ''));

            if (! $tracker && $login !== '') {
                $tracker = $byKey->get($login);
            }

            if (! $tracker && $login !== '') {
                $credPk = $credPkByLogin->get($login);
                if ($credPk) {
                    $tracker = $byKey->get((string) $credPk);
                }
            }

            if ($tracker) {
                $result->put($pk, $tracker);
            }
        }

        return $result;
    }

    protected function templateLabel(string $template): string
    {
        return match ($template) {
            self::TEMPLATE_B1 => 'Form step incomplete',
            self::TEMPLATE_B3 => 'Travel pending',
            default => 'Registration pending',
        };
    }

    /**
     * True when all fc_form_steps are done but the tracker's travel_done flag
     * (not itself a trackable step — see class doc) is still falsy/absent.
     */
    protected function isTravelPending(?object $trackerRow): bool
    {
        if ($trackerRow === null || ! array_key_exists('travel_done', (array) $trackerRow)) {
            return false;
        }

        return ! ((bool) $trackerRow->travel_done);
    }

    /** Credentials staged (user_id + password set) — same signal as FoundationCourseStatus::scopeWhereHasStagedCredentials. */
    protected function hasStagedCredentials(?object $roster): bool
    {
        if (! $roster) {
            return false;
        }

        return trim((string) ($roster->user_id ?? '')) !== ''
            && trim((string) ($roster->password ?? '')) !== '';
    }

    /**
     * @return array{done: int, pending_step: ?string, pending_steps: ?string}
     */
    protected function stepProgressFromTracker(
        ?FcForm $form,
        Collection $steps,
        ?object $trackerRow,
        ?object $roster = null,
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
            if (! $complete && $roster !== null && $this->applicability->notApplicableForRoster($step, $roster)) {
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
