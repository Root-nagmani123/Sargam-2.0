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
     * @return array{ok: bool, message: string, sent: int, skipped: int, failed: int}
     */
    public function send(string $template): array
    {
        $template = strtolower(trim($template));

        return match ($template) {
            self::TEMPLATE_B1 => $this->sendByTemplate(self::TEMPLATE_B1),
            self::TEMPLATE_B2 => $this->sendByTemplate(self::TEMPLATE_B2),
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
        $steps = $form ? $form->activeSteps()->get() : collect();

        $this->eligibleRosterQuery()->orderBy('pk')->chunkById(self::CHUNK_SIZE, function ($rows) use (
            &$counts,
            &$pageItems,
            $offsets,
            $perPage,
            $form,
            $steps
        ) {
            foreach ($this->classifyChunk($rows, $form, $steps) as $item) {
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
        }, 'pk');

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
     * @return array{ok: bool, message: string, sent: int, skipped: int, failed: int}
     */
    protected function sendByTemplate(string $template): array
    {
        @set_time_limit(0);

        $form = FcForm::activeRegistrationDynamicForm();
        $steps = $form ? $form->activeSteps()->get() : collect();
        $programme = $this->programmeName($form);
        $lastDate = $this->registrationDeadlineText();

        $sent = 0;
        $failed = 0;
        $matched = 0;

        $this->eligibleRosterQuery()->orderBy('pk')->chunkById(self::CHUNK_SIZE, function ($rows) use (
            $template,
            $form,
            $steps,
            $programme,
            $lastDate,
            &$sent,
            &$failed,
            &$matched
        ) {
            foreach ($this->classifyChunk($rows, $form, $steps) as $row) {
                if (($row['bucket'] ?? null) !== $template) {
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
                } catch (\Throwable $e) {
                    $failed++;
                }
            }
        }, 'pk');

        if ($matched === 0) {
            $emptyMsg = $template === self::TEMPLATE_B1
                ? 'No trainees found who started the form and still have a pending step.'
                : 'No trainees found with registration pending (no form step started yet).';

            return [
                'ok' => true,
                'message' => $emptyMsg,
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
            ];
        }

        $label = $template === self::TEMPLATE_B1 ? 'Form step incomplete' : 'Registration pending';

        return [
            'ok' => true,
            'message' => "{$label} SMS + Email processed for {$sent} trainee(s).",
            'sent' => $sent,
            'skipped' => 0,
            'failed' => $failed,
        ];
    }

    /**
     * Eligible roster query (columns + filters only — no get()).
     */
    protected function eligibleRosterQuery()
    {
        if (! Schema::hasTable('fc_registration_master')) {
            return DB::table('fc_registration_master')->whereRaw('1 = 0');
        }

        $columns = ['pk', 'display_name', 'contact_no', 'user_id'];
        if (Schema::hasColumn('fc_registration_master', 'email')) {
            $columns[] = 'email';
        }
        if (Schema::hasColumn('fc_registration_master', 'ph_value')) {
            $columns[] = 'ph_value';
        }

        $query = DB::table('fc_registration_master')
            ->select($columns)
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

        return $query;
    }

    /**
     * Classify one chunk with batch credential + tracker lookups (no N+1).
     *
     * @param  Collection<int, object>  $rows
     * @param  Collection<int, FcFormStep>  $steps
     * @return list<array<string, mixed>>
     */
    protected function classifyChunk(Collection $rows, ?FcForm $form, Collection $steps): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $logins = $rows->map(fn ($r) => trim((string) ($r->user_id ?? '')))
            ->filter(fn ($u) => $u !== '')
            ->unique()
            ->values()
            ->all();

        $credMap = [];
        if ($logins !== [] && Schema::hasTable('user_credentials')) {
            $credMap = DB::table('user_credentials')
                ->select(['pk', 'user_name'])
                ->whereIn('user_name', $logins)
                ->get()
                ->mapWithKeys(fn ($c) => [trim((string) $c->user_name) => (int) $c->pk])
                ->all();
        }

        $trackerByUser = [];
        if ($form && $steps->isNotEmpty()) {
            $trackerByUser = $this->batchTrackerRows($form, $rows, $credMap);
        }

        $out = [];
        foreach ($rows as $row) {
            $classified = $this->classifyRow($row, $form, $steps, $credMap, $trackerByUser);
            if ($classified !== null) {
                $out[] = $classified;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, int>  $credMap
     * @param  array<int|string, object>  $trackerByUser
     * @return array<string, mixed>|null
     */
    protected function classifyRow(
        object $row,
        ?FcForm $form,
        Collection $steps,
        array $credMap,
        array $trackerByUser,
    ): ?array {
        $mobile = trim((string) ($row->contact_no ?? ''));
        $login = trim((string) ($row->user_id ?? ''));
        if ($mobile === '' || $login === '') {
            return null;
        }

        $progressUserId = $credMap[$login] ?? FcRosterAuthService::stagedUserId((int) $row->pk);
        $trackerKey = $this->trackerLookupKey($progressUserId, $row, $credMap);
        $trackerRow = $trackerKey !== null ? ($trackerByUser[$trackerKey] ?? null) : null;

        $progress = $this->stepProgressFromTracker($form, $steps, $trackerRow, $row);

        if ($progress['pending_step'] === null && $progress['done'] > 0) {
            return null;
        }

        $email = trim((string) ($row->email ?? ''));
        $base = [
            'pk' => (int) $row->pk,
            'mobile' => $mobile,
            'name' => trim((string) ($row->display_name ?? '')),
            'user_id' => $login,
            'email' => $email !== '' ? $email : null,
            'step_name' => $progress['pending_step'],
            'pending_steps' => $progress['pending_steps'] ?? $progress['pending_step'],
        ];

        if ($progress['done'] >= 1 && $progress['pending_step'] !== null) {
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
     * @param  array<string, int>  $credMap
     * @return array<int|string, object>
     */
    protected function batchTrackerRows(FcForm $form, Collection $rows, array $credMap): array
    {
        $trackerTable = $form->trackerStorageTable();
        if (! fc_schema_has_table($trackerTable)) {
            return [];
        }

        $userCol = fc_user_col($trackerTable);
        $keys = [];

        foreach ($rows as $row) {
            $login = trim((string) ($row->user_id ?? ''));
            if ($login === '') {
                continue;
            }
            $progressUserId = $credMap[$login] ?? FcRosterAuthService::stagedUserId((int) $row->pk);
            $key = $this->trackerLookupKey($progressUserId, $row, $credMap);
            if ($key !== null) {
                $keys[] = $key;
            }
        }

        $keys = array_values(array_unique($keys));
        if ($keys === []) {
            return [];
        }

        $query = DB::table($trackerTable)->whereIn($userCol, $keys);
        if (fc_schema_has_column($trackerTable, 'form_id')) {
            $query->where('form_id', $form->id);
        }

        return $query->get()->keyBy($userCol)->all();
    }

    /**
     * @param  array<string, int>  $credMap
     */
    protected function trackerLookupKey(int $progressUserId, object $row, array $credMap): int|string|null
    {
        // Prefer migrated credentials pk; else staged roster pk when tracker uses integer user_id.
        if ($progressUserId > 0) {
            return $progressUserId;
        }

        $login = trim((string) ($row->user_id ?? ''));
        if ($login !== '' && isset($credMap[$login])) {
            return $credMap[$login];
        }

        return (int) $row->pk;
    }

    /**
     * @return array{done: int, pending_step: ?string, pending_steps: ?string}
     */
    protected function stepProgressFromTracker(
        ?FcForm $form,
        Collection $steps,
        ?object $trackerRow,
        object $rosterRow,
    ): array {
        if (! $form || $steps->isEmpty()) {
            return ['done' => 0, 'pending_step' => 'Basic Information', 'pending_steps' => 'Basic Information'];
        }

        $done = 0;
        $pending = [];
        $hasPh = filled($rosterRow->ph_value ?? null);

        foreach ($steps as $step) {
            $isSpecialAssistant = str_starts_with(
                strtolower(trim((string) $step->step_name)),
                'special assist'
            );
            if ($isSpecialAssistant && ! $hasPh) {
                continue;
            }

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

        if ($pending === [] && $trackerRow !== null) {
            $travelDone = isset($trackerRow->travel_done) ? (bool) $trackerRow->travel_done : true;
            if (! $travelDone) {
                $pending[] = 'Travel Plan';
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
