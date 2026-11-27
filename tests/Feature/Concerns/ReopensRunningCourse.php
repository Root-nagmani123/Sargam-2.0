<?php

namespace Tests\Feature\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * For tests that need a course running today. The dev databases' courses age out (none ran on
 * testsargam8 or testsargam9 after 2026-11-25), and a test that then skips still reports OK
 * (PR #356 F-014, F-016). Use inside RollsBackAgainstAppDatabase: the end_date change is rolled back.
 */
trait ReopensRunningCourse
{
    /**
     * When no active course with active students (narrowed by $where, applied to `course_master as c`)
     * has end_date >= today, move the most recently ended one's end_date 30 days ahead.
     */
    protected function ensureRunningCourse(?\Closure $where = null): void
    {
        $courses = fn () => DB::table('course_master as c')
            ->where('c.active_inactive', 1)
            ->whereExists(fn ($q) => $q->from('student_master_course__map as s')
                ->whereColumn('s.course_master_pk', 'c.pk')->where('s.active_inactive', 1))
            ->when($where, $where);
        if ($courses()->where('c.end_date', '>=', now()->toDateString())->exists()) {
            return;
        }
        $pk = $courses()->orderByDesc('c.end_date')->orderByDesc('c.pk')->value('c.pk');
        if (! $pk) {
            $this->markTestSkipped('no course with active students to run');
        }
        DB::table('course_master')->where('pk', $pk)->update(['end_date' => now()->addDays(30)->toDateString()]);
    }
}
