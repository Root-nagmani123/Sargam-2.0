<?php

namespace Tests\Feature;

use App\Models\User;
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

    private const LOG = 'C:/Workspace/LBSNAA/code-review/reviews/pr-356-remediation/f001-observations.txt';

    private static bool $logStarted = false;

    protected function setUp(): void
    {
        $this->rollbackSetUp();
        // Dev Redis is down; the approve actions bump a list-cache epoch through RedisBackedCache.
        config(['cache.default' => 'array', 'cache.redis_backed_unified_store' => 'array']);
        Mail::fake();
        Notification::fake();
    }

    private function record(string $line): void
    {
        if (! self::$logStarted) {
            file_put_contents(self::LOG, '# RoleAliasCapabilityTest observations, '.date('c')."\n");
            self::$logStarted = true;
        }
        file_put_contents(self::LOG, $this->getName().': '.$line."\n", FILE_APPEND);
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
            ? "select a.vehicle_tw_pk pk from vehicle_pass_tw_apply a where a.vech_card_status = 1
               and exists (select 1 from vehicle_pass_tw_apply_approval p where p.vehicle_TW_pk = a.vehicle_tw_pk and p.status = 0)
               and not exists (select 1 from vehicle_pass_tw_apply_approval p where p.vehicle_TW_pk = a.vehicle_tw_pk and (p.status <> 0 or p.veh_recommend_status = 1))
               order by a.vehicle_tw_pk desc limit 1"
            : "select a.vehicle_tw_pk pk from vehicle_pass_tw_apply a where a.vech_card_status = 1
               and exists (select 1 from vehicle_pass_tw_apply_approval p where p.vehicle_TW_pk = a.vehicle_tw_pk and p.status = 0)
               and exists (select 1 from vehicle_pass_tw_apply_approval p where p.vehicle_TW_pk = a.vehicle_tw_pk and (p.status = 1 or p.veh_recommend_status = 1))
               and not exists (select 1 from vehicle_pass_tw_apply_approval p where p.vehicle_TW_pk = a.vehicle_tw_pk and p.status in (2, 3))
               order by a.vehicle_tw_pk desc limit 1";
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
        $this->record("$pk before ".json_encode($before).' after '.json_encode($after));
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
        $this->record("$pk before ".json_encode($before).' after '.json_encode($after));
        $this->assertSame(2, $after['card_status'], 'application approved');
        $this->assertContains('2/2', $after['steps']);
    }

    public function test_vehicle_pass_approver_iii_final_approves_a_new_request_no_level_two_recommendation(): void
    {
        // approve() reads the role, never the stage: Level III takes the latest status-0 step.
        $user = $this->staffWithRole('Security Approver III');
        $pk = $this->pendingTw('new');
        $before = $this->twState($pk);

        $response = $this->approveTw($user, $pk);

        $after = $this->twState($pk);
        $this->record("$pk before ".json_encode($before).' after '.json_encode($after).' flash '.json_encode(session('success') ?? session('error')));
        $response->assertRedirect(route('admin.security.vehicle_pass_approval.index'))
            ->assertSessionHas('success', 'Vehicle Pass approved successfully');
        $this->assertSame(2, $after['card_status'], 'approved outright, with no Level II recommendation on record');
        $this->assertNotContains('1/1', $after['steps']);
    }

    public function test_vehicle_pass_approver_ii_on_a_level_one_recommended_request_recommends_again(): void
    {
        $user = $this->staffWithRole('Security Approver II');
        $pk = $this->pendingTw('level1');
        $before = $this->twState($pk);

        $this->approveTw($user, $pk)
            ->assertRedirect(route('admin.security.vehicle_pass_approval.index'))
            ->assertSessionHas('success', 'Vehicle Pass approved successfully');

        $after = $this->twState($pk);
        $this->record("$pk before ".json_encode($before).' after '.json_encode($after));
        $this->assertSame(1, $after['card_status']);
        $this->assertCount(count($before['steps']) + 1, $after['steps'], 'a second Level II recommendation and another open step');
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
            ? "select a.fml_id_apply pk from security_family_id_apply a where a.id_status = 1
               and exists (select 1 from security_family_id_apply_approval p where p.security_fm_id_apply_pk = a.fml_id_apply and p.status = 0)
               and not exists (select 1 from security_family_id_apply_approval p where p.security_fm_id_apply_pk = a.fml_id_apply and (p.status <> 0 or p.recommend_status = 1))
               order by a.fml_id_apply desc limit 1"
            : "select a.fml_id_apply pk from security_family_id_apply a where a.id_status = 1
               and exists (select 1 from security_family_id_apply_approval p where p.security_fm_id_apply_pk = a.fml_id_apply and p.status = 0)
               and exists (select 1 from security_family_id_apply_approval p where p.security_fm_id_apply_pk = a.fml_id_apply and (p.status = 1 or p.recommend_status = 1))
               and not exists (select 1 from security_family_id_apply_approval p where p.security_fm_id_apply_pk = a.fml_id_apply and p.status in (2, 3))
               order by a.fml_id_apply desc limit 1";
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
        $this->record("$pk before ".json_encode($before).' after '.json_encode($after));
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
        $this->record("$pk before ".json_encode($before).' after '.json_encode($after));
        $this->assertSame(2, $after['id_status']);
        $this->assertContains('2/n', $after['steps']);
    }

    public function test_family_id_approver_iii_final_approves_a_new_request_no_level_two_recommendation(): void
    {
        $user = $this->staffWithRole('Security Approver III');
        $pk = $this->pendingFamily('new');
        $before = $this->familyState($pk);

        $response = $this->approveFamily($user, $pk);

        $after = $this->familyState($pk);
        $this->record("$pk before ".json_encode($before).' after '.json_encode($after).' flash '.json_encode(session('success') ?? session('error')));
        $response->assertRedirect(route('admin.security.family_idcard_approval.index'))
            ->assertSessionHas('success', 'Family ID Card approved successfully');
        $this->assertSame(2, $after['id_status'], 'approved outright, with no Level II recommendation on record');
        $this->assertNotContains('1/1', $after['steps']);
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

    public function test_training_induction_generate_estate_bill_lists_every_employees_bills_but_print_all_is_refused(): void
    {
        $ym = $this->billMonth();
        $user = $this->staffWithRole('Training-Induction');

        $all = $this->billList($user, ['bill_month' => $ym]);
        $self = $this->billList($user, ['bill_month' => $ym, 'scope' => 'self']);
        $this->assertNotNull($all['print_all'], 'the page renders a Print All button');
        $print = $this->as($user, $this->rolesOf($user))->get($all['print_all']);
        $selfPrint = $this->as($user, $this->rolesOf($user))->get($self['print_all']);
        $this->record("ym=$ym list ".json_encode($all)." self ".json_encode($self)." print_all={$print->status()} self_print_all={$selfPrint->status()}");

        $this->assertSame(200, $all['status']);
        $this->assertGreaterThan(1, $all['names'], 'the list without ?scope=self carries other employees\' bills');
        $this->assertGreaterThan($self['bills'], $all['bills']);
        $this->assertStringNotContainsString('scope=self', $all['print_all']);
        $this->assertSame(403, $print->status(), 'the Print All link on that same page is refused');
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
        $this->record("ym=$ym list ".json_encode($all)." self ".json_encode($self)." print_all={$print->status()} self_print_all={$selfPrint->status()}");

        $this->assertSame(200, $all['status']);
        $this->assertSame(200, $self['status']);
        $this->assertGreaterThan(0, $self['bills'], 'their own bill is listed');
        $this->assertSame($self['bills'], $all['bills'], 'no ?scope=self still lists only their own');
        $this->assertSame(1, $all['names']);
        $this->assertSame(200, $print->status());
        $this->assertSame(200, $selfPrint->status());
    }

    /** /admin/estate/update-meter-reading/list: the page 403s non-estate roles, this feed has no gate. */
    public function test_update_meter_reading_list_feed_scope_for_training_induction_and_employee(): void
    {
        $ym = $this->billMonth();
        $ti = $this->staffWithRole('Training-Induction');
        $emp = $this->employeeOnly($ym);
        $feed = fn (User $u, array $q) => $this->as($u, $this->rolesOf($u))->getJson(route('admin.estate.update-meter-reading.list', $q));

        // The feed lists possessions still awaiting a reading for the chosen month, so take the
        // month of the reading year where it lists the most for Training-Induction.
        $query = null;
        $best = -1;
        foreach (range(1, 12) as $m) {
            $q = ['bill_month' => $m, 'bill_year' => substr($ym, 0, 4)];
            $n = count((array) $feed($ti, $q)->json('data'));
            if ($n > $best) {
                [$best, $query] = [$n, $q];
            }
        }

        $tiRes = $feed($ti, $query);
        $empRes = $feed($emp, $query);
        $tiPage = $this->as($ti, $this->rolesOf($ti))->get(route('admin.estate.update-meter-reading'));
        $count = fn ($r) => $r->status() === 200 ? count((array) $r->json('data')) : null;
        $names = fn ($r) => $r->status() === 200 ? collect($r->json('data'))->pluck('name')->unique()->count() : null;
        $this->record('query '.json_encode($query)." TI feed={$tiRes->status()} rows={$count($tiRes)} names={$names($tiRes)}; Employee feed={$empRes->status()} rows={$count($empRes)} names={$names($empRes)}; TI page={$tiPage->status()}");

        $this->assertSame(403, $tiPage->status(), 'the Update Meter Reading page itself refuses Training-Induction');
        $tiRes->assertOk();
        $empRes->assertOk();
        $this->assertGreaterThan(1, $names($tiRes), 'Training-Induction reads every resident\'s readings from the feed');
        $this->assertLessThanOrEqual(1, $names($empRes), 'an Employee reads only their own');
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
        $this->record(json_encode($s));

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
        $this->record(json_encode($s));

        // Recorded as observed; see the observations file for the faculty-pk resolution.
        $this->assertSame(200, $s['list'][0]);
        $this->assertSame(0, $s['list'][1], 'student list: nothing');
        $this->assertSame(403, $s['export_print'], 'student list export refused');
        $this->assertSame(403, $s['ot_participants'][0], 'OT / Participants refused');
        $this->assertSame([200, 0], $s['course_participants']);
        $this->assertStringStartsWith('200', $s['detail'], 'student detail: no check for a non-faculty login');
        $this->assertTrue($s['detail_shows_student'], 'the page carries that student record');
    }

    public function test_course_participants_page_is_routed_without_auth(): void
    {
        $route = app('router')->getRoutes()->getByName('my.course.participant');
        $this->assertNotContains('auth', $route->gatherMiddleware());
        $r = $this->getJson(route('my.course.participant').'?draw=1&start=0&length=1', ['X-Requested-With' => 'XMLHttpRequest']);
        $this->record('guest '.$r->status().' recordsTotal='.json_encode($r->status() === 200 ? $r->json('recordsTotal') : null));
        $this->assertNotSame(500, $r->status());
    }
}
