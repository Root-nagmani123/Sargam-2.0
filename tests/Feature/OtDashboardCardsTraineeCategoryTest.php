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

    /**
     * The marks card's figure on $user's Home dashboard. The count runs only for a
     * card that is shown, so the card is assigned to a throwaway role of theirs
     * (rolled back); null when the card did not render.
     */
    private function marksCardFor(User $user, array $sessionRoles): mixed
    {
        $card = DB::table('dashboard_cards')->where('key', 'discipline_marks_deducted')->value('id');
        $home = DB::table('sidebar_categories')->where('slug', 'home')->value('id');
        if (! $card || ! $home) {
            $this->markTestSkipped('needs the discipline_marks_deducted card and the home module');
        }
        $role = DB::table('roles')->insertGetId([
            'name' => 'OT marks card test '.uniqid(), 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('model_has_roles')->insert(['role_id' => $role, 'model_type' => User::class, 'model_id' => $user->pk]);
        DB::table('role_dashboard_cards')->insert(['role_id' => $role, 'dashboard_card_id' => $card, 'sidebar_category_id' => $home]);

        $response = $this->as($user->fresh(), $sessionRoles)->get(route('admin.dashboard'));
        $response->assertOk();

        return collect($response->viewData('cardsToRender'))->firstWhere('key', 'discipline_marks_deducted')['count'] ?? null;
    }

    public function test_a_non_trainee_login_holding_the_ot_role_sees_no_marks(): void
    {
        $pk = DB::table('user_credentials')->where('user_category', '!=', 'S')
            ->whereNotNull('user_id')->orderBy('pk')->value('pk');
        if (! $pk) {
            $this->markTestSkipped('no non-trainee login');
        }
        $this->fakeMarks();

        $count = $this->marksCardFor(User::findOrFail($pk), ['Student-OT']);

        $this->assertNotNull($count, 'the card must render, or this proves nothing');
        $this->assertEquals(0, $count);
    }

    public function test_a_trainee_login_still_sees_their_marks(): void
    {
        $this->fakeMarks();

        $this->assertEquals(7, $this->marksCardFor($this->officerTrainee(), ['Student-OT']));
    }
}
