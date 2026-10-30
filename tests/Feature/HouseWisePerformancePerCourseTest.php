<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\MemoDiscipline;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * House wise Performance added each OT's total across every running course to
 * every house they belonged to. A house is a group on one course, so an OT in
 * house X on course A and house Y on course B, with 5 marks lost on course A,
 * put 5 on both X and Y (PR #334 F-052).
 */
class HouseWisePerformancePerCourseTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function controllerCall(string $method)
    {
        $m = new \ReflectionMethod(UserController::class, $method);
        $m->setAccessible(true);

        return $m->invoke(app(UserController::class));
    }

    /** @return array{0:string,1:string} the two house names */
    private function otInTwoHousesWithMarksOnTheFirstCourse(): array
    {
        // Two running courses are needed; extend one more course if there is
        // only one (rolled back with everything else).
        $courses = $this->controllerCall('currentCourseIds')->values();
        if ($courses->count() < 2) {
            $extra = DB::table('course_master')->whereNotIn('pk', $courses->all() ?: [-1])->orderByDesc('pk')->value('pk');
            if ($extra) {
                DB::table('course_master')->where('pk', $extra)
                    ->update(['active_inactive' => 1, 'end_date' => now()->addYear()->toDateString()]);
            }
            $courses = $this->controllerCall('currentCourseIds')->values();
        }
        $houseType = DB::table('course_group_type_master')->where('active_inactive', 1)
            ->whereRaw('LOWER(type_name) LIKE ?', ['%house%'])->value('pk');
        if ($courses->count() < 2 || ! $houseType) {
            $this->markTestSkipped('needs two running courses and a House group type');
        }
        [$courseA, $courseB] = [(int) $courses[0], (int) $courses[1]];

        // A student with no closed discipline memo on either course, so the only
        // marks in play are the ones this test adds.
        $student = (int) DB::table('student_master as s')
            ->whereNotExists(fn ($q) => $q->from('discipline_memo_status as d')->whereColumn('d.student_master_pk', 's.pk'))
            ->whereNotExists(fn ($q) => $q->from('student_memo_status as m')->whereColumn('m.student_pk', 's.pk'))
            ->whereNotExists(fn ($q) => $q->from('student_notice_status as n')->whereColumn('n.student_pk', 's.pk'))
            ->orderBy('s.pk')->value('s.pk');
        if (! $student) {
            $this->markTestSkipped('no student without deductions');
        }

        $suffix = random_int(100000, 999999);
        $houses = ["Zq House X {$suffix}", "Zq House Y {$suffix}"];
        foreach ([$courseA, $courseB] as $i => $course) {
            $map = DB::table('group_type_master_course_master_map')->insertGetId([
                'type_name' => $houseType, 'group_name' => $houses[$i], 'course_name' => $course, 'active_inactive' => 1,
            ]);
            DB::table('student_course_group_map')->insert([
                'student_master_pk' => $student, 'group_type_master_course_master_map_pk' => $map, 'active_inactive' => 1,
            ]);
        }

        DB::table('discipline_memo_status')->insert([
            'course_master_pk' => $courseA,
            'student_master_pk' => $student,
            'date' => now()->toDateString(),
            'final_mark_deduction' => '5',
            'status' => MemoDiscipline::STATUS_CLOSED,
        ]);

        return $houses;
    }

    public function test_the_dashboard_panel_charges_only_the_house_on_that_course(): void
    {
        [$x, $y] = $this->otInTwoHousesWithMarksOnTheFirstCourse();

        $totals = $this->controllerCall('houseWisePerformance')->keyBy('house');

        $this->assertEquals(5, $totals[$x]['total']);
        $this->assertEquals(0, $totals[$y]['total'], "course A's marks must not reach the course-B house");
    }

    public function test_the_detail_page_lists_the_ot_only_under_the_house_on_that_course(): void
    {
        [$x, $y] = $this->otInTwoHousesWithMarksOnTheFirstCourse();

        $houses = $this->controllerCall('houseWisePerformanceRows')->keyBy('house');

        $this->assertEquals(5, $houses[$x]['total']);
        $this->assertFalse($houses->has($y), 'a house with nobody penalised on its course is not listed');
    }
}
