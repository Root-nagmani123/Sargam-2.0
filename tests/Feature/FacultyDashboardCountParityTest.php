<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * The faculty dashboard's My Counsellees / House Wise Details cards count with
 * one COUNT(DISTINCT) instead of hydrating facultyGroupRows() twice per load
 * (PR #334 F-010). The number must stay exactly what the list behind the card
 * shows: same mappings, same membership filter, same "student must exist" rule.
 */
class FacultyDashboardCountParityTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public function test_the_count_query_matches_the_rows_for_every_faculty(): void
    {
        $controller = app(UserController::class);
        $rows = new \ReflectionMethod($controller, 'facultyGroupRows');
        $rows->setAccessible(true);
        $count = new \ReflectionMethod($controller, 'facultyGroupStudentCount');
        $count->setAccessible(true);

        $faculty = DB::table('group_type_master_course_master_map')
            ->where('active_inactive', 1)
            ->where('facility_id', '>', 0)
            ->distinct()
            ->pluck('facility_id');

        if ($faculty->isEmpty()) {
            $this->markTestSkipped('no faculty mapped to a group');
        }

        $nonZero = 0;
        foreach ($faculty as $facultyPk) {
            foreach ([['%counsel%', 'counsellor_group_name'], ['%house%', 'house_group_name']] as [$like, $label]) {
                foreach ([true, false] as $currentOnly) {
                    $expected = $rows->invoke($controller, (int) $facultyPk, $like, $label, $currentOnly)
                        ->pluck('student_master_pk')->filter()->unique()->count();
                    $actual = $count->invoke($controller, (int) $facultyPk, $like, $currentOnly);

                    $this->assertSame($expected, $actual, "faculty {$facultyPk}, {$like}, currentOnly=" . (int) $currentOnly);
                    $nonZero += $expected > 0 ? 1 : 0;
                }
            }
        }

        if ($nonZero === 0) {
            $this->markTestIncomplete('every comparison was 0 = 0; parity is unproven on this data');
        }
    }
}
