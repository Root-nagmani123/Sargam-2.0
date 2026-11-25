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
 * coe_roll_numbers holds one roll number per OT per course run (unique on allocation +
 * student): an OT on FC and Phase-I has one on each, the same for every subject of that
 * course (rollNumbersOn()). coe_participant_codes holds one code per OT per subject of a course.
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
     * The roll numbers of a course (+ batch): a builder over coe_roll_numbers `rn` joined to
     * the run that issued them (`own`, coe_roll_number_allocations). One per OT per course;
     * the same OT may hold another number on another course.
     */
    public static function rollNumbersOn(int $courseId, ?int $batch = null): Builder
    {
        return DB::table('coe_roll_numbers as rn')
            ->join('coe_roll_number_allocations as own', 'own.pk', '=', 'rn.roll_number_allocation_pk')
            ->where('own.course_master_pk', $courseId)
            ->when($batch !== null, fn ($q) => $q->where('own.batch', $batch));
    }

    /**
     * A name as text: some course names were HTML-encoded more than once on import
     * ("&amp;quot;Land…&amp;quot;"), so decode until it stops changing. Blade escapes it again.
     */
    public static function plainName(?string $name): string
    {
        $value = (string) $name;
        for ($i = 0; $i < 3; $i++) {
            $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
            if ($decoded === $value) {
                break;
            }
            $value = $decoded;
        }

        return trim(preg_replace('/\s+/u', ' ', $value));
    }
}
