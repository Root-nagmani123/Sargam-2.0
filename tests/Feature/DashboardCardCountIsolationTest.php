<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CourseRepositoryController;
use App\Models\FacultyMaster;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #356 F-007: one card's count throwing blanks that card only. Card A and C keep their
 * figures, card B returns null and is logged; a refusal (HTTP / authorisation) is not hidden.
 */
class DashboardCardCountIsolationTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as rollbackSetUp;
    }

    private User $user;

    private int $homeId;

    protected function setUp(): void
    {
        $this->rollbackSetUp();
        config(['cache.default' => 'array']);
        $this->homeId = (int) DB::table('sidebar_categories')->where('slug', 'home')->value('id');
        $user = $this->staffWithRole('Super Admin');
        $role = DB::table('roles')->insertGetId(['name' => 'Count isolation test '.uniqid(), 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('model_has_roles')->insert(['role_id' => $role, 'model_type' => User::class, 'model_id' => $user->pk]);
        foreach (['upcoming_events', 'course_repository_folders', 'total_inhouse_faculty'] as $key) {
            $card = DB::table('dashboard_cards')->where('key', $key)->value('id');
            if (! $card || ! $this->homeId) {
                $this->markTestSkipped("needs the $key card and the home module");
            }
            DB::table('role_dashboard_cards')->insertOrIgnore(['role_id' => $role, 'dashboard_card_id' => $card, 'sidebar_category_id' => $this->homeId]);
        }
        $this->user = $user->fresh();
    }

    private function fetch(): TestResponse
    {
        return $this->as($this->user, ['Super Admin'])->getJson(route('admin.dashboard.cards', ['category' => $this->homeId]));
    }

    public function test_a_throwing_count_blanks_only_its_own_card_and_is_logged(): void
    {
        $this->mock(CourseRepositoryController::class, fn ($m) => $m->shouldReceive('dashboardRootFolderCount')
            ->andThrow(new \RuntimeException('simulated schema drift')));
        Log::spy();

        $response = $this->fetch()->assertOk();
        $counts = $response->json('counts');

        $this->assertArrayHasKey('course_repository_folders', $counts);
        $this->assertNull($counts['course_repository_folders'], 'card B: null, not 0');
        $this->assertSame(Holiday::active()->upcoming()->count(), $counts['upcoming_events'], 'card A keeps its figure');
        $this->assertSame(FacultyMaster::where('active_inactive', 1)->where('faculty_type', 1)->count(), $counts['total_inhouse_faculty'], 'card C keeps its figure');
        // The grid draws "could not be loaded" for B, not a loading placeholder that never resolves.
        $this->assertStringContainsString('This figure could not be loaded', $response->json('grid'));

        Log::shouldHaveReceived('error')->withArgs(fn ($message, $context = []) => $message === 'Dashboard card count failed'
            && ($context['card'] ?? null) === 'course_repository_folders'
            && ($context['exception'] ?? null) instanceof \RuntimeException)->once();
    }

    public function test_a_refusal_inside_a_count_is_not_swallowed(): void
    {
        $this->mock(CourseRepositoryController::class, fn ($m) => $m->shouldReceive('dashboardRootFolderCount')
            ->andThrow(new HttpException(403, 'refused')));

        $this->fetch()->assertForbidden();
    }
}
