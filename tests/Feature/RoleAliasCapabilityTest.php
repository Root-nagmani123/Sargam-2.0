<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use App\Services\StudentDetailAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #356 F-001: what the hasRole() aliases ('Security Card' => 'Security Approver II',
 * 'Admin Security' => 'Security Approver III', 'Training Induction Admin' => 'Training-Induction')
 * and isTrainingAdmin() let each role do at this head. Each test records current behaviour,
 * allowed or refused; none of it is a statement that the behaviour is intended (undecided).
 * Every write is rolled back; cache writes go to the array store.
 */
class RoleAliasCapabilityTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as rollbackSetUp;
    }

    protected function setUp(): void
    {
        $this->rollbackSetUp();
        // Dev Redis is down; the approve actions bump a list-cache epoch through RedisBackedCache.
        config(['cache.default' => 'array', 'cache.redis_backed_unified_store' => 'array']);
        Mail::fake();
        Notification::fake();
    }

    private function rolesOf(User $user): array
    {
        return $user->roles->pluck('name')->all();
    }

    /** A staff login whose only role is Employee; with $billYm, one who has a bill that month. */
    private function employeeOnly(?string $billYm = null): User
    {
        $q = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->join('user_credentials as u', 'u.pk', '=', 'm.model_id')
            ->where('m.model_type', User::class)
            ->where(fn ($w) => $w->whereNull('u.user_category')->orWhere('u.user_category', '!=', 'S'))
            ->groupBy('m.model_id')
            ->havingRaw("SUM(r.name = 'Employee') = 1 AND COUNT(*) = 1")
            ->orderBy('m.model_id');
        if ($billYm) {
            $q->whereIn('u.user_id', $this->billEmployeePks($billYm));
        }
        $pk = $q->value('m.model_id');
        if (! $pk) {
            $this->markTestSkipped('no staff login holding only the Employee role'.($billYm ? " with a $billYm bill" : ''));
        }

        return User::find($pk);
    }

    /** route() leaves '/' unencoded and the vehicle approve urldecode()s '+': avoid both. */
    private function enc(string $value): string
    {
        do {
            $e = encrypt($value);
        } while (str_contains($e, '/') || str_contains($e, '+'));

        return $e;
    }

    // ----- Vehicle pass ------------------------------------------------------------------

    /** A pending two-wheeler application: 'new' = only a status-0 step; 'level1' = recommended, final step open. */
    private function pendingTw(string $stage): string
    {
        $sql = $stage === 'new'
            ? 'select a.vehicle_tw_pk pk from vehicle_pass_tw_apply a where a.vech_card_status = 1
               and exists (select 1 from vehicle_pass_tw_apply_approval p where p.vehicle_TW_pk = a.vehicle_tw_pk and p.status = 0)
               and not exists (select 1 from vehicle_pass_tw_apply_approval p where p.vehicle_TW_pk = a.vehicle_tw_pk and (p.status <> 0 or p.veh_recommend_status = 1))
               order by a.vehicle_tw_pk desc limit 1'
            : 'select a.vehicle_tw_pk pk from vehicle_pass_tw_apply a where a.vech_card_status = 1
               and exists (select 1 from vehicle_pass_tw_apply_approval p where p.vehicle_TW_pk = a.vehicle_tw_pk and p.status = 0)
               and exists (select 1 from vehicle_pass_tw_apply_approval p where p.vehicle_TW_pk = a.vehicle_tw_pk and (p.status = 1 or p.veh_recommend_status = 1))
               and not exists (select 1 from vehicle_pass_tw_apply_approval p where p.vehicle_TW_pk = a.vehicle_tw_pk and p.status in (2, 3))
               order by a.vehicle_tw_pk desc limit 1';
        $row = DB::selectOne($sql);
        if (! $row) {
            $this->markTestSkipped("no $stage-stage pending two-wheeler application on this database");
        }

        return $row->pk;
    }

    private function twState(string $pk): array
    {
        return [
            'card_status' => (int) DB::table('vehicle_pass_tw_apply')->where('vehicle_tw_pk', $pk)->value('vech_card_status'),
            'steps' => DB::table('vehicle_pass_tw_apply_approval')->where('vehicle_TW_pk', $pk)
                ->orderBy('pk')->get(['status', 'veh_recommend_status', 'veh_emp_approval_pk'])
                ->map(fn ($r) => $r->status.'/'.($r->veh_recommend_status ?? 'n'))->all(),
        ];
    }

    private function approveTw(User $user, string $pk)
    {
        return $this->as($user, $this->rolesOf($user))
            ->post(route('admin.security.vehicle_pass_approval.approve', ['id' => $this->enc('tw-'.$pk)]), ['veh_approval_remarks' => 'F-001 test']);
    }

    public function test_vehicle_pass_approver_ii_recommends_a_new_request_at_level_one(): void
    {
        $user = $this->staffWithRole('Security Approver II');
        $pk = $this->pendingTw('new');
        $before = $this->twState($pk);

        $this->approveTw($user, $pk)
            ->assertRedirect(route('admin.security.vehicle_pass_approval.index'))
            ->assertSessionHas('success', 'Vehicle Pass approved successfully');

        $after = $this->twState($pk);
        $this->assertSame(1, $after['card_status'], 'still pending: Level II only recommends');
        $this->assertSame(['1/1', '0/n'], array_slice($after['steps'], -2), 'status-0 step became 1/1 and a new status-0 step opened');
        $this->assertCount(count($before['steps']) + 1, $after['steps']);
    }

    public function test_vehicle_pass_approver_iii_final_approves_a_level_one_recommended_request(): void
    {
        $user = $this->staffWithRole('Security Approver III');
        $pk = $this->pendingTw('level1');
        $before = $this->twState($pk);

        $this->approveTw($user, $pk)
            ->assertRedirect(route('admin.security.vehicle_pass_approval.index'))
            ->assertSessionHas('success', 'Vehicle Pass approved successfully');

        $after = $this->twState($pk);
        $this->assertSame(2, $after['card_status'], 'application approved');
        $this->assertContains('2/2', $after['steps']);
    }

    /** PR #356 L-87: Level III cannot final-approve what Level II never recommended. */
    public function test_vehicle_pass_approver_iii_is_refused_a_request_level_two_never_recommended(): void
    {
        $user = $this->staffWithRole('Security Approver III');
        $pk = $this->pendingTw('new');
        $before = $this->twState($pk);

        $response = $this->approveTw($user, $pk);

        $response->assertRedirect()->assertSessionHas('error', 'This request is not at your approval stage.');
        $this->assertSame($before, $this->twState($pk), 'nothing written');
    }

    /** PR #356 L-87: Level II cannot recommend a request it already recommended. */
    public function test_vehicle_pass_approver_ii_is_refused_a_request_already_recommended(): void
    {
        $user = $this->staffWithRole('Security Approver II');
        $pk = $this->pendingTw('level1');
        $before = $this->twState($pk);

        $this->approveTw($user, $pk)->assertRedirect()
            ->assertSessionHas('error', 'This request is not at your approval stage.');

        $this->assertSame($before, $this->twState($pk), 'no second recommendation, no extra open step');
    }

    public function test_vehicle_pass_approve_refuses_an_employee_only_login(): void
    {
        $user = $this->employeeOnly();
        $pk = $this->pendingTw('new');
        $before = $this->twState($pk);

        $this->approveTw($user, $pk)->assertRedirect()
            ->assertSessionHas('error', 'You are not authorized to approve this request.');
        $this->assertSame($before, $this->twState($pk));
    }

    public function test_vehicle_pass_approve_refuses_a_mess_billing_login(): void
    {
        $user = $this->staffWithRole('Mess Billing');
        $pk = $this->pendingTw('level1');
        $before = $this->twState($pk);

        $this->approveTw($user, $pk)->assertRedirect()
            ->assertSessionHas('error', 'You are not authorized to approve this request.');
        $this->assertSame($before, $this->twState($pk));
    }

    // ----- Family ID ---------------------------------------------------------------------

    private function pendingFamily(string $stage): string
    {
        $sql = $stage === 'new'
            ? 'select a.fml_id_apply pk from security_family_id_apply a where a.id_status = 1
               and exists (select 1 from security_family_id_apply_approval p where p.security_fm_id_apply_pk = a.fml_id_apply and p.status = 0)
               and not exists (select 1 from security_family_id_apply_approval p where p.security_fm_id_apply_pk = a.fml_id_apply and (p.status <> 0 or p.recommend_status = 1))
               order by a.fml_id_apply desc limit 1'
            : 'select a.fml_id_apply pk from security_family_id_apply a where a.id_status = 1
               and exists (select 1 from security_family_id_apply_approval p where p.security_fm_id_apply_pk = a.fml_id_apply and p.status = 0)
               and exists (select 1 from security_family_id_apply_approval p where p.security_fm_id_apply_pk = a.fml_id_apply and (p.status = 1 or p.recommend_status = 1))
               and not exists (select 1 from security_family_id_apply_approval p where p.security_fm_id_apply_pk = a.fml_id_apply and p.status in (2, 3))
               order by a.fml_id_apply desc limit 1';
        $row = DB::selectOne($sql);
        if (! $row) {
            $this->markTestSkipped("no $stage-stage pending family ID application on this database");
        }

        return $row->pk;
    }

    private function familyState(string $pk): array
    {
        return [
            'id_status' => (int) DB::table('security_family_id_apply')->where('fml_id_apply', $pk)->value('id_status'),
            'steps' => DB::table('security_family_id_apply_approval')->where('security_fm_id_apply_pk', $pk)
                ->orderBy('pk')->get(['status', 'recommend_status'])
                ->map(fn ($r) => $r->status.'/'.($r->recommend_status ?? 'n'))->all(),
        ];
    }

    private function approveFamily(User $user, string $pk)
    {
        return $this->as($user, $this->rolesOf($user))
            ->post(route('admin.security.family_idcard_approval.approve', ['id' => $this->enc($pk)]), ['approval_remarks' => 'F-001 test']);
    }

    public function test_family_id_approver_ii_recommends_a_new_request_at_level_one(): void
    {
        $user = $this->staffWithRole('Security Approver II');
        $pk = $this->pendingFamily('new');
        $before = $this->familyState($pk);

        $this->approveFamily($user, $pk)
            ->assertRedirect(route('admin.security.family_idcard_approval.index'))
            ->assertSessionHas('success', 'Family ID Card approved successfully');

        $after = $this->familyState($pk);
        $this->assertSame(1, $after['id_status']);
        $this->assertSame(['1/1', '0/n'], array_slice($after['steps'], -2));
        $this->assertCount(count($before['steps']) + 1, $after['steps']);
    }

    public function test_family_id_approver_iii_final_approves_a_level_one_recommended_request(): void
    {
        $user = $this->staffWithRole('Security Approver III');
        $pk = $this->pendingFamily('level1');
        $before = $this->familyState($pk);

        $this->approveFamily($user, $pk)
            ->assertRedirect(route('admin.security.family_idcard_approval.index'))
            ->assertSessionHas('success', 'Family ID Card approved successfully');

        $after = $this->familyState($pk);
        $this->assertSame(2, $after['id_status']);
        $this->assertContains('2/n', $after['steps']);
    }

    /** PR #356 L-87: Level III cannot final-approve a family ID Level II never recommended. */
    public function test_family_id_approver_iii_is_refused_a_request_level_two_never_recommended(): void
    {
        $user = $this->staffWithRole('Security Approver III');
        $pk = $this->pendingFamily('new');
        $before = $this->familyState($pk);

        $response = $this->approveFamily($user, $pk);

        $response->assertRedirect()->assertSessionHas('error', 'This request is not at your approval stage.');
        $this->assertSame($before, $this->familyState($pk), 'nothing written');
    }

    /** PR #356 L-87: Level II cannot recommend a family ID it already recommended. */
    public function test_family_id_approver_ii_is_refused_a_request_already_recommended(): void
    {
        $user = $this->staffWithRole('Security Approver II');
        $pk = $this->pendingFamily('level1');
        $before = $this->familyState($pk);

        $this->approveFamily($user, $pk)->assertRedirect()
            ->assertSessionHas('error', 'This request is not at your approval stage.');
        $this->assertSame($before, $this->familyState($pk));
    }

    public function test_family_id_approve_refuses_an_employee_only_login(): void
    {
        $user = $this->employeeOnly();
        $pk = $this->pendingFamily('new');
        $before = $this->familyState($pk);

        $this->approveFamily($user, $pk)->assertRedirect()
            ->assertSessionHas('error', 'You are not authorized to approve this request.');
        $this->assertSame($before, $this->familyState($pk));
    }

    public function test_family_id_approve_refuses_a_mess_billing_login(): void
    {
        $user = $this->staffWithRole('Mess Billing');
        $pk = $this->pendingFamily('level1');
        $before = $this->familyState($pk);

        $this->approveFamily($user, $pk)->assertRedirect()
            ->assertSessionHas('error', 'You are not authorized to approve this request.');
        $this->assertSame($before, $this->familyState($pk));
    }

    // ----- Reject: only the approver at the request's current stage ------------------------

    private function rejectTw(User $user, string $pk)
    {
        return $this->as($user, $this->rolesOf($user))
            ->post(route('admin.security.vehicle_pass_approval.reject', ['id' => $this->enc('tw-'.$pk)]), ['veh_approval_remarks' => 'reject test']);
    }

    private function rejectFamily(User $user, string $pk, bool $group = false)
    {
        $route = $group ? 'admin.security.family_idcard_approval.reject_group' : 'admin.security.family_idcard_approval.reject';

        return $this->as($user, $this->rolesOf($user))
            ->post(route($route, ['id' => $this->enc($pk)]), ['approval_remarks' => 'reject test']);
    }

    public function test_vehicle_pass_reject_is_refused_off_stage_and_allowed_at_stage(): void
    {
        $pk = $this->pendingTw('new');
        $before = $this->twState($pk);

        foreach ([$this->employeeOnly(), $this->staffWithRole('Security Approver III')] as $user) {
            $this->rejectTw($user, $pk)->assertRedirect()
                ->assertSessionHas('error', 'This request is not at your approval stage.');
            $this->assertSame($before, $this->twState($pk), 'nothing written for '.implode(',', $this->rolesOf($user)));
        }

        $this->rejectTw($this->staffWithRole('Security Approver II'), $pk)
            ->assertRedirect(route('admin.security.vehicle_pass_approval.index'))
            ->assertSessionHas('success', 'Vehicle Pass rejected');
        $this->assertSame(3, $this->twState($pk)['card_status']);
    }

    public function test_family_id_reject_is_refused_off_stage_and_allowed_at_stage(): void
    {
        $pk = $this->pendingFamily('new');
        $before = $this->familyState($pk);

        foreach ([$this->employeeOnly(), $this->staffWithRole('Security Approver III')] as $user) {
            $this->rejectFamily($user, $pk)->assertRedirect()
                ->assertSessionHas('error', 'This request is not at your approval stage.');
            $this->assertSame($before, $this->familyState($pk), 'nothing written for '.implode(',', $this->rolesOf($user)));
        }

        $this->rejectFamily($this->staffWithRole('Security Approver II'), $pk)
            ->assertRedirect(route('admin.security.family_idcard_approval.index'))
            ->assertSessionHas('success', 'Family ID Card rejected');
        $this->assertSame(3, $this->familyState($pk)['id_status']);
    }

    public function test_family_id_reject_group_refuses_a_login_at_no_members_stage(): void
    {
        $pk = $this->pendingFamily('new');
        $before = $this->familyState($pk);

        $this->rejectFamily($this->employeeOnly(), $pk, true)
            ->assertRedirect(route('admin.security.family_idcard_approval.index'))
            ->assertSessionHas('error', 'No member of this group is at your approval stage.');
        $this->assertSame($before, $this->familyState($pk));
    }

    // ----- Family ID: approve a whole group ------------------------------------------------

    /**
     * A pending family group (same emp_id_apply, created_by, created_date) whose every pending
     * member is at $stage, largest first; returns [the member pk to post, every member pk].
     */
    private function familyGroup(string $stage): array
    {
        $atStage = $stage === 'new'
            ? 'exists (select 1 from security_family_id_apply_approval p where p.security_fm_id_apply_pk = a.fml_id_apply and p.status = 0)
               and not exists (select 1 from security_family_id_apply_approval p where p.security_fm_id_apply_pk = a.fml_id_apply and (p.status <> 0 or p.recommend_status = 1))'
            : 'exists (select 1 from security_family_id_apply_approval p where p.security_fm_id_apply_pk = a.fml_id_apply and p.status = 0)
               and exists (select 1 from security_family_id_apply_approval p where p.security_fm_id_apply_pk = a.fml_id_apply and p.status = 1)
               and not exists (select 1 from security_family_id_apply_approval p where p.security_fm_id_apply_pk = a.fml_id_apply and p.status in (2, 3))';
        $group = DB::selectOne("select emp_id_apply, created_by, created_date, max(fml_id_apply) pk from security_family_id_apply a
            where a.id_status = 1 group by emp_id_apply, created_by, created_date
            having count(*) = sum($atStage) order by count(*) desc, pk desc limit 1");
        if (! $group) {
            $this->markTestSkipped("no family group with every pending member at the $stage stage");
        }
        $members = DB::table('security_family_id_apply')->where('id_status', 1)
            ->where('emp_id_apply', $group->emp_id_apply)->where('created_by', $group->created_by)
            ->where('created_date', $group->created_date)->orderBy('fml_id_apply')->pluck('fml_id_apply')->all();

        return [$group->pk, $members];
    }

    private function approveFamilyGroup(User $user, string $pk)
    {
        return $this->as($user, $this->rolesOf($user))
            ->post(route('admin.security.family_idcard_approval.approve_group', ['id' => $this->enc($pk)]), ['approval_remarks' => 'F-001 group test']);
    }

    /** @param list<string> $members */
    private function groupState(array $members): array
    {
        return array_combine($members, array_map(fn ($pk) => $this->familyState($pk), $members));
    }

    public function test_family_id_approve_group_ii_recommends_every_member_at_level_one(): void
    {
        [$pk, $members] = $this->familyGroup('new');
        $before = $this->groupState($members);

        $this->approveFamilyGroup($this->staffWithRole('Security Approver II'), $pk)
            ->assertRedirect(route('admin.security.family_idcard_approval.index'))
            ->assertSessionHas('success', 'Family ID Card group approved ('.count($members).' members)');

        foreach ($this->groupState($members) as $member => $after) {
            $this->assertSame(1, $after['id_status'], "$member stays pending for Level III");
            $this->assertSame(['1/1', '0/n'], array_slice($after['steps'], -2), "$member recommended, next step open");
            $this->assertCount(count($before[$member]['steps']) + 1, $after['steps'], "$member gains one step");
        }
    }

    public function test_family_id_approve_group_iii_final_approves_a_recommended_group(): void
    {
        [$pk, $members] = $this->familyGroup('level1');

        $this->approveFamilyGroup($this->staffWithRole('Security Approver III'), $pk)
            ->assertRedirect(route('admin.security.family_idcard_approval.index'))
            ->assertSessionHas('success', 'Family ID Card group approved ('.count($members).' members)');

        foreach ($this->groupState($members) as $member => $after) {
            $this->assertSame(2, $after['id_status'], "$member final-approved");
            $this->assertContains('2/n', $after['steps'], "$member has its Level III step");
        }
    }

    /** PR #356 L-87 on the bulk path: Level III cannot final-approve a group Level II never recommended. */
    public function test_family_id_approve_group_iii_is_refused_a_group_level_two_never_recommended(): void
    {
        [$pk, $members] = $this->familyGroup('new');
        $before = $this->groupState($members);

        $this->approveFamilyGroup($this->staffWithRole('Security Approver III'), $pk)
            ->assertRedirect(route('admin.security.family_idcard_approval.index'))
            ->assertSessionHas('error', 'No member of this group is at your approval stage.');
        $this->assertSame($before, $this->groupState($members), 'nothing written');
    }

    /** PR #356 L-87 on the bulk path: Level II cannot recommend a group it already recommended. */
    public function test_family_id_approve_group_ii_is_refused_a_group_already_recommended(): void
    {
        [$pk, $members] = $this->familyGroup('level1');
        $before = $this->groupState($members);

        $this->approveFamilyGroup($this->staffWithRole('Security Approver II'), $pk)
            ->assertRedirect(route('admin.security.family_idcard_approval.index'))
            ->assertSessionHas('error', 'No member of this group is at your approval stage.');
        $this->assertSame($before, $this->groupState($members), 'nothing written');
    }

    public function test_family_id_approve_group_refuses_an_employee_only_and_a_mess_billing_login(): void
    {
        [$pk, $members] = $this->familyGroup('new');
        $before = $this->groupState($members);

        foreach ([$this->employeeOnly(), $this->staffWithRole('Mess Billing')] as $user) {
            $this->approveFamilyGroup($user, $pk)
                ->assertRedirect(route('admin.security.family_idcard_approval.index'))
                ->assertSessionHas('error', 'You are not authorized to approve these requests.');
            $this->assertSame($before, $this->groupState($members), 'nothing written for '.implode(',', $this->rolesOf($user)));
        }
    }

    // ----- OT / Participants: store a comment ----------------------------------------------

    /** A running-course student who has a trainee login, so Notify OT has someone to reach. */
    private function runningStudentWithLogin(): int
    {
        $this->ensureRunningCourse(fn ($q) => $q->whereExists(fn ($e) => $e->from('student_master_course__map as s')
            ->join('user_credentials as u', fn ($j) => $j->on('u.user_id', '=', 's.student_master_pk')->where('u.user_category', 'S'))
            ->whereColumn('s.course_master_pk', 'c.pk')->where('s.active_inactive', 1)));
        $pk = DB::table('student_master_course__map as s')
            ->join('course_master as c', 'c.pk', '=', 's.course_master_pk')
            ->join('user_credentials as u', fn ($j) => $j->on('u.user_id', '=', 's.student_master_pk')->where('u.user_category', 'S'))
            ->where('s.active_inactive', 1)->where('c.active_inactive', 1)
            ->where('c.end_date', '>=', now()->toDateString())
            ->orderByDesc('s.student_master_pk')->value('s.student_master_pk');
        if (! $pk) {
            $this->markTestSkipped('no student with a trainee login on a running course');
        }

        return (int) $pk;
    }

    private function storeOtComment(User $user, int $studentPk, array $extra = [])
    {
        return $this->as($user, $this->rolesOf($user))->postJson(route('admin.dashboard.ot-participants.comment.store'), [
            'student_master_pk' => $studentPk, 'message' => 'F-001 comment test '.uniqid(), 'notify_ot' => 1,
        ] + $extra);
    }

    /** PR #356 L-94: Training-Induction comments on any participant and notifies them; attribution cannot be forged. */
    public function test_training_induction_stores_an_attributed_comment_and_notifies_the_ot(): void
    {
        $user = $this->staffWithRole('Training-Induction');
        $student = $this->runningStudentWithLogin();
        $forged = (int) DB::table('user_credentials')->where('user_id', '!=', $user->user_id)->whereNotNull('user_id')->value('user_id');

        $response = $this->storeOtComment($user, $student, ['comment_by_user_id' => $forged, 'created_by' => $forged, 'active_inactive' => 0]);

        $response->assertOk()->assertJson(['success' => true]);
        $comment = DB::table('ot_participant_comment')->where('student_master_pk', $student)->orderByDesc('pk')->first();
        $this->assertNotNull($comment);
        $this->assertSame(json_decode($response->getContent())->count, DB::table('ot_participant_comment')->where('student_master_pk', $student)->where('active_inactive', 1)->count());
        $this->assertEquals($user->user_id, $comment->comment_by_user_id, 'author is the login, not the payload');
        $this->assertEquals($user->user_id, $comment->created_by);
        $this->assertEquals(1, $comment->active_inactive);
        $this->assertTrue(DB::table('notifications')->where('type', 'ot_comment')->where('reference_pk', $comment->pk)
            ->where('receiver_user_id', $student)->exists(), 'the OT is notified');
    }

    public function test_ot_comment_store_refuses_an_employee_only_login(): void
    {
        // Refused before the participant is looked up, so any student will do.
        $student = (int) DB::table('student_master')->max('pk');
        $comments = DB::table('ot_participant_comment')->count();
        $notifications = DB::table('notifications')->where('type', 'ot_comment')->count();

        $this->storeOtComment($this->employeeOnly(), $student)->assertForbidden();

        $this->assertSame($comments, DB::table('ot_participant_comment')->count());
        $this->assertSame($notifications, DB::table('notifications')->where('type', 'ot_comment')->count());
    }

    /** The roster is the boundary: a faculty login may not comment on a participant outside it. */
    public function test_ot_comment_store_refuses_a_faculty_login_for_a_participant_off_its_roster(): void
    {
        $user = $this->staffWithRole('Faculty');
        $this->actingAs($user)->withSession(['user_roles' => $this->rolesOf($user)]);
        session(['user_roles' => $this->rolesOf($user)]);
        $roster = (fn () => $this->canUseOtParticipants() ? $this->otParticipantRosterPks() : false)->call(app(UserController::class));
        if (! is_array($roster)) {
            $this->markTestSkipped('this Faculty login cannot use OT / Participants, or sees every participant');
        }
        $student = (int) DB::table('student_master')->when($roster !== [], fn ($q) => $q->whereNotIn('pk', array_keys($roster)))->max('pk');
        $comments = DB::table('ot_participant_comment')->count();

        $this->storeOtComment($user, $student)->assertForbidden()->assertJson(['message' => 'This participant is not on your roster.']);
        $this->assertSame($comments, DB::table('ot_participant_comment')->count());
    }

    public function test_ot_comment_store_answers_404_for_an_unknown_participant(): void
    {
        $this->storeOtComment($this->staffWithRole('Training-Induction'), (int) DB::table('student_master')->max('pk') + 1000)
            ->assertNotFound()->assertJson(['success' => false]);
    }

    // ----- Estate ------------------------------------------------------------------------

    /** The latest reading month (Y-m, by to_date) with more than ten readings. */
    private function billMonth(): string
    {
        $ym = DB::table('estate_month_reading_details')->whereNotNull('to_date')
            ->selectRaw("DATE_FORMAT(to_date, '%Y-%m') ym")->groupBy('ym')->havingRaw('COUNT(*) > 10')
            ->orderByDesc('ym')->value('ym');
        if (! $ym) {
            $this->markTestSkipped('no estate bill month with readings');
        }

        return $ym;
    }

    private function billEmployeePks(string $ym): array
    {
        return DB::table('estate_month_reading_details as d')
            ->join('estate_possession_details as p', 'p.pk', '=', 'd.estate_possession_details_pk')
            ->join('estate_home_request_details as h', 'h.pk', '=', 'p.estate_home_request_details')
            ->whereRaw("DATE_FORMAT(d.to_date, '%Y-%m') = ?", [$ym])
            ->distinct()->pluck('h.employee_pk')->filter()->all();
    }

    /** Status, bill count and distinct names on Generate Estate Bill, plus its Print All href. */
    private function billList(User $user, array $query): array
    {
        $response = $this->as($user, $this->rolesOf($user))->get(route('admin.estate.generate-estate-bill', $query));
        $bills = $response->status() === 200 ? collect($response->viewData('bills')) : collect();
        preg_match('/<a href="([^"]+)"[^>]*id="btn_print_all"/', $response->getContent(), $m);

        return [
            'status' => $response->status(),
            'bills' => $bills->count(),
            'names' => $bills->pluck('emp_name')->filter()->unique()->count(),
            'print_all' => isset($m[1]) ? html_entity_decode($m[1]) : null,
        ];
    }

    /** PR #356 L-91: the list and its Print All follow one rule, own bills only, as for an Employee. */
    public function test_training_induction_generate_estate_bill_and_print_all_are_both_self_scoped(): void
    {
        $ym = $this->billMonth();
        $user = $this->staffWithRole('Training-Induction');

        $all = $this->billList($user, ['bill_month' => $ym]);
        $self = $this->billList($user, ['bill_month' => $ym, 'scope' => 'self']);
        $this->assertNotNull($all['print_all'], 'the page renders a Print All button');
        $print = $this->as($user, $this->rolesOf($user))->get($all['print_all']);
        $selfPrint = $this->as($user, $this->rolesOf($user))->get($self['print_all']);

        $this->assertSame(200, $all['status']);
        $this->assertSame($self['bills'], $all['bills'], 'no ?scope=self still lists only their own bills');
        $this->assertLessThanOrEqual(1, $all['names']);
        $this->assertSame(200, $print->status(), 'the Print All link on that same page opens');
        $this->assertSame(200, $selfPrint->status());
    }

    public function test_employee_generate_estate_bill_is_self_scoped_with_or_without_scope_self(): void
    {
        $ym = $this->billMonth();
        $user = $this->employeeOnly($ym);

        $all = $this->billList($user, ['bill_month' => $ym]);
        $self = $this->billList($user, ['bill_month' => $ym, 'scope' => 'self']);
        $print = $this->as($user, $this->rolesOf($user))->get($all['print_all']);
        $selfPrint = $this->as($user, $this->rolesOf($user))->get($self['print_all']);

        $this->assertSame(200, $all['status']);
        $this->assertSame(200, $self['status']);
        $this->assertGreaterThan(0, $self['bills'], 'their own bill is listed');
        $this->assertSame($self['bills'], $all['bills'], 'no ?scope=self still lists only their own');
        $this->assertSame(1, $all['names']);
        $this->assertSame(200, $print->status());
        $this->assertSame(200, $selfPrint->status());
    }

    /** PR #356 L-90: the Update Meter Reading feed is gated like its page: estate authority only. */
    public function test_update_meter_reading_list_feed_is_estate_authority_only(): void
    {
        $ym = $this->billMonth();
        $ti = $this->staffWithRole('Training-Induction');
        $emp = $this->employeeOnly($ym);
        $estate = $this->staffWithRole('Estate Admin');
        $feed = fn (User $u, array $q) => $this->as($u, $this->rolesOf($u))->getJson(route('admin.estate.update-meter-reading.list', $q));

        // The feed lists possessions still awaiting a reading for the chosen month, so take the
        // month of the reading year where it lists the most for Training-Induction.
        $query = null;
        $best = -1;
        foreach (range(1, 12) as $m) {
            $q = ['bill_month' => $m, 'bill_year' => substr($ym, 0, 4)];
            $n = count((array) $feed($estate, $q)->json('data'));
            if ($n > $best) {
                [$best, $query] = [$n, $q];
            }
        }

        $tiRes = $feed($ti, $query);
        $empRes = $feed($emp, $query);
        $estateRes = $feed($estate, $query);
        $tiPage = $this->as($ti, $this->rolesOf($ti))->get(route('admin.estate.update-meter-reading'));

        $this->assertSame(403, $tiPage->status(), 'the Update Meter Reading page refuses Training-Induction');
        $this->assertSame(403, $tiRes->status(), 'and so does its feed');
        $this->assertSame(403, $empRes->status(), 'an Employee is refused the feed too');
        $estateRes->assertOk();
        $this->assertGreaterThan(0, count((array) $estateRes->json('data')), 'Estate Admin still reads the readings');
    }

    // ----- Student data ------------------------------------------------------------------

    private function dtTotal(User $user, string $url): array
    {
        $r = $this->as($user, $this->rolesOf($user))
            ->getJson($url.(str_contains($url, '?') ? '&' : '?').'draw=1&start=0&length=1', ['X-Requested-With' => 'XMLHttpRequest']);

        return [$r->status(), $r->status() === 200 ? $r->json('recordsTotal') : null];
    }

    /**
     * PR #356 F-014: these tests need a course that is running today, and the dev databases' courses
     * age out (none was running on testsargam8 or testsargam9 after 2026-11-25, so the tests skipped
     * and the file still reported OK). When no qualifying course runs, reopen the most recently ended
     * one by moving its end_date forward, inside this test's rolled-back transaction. $where narrows
     * which courses qualify (students with a trainee login, a Faculty-login coordinator).
     */
    private function ensureRunningCourse(?\Closure $where = null): void
    {
        $courses = fn () => DB::table('course_master as c')
            ->where('c.active_inactive', 1)
            ->whereExists(fn ($q) => $q->from('student_master_course__map as s')
                ->whereColumn('s.course_master_pk', 'c.pk')->where('s.active_inactive', 1))
            ->when($where, $where);
        if ($courses()->where('c.end_date', '>=', now()->toDateString())->exists()) {
            return;
        }
        $pk = $courses()->orderByDesc('c.end_date')->orderByDesc('c.pk')->value('c.pk');
        if (! $pk) {
            $this->markTestSkipped('no course with active students to run');
        }
        DB::table('course_master')->where('pk', $pk)->update(['end_date' => now()->addDays(30)->toDateString()]);
    }

    /** A student enrolled on a running course. */
    private function runningStudentPk(): int
    {
        $this->ensureRunningCourse();
        $pk = DB::table('student_master_course__map as s')
            ->join('course_master as c', 'c.pk', '=', 's.course_master_pk')
            ->where('s.active_inactive', 1)->where('c.active_inactive', 1)
            ->where('c.end_date', '>=', now()->toDateString())
            ->orderByDesc('s.student_master_pk')->value('s.student_master_pk');
        if (! $pk) {
            $this->markTestSkipped('no student on a running course');
        }

        return (int) $pk;
    }

    private function studentSurface(User $user): array
    {
        $roles = $this->rolesOf($user);
        $this->actingAs($user)->withSession(['user_roles' => $roles]);
        session(['user_roles' => $roles]);
        $facultyPk = get_auth_faculty_master_pk();
        $facultyPortal = is_faculty_portal_user();

        $student = $this->runningStudentPk();
        $detail = $this->as($user, $roles)->get(route('admin.dashboard.students.detail', ['id' => $this->enc((string) $student)]));
        $print = $this->as($user, $roles)->get(route('admin.dashboard.students.export', ['format' => 'print']));

        return [
            'faculty_pk' => $facultyPk,
            'faculty_portal' => $facultyPortal,
            // Empty dates = the Time Period filter cleared (a bare load defaults to today's sessions).
            'list' => $this->dtTotal($user, route('admin.dashboard.students').'?from_date=&to_date='),
            'detail' => $detail->status().($detail->isRedirect() ? ' -> '.$detail->headers->get('Location').' '.json_encode(session('error')) : ''),
            'detail_shows_student' => $detail->status() === 200 && (int) optional($detail->viewData('student'))->pk === $student,
            'detail_student' => $student,
            'export_print' => $print->status(),
            'ot_participants' => $this->dtTotal($user, route('admin.dashboard.ot-participants')),
            'course_participants' => $this->dtTotal($user, route('my.course.participant')),
        ];
    }

    public function test_training_induction_student_data_surface(): void
    {
        $s = $this->studentSurface($this->staffWithRole('Training-Induction'));

        $this->assertSame(200, $s['list'][0]);
        $this->assertGreaterThan(0, $s['list'][1], 'student list: every running course');
        $this->assertStringStartsWith('200', $s['detail'], 'student detail opens');
        $this->assertTrue($s['detail_shows_student']);
        $this->assertSame(200, $s['export_print'], 'student list export (print) allowed');
        $this->assertSame(200, $s['ot_participants'][0]);
        $this->assertGreaterThan(0, $s['ot_participants'][1], 'OT / Participants: whole roster');
        $this->assertSame([200, 0], $s['course_participants'], 'course participants: no course carries this role');
    }

    public function test_employee_only_student_data_surface(): void
    {
        $s = $this->studentSurface($this->employeeOnly());

        // Recorded as observed.
        $this->assertSame(200, $s['list'][0]);
        $this->assertSame(0, $s['list'][1], 'student list: nothing');
        $this->assertSame(403, $s['export_print'], 'student list export refused');
        $this->assertSame(403, $s['ot_participants'][0], 'OT / Participants refused');
        $this->assertSame([200, 0], $s['course_participants']);
        // PR #356 L-93: no students on either list, so no student's record either.
        $this->assertStringStartsWith('302', $s['detail'], 'student detail refused for a non-faculty login');
        $this->assertStringStartsWith('302 -> '.route('admin.dashboard.students'), $s['detail'], 'sent back to the student list');
        $this->assertFalse($s['detail_shows_student']);
    }

    /** PR #356 L-93 non-regression: a faculty login still opens its own students, and is refused others; F-010: Admin / PA open those too. */
    public function test_faculty_student_detail_follows_the_access_service(): void
    {
        $access = app(StudentDetailAccessService::class);
        // A running course whose coordinator is a Faculty login, so some faculty may view its students.
        $this->ensureRunningCourse(fn ($q) => $q->whereExists(fn ($e) => $e->from('course_coordinator_master as cc')
            ->join('faculty_master as f', 'f.pk', '=', 'cc.Coordinator_name')
            ->join('user_credentials as u', 'u.user_id', '=', 'f.employee_master_pk')
            ->join('model_has_roles as m', fn ($j) => $j->on('m.model_id', '=', 'u.pk')->where('m.model_type', User::class))
            ->join('roles as r', fn ($j) => $j->on('r.id', '=', 'm.role_id')->where('r.name', 'Faculty'))
            ->whereColumn('cc.courses_master_pk', 'c.pk')));
        $students = DB::table('student_master_course__map as s')
            ->join('course_master as c', 'c.pk', '=', 's.course_master_pk')
            ->where('s.active_inactive', 1)->where('c.active_inactive', 1)
            ->where('c.end_date', '>=', now()->toDateString())
            ->distinct()->orderBy('s.student_master_pk')->limit(400)->pluck('s.student_master_pk');
        // Students of courses that have ended: a coordinator of every running course may see all running ones.
        $others = DB::table('student_master_course__map')->whereNotIn('student_master_pk', $students)->distinct()->orderByDesc('student_master_pk')->limit(400)->pluck('student_master_pk');
        $logins = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->join('user_credentials as u', 'u.pk', '=', 'm.model_id')
            ->where('r.name', 'Faculty')->where('m.model_type', User::class)
            ->where(fn ($q) => $q->whereNull('u.user_category')->orWhere('u.user_category', '!=', 'S'))
            ->orderBy('m.model_id')->limit(300)->pluck('m.model_id');
        foreach ($logins as $login) {
            $user = User::find($login);
            $roles = $user ? $this->rolesOf($user) : [];
            if (! $user || array_intersect($roles, ['Super Admin', 'Training-Induction', 'Training MCTP Admin', 'Training IST'])) {
                continue;
            }
            $this->actingAs($user)->withSession(['user_roles' => $roles]);
            session(['user_roles' => $roles]);
            $facultyPk = (int) get_auth_faculty_master_pk();
            if (! $facultyPk || isOfficerTraineeUser()) {
                continue;
            }
            $allowed = $students->first(fn ($s) => $access->canFacultyViewStudent($facultyPk, (int) $s));
            $refused = $students->concat($others)->first(fn ($s) => ! $access->canFacultyViewStudent($facultyPk, (int) $s));
            if (! $allowed || ! $refused) {
                continue;
            }
            $open = fn ($s) => $this->as($user, $roles)->get(route('admin.dashboard.students.detail', ['id' => $this->enc((string) $s)]));

            $this->assertSame(200, $open($allowed)->status(), "faculty $facultyPk opens student $allowed");
            $open($refused)->assertRedirect(route('admin.dashboard.students'));
            // F-010: OT / Participants lists every participant for Admin and PA, so the same
            // faculty login holding either role opens the student it was refused above.
            foreach (['PA', 'Admin'] as $exempt) {
                $this->assertSame(200, $this->as($user, array_merge($roles, [$exempt]))
                    ->get(route('admin.dashboard.students.detail', ['id' => $this->enc((string) $refused)]))->status(),
                    "$exempt with faculty $facultyPk opens student $refused");
            }

            return;
        }
        $this->markTestSkipped('no faculty login with both a viewable and a non-viewable running student');
    }
}
