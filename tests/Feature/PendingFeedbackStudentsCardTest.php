<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PA / TA and RF's "Pending Feedback Students" card shows the student total the Pending
 * Feedback – Students page shows on landing: its default course (pre-selected in the Course
 * filter), Active tab, Feedback "Not given". Read-only.
 */
class PendingFeedbackStudentsCardTest extends TestCase
{
    public function test_card_matches_the_page_landing_total(): void
    {
        $pk = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')->where('r.name', 'PA')->value('m.model_id');
        if (! $pk) {
            $this->markTestSkipped('no PA login');
        }
        $user = User::findOrFail($pk);
        $roles = $user->roles()->pluck('name')->all();
        $this->actingAs($user)->withSession(['user_roles' => $roles]);
        session(['user_roles' => $roles]);

        request()->query->set('category', (string) DB::table('sidebar_categories')->where('slug', 'home')->value('id'));
        $cards = app(UserController::class)->dashboardCards(request())->getData(true)['counts'];

        // The course the page pre-selects, then the request its first load sends.
        $defaultCourse = $this->get(route('admin.feedback.pending.students'))->assertOk()->viewData('activeCourse');
        $pageTotal = $this->getJson(route('admin.feedback.pending.grouped', [
            'course_pk' => $defaultCourse,
            'course_type' => 'active',
            'filter_feedback_state' => 'not_given',
            'page' => 1,
            'per_page' => 20,
        ]))->assertOk()->json('total');

        $this->assertSame((int) $pageTotal, $cards['pending_feedback_students']);
    }
}
