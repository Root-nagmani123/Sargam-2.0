<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo data for the House wise Performance panel and its detail page.
 *
 * NOT for production. The panel draws houses from the courses running NOW, and
 * on a development copy every course with houses mapped has usually already
 * ended — so the panel renders its empty state and the screen cannot be looked
 * at. This puts four houses on the running course, fills them with officer
 * trainees, and books a few closed discipline memos against them so the totals
 * differ and the ordering is visible.
 *
 * Idempotent: re-running reuses the rows it created rather than duplicating
 * them. Everything it writes is identifiable —
 *   - mapping rows:    group_type_master_course_master_map.group_name in HOUSES,
 *                      on the running course, under the house group type
 *   - membership rows: student_course_group_map pointing at those mappings
 *   - deductions:      discipline_memo_status.remarks = self::MARKER
 * so what it adds can be removed again on those three predicates.
 *
 *   php artisan db:seed --class=DummyHousePerformanceSeeder
 */
class DummyHousePerformanceSeeder extends Seeder
{
    /** Tags every row this seeder books, so the demo data can be found and removed. */
    private const MARKER = 'Dummy data - House wise Performance demo';

    /**
     * House => marks to book against it. Deliberately uneven: a panel where
     * every house shows the same figure proves nothing about the ordering.
     * Each entry is split into separate memos, the way real deductions arrive.
     */
    private const HOUSES = [
        'Nanda Devi' => [2, 2],
        'Kangchendjunga' => [5, 3, 9],
        'Namcha Barwa' => [10, 3],
        'Stok Kangri' => [5, 5, 10, 3],
    ];

    /** Officer trainees placed in each house. */
    private const MEMBERS_PER_HOUSE = 8;

    public function run(): void
    {
        $course = DB::table('course_master')
            ->where('active_inactive', 1)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->orderBy('pk')
            ->first(['pk', 'course_name']);

        if (! $course) {
            $this->command?->warn('No running course (active, end_date >= today) - nothing seeded.');

            return;
        }

        // The same rule the dashboard uses to decide what counts as a house.
        $houseTypePk = DB::table('course_group_type_master')
            ->where('active_inactive', 1)
            ->whereRaw('LOWER(type_name) LIKE ?', ['%house%'])
            ->value('pk');

        if (! $houseTypePk) {
            $this->command?->warn('No active house group type in course_group_type_master - nothing seeded.');

            return;
        }

        // Real officer trainees, so the detail page shows real names. They are
        // taken from whichever course has the most of them: on a development
        // copy the running course usually has no student_master rows at all,
        // and the house mappings already in the database have the same split.
        $sourceCourse = DB::table('student_master')
            ->select('course_master_pk', DB::raw('COUNT(*) AS c'))
            ->whereNotNull('course_master_pk')
            ->groupBy('course_master_pk')
            ->orderByDesc('c')
            ->value('course_master_pk');

        $students = DB::table('student_master')
            ->where('course_master_pk', $sourceCourse)
            ->orderBy('pk')
            ->limit(count(self::HOUSES) * self::MEMBERS_PER_HOUSE)
            ->pluck('pk');

        if ($students->isEmpty()) {
            $this->command?->warn('No student_master rows to place in houses - nothing seeded.');

            return;
        }

        $now = now();
        $chunks = $students->chunk(self::MEMBERS_PER_HOUSE)->values();
        $houses = 0;
        $members = 0;
        $memos = 0;

        foreach (array_keys(self::HOUSES) as $i => $house) {
            $mappingPk = DB::table('group_type_master_course_master_map')
                ->where('type_name', $houseTypePk)
                ->where('course_name', $course->pk)
                ->where('group_name', $house)
                ->value('pk');

            if (! $mappingPk) {
                $mappingPk = DB::table('group_type_master_course_master_map')->insertGetId([
                    'type_name' => $houseTypePk,
                    'group_name' => $house,
                    'course_name' => $course->pk,
                    'active_inactive' => 1,
                    'created_date' => $now,
                    'modified_date' => $now,
                ]);
                $houses++;
            }

            // ->values(): chunk() keeps the original keys, so a later chunk has
            // no index 0 and the memo loop below would fail on it.
            $roster = ($chunks[$i] ?? collect())->values();

            foreach ($roster as $studentPk) {
                $already = DB::table('student_course_group_map')
                    ->where('group_type_master_course_master_map_pk', $mappingPk)
                    ->where('student_master_pk', $studentPk)
                    ->exists();

                if (! $already) {
                    DB::table('student_course_group_map')->insert([
                        'student_master_pk' => $studentPk,
                        'group_type_master_course_master_map_pk' => $mappingPk,
                        'active_inactive' => 1,
                        'created_date' => $now,
                        'modified_date' => $now,
                    ]);
                    $members++;
                }
            }

            // Deductions are scoped to the running course by OtMarksDeductedService,
            // so the memo must be booked on that course, not on the students' own.
            // Status 3 = closed; an open case carries no mark yet.
            $booked = DB::table('discipline_memo_status')
                ->where('remarks', self::MARKER)
                ->where('course_master_pk', $course->pk)
                ->whereIn('student_master_pk', $roster)
                ->count();

            if ($booked > 0 || $roster->isEmpty()) {
                continue;
            }

            foreach (self::HOUSES[$house] as $n => $marks) {
                $studentPk = $roster[$n % $roster->count()];

                DB::table('discipline_memo_status')->insert([
                    'course_master_pk' => $course->pk,
                    'student_master_pk' => $studentPk,
                    'discipline_master_pk' => DB::table('discipline_master')->orderBy('pk')->value('pk'),
                    'date' => $now->copy()->subDays(($n + 1) * 3)->toDateString(),
                    'mark_deduction_submit' => (string) $marks,
                    'final_mark_deduction' => (string) $marks,
                    'minor_major' => 1,
                    'remarks' => self::MARKER,
                    'conclusion_remark' => self::MARKER,
                    'status' => 3,
                    'created_date' => $now,
                    'modified_date' => $now,
                ]);
                $memos++;
            }
        }

        $this->command?->info(sprintf(
            'House wise Performance demo data on "%s" (pk %d): %d houses, %d memberships, %d closed memos added.',
            $course->course_name,
            $course->pk,
            $houses,
            $members,
            $memos
        ));
    }
}
