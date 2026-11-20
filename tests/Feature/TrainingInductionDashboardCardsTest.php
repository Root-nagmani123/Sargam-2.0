<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\MemoDiscipline;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Training-Induction cards show the number of rows the page behind their link lists:
 * Leave Approval's Pending tab, and the discipline memo listing on Status "Open" (Recorded
 * or Memo Sent) across all dates. Read-only.
 */
class TrainingInductionDashboardCardsTest extends TestCase
{
    /** @return array<string, int> */
    private function cardsOn(User $user, string $tabSlug): array
    {
        request()->query->set('category', (string) DB::table('sidebar_categories')->where('slug', $tabSlug)->value('id'));

        return app(UserController::class)->dashboardCards(request())->getData(true)['counts'];
    }

    public function test_training_cards_match_the_pages_they_open(): void
    {
        $pk = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')->where('r.name', 'Training-Induction')->value('m.model_id');
        if (! $pk) {
            $this->markTestSkipped('no Training-Induction login');
        }
        $user = User::findOrFail($pk);
        $roles = $user->roles()->pluck('name')->all();
        $this->actingAs($user)->withSession(['user_roles' => $roles]);
        session(['user_roles' => $roles]);

        $home = $this->cardsOn($user, 'home');
        $leavePage = $this->getJson(route('faculty.leave-approval.index', ['draw' => 1, 'start' => 0, 'length' => 10, 'status' => 1]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('recordsTotal');
        $this->assertSame($leavePage, $home['pending_leave_approvals']);
        $this->assertArrayHasKey('medical_exemptions_today', $home);

        $academics = $this->cardsOn($user, 'academics');
        $memoPage = $this->get(route('memo.discipline.index', ['status' => 'open', 'from_date' => '', 'to_date' => '']))
            ->assertOk()->viewData('memos');
        $this->assertSame($memoPage->total(), $academics['open_discipline_memos']);

        // "Open" is Recorded + Memo Sent on the same page, not a status of its own.
        $byStatus = fn (string $s) => $this->get(route('memo.discipline.index', ['status' => $s, 'from_date' => '', 'to_date' => '']))->viewData('memos')->total();
        $this->assertSame(array_sum(array_map(fn ($s) => $byStatus((string) $s), MemoDiscipline::OPEN_STATUSES)), $memoPage->total());
    }
}
