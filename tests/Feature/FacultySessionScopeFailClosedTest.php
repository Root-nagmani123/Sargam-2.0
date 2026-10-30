<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Timetable\FacultySessionScope;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * The faculty "own sessions only" lock must fail CLOSED. Every consumer reads
 * a null lock as "may see everyone", so a faculty-portal user whose login
 * resolves to no faculty_master row is locked to NO_FACULTY instead and sees
 * nothing (PR #334 F-012).
 */
class FacultySessionScopeFailClosedTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** A staff login that resolves to no faculty_master row. */
    private function unlinkedPortalUser(): User
    {
        foreach (DB::table('user_credentials')->where('user_category', 'E')->orderBy('pk')->limit(200)->pluck('pk') as $pk) {
            $user = User::find($pk);
            $this->actingAs($user);
            session(['user_roles' => ['Guest Faculty']]);

            if (get_auth_faculty_master_pk() === null) {
                return $user;
            }
        }

        $this->markTestSkipped('every sampled staff login resolves to a faculty record');
    }

    public function test_an_unlinked_portal_user_is_locked_to_no_faculty(): void
    {
        $this->unlinkedPortalUser();

        $this->assertSame(FacultySessionScope::NO_FACULTY, FacultySessionScope::lockedFacultyPk());
    }

    public function test_the_no_faculty_lock_matches_no_session(): void
    {
        $query = DB::table('timetable as t');
        FacultySessionScope::applyFaculty($query, FacultySessionScope::NO_FACULTY);
        $this->assertSame(0, $query->count(), 'NO_FACULTY must select no timetable session');

        $withSupporting = DB::table('timetable as t');
        FacultySessionScope::applyFacultyWithSupporting($withSupporting, FacultySessionScope::NO_FACULTY);
        $this->assertSame(0, $withSupporting->count());
    }

    public function test_an_unlinked_portal_user_sees_no_feedback_rows(): void
    {
        $this->unlinkedPortalUser();

        $query = DB::table('topic_feedback as tf');
        $method = new \ReflectionMethod(\App\Http\Controllers\Admin\FeedbackController::class, 'applyFacultyViewOwnerScope');
        $method->setAccessible(true);
        $method->invoke(app(\App\Http\Controllers\Admin\FeedbackController::class), $query);

        $this->assertSame(0, $query->count(), 'the faculty_view owner scope must return nothing for an unlinked portal user');
    }

    public function test_super_admin_stays_unlocked(): void
    {
        $this->as($this->userWithRole('Super Admin'), ['Super Admin', 'Guest Faculty']);
        session(['user_roles' => ['Super Admin', 'Guest Faculty']]);

        $this->assertNull(FacultySessionScope::lockedFacultyPk());
    }
}
