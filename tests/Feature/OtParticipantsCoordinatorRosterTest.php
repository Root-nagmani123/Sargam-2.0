<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * A course coordinator with no Counsellor or House group got an empty OT / Participants
 * page ("Cadre not available") while their Total Students card counted their courses'
 * participants. They now get that roster, without any group's data; a faculty who owns
 * groups is still narrowed to them (UserController::otParticipantsRowsFor()).
 */
class OtParticipantsCoordinatorRosterTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** A Faculty login coordinating a running course and mapped to no course group. */
    private function coordinatorOnly(): User
    {
        $faculty = DB::table('course_coordinator_master as ccm')
            ->join('course_master as cm', 'cm.pk', '=', 'ccm.courses_master_pk')
            ->where('cm.active_inactive', 1)->where('cm.end_date', '>=', now()->toDateString())
            ->whereNotExists(fn ($q) => $q->from('group_type_master_course_master_map as g')
                ->whereColumn('g.facility_id', 'ccm.Coordinator_name')->where('g.active_inactive', 1))
            ->pluck('ccm.Coordinator_name');
        foreach ($faculty as $pk) {
            $emp = DB::table('faculty_master')->where('pk', $pk)->value('employee_master_pk');
            $user = $emp ? User::where('user_id', $emp)->first() : null;
            if ($user && $user->roles->pluck('name')->contains('Faculty')) {
                return $user;
            }
        }
        $this->markTestSkipped('no coordinator-only Faculty login on a running course');
    }

    public function test_a_coordinator_without_groups_sees_their_courses_participants(): void
    {
        $user = $this->coordinatorOnly();
        $roles = $user->roles->pluck('name')->all();

        $card = DB::table('dashboard_cards')->where('key', 'total_students')->value('id');
        $home = DB::table('sidebar_categories')->where('slug', 'home')->value('id');
        $role = DB::table('roles')->insertGetId(['name' => 'Roster test '.uniqid(), 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('model_has_roles')->insert(['role_id' => $role, 'model_type' => User::class, 'model_id' => $user->pk]);
        DB::table('role_dashboard_cards')->insert(['role_id' => $role, 'dashboard_card_id' => $card, 'sidebar_category_id' => $home]);
        $count = $this->as($user->fresh(), $roles)->getJson(route('admin.dashboard.cards'))->assertOk()->json('counts.total_students');
        // The card opens the coordinator's whole-course tab.
        $link = $this->as($user->fresh(), $roles)->get(route('admin.dashboard'))->viewData('cardsToRender')->firstWhere('key', 'total_students')['link'];
        $this->assertSame(route('admin.dashboard.ot-participants', ['scope' => 'course']), $link);
        $this->assertSame($count, $this->as($user->fresh(), $roles)
            ->getJson($link.'&draw=1&start=0&length=1', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('recordsTotal'));

        foreach (['cadre', 'house_group'] as $view) {
            $json = $this->as($user->fresh(), $roles)
                ->getJson(route('admin.dashboard.ot-participants', ['view' => $view, 'draw' => 1, 'start' => 0, 'length' => 100]), ['X-Requested-With' => 'XMLHttpRequest'])
                ->assertOk()->json();
            $this->assertGreaterThan(0, $json['recordsTotal'], "$view view lists the roster");
            $this->assertSame($count, $json['recordsTotal'], "$view view matches the Total Students card");
            // No group's data: they own none.
            foreach ($json['data'] as $row) {
                $this->assertSame('N/A', $row['counsellor']);
                $this->assertSame('N/A', $row['house_faculty']);
            }
        }

        $this->as($user->fresh(), $roles)->get(route('admin.dashboard.ot-participants'))->assertOk()->assertDontSee('not available');
    }

    public function test_a_faculty_with_groups_is_still_narrowed_to_them(): void
    {
        // A counsellor group faculty: every row they see is one of their own counsellees.
        // On an ended course (the Archived tab): the dev copy has hardly any running groups.
        $map = DB::table('group_type_master_course_master_map as g')
            ->join('course_master as cm', 'cm.pk', '=', 'g.course_name')
            ->join('course_group_type_master as t', 't.pk', '=', 'g.type_name')
            ->where('g.active_inactive', 1)->where('cm.active_inactive', 1)->where('cm.end_date', '<', now()->toDateString())
            ->where('t.type_name', 'like', '%counsel%')
            ->select('g.facility_id')->distinct()->pluck('g.facility_id');
        $user = null;
        foreach ($map as $pk) {
            $emp = DB::table('faculty_master')->where('pk', $pk)->value('employee_master_pk');
            $u = $emp ? User::where('user_id', $emp)->first() : null;
            if ($u && $u->roles->pluck('name')->contains('Faculty')) {
                $user = $u;
                $facultyPk = (int) $pk;
                break;
            }
        }
        if (! $user) {
            $this->markTestSkipped('no counsellor-group Faculty login on an ended course');
        }
        $roles = $user->roles->pluck('name')->all();

        $json = $this->as($user, $roles)
            ->getJson(route('admin.dashboard.ot-participants', ['status' => 'archive', 'view' => 'cadre', 'draw' => 1, 'start' => 0, 'length' => 500]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json();
        $this->assertGreaterThan(0, $json['recordsTotal'], 'their counsellees are listed');
        $faculty = DB::table('faculty_master')->where('pk', $facultyPk)->value('full_name');
        foreach ($json['data'] as $row) {
            $this->assertStringContainsString(e(trim((string) $faculty)), $row['counsellor'], 'only their own counsellees');
        }

        // ?scope=course widens nothing for a faculty who coordinates no course.
        if (DB::table('course_coordinator_master')->where('Coordinator_name', $facultyPk)->orWhere('Assistant_Coordinator_name', $facultyPk)->doesntExist()) {
            $scoped = $this->as($user, $roles)
                ->getJson(route('admin.dashboard.ot-participants', ['status' => 'archive', 'view' => 'cadre', 'scope' => 'course', 'draw' => 1, 'start' => 0, 'length' => 500]), ['X-Requested-With' => 'XMLHttpRequest'])
                ->assertOk()->json();
            $this->assertSame($json['recordsTotal'], $scoped['recordsTotal']);
        }
    }

    public function test_a_coordinator_with_groups_gets_a_whole_course_tab(): void
    {
        // A coordinator who counsels on a course they coordinate (an ended one: the Archived tab).
        $pair = DB::table('group_type_master_course_master_map as g')
            ->join('course_group_type_master as t', 't.pk', '=', 'g.type_name')
            ->join('course_master as cm', 'cm.pk', '=', 'g.course_name')
            ->join('course_coordinator_master as ccm', function ($j) {
                $j->on('ccm.courses_master_pk', '=', 'g.course_name')->on('ccm.Coordinator_name', '=', 'g.facility_id');
            })
            ->where('g.active_inactive', 1)->where('t.type_name', 'like', '%counsel%')
            ->where('cm.active_inactive', 1)->where('cm.end_date', '<', now()->toDateString())
            ->select('g.facility_id')->distinct()->pluck('g.facility_id');
        $user = null;
        foreach ($pair as $pk) {
            $emp = DB::table('faculty_master')->where('pk', $pk)->value('employee_master_pk');
            $u = $emp ? User::where('user_id', $emp)->first() : null;
            if ($u && $u->roles->pluck('name')->contains('Faculty')) {
                [$user, $facultyPk] = [$u, (int) $pk];
                break;
            }
        }
        if (! $user) {
            $this->markTestSkipped('no Faculty login that coordinates and counsels on the same ended course');
        }
        $roles = $user->roles->pluck('name')->all();
        $grid = fn (array $q) => $this->as($user, $roles)
            ->getJson(route('admin.dashboard.ot-participants', $q + ['status' => 'archive', 'view' => 'cadre', 'draw' => 1, 'start' => 0, 'length' => 1000]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json();
        $own = trim((string) DB::table('faculty_master')->where('pk', $facultyPk)->value('full_name'));

        $mine = $grid([]);
        $course = $grid(['scope' => 'course']);
        $this->assertGreaterThan($mine['recordsTotal'], $course['recordsTotal'], 'the whole course is wider than their groups');
        foreach ($mine['data'] as $row) {
            $this->assertStringContainsString(e($own), $row['counsellor'], 'My groups: only their own counsellees');
        }
        foreach ($course['data'] as $row) {
            $this->assertContains($row['counsellor'], ['N/A', e($own)], 'no other counsellor group data');
        }
        $this->assertSame([], $course['filterOptions']['counsellorsByCadre'] ?? [], 'no group filters on the whole course');

        $page = $this->as($user, $roles)->get(route('admin.dashboard.ot-participants', ['status' => 'archive', 'view' => 'cadre']))->assertOk();
        $this->assertSame(['active' => 'mine', 'mine' => $mine['recordsTotal'], 'course' => $course['recordsTotal']], $page->viewData('scopeTabs'));
        $page->assertSee('All course participants');
    }
}
