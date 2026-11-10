<?php

namespace App\Services\COE;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Which OTs are on which course + batch, for COE Roll Numbers and Unique Participant Codes.
 *
 * ── Course ──
 *   An OT is on a course by student_master.course_master_pk, or by an active
 *   student_master_course__map row: an OT enrolled on a further course (a test or
 *   phase course) keeps their first course in student_master, so reading
 *   student_master alone leaves that course with nobody on it.
 *
 * ── Batch ──
 *   student_master.batch is the officer's service batch and is empty for most OTs.
 *   Empty, it falls back to the course's course_year — usually the drive's academic
 *   session too, which is what Result Management matches codes on.
 *
 * coe_roll_numbers and coe_participant_codes hold one row per OT (unique on
 * student_master_pk), so an OT on two courses can hold a roll number / code on only
 * one of them; see rollNumbersElsewhere() and codesElsewhere().
 */
class CourseParticipants
{
    /**
     * One row per OT per course: cp.student_master_pk, cp.course_master_pk, cp.batch.
     * Join it as a subquery: ->joinSub(CourseParticipants::query(), 'cp', ...).
     */
    public static function query(): Builder
    {
        $members = DB::table('student_master')
            ->whereNotNull('course_master_pk')
            ->select('pk as student_master_pk', 'course_master_pk')
            // UNION, not UNION ALL: an OT mapped to the course student_master already names counts once.
            ->union(DB::table('student_master_course__map')
                ->where('active_inactive', 1)
                ->select('student_master_pk', 'course_master_pk'));

        $rows = DB::query()->fromSub($members, 'm')
            ->join('student_master as s', 's.pk', '=', 'm.student_master_pk')
            ->join('course_master as c', 'c.pk', '=', 'm.course_master_pk')
            ->whereNotNull('c.course_name')
            ->where('c.course_name', '!=', '')
            ->select(
                'm.student_master_pk',
                'm.course_master_pk',
                DB::raw("COALESCE(NULLIF(s.batch, 0), CAST(NULLIF(TRIM(c.course_year), '') AS UNSIGNED)) as batch")
            );

        return DB::query()->fromSub($rows, 'cp')->where('cp.batch', '>', 0);
    }

    /**
     * Every course + batch with OTs on it, with its head count, course name order.
     *
     * @return Collection<int, object{course_master_pk:int, course_name:string, batch:int, n:int}>
     */
    public static function cohorts(): Collection
    {
        return DB::query()->fromSub(self::query(), 'cp')
            ->join('course_master as c', 'c.pk', '=', 'cp.course_master_pk')
            ->groupBy('cp.course_master_pk', 'c.course_name', 'cp.batch')
            ->orderBy('c.course_name')
            ->orderByDesc('cp.batch')
            ->get(['cp.course_master_pk', 'c.course_name', 'cp.batch', DB::raw('count(*) as n')])
            ->map(fn ($r) => (object) [
                'course_master_pk' => (int) $r->course_master_pk,
                'course_name' => (string) $r->course_name,
                'batch' => (int) $r->batch,
                'n' => (int) $r->n,
            ]);
    }

    /** The OTs of one course + batch, as a subquery of student_master_pk. */
    public static function of(int $courseId, int $batch): Builder
    {
        return DB::query()->fromSub(self::query(), 'cp')
            ->where('cp.course_master_pk', $courseId)
            ->where('cp.batch', $batch)
            ->select('cp.student_master_pk');
    }

    /** Whether the OT is on the course + batch. */
    public static function isOn(int $studentPk, int $courseId, int $batch): bool
    {
        return self::of($courseId, $batch)->where('cp.student_master_pk', $studentPk)->exists();
    }

    /**
     * Of $studentPks, the OTs whose roll number belongs to another course + batch.
     *
     * @param  array<int, int>  $studentPks
     * @return array<int, string> student pk => "roll number (course, batch)"
     */
    public static function rollNumbersElsewhere(array $studentPks, int $courseId, int $batch): array
    {
        if ($studentPks === []) {
            return [];
        }

        return DB::table('coe_roll_numbers as r')
            ->join('coe_roll_number_allocations as a', 'a.pk', '=', 'r.roll_number_allocation_pk')
            ->join('course_master as c', 'c.pk', '=', 'a.course_master_pk')
            ->whereIn('r.student_master_pk', $studentPks)
            ->where(fn ($q) => $q->where('a.course_master_pk', '!=', $courseId)->orWhere('a.batch', '!=', $batch))
            ->get(['r.student_master_pk', 'r.roll_number', 'c.course_name', 'a.batch'])
            ->mapWithKeys(fn ($r) => [(int) $r->student_master_pk => "{$r->roll_number} ({$r->course_name}, {$r->batch})"])
            ->all();
    }

    /**
     * Of $studentPks, the OTs whose Unique Participant Code belongs to another course + batch.
     *
     * @param  array<int, int>  $studentPks
     * @return array<int, string> student pk => "code (course, batch)"
     */
    public static function codesElsewhere(array $studentPks, int $courseId, int $batch): array
    {
        if ($studentPks === []) {
            return [];
        }

        return DB::table('coe_participant_codes as p')
            ->join('course_master as c', 'c.pk', '=', 'p.course_master_pk')
            ->whereIn('p.student_master_pk', $studentPks)
            ->where(fn ($q) => $q->where('p.course_master_pk', '!=', $courseId)->orWhere('p.batch', '!=', $batch))
            ->get(['p.student_master_pk', 'p.unique_code', 'c.course_name', 'p.batch'])
            ->mapWithKeys(fn ($r) => [(int) $r->student_master_pk => "{$r->unique_code} ({$r->course_name}, {$r->batch})"])
            ->all();
    }

    /** "3 OTs already hold … on another course (e.g. UC12 (FC100, 2025)) …" for a refusal. */
    public static function elsewhereMessage(array $elsewhere, string $what): string
    {
        $n = count($elsewhere);

        return sprintf(
            '%d OT(s) of this course already hold a %s on another course (e.g. %s). An OT can hold only one %s, so nothing was changed. Delete those first if they should move to this course.',
            $n, $what, implode(', ', array_slice(array_values($elsewhere), 0, 3)), $what
        );
    }
}
