<?php

namespace App\Services\FC;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
class FcMigrateStudentsExportService
{
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
    public function applyUserCredentialsMatchExists(Builder $query, bool $mustExist): void
    {
        $callback = function ($sub) {
            $sub->select(DB::raw(1))
                ->from('user_credentials as uc')
                ->whereRaw($this->userCredentialsMatchWhereSql('uc'));
        };

        if ($mustExist) {
            $query->whereExists($callback);
        } else {
            $query->whereNotExists($callback);
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
