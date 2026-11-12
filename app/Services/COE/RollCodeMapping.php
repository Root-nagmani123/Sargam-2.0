<?php

namespace App\Services\COE;

use App\Models\COE\ParticipantCode;
use App\Models\COE\RollNumberAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Which Unique Participant Code belongs to which roll number.
 *
 * Nothing is stored for this: both lists already carry the student, and each
 * student has at most one roll number (coe_roll_numbers.student_master_pk is
 * unique) and one code (coe_participant_codes.student_master_pk is unique).
 * The two are matched on the same student, course and batch — the roll number's
 * course and batch come from its allocation (coe_roll_number_allocations).
 *
 * Every OT that has either a roll number or a code is listed, so the gaps show:
 * MAPPED (both), CODE_MISSING (roll number only), ROLL_MISSING (code only).
 *
 * Confidential: the mapping undoes the anonymity of the codes, so only the
 * COE screen (menu permission coe_roll_code_mapping) reads it.
 */
class RollCodeMapping
{
    public const SCOPES = ['active', 'archived'];

    public const MAPPED = 'Mapped';

    public const CODE_MISSING = 'Unique code missing';

    public const ROLL_MISSING = 'Roll number missing';

    public const STATES = [self::MAPPED, self::CODE_MISSING, self::ROLL_MISSING];

    /**
     * One row per OT of the scope's courses, ordered by course, batch, then roll
     * number (OTs with only a code last, by code).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(string $scope = 'active'): Collection
    {
        $coursePks = DB::table('course_master as cm')
            ->where(fn ($q) => $this->whereCourseInScope($q, $scope, 'cm'))
            ->pluck('cm.pk')->map(fn ($pk) => (int) $pk)->all();
        if ($coursePks === []) {
            return collect();
        }

        $rolls = DB::table('coe_roll_numbers as r')
            ->join('coe_roll_number_allocations as a', 'a.pk', '=', 'r.roll_number_allocation_pk')
            ->whereIn('a.course_master_pk', $coursePks)
            ->get(['a.course_master_pk', 'a.batch', 'a.status as allocation_status', 'r.student_master_pk',
                'r.roll_number', 'r.serial_number', 'r.frozen_at']);

        $codes = DB::table('coe_participant_codes')
            ->whereIn('course_master_pk', $coursePks)
            ->get(['course_master_pk', 'batch', 'student_master_pk', 'unique_code', 'status']);

        $key = fn ($r) => $r->course_master_pk . ':' . $r->batch . ':' . $r->student_master_pk;
        $byKey = [];
        foreach ($rolls as $r) {
            $byKey[$key($r)]['roll'] = $r;
        }
        foreach ($codes as $c) {
            $byKey[$key($c)]['code'] = $c;
        }
        if ($byKey === []) {
            return collect();
        }

        $studentPks = collect($byKey)->map(fn ($p) => ($p['roll'] ?? $p['code'])->student_master_pk)->unique()->values();
        $students = DB::table('student_master as s')
            ->leftJoin('service_master as sv', 'sv.pk', '=', 's.service_master_pk')
            ->whereIn('s.pk', $studentPks)
            ->get(['s.pk', 's.display_name', 's.first_name', 's.middle_name', 's.last_name', 'sv.service_short_name'])
            ->keyBy('pk');
        $courses = DB::table('course_master')->whereIn('pk', $coursePks)->pluck('course_name', 'pk');

        return collect($byKey)->map(function (array $pair) use ($students, $courses) {
            $roll = $pair['roll'] ?? null;
            $code = $pair['code'] ?? null;
            $any = $roll ?? $code;
            $student = $students[$any->student_master_pk] ?? null;

            return [
                'course_pk'    => (int) $any->course_master_pk,
                'course_name'  => trim((string) ($courses[$any->course_master_pk] ?? '—')),
                'batch'        => (int) $any->batch,
                'student_pk'   => (int) $any->student_master_pk,
                'ot_name'      => $this->studentName($student),
                'service'      => trim((string) ($student->service_short_name ?? '')) ?: '—',
                'roll_number'  => $roll ? trim((string) $roll->roll_number) : '',
                'serial'       => $roll ? (int) $roll->serial_number : PHP_INT_MAX,
                'roll_status'  => $roll
                    ? ($roll->frozen_at || (int) $roll->allocation_status === RollNumberAllocation::STATUS_FROZEN ? 'Frozen' : 'Generated')
                    : '—',
                'unique_code'  => $code ? trim((string) $code->unique_code) : '',
                'code_status'  => $code ? ParticipantCode::statusLabel((int) $code->status) : '—',
                'mapping'      => $roll && $code ? self::MAPPED : ($roll ? self::CODE_MISSING : self::ROLL_MISSING),
            ];
        })
            ->sort(fn ($a, $b) => [strtolower($a['course_name']), $a['batch'], $a['serial'], $a['unique_code']]
                <=> [strtolower($b['course_name']), $b['batch'], $b['serial'], $b['unique_code']])
            ->values();
    }

    /**
     * Same split as Roll Numbers / Unique Participant Code.
     * Active: active and not past its end date. Archived: everything else.
     */
    private function whereCourseInScope($query, string $scope, string $alias): void
    {
        $today = now()->toDateString();

        if ($scope === 'archived') {
            $query->where("{$alias}.active_inactive", '!=', 1)->orWhereNull("{$alias}.active_inactive")
                ->orWhere("{$alias}.end_date", '<', $today);
        } else {
            $query->where("{$alias}.active_inactive", 1)
                ->where(fn ($w) => $w->whereNull("{$alias}.end_date")->orWhere("{$alias}.end_date", '>=', $today));
        }
    }

    private function studentName(?object $s): string
    {
        if (! $s) {
            return '—';
        }
        $name = trim((string) ($s->display_name ?? ''));
        if ($name === '') {
            $name = trim(implode(' ', array_filter([$s->first_name ?? null, $s->middle_name ?? null, $s->last_name ?? null])));
        }

        return $name !== '' ? $name : '—';
    }
}
