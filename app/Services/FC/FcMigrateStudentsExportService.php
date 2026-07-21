<?php

namespace App\Services\FC;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
class FcMigrateStudentsExportService
{
    /** Cache key for the roster↔credentials match set. */
    private const MATCHED_PKS_CACHE_KEY = 'fc_migrate_matched_roster_pks';

    public const LIST_MIGRATED = 'migrated';

    public const LIST_ELIGIBLE = 'eligible';

    /** @deprecated Use LIST_MIGRATED */
    public const LIST_IMPORTED = 'imported';

    public const MAX_EXPORT_ROWS = 15000;

    public function validateList(string $list): string
    {
        if ($list === self::LIST_IMPORTED) {
            $list = self::LIST_MIGRATED;
        }

        if (! in_array($list, [self::LIST_MIGRATED, self::LIST_ELIGIBLE], true)) {
            abort(404, 'Unknown export list.');
        }

        return $list;
    }

    /**
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     columns: array<int, array{key: string, label: string}>,
     *     title: string,
     *     filterLine: string,
     *     footerNote: string,
     *     truncated: bool,
     *     totalMatching: int
     * }
     */
    public function buildExportPayload(Request $request, string $list): array
    {
        $list = $this->validateList($list);
        $query = $list === self::LIST_ELIGIBLE
            ? $this->eligibleQuery($request)
            : $this->migratedQuery($request);

        $totalMatching = $this->countQuery($query);
        $truncated = $totalMatching > self::MAX_EXPORT_ROWS;

        $rows = $query->orderBy('r.pk', $list === self::LIST_ELIGIBLE ? 'asc' : 'desc')
            ->limit(self::MAX_EXPORT_ROWS)
            ->get();

        $mapped = $list === self::LIST_ELIGIBLE
            ? $this->mapEligibleRows($rows)
            : $this->mapMigratedRows($rows);

        return [
            'rows' => $mapped->all(),
            'columns' => $this->columnsForList($list),
            'title' => $list === self::LIST_ELIGIBLE
                ? 'FC Registration — Ready to migrate'
                : 'FC Registration — Migrated records',
            'filterLine' => $this->filterDescription($request, $list),
            'footerNote' => $list === self::LIST_ELIGIBLE
                ? 'LBSNAA Mussoorie — FC migrate students (eligible)'
                : 'LBSNAA Mussoorie — FC migrate students (migrated)',
            'truncated' => $truncated,
            'totalMatching' => $totalMatching,
        ];
    }

    /**
     * @return array{migrated: int, eligible: int}
     */
    public function tabCounts(Request $request): array
    {
        return [
            'migrated' => $this->countQuery($this->migratedQuery($request)),
            'eligible' => $this->countQuery($this->eligibleQuery($request)),
        ];
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function columnsForList(string $list): array
    {
        if ($list === self::LIST_ELIGIBLE) {
            return [
                ['key' => 'sno', 'label' => '#'],
                ['key' => 'pk', 'label' => 'Student PK'],
                ['key' => 'student_name', 'label' => 'Student Name'],
                ['key' => 'user_id', 'label' => 'Username'],
                ['key' => 'generated_OT_code', 'label' => 'OT Code'],
                ['key' => 'course', 'label' => 'Course'],
                ['key' => 'service', 'label' => 'Service'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'contact_no', 'label' => 'Mobile'],
                ['key' => 'exam_year', 'label' => 'Exam Year'],
                ['key' => 'status', 'label' => 'Status'],
            ];
        }

        return [
            ['key' => 'sno', 'label' => '#'],
            ['key' => 'pk', 'label' => 'Roster PK'],
            ['key' => 'student_name', 'label' => 'Student Name'],
            ['key' => 'user_id', 'label' => 'Username'],
            ['key' => 'generated_OT_code', 'label' => 'OT Code'],
            ['key' => 'course', 'label' => 'Course'],
            ['key' => 'service', 'label' => 'Service'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'contact_no', 'label' => 'Mobile'],
            ['key' => 'exam_year', 'label' => 'Exam Year'],
            ['key' => 'status', 'label' => 'Status'],
        ];
    }

    public function filterDescription(Request $request, string $list): string
    {
        $parts = [];
        $coursePk = $request->input('course_filter') ?: $request->input('filter_course');
        if ($coursePk !== null && $coursePk !== '') {
            $courseName = DB::table('course_master')
                ->where('pk', (int) $coursePk)
                ->value('course_name');
            $parts[] = 'Course: '.($courseName ?: (string) $coursePk);
        }
        if ($request->filled('service_filter')) {
            $parts[] = 'Service filter applied';
        }
        if ($request->filled('filter_search')) {
            $parts[] = 'Search: '.$request->string('filter_search');
        }
        $parts[] = 'List: '.($list === self::LIST_ELIGIBLE ? 'Ready to migrate' : 'Migrated');

        return implode(' | ', $parts);
    }

    public function rosterBaseQuery(Request $request): Builder
    {
        $query = DB::table('fc_registration_master as r')
            ->leftJoin('service_master as s', 'r.service_master_pk', '=', 's.pk')
            ->leftJoin('course_master as c', 'r.course_master_pk', '=', 'c.pk')
            ->select($this->rosterListColumns());

        $this->applyListFilters($query, $request);

        return $query;
    }

    public function migratedQuery(Request $request): Builder
    {
        $query = $this->rosterBaseQuery($request);
        $this->applyMigratedConstraints($query);

        return $query;
    }

    /** @deprecated Use migratedQuery() */
    public function importedQuery(Request $request): Builder
    {
        return $this->migratedQuery($request);
    }

    public function eligibleQuery(Request $request): Builder
    {
        $query = $this->rosterBaseQuery($request);
        $this->applyEligibleConstraints($query);

        return $query;
    }

    public function applyMigratedConstraints(Builder $query): void
    {
        $this->applyUserCredentialsMatchExists($query, true);
    }

    public function applyListFilters(Builder $query, Request $request): void
    {
        $coursePk = $request->input('course_filter') ?: $request->input('filter_course');
        if ($coursePk !== null && $coursePk !== '') {
            $query->where('r.course_master_pk', (int) $coursePk);
        }

        if ($services = $request->input('filter_services')) {
            $ids = is_array($services) ? $services : explode(',', (string) $services);
            $ids = array_filter(array_map('intval', $ids));
            if ($ids !== []) {
                $query->whereIn('r.service_master_pk', $ids);
            }
        }

        if ($search = trim((string) $request->input('filter_search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('r.user_id', 'like', "%{$search}%")
                    ->orWhere('r.email', 'like', "%{$search}%")
                    ->orWhere('r.contact_no', 'like', "%{$search}%")
                    ->orWhere('r.first_name', 'like', "%{$search}%")
                    ->orWhere('r.last_name', 'like', "%{$search}%")
                    ->orWhere('r.generated_OT_code', 'like', "%{$search}%")
                    ->orWhere('c.course_name', 'like', "%{$search}%")
                    ->orWhere('c.couse_short_name', 'like', "%{$search}%")
                    ->orWhere('s.service_name', 'like', "%{$search}%")
                    ->orWhere('s.service_short_name', 'like', "%{$search}%");
            });
        }
    }

    public function applyEligibleConstraints(Builder $query): void
    {
        $query->where('r.is_registered', 1)
            ->whereNotNull('r.user_id')
            ->where('r.user_id', '!=', '')
            ->whereNotNull('r.password')
            ->where('r.password', '!=', '');

        $this->applyUserCredentialsMatchExists($query, false);
    }

    /**
     * EXISTS / NOT EXISTS — same rules as the old correlated MIN(uc.pk) filter, without per-row SELECT cost.
     */
    /**
     * Roster pks that already have a matching user_credentials row.
     *
     * The membership test itself is unavoidably expensive: fc_registration_master
     * is latin1_swedish_ci while user_credentials is utf8mb4_0900_ai_ci, so every
     * comparison needs a runtime charset conversion (hence the TRIM/CAST/LOWER
     * wrappers) and no index — not even the idx_uc_mig_* functional indexes — can
     * be used. Evaluated per roster row it cost ~690ms on every request, including
     * every sort, search and page change.
     *
     * Since the predicate is a pure membership test on r.pk, it is evaluated once
     * and cached as a pk list; the table query then filters on the primary key
     * (~18ms). The result set is identical.
     *
     * Invalidate with flushMatchedRosterPks() after migrating trainees.
     *
     * @return list<int>
     */
    public function matchedRosterPks(): array
    {
        static $memo = null;

        if ($memo !== null) {
            return $memo;
        }

        $build = fn () => DB::table('fc_registration_master as r')
            ->whereRaw($this->userCredentialsMatchExistsSql())
            ->pluck('r.pk')
            ->map(fn ($pk) => (int) $pk)
            ->all();

        $ttl = (int) config('fc.migrate_match_cache_ttl', 300);
        if ($ttl <= 0) {
            return $memo = $build();
        }

        try {
            return $memo = Cache::remember(self::MATCHED_PKS_CACHE_KEY, $ttl, $build);
        } catch (\Throwable $e) {
            return $memo = $build();
        }
    }

    /** Drop the cached roster-match set (call after a migration writes credentials). */
    public static function flushMatchedRosterPks(): void
    {
        try {
            Cache::forget(self::MATCHED_PKS_CACHE_KEY);
        } catch (\Throwable $e) {
            // never let cache invalidation break a migration
        }
    }

    /**
     * The original OR-of-EXISTS predicate, used once to build the cached pk set.
     */
    private function userCredentialsMatchExistsSql(): string
    {
        return "(
            EXISTS (SELECT 1 FROM user_credentials uc WHERE TRIM(CAST(uc.user_name AS CHAR)) = TRIM(CAST(r.user_id AS CHAR)))
            OR (
                TRIM(COALESCE(r.contact_no, '')) <> ''
                AND EXISTS (SELECT 1 FROM user_credentials uc WHERE TRIM(CAST(uc.mobile_no AS CHAR)) = TRIM(CAST(r.contact_no AS CHAR)))
            )
            OR (
                TRIM(COALESCE(r.email, '')) <> ''
                AND EXISTS (SELECT 1 FROM user_credentials uc WHERE LOWER(TRIM(uc.email_id)) = LOWER(TRIM(r.email)))
            )
        )";
    }

    public function applyUserCredentialsMatchExists(Builder $query, bool $mustExist): void
    {
        // Migrated and Eligible are exact complements of the same membership test,
        // so both are served from one cached pk set: IN for migrated, NOT IN for
        // eligible. Semantics are unchanged — see matchedRosterPks().
        $matched = $this->matchedRosterPks();

        if ($mustExist) {
            $query->whereIn('r.pk', $matched);
        } else {
            $query->whereNotIn('r.pk', $matched);
        }

        return;
    }

    /** @deprecated superseded by the cached pk set above; kept for reference. */
    private function applyUserCredentialsMatchExistsLegacy(Builder $query, bool $mustExist): void
    {
        // Each identifier (username / mobile / email) is matched with its own
        // single-column EXISTS subquery instead of one subquery that ORs all three
        // columns together. A combined OR forces MySQL to full-scan user_credentials
        // once per roster row (the TRIM/CAST/LOWER wrappers make it unindexable as a
        // group); split single-column lookups let each run as an index scan/seek.
        //
        // The result set is unchanged. Logically EXISTS(a OR b OR c) == EXISTS(a) OR
        // EXISTS(b) OR EXISTS(c), and the contact_no/email "is present" guards are
        // constant per roster row so they factor out of the subquery unchanged.
        $usernameMatch = fn ($sub) => $sub->select(DB::raw(1))->from('user_credentials as uc')
            ->whereRaw('TRIM(CAST(uc.user_name AS CHAR)) = TRIM(CAST(r.user_id AS CHAR))');
        $mobileMatch = fn ($sub) => $sub->select(DB::raw(1))->from('user_credentials as uc')
            ->whereRaw('TRIM(CAST(uc.mobile_no AS CHAR)) = TRIM(CAST(r.contact_no AS CHAR))');
        $emailMatch = fn ($sub) => $sub->select(DB::raw(1))->from('user_credentials as uc')
            ->whereRaw('LOWER(TRIM(uc.email_id)) = LOWER(TRIM(r.email))');

        if ($mustExist) {
            // Migrated: username matches, OR (has contact AND mobile matches),
            // OR (has email AND email matches).
            $query->where(function ($q) use ($usernameMatch, $mobileMatch, $emailMatch) {
                $q->whereExists($usernameMatch)
                    ->orWhere(function ($q2) use ($mobileMatch) {
                        $q2->whereRaw("TRIM(COALESCE(r.contact_no, '')) <> ''")
                            ->whereExists($mobileMatch);
                    })
                    ->orWhere(function ($q3) use ($emailMatch) {
                        $q3->whereRaw("TRIM(COALESCE(r.email, '')) <> ''")
                            ->whereExists($emailMatch);
                    });
            });
        } else {
            // Eligible: username does NOT match, AND (no contact OR mobile does not
            // match), AND (no email OR email does not match).
            $query->whereNotExists($usernameMatch)
                ->where(function ($q) use ($mobileMatch) {
                    $q->whereRaw("TRIM(COALESCE(r.contact_no, '')) = ''")
                        ->orWhereNotExists($mobileMatch);
                })
                ->where(function ($q) use ($emailMatch) {
                    $q->whereRaw("TRIM(COALESCE(r.email, '')) = ''")
                        ->orWhereNotExists($emailMatch);
                });
        }
    }

    /**
     * @deprecated Prefer applyUserCredentialsMatchExists(); kept for any legacy callers.
     */
    public function userCredentialsMatchPkSubquery(): string
    {
        return '(SELECT MIN(uc.pk) FROM user_credentials uc WHERE '.$this->userCredentialsMatchWhereSql('uc').')';
    }

    /**
     * SQL predicate (roster alias must be r).
     */
    public function userCredentialsMatchWhereSql(string $ucAlias = 'uc'): string
    {
        $uc = $ucAlias;

        return "(
            TRIM(CAST({$uc}.user_name AS CHAR)) = TRIM(CAST(r.user_id AS CHAR))
            OR (
                TRIM(COALESCE(r.contact_no, '')) <> ''
                AND TRIM(CAST({$uc}.mobile_no AS CHAR)) = TRIM(CAST(r.contact_no AS CHAR))
            )
            OR (
                TRIM(COALESCE(r.email, '')) <> ''
                AND LOWER(TRIM({$uc}.email_id)) = LOWER(TRIM(r.email))
            )
        )";
    }

    public function rosterHasUserCredentials(object $row): bool
    {
        return ! empty($row->uc_pk ?? null);
    }

    private function countQuery(Builder $query): int
    {
        return (int) (clone $query)->count('r.pk');
    }

    /**
     * @return array<int, string>
     */
    private function rosterListColumns(): array
    {
        return [
            'r.pk',
            'r.user_id',
            'r.email',
            'r.contact_no',
            'r.first_name',
            'r.middle_name',
            'r.last_name',
            'r.display_name',
            'r.exam_year',
            'r.is_registered',
            'r.generated_OT_code',
            's.service_name',
            's.service_short_name',
            'c.course_name',
            'c.couse_short_name as course_short_name',
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function mapMigratedRows(Collection $rows): Collection
    {
        return $rows->values()->map(function ($row, int $i) {
            return [
                'sno' => $i + 1,
                'pk' => (int) $row->pk,
                'student_name' => $this->studentName($row),
                'user_id' => (string) ($row->user_id ?? '—'),
                'generated_OT_code' => (string) ($row->generated_OT_code ?? '—'),
                'course' => $this->formatNameWithShort($row->course_name ?? null, $row->course_short_name ?? null),
                'service' => $this->formatNameWithShort($row->service_name ?? null, $row->service_short_name ?? null),
                'email' => (string) ($row->email ?? '—'),
                'contact_no' => (string) ($row->contact_no ?? '—'),
                'exam_year' => (string) ($row->exam_year ?? '—'),
                'status' => 'Migrated',
            ];
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function mapEligibleRows(Collection $rows): Collection
    {
        return $rows->values()->map(function ($row, int $i) {
            return [
                'sno' => $i + 1,
                'pk' => (int) $row->pk,
                'student_name' => $this->studentName($row),
                'user_id' => (string) ($row->user_id ?? '—'),
                'generated_OT_code' => (string) ($row->generated_OT_code ?? '—'),
                'course' => $this->formatNameWithShort($row->course_name ?? null, $row->course_short_name ?? null),
                'service' => $this->formatNameWithShort($row->service_name ?? null, $row->service_short_name ?? null),
                'email' => (string) ($row->email ?? '—'),
                'contact_no' => (string) ($row->contact_no ?? '—'),
                'exam_year' => (string) ($row->exam_year ?? '—'),
                'status' => 'Ready to migrate',
            ];
        });
    }

    private function studentName(object $row): string
    {
        $parts = array_filter([
            $row->first_name ?? '',
            $row->middle_name ?? '',
            $row->last_name ?? '',
        ]);
        $name = $parts ? strtoupper(implode(' ', $parts)) : trim((string) ($row->display_name ?? ''));

        return $name !== '' ? $name : '—';
    }

    private function formatNameWithShort(?string $name, ?string $short): string
    {
        $name = trim((string) ($name ?? ''));
        $short = trim((string) ($short ?? ''));
        if ($name === '') {
            return '—';
        }

        return $short !== '' ? "{$name} ({$short})" : $name;
    }
}
