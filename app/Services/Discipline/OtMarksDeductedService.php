<?php

namespace App\Services\Discipline;

use App\Models\MemoDiscipline;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every mark an OT has actually lost, from both places the Academy takes them.
 *
 *   Discipline Memo   discipline_memo_status.final_mark_deduction
 *   Memo / Notice     student_memo_status / student_notice_status.mark_of_deduction
 *
 * The dashboard card and the page it opens both read this, so the figure on the
 * tile is the figure the rows add up to.
 *
 * Only CLOSED records count. A mark is written at conclusion — before that a
 * discipline memo carries only what was PROPOSED (mark_deduction_submit), and a
 * memo or notice carries nothing at all — so an open case has no penalty yet.
 *
 * A Notice can be closed as a notice or escalated into a Memo, and the pair is
 * ONE case: student_memo_status.student_notice_status_pk points back at the
 * notice it came from. Every memo on record was raised that way, so adding the
 * two tables would charge the same case twice. The memo is the later decision
 * and wins — but only where it recorded a figure: one case on record closes its
 * memo with no mark while its notice holds 5, and that 5 is still what was taken.
 */
class OtMarksDeductedService
{
    /** Closed, per module. See MemoDiscipline and CourseAttendanceNoticeMapController. */
    private const MEMO_NOTICE_CLOSED = 2;

    /**
     * What the card shows: every closed deduction against this OT, summed.
     */
    public function totalFor(int $studentPk): float
    {
        return (float) $this->rowsFor($studentPk)->sum('marks');
    }

    /**
     * The same total as {@see totalFor()}, for many OTs at once and optionally
     * scoped to a set of courses — what the House Wise Performance panel adds up.
     *
     * rowsFor() per student would be two queries each, so a house of 400 would cost
     * 800. This runs three, and deliberately reuses the rules above rather than
     * restating them: closed only, and a memo supersedes the notice it came from
     * except where the memo recorded no figure.
     *
     * @param  list<int>  $studentPks
     * @param  list<int>|null  $courseIds  null = every course
     * @return Collection<int, float>  student_master_pk => marks deducted
     */
    public function totalsForStudents(array $studentPks, ?array $courseIds = null): Collection
    {
        if ($studentPks === []) {
            return collect();
        }

        $scope = fn ($query, string $column) => $courseIds === null
            ? $query
            : $query->whereIn($column, $courseIds ?: [-1]);

        $totals = [];
        $add = function ($studentPk, $marks) use (&$totals) {
            $pk = (int) $studentPk;
            if ($pk > 0) {
                $totals[$pk] = ($totals[$pk] ?? 0.0) + (float) $marks;
            }
        };

        // Discipline memos — final_mark_deduction on closed records.
        $discipline = $scope(
            DB::table('discipline_memo_status')
                ->whereIn('student_master_pk', $studentPks)
                ->where('status', MemoDiscipline::STATUS_CLOSED),
            'course_master_pk'
        )->selectRaw('student_master_pk, SUM(COALESCE(final_mark_deduction, 0)) AS marks')
            ->groupBy('student_master_pk')
            ->get();

        foreach ($discipline as $row) {
            $add($row->student_master_pk, $row->marks);
        }

        // Closed memos — their own figure, falling back to the notice they came from.
        $memos = $scope(
            DB::table('student_memo_status as m')
                ->leftJoin('student_notice_status as n', 'n.pk', '=', 'm.student_notice_status_pk')
                ->whereIn('m.student_pk', $studentPks)
                ->where('m.status', self::MEMO_NOTICE_CLOSED),
            'm.course_master_pk'
        )->get(['m.student_pk', 'm.mark_of_deduction', 'n.mark_of_deduction as notice_mark']);

        foreach ($memos as $row) {
            $add($row->student_pk, $row->mark_of_deduction !== null && $row->mark_of_deduction !== ''
                ? $row->mark_of_deduction
                : ($row->notice_mark ?: 0));
        }

        // Closed notices no closed memo has already accounted for. A notice carries
        // its student directly or through the attendance record it was raised off.
        $notices = $scope(
            DB::table('student_notice_status as n')
                ->leftJoin('course_student_attendance as csa', 'csa.pk', '=', 'n.course_student_attendance_pk')
                ->where(function ($q) use ($studentPks) {
                    $q->whereIn('n.student_pk', $studentPks)
                        ->orWhereIn('csa.Student_master_pk', $studentPks);
                })
                ->where('n.status', self::MEMO_NOTICE_CLOSED)
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('student_memo_status as sms')
                        ->whereColumn('sms.student_notice_status_pk', 'n.pk')
                        ->where('sms.status', self::MEMO_NOTICE_CLOSED);
                }),
            'n.course_master_pk'
        )->selectRaw('COALESCE(csa.Student_master_pk, n.student_pk) AS spk, SUM(COALESCE(n.mark_of_deduction, 0)) AS marks')
            ->groupBy(DB::raw('COALESCE(csa.Student_master_pk, n.student_pk)'))
            ->get();

        foreach ($notices as $row) {
            $add($row->spk, $row->marks);
        }

        // A student with no deduction still belongs in the result, at zero.
        return collect($studentPks)
            ->mapWithKeys(fn ($pk) => [(int) $pk => (float) ($totals[(int) $pk] ?? 0.0)]);
    }

    /**
     * One row per deduction, newest first — what the page lists.
     *
     * @return Collection<int, array{date: ?string, type: string, course: string, detail: string, status: string, marks: float}>
     */
    public function rowsFor(int $studentPk): Collection
    {
        return $this->disciplineRows($studentPk)
            ->concat($this->memoNoticeRows($studentPk))
            ->sortByDesc(fn (array $row) => $row['date'] ?? '')
            ->values();
    }

    /**
     * Closed discipline memos, with the discipline they were raised under.
     */
    private function disciplineRows(int $studentPk): Collection
    {
        return DB::table('discipline_memo_status as d')
            ->leftJoin('course_master as cm', 'cm.pk', '=', 'd.course_master_pk')
            ->leftJoin('discipline_master as dm', 'dm.pk', '=', 'd.discipline_master_pk')
            ->where('d.student_master_pk', $studentPk)
            ->where('d.status', MemoDiscipline::STATUS_CLOSED)
            ->orderByDesc('d.date')
            ->get([
                'd.pk', 'd.date', 'd.final_mark_deduction', 'd.minor_major',
                'd.conclusion_remark', 'd.remarks',
                'cm.course_name', 'dm.discipline_name',
            ])
            ->map(fn ($r) => [
                'date' => $r->date,
                'type' => 'Discipline Memo',
                'course' => (string) ($r->course_name ?? '—'),
                'detail' => trim((string) ($r->discipline_name ?? ''))
                    ?: trim((string) ($r->remarks ?? '')) ?: '—',
                'category' => match ((int) $r->minor_major) {
                    2 => 'Major',
                    1 => 'Minor',
                    default => '',
                },
                'remark' => (string) ($r->conclusion_remark ?? ''),
                'marks' => (float) ($r->final_mark_deduction ?: 0),
            ]);
    }

    /**
     * Closed Memo/Notice cases: the memo where there is one, otherwise the notice,
     * and never both.
     */
    private function memoNoticeRows(int $studentPk): Collection
    {
        $memos = DB::table('student_memo_status as m')
            ->leftJoin('course_master as cm', 'cm.pk', '=', 'm.course_master_pk')
            ->leftJoin('student_notice_status as n', 'n.pk', '=', 'm.student_notice_status_pk')
            ->where('m.student_pk', $studentPk)
            ->where('m.status', self::MEMO_NOTICE_CLOSED)
            ->get([
                'm.pk', 'm.date', 'm.mark_of_deduction', 'm.conclusion_remark',
                'm.student_notice_status_pk',
                'n.mark_of_deduction as notice_mark', 'n.subject_topic as notice_topic',
                'cm.course_name',
            ])
            ->map(fn ($r) => [
                'date' => $r->date,
                'type' => 'Memo',
                'course' => (string) ($r->course_name ?? '—'),
                'detail' => trim((string) ($r->notice_topic ?? '')) ?: 'Memo / Notice',
                'category' => '',
                'remark' => (string) ($r->conclusion_remark ?? ''),
                // The memo's own figure, falling back to the notice it came from.
                'marks' => (float) ($r->mark_of_deduction !== null && $r->mark_of_deduction !== ''
                    ? $r->mark_of_deduction
                    : ($r->notice_mark ?: 0)),
            ]);

        // Notices the memo above has not already accounted for: no memo at all, or
        // one that is still open. A notice concluded at 5 while its memo is still
        // being argued has had that 5 taken — dropping it because a memo exists
        // would lose a real deduction (one case on record is exactly this).
        $notices = DB::table('student_notice_status as n')
            ->leftJoin('course_student_attendance as csa', 'csa.pk', '=', 'n.course_student_attendance_pk')
            ->leftJoin('course_master as cm', 'cm.pk', '=', 'n.course_master_pk')
            ->where(function ($q) use ($studentPk) {
                $q->where('n.student_pk', $studentPk)
                    ->orWhere('csa.Student_master_pk', $studentPk);
            })
            ->where('n.status', self::MEMO_NOTICE_CLOSED)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('student_memo_status as sms')
                    ->whereColumn('sms.student_notice_status_pk', 'n.pk')
                    ->where('sms.status', self::MEMO_NOTICE_CLOSED);
            })
            ->get([
                'n.pk', 'n.date_ as date', 'n.mark_of_deduction', 'n.conclusion_remark',
                'n.subject_topic', 'cm.course_name',
            ])
            ->map(fn ($r) => [
                'date' => $r->date,
                'type' => 'Notice',
                'course' => (string) ($r->course_name ?? '—'),
                'detail' => trim((string) ($r->subject_topic ?? '')) ?: 'Notice',
                'category' => '',
                'remark' => (string) ($r->conclusion_remark ?? ''),
                'marks' => (float) ($r->mark_of_deduction ?: 0),
            ]);

        return $memos->concat($notices);
    }
}
