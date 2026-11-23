<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Two role-name mismatches that zeroed dashboard cards:
 *  - the Security approval code checks 'Security Card' / 'Admin Security', but the roles are
 *    'Security Approver II' / 'III' (hasRole() now reads one as the other);
 *  - training checks named 'Training Induction Admin', but the role is 'Training-Induction'.
 * Each card must equal the rows on the page it opens. Assignments on a throwaway role, rolled back.
 */
class SecurityAndTrainingCardsTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const SECURITY_CARDS = [
        'pending_permanent_id', 'pending_contractual_id', 'duplicate_permanent_id',
        'duplicate_contractual_id', 'requested_family_id', 'requested_vehicle_pass',
    ];

    private function loginWithRole(string $role): User
    {
        $pk = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->join('user_credentials as u', 'u.pk', '=', 'm.model_id')
            ->where('r.name', $role)->where('m.model_type', User::class)
            ->where(fn ($q) => $q->whereNull('u.user_category')->orWhere('u.user_category', '!=', 'S'))
            ->orderBy('m.model_id')->value('m.model_id');
        if (! $pk) {
            $this->markTestSkipped("no login holds '$role'");
        }

        return User::find($pk);
    }

    private function giveCards(User $user, array $keys): void
    {
        $home = DB::table('sidebar_categories')->where('slug', 'home')->value('id');
        $role = DB::table('roles')->insertGetId(['name' => 'Cards test '.uniqid(), 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('model_has_roles')->insert(['role_id' => $role, 'model_type' => User::class, 'model_id' => $user->pk]);
        foreach ($keys as $key) {
            $card = DB::table('dashboard_cards')->where('key', $key)->value('id');
            if (! $card || ! $home) {
                $this->markTestSkipped("needs the $key card and the home module");
            }
            DB::table('role_dashboard_cards')->insert(['role_id' => $role, 'dashboard_card_id' => $card, 'sidebar_category_id' => $home]);
        }
    }

    /** The page total a Security card's link lists on its New Request tab. */
    private function newTabTotal(User $user, array $roles, string $link): int
    {
        $data = $this->as($user, $roles)->get($link)->assertOk()->original->getData();
        foreach (['newRequests', 'newFamilyGroups', 'newApplications'] as $key) {
            if (isset($data[$key])) {
                return $data[$key]->total();
            }
        }
        $this->fail("no New Request list on $link");
    }

    private function assertSecurityCardsMatch(string $role, string $page): void
    {
        $user = $this->loginWithRole($role);
        $this->giveCards($user, self::SECURITY_CARDS);
        $user = $user->fresh();
        $roles = $user->roles->pluck('name')->all();

        $counts = $this->as($user, $roles)->getJson(route('admin.dashboard.cards'))->assertOk()->json('counts');
        $cards = $this->as($user, $roles)->get(route('admin.dashboard'))->viewData('cardsToRender')->keyBy('key');
        $total = 0;
        foreach (self::SECURITY_CARDS as $key) {
            $this->assertTrue($cards->has($key), "$key is shown to a $role login");
            $link = $cards[$key]['link'];
            if (str_starts_with($key, 'pending_') || str_starts_with($key, 'duplicate_')) {
                $this->assertStringContainsString("employee-idcard-approval/$page", $link);
            }
            $this->assertSame($this->newTabTotal($user, $roles, $link), $counts[$key], "$key equals the New Request rows it opens");
            $total += $counts[$key];
        }
        $this->assertGreaterThan(0, $total, 'something is waiting at this level on this database');
        $this->assertStringContainsString('wheeler=all', $cards['requested_vehicle_pass']['link']);
    }

    public function test_approval_two_cards_count_their_queue(): void
    {
        $this->assertSecurityCardsMatch('Security Approver II', 'approval2');
    }

    public function test_approval_three_cards_count_their_queue(): void
    {
        $this->assertSecurityCardsMatch('Security Approver III', 'approval3');
    }

    public function test_security_approver_ii_is_read_as_the_level_ii_role(): void
    {
        $user = $this->loginWithRole('Security Approver II');
        $this->as($user, $user->roles->pluck('name')->all())->get(route('admin.dashboard'))->assertOk();
        $this->assertTrue(hasRole('Security Card'));
        $this->assertFalse(hasRole('Admin Security'));
    }

    public function test_training_induction_is_read_as_the_induction_admin_role(): void
    {
        // Estate, Memo, Attendance and Calendar check 'Training Induction Admin', which no
        // role carries; the Induction admins hold 'Training-Induction'.
        $user = $this->loginWithRole('Training-Induction');
        $this->as($user, $user->roles->pluck('name')->all())->get(route('admin.dashboard'))->assertOk();
        $this->assertTrue(hasRole('Training Induction Admin'));

        // An Employee login holding no other role.
        $plainPk = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('m.model_type', User::class)->groupBy('m.model_id')
            ->havingRaw("SUM(r.name = 'Employee') = 1 AND COUNT(*) = 1")
            ->orderBy('m.model_id')->value('m.model_id');
        if (! $plainPk) {
            $this->markTestSkipped('no login holding only the Employee role');
        }
        $plain = User::find($plainPk);
        $this->as($plain, $plain->roles->pluck('name')->all())->get(route('admin.dashboard'))->assertOk();
        $this->assertFalse(hasRole('Training Induction Admin'));
    }

    public function test_security_cards_are_hidden_from_everyone_else(): void
    {
        $user = $this->staffWithRole('Super Admin');
        if ($user->roles->pluck('name')->intersect(['Security Approver II', 'Security Approver III'])->isNotEmpty()) {
            $this->markTestSkipped('this Super Admin is also a Security approver');
        }
        $this->giveCards($user, self::SECURITY_CARDS);

        $keys = $this->as($user->fresh(), ['Super Admin'])->get(route('admin.dashboard'))->viewData('cardsToRender')->pluck('key');
        $this->assertSame([], $keys->intersect(self::SECURITY_CARDS)->values()->all());
    }

    public function test_an_array_card_type_is_not_a_500(): void
    {
        $user = $this->loginWithRole('Security Approver III');
        $this->as($user, $user->roles->pluck('name')->all())
            ->get(route('admin.security.employee_idcard_approval.approval3').'?type[]=perm')->assertOk();
    }

    public function test_training_induction_total_students_opens_the_roster_it_counts(): void
    {
        $user = $this->loginWithRole('Training-Induction');
        $this->giveCards($user, ['total_students']);
        $user = $user->fresh();
        $roles = $user->roles->pluck('name')->all();

        $count = $this->as($user, $roles)->getJson(route('admin.dashboard.cards'))->assertOk()->json('counts.total_students');
        $link = $this->as($user, $roles)->get(route('admin.dashboard'))->viewData('cardsToRender')->firstWhere('key', 'total_students')['link'];
        $this->assertSame(route('admin.dashboard.ot-participants'), $link);
        $listed = $this->as($user, $roles)->getJson($link.'?draw=1&start=0&length=1', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('recordsTotal');

        $this->assertGreaterThan(0, $count);
        $this->assertSame($listed, $count);
    }
}
