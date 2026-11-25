<?php

namespace Tests\Feature;

use App\Models\SecurityParmIdApply;
use App\Models\User;
use App\Support\IdCardSecurityMapper;
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

    /**
     * PR #356 F-002: a Security approver's Home card fetch stays under 200 queries (Approver III ran
     * 2,682: a schema lookup per family group and five lookups per permanent ID row; Approver II ran
     * 1,496 on testsargam9: about twelve lookups per contractual ID row), with the same counts the
     * New Request tabs list (asserted above).
     *
     * @dataProvider securityApproverRoles
     */
    public function test_security_home_fetch_stays_under_200_queries(string $securityRole): void
    {
        config(['cache.default' => 'array']);
        $user = $this->loginWithRole($securityRole);
        $this->giveCards($user, self::SECURITY_CARDS);
        $user = $user->fresh();
        $roles = $user->roles->pluck('name')->all();
        $home = DB::table('sidebar_categories')->where('slug', 'home')->value('id');
        $this->as($user, $roles)->getJson(route('admin.dashboard.cards', ['category' => $home]))->assertOk(); // warm the schema cache

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });
        $counts = $this->as($user, $roles)->getJson(route('admin.dashboard.cards', ['category' => $home]))->assertOk()->json('counts');

        $this->assertLessThan(200, $queries, "$securityRole Home /dashboard/cards ran $queries queries");
        foreach (self::SECURITY_CARDS as $key) {
            $this->assertIsInt($counts[$key] ?? null, "$key has a figure");
        }
    }

    /** The prefetched creator / card labels give the same DTO the per-row lookups did. */
    public function test_prefetched_permanent_id_rows_map_like_the_per_row_lookups(): void
    {
        $rows = SecurityParmIdApply::with(['employee.designation', 'employee.department', 'creator.department', 'approvals.approver'])
            ->orderByDesc('created_date')->limit(300)->get();
        if ($rows->isEmpty()) {
            $this->markTestSkipped('no permanent ID card requests');
        }
        $lookups = IdCardSecurityMapper::prefetchCardLookupsForPermApplies($rows);
        $fields = ['card_type', 'sub_type', 'requested_by', 'requested_section', 'created_by_name', 'name', 'status'];
        foreach ($rows as $row) {
            $plain = IdCardSecurityMapper::toEmployeeRequestDto($row);
            $fast = IdCardSecurityMapper::toEmployeeRequestDto($row, $lookups);
            foreach ($fields as $field) {
                $this->assertSame($plain->$field, $fast->$field, "row {$row->emp_id_apply}: $field");
            }
        }
    }

    /**
     * PR #356 F-004: being a Security approver gates only the Security cards. A faculty login
     * given Security Approver II or III keeps its faculty cards and gains the Security ones.
     */
    public static function securityApproverRoles(): array
    {
        return ['Approval II' => ['Security Approver II'], 'Approval III' => ['Security Approver III']];
    }

    /** @dataProvider securityApproverRoles */
    public function test_a_faculty_member_who_is_also_a_security_approver_keeps_faculty_cards(string $securityRole): void
    {
        $faculty = $this->staffWithRole('Faculty');
        $roleId = DB::table('roles')->where('name', $securityRole)->value('id');
        if (! $roleId) {
            $this->markTestSkipped("no $securityRole role");
        }
        DB::table('model_has_roles')->insertOrIgnore(['role_id' => $roleId, 'model_type' => User::class, 'model_id' => $faculty->pk]);
        $this->giveCards($faculty, ['total_sessions', 'my_timetable', 'whos_who', 'pending_contractual_id', 'requested_vehicle_pass']);
        $faculty = $faculty->fresh();
        $roles = $faculty->roles->pluck('name')->all();

        $keys = $this->as($faculty, $roles)->get(route('admin.dashboard'))->assertOk()->viewData('cardsToRender')->pluck('key')->all();

        foreach (['total_sessions', 'my_timetable', 'whos_who'] as $facultyCard) {
            $this->assertContains($facultyCard, $keys, "$facultyCard stays for a Faculty + $securityRole login");
        }
        foreach (['pending_contractual_id', 'requested_vehicle_pass'] as $securityCard) {
            $this->assertContains($securityCard, $keys, "$securityCard shows for a Faculty + $securityRole login");
        }
    }

    /** ... and a faculty login without a Security role still sees no Security card. */
    public function test_a_faculty_member_without_a_security_role_sees_no_security_cards(): void
    {
        $faculty = $this->staffWithRole('Faculty');
        $roles = $faculty->roles->pluck('name')->all();
        if (array_intersect($roles, ['Security Approver II', 'Security Approver III'])) {
            $this->markTestSkipped('this Faculty login is also a Security approver');
        }
        $this->giveCards($faculty, ['total_sessions', 'pending_contractual_id', 'requested_vehicle_pass']);
        $faculty = $faculty->fresh();

        $keys = $this->as($faculty, $faculty->roles->pluck('name')->all())->get(route('admin.dashboard'))->assertOk()->viewData('cardsToRender')->pluck('key');

        $this->assertContains('total_sessions', $keys->all());
        $this->assertSame([], $keys->intersect(['pending_contractual_id', 'requested_vehicle_pass'])->values()->all());
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

        $this->assertSame($listed, $count);
        if ($count === 0) {
            $this->markTestSkipped('no running-course participants on this database');
        }
    }
}
