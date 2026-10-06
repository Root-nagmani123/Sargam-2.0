<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * /student-faculty-feedback took any signed-in session without a token and read
 * submitted feedback by auth()->user()->user_id. For staff and faculty that id is
 * an employee / faculty pk, which can equal a trainee's student_master.pk
 * (PR #334 F-034). submitFeedback() wrote by the same id.
 */
class StudentFacultyFeedbackTraineeOnlyTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function nonTrainee(): User
    {
        $pk = DB::table('user_credentials')
            ->where('user_category', '!=', 'S')
            ->whereNotNull('user_category')
            ->whereNotNull('user_id')
            ->orderBy('pk')
            ->value('pk');

        if (! $pk) {
            $this->markTestSkipped('no non-trainee login');
        }

        return User::findOrFail($pk);
    }

    public function test_a_non_trainee_without_a_token_gets_403(): void
    {
        $this->actingAs($this->nonTrainee())
            ->get(route('feedback.get.studentFacultyFeedback'))
            ->assertForbidden();
    }

    public function test_a_non_trainee_cannot_submit_feedback(): void
    {
        $this->actingAs($this->nonTrainee())
            ->post(route('feedback.submit.feedback'), ['timetable_pk' => ['1_1']])
            ->assertForbidden();
    }

    public function test_a_trainee_without_a_token_is_served(): void
    {
        $this->actingAs($this->officerTrainee())
            ->get(route('feedback.get.studentFacultyFeedback'))
            ->assertOk();
    }
}
