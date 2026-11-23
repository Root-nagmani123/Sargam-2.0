<?php

namespace Tests\Feature;

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

    /** A student enrolled on a running course. */
    private function runningStudentPk(): int
    {
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

    /** PR #356 L-93 non-regression: a faculty login still opens its own students, and is refused others. */
    public function test_faculty_student_detail_follows_the_access_service(): void
    {
        $access = app(StudentDetailAccessService::class);
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

            return;
        }
        $this->markTestSkipped('no faculty login with both a viewable and a non-viewable running student');
    }
}
