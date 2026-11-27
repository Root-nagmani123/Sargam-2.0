<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\Feature\Concerns\SeedsModuleDashboardCards;
use Tests\TestCase;

/** CR-Admin's "Course Repository Folders" card counts the root folders the repository page lists. Read-only. */
class CourseRepositoryDashboardCardTest extends TestCase
{
    use RollsBackAgainstAppDatabase;
    use SeedsModuleDashboardCards;

    public function test_card_matches_the_repository_root(): void
    {
        $this->seedModuleDashboardCards();
        $pk = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')->where('r.name', 'CR-Admin')->value('m.model_id');
        if (! $pk) {
            $this->markTestSkipped('no CR-Admin login');
        }
        $user = User::findOrFail($pk);
        $roles = $user->roles()->pluck('name')->all();
        $this->actingAs($user)->withSession(['user_roles' => $roles]);
        session(['user_roles' => $roles]);

        request()->query->set('category', (string) DB::table('sidebar_categories')->where('slug', 'setup')->value('id'));
        $cards = app(UserController::class)->dashboardCards(request())->getData(true)['counts'];

        $page = $this->get(route('course-repository.index'))->assertOk()->viewData('repositories');

        $this->assertSame($page->count(), $cards['course_repository_folders']);
    }
}
