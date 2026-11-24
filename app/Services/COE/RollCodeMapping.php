<?php

namespace App\Services\COE;

use App\Models\COE\ParticipantCode;
use App\Models\COE\RollNumberAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Which Unique Participant Codes belong to which roll number.
 *
 * Nothing is stored for this: both lists already carry the student. An OT has one roll
 * number per course (CourseParticipants::rollNumbersOn) and one code per subject of a
 * course (coe_participant_codes).
 *
 * One row per OT per subject code, matched on student, course and batch, plus one row
 * for an OT holding a roll number but no code at all, so the gaps show:
 * MAPPED (both, and BOTH frozen), NOT_FROZEN (both, but either one not frozen yet),
 * CODE_MISSING (roll number, no code for any subject), ROLL_MISSING (code, no roll number).
 * A pair only counts as mapped once it is final — an unfrozen roll number or code can
 * still change.
 *
 * Confidential: the mapping undoes the anonymity of the codes, so only the
 * COE screen (menu permission coe_roll_code_mapping) reads it.
 */
class RollCodeMapping
{
    public const SCOPES = ['active', 'archived'];

    public const MAPPED = 'Mapped';

    public const NOT_FROZEN = 'Not frozen';

    public const CODE_MISSING = 'Unique code missing';

    public const ROLL_MISSING = 'Roll number missing';

    public const STATES = [self::MAPPED, self::NOT_FROZEN, self::CODE_MISSING, self::ROLL_MISSING];

    /**
     * Rows of the scope's courses, ordered by course, batch, roll number (OTs with only
     * codes last), then subject.
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

        // Each course + batch with a roll-number run: the numbers issued on it.
        $rolls = []; // "course:batch:student" => roll row
        $runs = DB::table('coe_roll_number_allocations')->whereIn('course_master_pk', $coursePks)->get(['course_master_pk', 'batch']);
        foreach ($runs as $run) {
            CourseParticipants::rollNumbersOn((int) $run->course_master_pk, (int) $run->batch)
                ->get(['rn.student_master_pk', 'rn.roll_number', 'rn.serial_number', 'rn.frozen_at', 'own.status as allocation_status'])
                ->each(function ($r) use (&$rolls, $run) {
                    $r->course_master_pk = (int) $run->course_master_pk;
                    $r->batch = (int) $run->batch;
                    $rolls[$run->course_master_pk . ':' . $run->batch . ':' . $r->student_master_pk] = $r;
                });
        }

        $codes = DB::table('coe_participant_codes as p')
            ->join('subject_master as sub', 'sub.pk', '=', 'p.subject_master_pk')
            ->whereIn('p.course_master_pk', $coursePks)
            ->get(['p.course_master_pk', 'p.batch', 'p.student_master_pk', 'p.unique_code', 'p.status', 'sub.subject_name'])
            ->groupBy(fn ($c) => $c->course_master_pk . ':' . $c->batch . ':' . $c->student_master_pk);

        $keys = array_unique(array_merge(array_keys($rolls), $codes->keys()->all()));
        if ($keys === []) {
            return collect();
        }

        $studentPks = array_values(array_unique(array_map(fn ($k) => (int) explode(':', $k)[2], $keys)));
        $students = DB::table('student_master as s')
            ->leftJoin('service_master as sv', 'sv.pk', '=', 's.service_master_pk')
            ->whereIn('s.pk', $studentPks)
            ->get(['s.pk', 's.display_name', 's.first_name', 's.middle_name', 's.last_name', 'sv.service_short_name'])
            ->keyBy('pk');
        $courses = DB::table('course_master')->whereIn('pk', $coursePks)->pluck('course_name', 'pk');

        $out = [];
        foreach ($keys as $key) {
            [$coursePk, $batch, $studentPk] = array_map('intval', explode(':', $key));
            $roll = $rolls[$key] ?? null;
            $student = $students[$studentPk] ?? null;
            $rollFrozen = $roll && ($roll->frozen_at || (int) $roll->allocation_status === RollNumberAllocation::STATUS_FROZEN);

            $base = [
                'course_pk'   => $coursePk,
                'course_name' => CourseParticipants::plainName($courses[$coursePk] ?? '—'),
                'batch'       => $batch,
                'student_pk'  => $studentPk,
                'ot_name'     => $this->studentName($student),
                'service'     => trim((string) ($student->service_short_name ?? '')) ?: '—',
                'roll_number' => $roll ? trim((string) $roll->roll_number) : '',
                'serial'      => $roll ? (int) $roll->serial_number : PHP_INT_MAX,
                'roll_status' => $roll ? ($rollFrozen ? 'Frozen' : 'Generated') : '—',
            ];

            $held = $codes[$key] ?? collect();
            if ($held->isEmpty()) {
                $out[] = $base + ['subject_name' => '—', 'unique_code' => '', 'code_status' => '—', 'mapping' => self::CODE_MISSING];

                continue;
            }

            foreach ($held as $code) {
                // Only a pair where BOTH sides are frozen is mapped; unfrozen values can still change.
                $codeFrozen = (int) $code->status === ParticipantCode::STATUS_FROZEN;
                $out[] = $base + [
                    'subject_name' => CourseParticipants::plainName($code->subject_name),
                    'unique_code'  => trim((string) $code->unique_code),
                    'code_status'  => ParticipantCode::statusLabel((int) $code->status),
                    'mapping'      => ! $roll ? self::ROLL_MISSING : ($rollFrozen && $codeFrozen ? self::MAPPED : self::NOT_FROZEN),
                ];
            }
        }

        return collect($out)
            ->sort(fn ($a, $b) => [strtolower($a['course_name']), $a['batch'], $a['serial'], $a['ot_name'], strtolower($a['subject_name'])]
                <=> [strtolower($b['course_name']), $b['batch'], $b['serial'], $b['ot_name'], strtolower($b['subject_name'])])
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
