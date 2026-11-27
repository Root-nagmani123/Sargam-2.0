<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * A card's figure is the number of rows on the page it opens. Each test takes a real login,
 * gives it the card on Home through a throwaway role (rolled back), reads the figure from
 * admin.dashboard.cards and the rows from the card's own link.
 */
class DashboardCardPageParityTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function giveCard(User $user, string $key): void
    {
        $card = DB::table('dashboard_cards')->where('key', $key)->value('id');
        $home = DB::table('sidebar_categories')->where('slug', 'home')->value('id');
        if (! $card || ! $home) {
            $this->markTestSkipped("needs the $key card and the home module");
        }
        $role = DB::table('roles')->insertGetId([
            'name' => 'Card parity test '.uniqid(), 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('model_has_roles')->insert(['role_id' => $role, 'model_type' => User::class, 'model_id' => $user->pk]);
        DB::table('role_dashboard_cards')->insert(['role_id' => $role, 'dashboard_card_id' => $card, 'sidebar_category_id' => $home]);
    }

    /** [figure, link] of $key on $user's Home dashboard. */
    private function card(User $user, array $roles, string $key): array
    {
        $this->giveCard($user, $key);
        $user = $user->fresh();
        $count = $this->as($user, $roles)->getJson(route('admin.dashboard.cards'))->assertOk()->json("counts.$key");
        $page = $this->as($user, $roles)->get(route('admin.dashboard'))->assertOk();
        $link = $page->viewData('cardsToRender')->firstWhere('key', $key)['link'] ?? null;
        $this->assertNotNull($link, "$key is on the dashboard");

        return [$count, $link];
    }

    public function test_pending_approval_one_counts_the_filtered_queue_it_opens(): void
    {
        // A department authority with a contractual request still waiting at Approval I, any date.
        $emp = DB::table('security_con_oth_id_apply')->where('id_status', 1)->where('depart_approval_status', 1)
            ->whereNotNull('department_approval_emp_pk')->pluck('department_approval_emp_pk')
            ->first(fn ($pk) => User::where('user_id', $pk)->exists());
        if (! $emp) {
            $this->markTestSkipped('no authority login with a request pending at Approval I');
        }
        $user = User::where('user_id', $emp)->first();
        $roles = $user->roles->pluck('name')->all();

        [$count, $link] = $this->card($user, $roles, 'pending_id_approval1');
        $this->assertStringContainsString('pending=1', $link);
        $this->assertStringContainsString('type=idcard', $link);
        $requests = $this->as($user, $roles)->get($link)->assertOk()->viewData('requests');

        $this->assertSame($requests->total(), $count);
        $this->assertTrue(collect($requests->items())->every(fn ($r) => $r->status === 'Pending' && empty($r->is_view_only)), 'only rows awaiting this approver');
    }

    public function test_upcoming_events_lists_the_holidays_it_counts(): void
    {
        $user = $this->staffWithRole('Super Admin');
        [$count, $link] = $this->card($user, ['Super Admin'], 'upcoming_events');

        $page = $this->as($user->fresh(), ['Super Admin'])->get($link)->assertOk();
        $this->assertSame($page->viewData('holidays')->count(), $count);
        $page->assertDontSee('Black Friday');
        foreach ($page->viewData('holidays') as $holiday) {
            $page->assertSee(e($holiday->holiday_name), false);
        }
    }

    public function test_session_details_lists_the_sessions_it_counts(): void
    {
        $pk = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->join('user_credentials as u', 'u.pk', '=', 'm.model_id')
            ->join('faculty_master as f', 'f.employee_master_pk', '=', 'u.user_id')
            ->where('r.name', 'Faculty')->where('m.model_type', User::class)
            ->whereExists(fn ($q) => $q->from('timetable as t')->where('t.active_inactive', 1)
                ->whereRaw('FIND_IN_SET(f.pk, t.faculty_master) OR JSON_CONTAINS(t.faculty_master, JSON_QUOTE(CAST(f.pk AS CHAR)))'))
            ->orderBy('m.model_id')->value('m.model_id');
        if (! $pk) {
            $this->markTestSkipped('no Faculty login with sessions');
        }
        $user = User::find($pk);
        $roles = $user->roles->pluck('name')->all();

        [$count, $link] = $this->card($user, $roles, 'session_details');
        $sessions = $this->as($user->fresh(), $roles)->get($link)->assertOk()->viewData('sessions');

        $this->assertGreaterThan(0, $count);
        $this->assertSame($sessions->count(), $count);
    }

    public function test_total_students_opens_the_roster_it_counts_for_super_admin(): void
    {
        $user = $this->staffWithRole('Super Admin');
        [$count, $link] = $this->card($user, ['Super Admin'], 'total_students');

        $this->assertSame(route('admin.dashboard.ot-participants'), $link);
        $listed = $this->as($user->fresh(), ['Super Admin'])
            ->getJson($link.'?draw=1&start=0&length=1', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('recordsTotal');
        $this->assertSame($listed, $count);
    }
}
