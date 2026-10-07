<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\GroupMappingController;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * POST group-mapping/store is bounded by the user's course scope (register L-53),
 * as the grid, the exports and the student writes already are: an account whose
 * roles cover no course cannot create or rename a group, a scoped account only
 * within its courses, and Admin / Super Admin anywhere. Group names print on the
 * calendar, so an unbounded writer was a route for stored script (PR #331 F-018).
 *
 * Writes run inside DatabaseTransactions and are rolled back.
 */
class GroupMappingStoreScopeTest extends TestCase
{
    use DatabaseTransactions;

    private const ACTOR_PK = 90320099;

    private const COURSE_IN = 90320001;

    private const COURSE_OUT = 90320002;

    private const MAPPING_OUT = 90320021;   // an existing group of COURSE_OUT

    private int $roleId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleId = (int) DB::table('roles')->insertGetId([
            'name' => 'Group Mapping Scope Fixture', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ([self::COURSE_IN => $this->roleId, self::COURSE_OUT => null] as $pk => $role) {
            DB::table('course_master')->insert([
                'pk' => $pk, 'course_name' => "Fixture course $pk", 'couse_short_name' => "FC$pk",
                'course_year' => 2026, 'start_year' => '2026-01-05', 'end_date' => '2026-01-30',
                'user_role_master_pk' => $role,
            ]);
        }
        DB::table('group_type_master_course_master_map')->insert([
            'pk' => self::MAPPING_OUT, 'type_name' => 1, 'course_name' => self::COURSE_OUT, 'group_name' => 'Group A',
        ]);
    }

    private function actAs(array $sessionRoles, bool $holdsCourseRole): void
    {
        if ($holdsCourseRole) {
            DB::table('model_has_roles')->insert([
                'role_id' => $this->roleId, 'model_type' => User::class, 'model_id' => self::ACTOR_PK,
            ]);
        }
        $user = new User;
        $user->forceFill(['pk' => self::ACTOR_PK, 'user_id' => self::ACTOR_PK, 'user_category' => 'E']);
        $user->exists = true;
        Auth::setUser($user);
        Session::put('user_roles', $sessionRoles);
    }

    private function store(array $input): int
    {
        $request = Request::create('/group-mapping/store', 'POST', $input + ['type_id' => '1'], [], [], ['HTTP_ACCEPT' => 'application/json']);

        return app(GroupMappingController::class)->store($request)->getStatusCode();
    }

    private function groupsNamed(string $name): int
    {
        return DB::table('group_type_master_course_master_map')->where('group_name', $name)->count();
    }

    public function test_an_account_whose_roles_cover_no_course_cannot_create_or_rename(): void
    {
        $this->actAs([], false);

        $this->assertSame(403, $this->store(['course_id' => (string) self::COURSE_IN, 'group_name' => 'Planted"x']));
        $this->assertSame(0, $this->groupsNamed('Planted"x'));

        $this->assertSame(403, $this->store([
            'pk' => encrypt(self::MAPPING_OUT), 'course_id' => (string) self::COURSE_OUT, 'group_name' => 'Renamed"x',
        ]));
        $this->assertSame('Group A', DB::table('group_type_master_course_master_map')->where('pk', self::MAPPING_OUT)->value('group_name'));
    }

    public function test_a_scoped_account_writes_within_its_course_only(): void
    {
        $this->actAs([], true);

        $this->assertSame(200, $this->store(['course_id' => (string) self::COURSE_IN, 'group_name' => 'In scope group']));
        $this->assertSame(1, $this->groupsNamed('In scope group'));

        $this->assertSame(403, $this->store(['course_id' => (string) self::COURSE_OUT, 'group_name' => 'Out of scope group']));
        $this->assertSame(0, $this->groupsNamed('Out of scope group'));
    }

    /** Moving another course's group into your own course is still a write to that group. */
    public function test_a_scoped_account_cannot_take_over_another_courses_group(): void
    {
        $this->actAs([], true);

        $this->assertSame(403, $this->store([
            'pk' => encrypt(self::MAPPING_OUT), 'course_id' => (string) self::COURSE_IN, 'group_name' => 'Hijacked',
        ]));
        $this->assertSame(self::COURSE_OUT, (int) DB::table('group_type_master_course_master_map')->where('pk', self::MAPPING_OUT)->value('course_name'));
    }

    public function test_super_admin_writes_to_any_course(): void
    {
        $this->actAs(['Super Admin'], false);

        $this->assertSame(200, $this->store([
            'pk' => encrypt(self::MAPPING_OUT), 'course_id' => (string) self::COURSE_OUT, 'group_name' => 'Group A renamed',
        ]));
        $this->assertSame(1, $this->groupsNamed('Group A renamed'));
    }
}
