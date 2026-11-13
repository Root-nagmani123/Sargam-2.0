<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * /memo/discipline/my-marks reads rows by the login's user_id. That is a
 * student_master.pk only for an Officer Trainee (user_category 'S'); for staff
 * and faculty it is an employee / faculty pk that can equal some student's pk
 * (400 such logins in the development copy). Non-OT logins are refused
 * (PR #334 F-007).
 */
class MyMarksAccessTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const URL = '/memo/discipline/my-marks';

    public function test_a_non_ot_login_whose_user_id_collides_with_a_student_is_refused(): void
    {
        $pk = DB::table('user_credentials as u')
            ->join('student_master as sm', 'sm.pk', '=', 'u.user_id')
            ->whereRaw("COALESCE(u.user_category, '') <> 'S'")
            ->value('u.pk');

        if (! $pk) {
            $this->markTestSkipped('no non-OT login whose user_id equals a student pk');
        }

        // Even with an OT session role, the category decides.
        $this->as(User::findOrFail($pk), ['Student-OT'])->get(self::URL)->assertForbidden();
    }

    public function test_an_officer_trainee_sees_their_own_page(): void
    {
        $this->as($this->officerTrainee(), ['Student-OT'])->get(self::URL)->assertOk();
    }
}
