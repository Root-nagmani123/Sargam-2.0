<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * My Groups identified the trainee by the Student-OT session role alone and read
 * the groups of student_master pk = user_id. The Moodle token login grants that
 * role to any account; for a non-'S' login user_id is an employee / faculty pk
 * that can equal another trainee's, whose groups, roster and messaging it then
 * reached (PR #334 F-055, the F-047 rule).
 */
class MyGroupsTraineeCategoryTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** A non-'S' login whose user_id is made a member of an active group. @return array{0: User, 1: int} */
    private function nonTraineeInAGroup(): array
    {
        $userPk = DB::table('user_credentials')->where('user_category', '!=', 'S')
            ->whereNotNull('user_id')->where('user_id', '>', 0)->orderBy('pk')->value('pk');
        $mapPk = DB::table('group_type_master_course_master_map')->where('active_inactive', 1)
            ->orderBy('pk')->value('pk');
        if (! $userPk || ! $mapPk) {
            $this->markTestSkipped('no non-trainee login or no active group');
        }

        $user = User::findOrFail($userPk);

        // The trainee whose pk equals this login's user_id is in the group.
        DB::table('student_course_group_map')->insert([
            'student_master_pk' => (int) $user->user_id,
            'group_type_master_course_master_map_pk' => (int) $mapPk,
            'active_inactive' => 1,
        ]);

        return [$user, (int) $mapPk];
    }

    public function test_a_non_trainee_login_holding_the_ot_role_is_refused_the_roster(): void
    {
        [$user, $mapPk] = $this->nonTraineeInAGroup();

        $this->as($user, ['Student-OT'])
            ->getJson("/dashboard/my-groups/{$mapPk}/students")
            ->assertStatus(403);
    }

    public function test_a_non_trainee_login_holding_the_ot_role_cannot_message_the_group(): void
    {
        // A fresh in-memory limiter, so the route's throttle touches no shared cache.
        config(['cache.limiter' => 'array']);
        [$user, $mapPk] = $this->nonTraineeInAGroup();

        $this->as($user, ['Student-OT'])
            ->postJson("/dashboard/my-groups/{$mapPk}/students/message", ['channel' => 'email', 'message' => 'hi'])
            ->assertStatus(403);
    }

    public function test_a_non_trainee_login_holding_the_ot_role_is_sent_away_from_my_groups(): void
    {
        [$user] = $this->nonTraineeInAGroup();

        $this->as($user, ['Student-OT'])
            ->get(route('admin.dashboard.my-groups'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_a_trainee_still_reads_their_own_group(): void
    {
        $row = DB::table('user_credentials as u')
            ->join('student_course_group_map as scgm', 'scgm.student_master_pk', '=', 'u.user_id')
            ->join('group_type_master_course_master_map as g', 'g.pk', '=', 'scgm.group_type_master_course_master_map_pk')
            ->where('u.user_category', 'S')
            ->where('scgm.active_inactive', 1)
            ->where('g.active_inactive', 1)
            ->first(['u.pk as user_pk', 'g.pk as map_pk']);
        if (! $row) {
            $this->markTestSkipped('no trainee in an active group');
        }

        $this->as(User::findOrFail($row->user_pk), ['Student-OT'])
            ->getJson("/dashboard/my-groups/{$row->map_pk}/students")
            ->assertOk();
    }
}
