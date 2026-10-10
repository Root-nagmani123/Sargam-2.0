<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Discipline\OtMarksDeductedService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * The dashboard computed the marks-deducted and pending-feedback cards for
 * user_id whenever the login held an Officer Trainee role. user_id is a
 * student_master pk only for user_category 'S'; for a staff login holding the
 * role it is an employee / faculty pk that can equal another trainee's, whose
 * figures the card then showed (PR #334 F-047).
 */
class OtDashboardCardsTraineeCategoryTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** Every student "has" 7 marks deducted, so a figure that leaks is visible. */
    private function fakeMarks(): void
    {
        $mock = \Mockery::mock(OtMarksDeductedService::class)->makePartial();
        $mock->shouldReceive('totalFor')->andReturn(7.0);
        $this->app->instance(OtMarksDeductedService::class, $mock);
    }

    public function test_a_non_trainee_login_holding_the_ot_role_sees_no_marks(): void
    {
        $pk = DB::table('user_credentials')->where('user_category', '!=', 'S')
            ->whereNotNull('user_id')->orderBy('pk')->value('pk');
        if (! $pk) {
            $this->markTestSkipped('no non-trainee login');
        }
        $this->fakeMarks();

        $response = $this->as(User::findOrFail($pk), ['Student-OT'])->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertEquals(0, $response->viewData('disciplineMarksDeducted'));
    }

    public function test_a_trainee_login_still_sees_their_marks(): void
    {
        $this->fakeMarks();

        $response = $this->as($this->officerTrainee(), ['Student-OT'])->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertEquals(7, $response->viewData('disciplineMarksDeducted'));
    }
}
