<?php

namespace Tests\Feature;

use App\Models\LeaveNatureMaster;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Two leave screens gated on their own menu permission, the rule the sidebar uses
 * to show the menu (Super Admin, or holding the permission; trainees refused).
 *
 *  - Nature Leave Master (PR #334 F-010): add / edit / status / delete carried only
 *    `auth`, so any login could change the natures the Training Section records
 *    leave under.
 *  - Leave on Behalf (PR #334 F-009): the route admitted a role list that did not
 *    match who held the menu. It now admits exactly the menu's holders, still
 *    confined to their own courses unless Super Admin.
 *
 * Requests go through the real HTTP kernel; writes roll back.
 */
class LeaveMenuPermissionGateTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function staffWithOnlyRole(string $role): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id');
        $pk = $roleId ? DB::table('user_credentials as u')
            ->where('u.user_category', '!=', 'S')
            ->whereExists(fn ($q) => $q->from('model_has_roles as m')->whereColumn('m.model_id', 'u.pk')
                ->where('m.model_type', User::class)->where('m.role_id', $roleId))
            ->whereNotExists(fn ($q) => $q->from('model_has_roles as m')->whereColumn('m.model_id', 'u.pk')
                ->where('m.model_type', User::class)->where('m.role_id', '!=', $roleId))
            ->orderBy('u.pk')
            ->value('u.pk') : null;

        if (! $pk) {
            $this->markTestSkipped("no staff login holding only the '{$role}' role");
        }

        return User::findOrFail($pk);
    }

    /** A fresh model per request, as a real request loads one: Spatie caches roles.permissions on the instance. */
    private function asUser(User $user)
    {
        $user = $user->fresh();

        return $this->as($user, $user->user_category === 'S'
            ? ['Student-OT']
            : $user->roles()->pluck('name')->all());
    }

    private function inactiveNature(): LeaveNatureMaster
    {
        return LeaveNatureMaster::create([
            'leave_type' => 'LEAVE',
            'nature_name' => 'PR334 gate probe '.uniqid(),
            'display_order' => 999,
            'active_inactive' => 2,
            'created_date' => now(),
            'modified_date' => now(),
        ]);
    }

    /* ---------------------------- F-010 ---------------------------- */

    private function assertNatureMasterRefused(User $actor): void
    {
        $nature = $this->inactiveNature();
        $enc = encrypt($nature->pk);
        $before = LeaveNatureMaster::count();

        $this->asUser($actor)->get(route('master.leave-nature.index'))->assertForbidden();
        $this->asUser($actor)->get(route('master.leave-nature.create'))->assertForbidden();
        $this->asUser($actor)->get(route('master.leave-nature.edit', $enc))->assertForbidden();
        $this->asUser($actor)->post(route('master.leave-nature.store'), [
            'leave_type' => 'LEAVE', 'nature_name' => 'PR334 denied '.uniqid(), 'active_inactive' => 1,
        ])->assertForbidden();
        $this->asUser($actor)->postJson(route('master.leave-nature.status', $enc), ['active_inactive' => 1])->assertForbidden();
        $this->asUser($actor)->delete(route('master.leave-nature.delete', $enc))->assertForbidden();

        $this->assertSame($before, LeaveNatureMaster::count(), 'nothing created or deleted');
        $this->assertSame(2, (int) $nature->fresh()->active_inactive, 'status not changed');
    }

    public function test_an_officer_trainee_cannot_touch_the_nature_master(): void
    {
        $this->assertNatureMasterRefused($this->officerTrainee());
    }

    public function test_an_employee_cannot_touch_the_nature_master(): void
    {
        $this->assertNatureMasterRefused($this->staffWithOnlyRole('Employee'));
    }

    public function test_a_holder_of_the_menu_permission_may_manage_natures(): void
    {
        $holder = $this->staffWithRole('Training-Induction');
        $this->actingAs($holder);
        if (! hasMenuPermission('master_leave_nature_master')) {
            $this->markTestSkipped('the Training-Induction login does not hold master_leave_nature_master here');
        }

        $name = 'PR334 allowed '.uniqid();
        $this->asUser($holder)->post(route('master.leave-nature.store'), [
            'leave_type' => 'LEAVE', 'nature_name' => $name, 'active_inactive' => 2,
        ])->assertRedirect(route('master.leave-nature.index'));

        $nature = LeaveNatureMaster::where('nature_name', $name)->firstOrFail();

        $this->asUser($holder)->postJson(route('master.leave-nature.status', encrypt($nature->pk)), ['active_inactive' => 1])
            ->assertOk();
        $this->assertSame(1, (int) $nature->fresh()->active_inactive);
    }

    /* ---------------------------- F-009 ---------------------------- */

    public function test_an_officer_trainee_cannot_open_leave_on_behalf(): void
    {
        $this->asUser($this->officerTrainee())->get(route('admin.leave-on-behalf.index'))->assertForbidden();
    }

    /**
     * Training MCTP Admin passed the old role-list gate but, on the dev copy, does
     * not hold the menu. The route now agrees with the menu: refused. Granting the
     * menu to the role is all it takes to admit it — and it is then still confined
     * to its own courses.
     */
    public function test_the_route_follows_the_menu_permission_and_keeps_course_scope(): void
    {
        $role = Role::where('name', 'Training MCTP Admin')->first();
        if (! $role) {
            $this->markTestSkipped('no Training MCTP Admin role');
        }
        $operator = $this->staffWithRole('Training MCTP Admin');
        $this->actingAs($operator);
        if (hasMenuPermission('apply_leave_on_behalf_of_ot')) {
            $this->markTestSkipped('this operator already holds the menu, so the refusal cannot be shown');
        }

        $theirRoleIds = DB::table('model_has_roles')->where('model_id', $operator->pk)
            ->where('model_type', User::class)->pluck('role_id')->all();
        $foreign = (int) DB::table('course_master')
            ->where(fn ($q) => $q->whereNotIn('user_role_master_pk', $theirRoleIds)->orWhereNull('user_role_master_pk'))
            ->value('pk');
        $student = (int) (DB::table('student_master_course__map')->where('course_master_pk', $foreign)->value('student_master_pk') ?? 1);
        $context = '/admin/leave-on-behalf/context?'.http_build_query(['course_master_pk' => $foreign, 'student_master_pk' => $student]);

        // Without the menu: refused, like the sidebar hides it.
        $this->asUser($operator)->get(route('admin.leave-on-behalf.index'))->assertForbidden();

        $registrar = app(PermissionRegistrar::class);
        try {
            $role->givePermissionTo('apply_leave_on_behalf_of_ot');
            $registrar->forgetCachedPermissions();

            // With the menu: admitted to the screen...
            $this->assertNotSame(403, $this->asUser($operator)->get(route('admin.leave-on-behalf.index'))->getStatusCode());
            // ...and still refused a course its role does not own.
            $this->asUser($operator)->getJson($context)->assertForbidden();
        } finally {
            // Rolled back with the transaction anyway; revoked and flushed here so the
            // permission cache never outlives the rollback holding the grant.
            $role->revokePermissionTo('apply_leave_on_behalf_of_ot');
            $registrar->forgetCachedPermissions();
        }
    }
}
