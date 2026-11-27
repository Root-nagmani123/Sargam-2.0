<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\Feature\Concerns\SeedsModuleDashboardCards;
use Tests\TestCase;

/**
 * Employee self-service cards show the signed-in user's own rows on the page each card
 * opens. Checked for employees who actually have such rows, so no assertion is 0 = 0.
 * Read-only.
 */
class EmployeeSelfServiceCardsTest extends TestCase
{
    use RollsBackAgainstAppDatabase;
    use SeedsModuleDashboardCards;

    /** An Employee login whose user_id appears in $table.$column on a row matching $where. */
    private function employeeWith(string $table, string $column, array $where): ?User
    {
        $pk = DB::table($table.' as t')
            ->join('user_credentials as u', 'u.user_id', '=', 't.'.$column)
            ->join('model_has_roles as m', 'm.model_id', '=', 'u.pk')
            ->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('r.name', 'Employee')->where('u.user_category', 'E')
            ->where(fn ($q) => collect($where)->each(fn ($v, $k) => is_array($v) ? $q->whereIn('t.'.$k, $v) : $q->where('t.'.$k, $v)))
            ->value('u.pk');

        return $pk ? User::find($pk) : null;
    }

    /** @return array<string, int> */
    private function homeCardsAs(User $user): array
    {
        app()->forgetInstance('datatables.request');
        $roles = $user->roles()->pluck('name')->all();
        $this->actingAs($user)->withSession(['user_roles' => $roles]);
        session(['user_roles' => $roles]);
        request()->query->replace([]);

        return app(UserController::class)->dashboardCards(request())->getData(true)['counts'];
    }

    private function pageJson(string $url): array
    {
        app()->forgetInstance('datatables.request');

        return $this->getJson($url, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json();
    }

    public function test_my_open_issues_matches_raised_by_you_open(): void
    {
        $this->seedModuleDashboardCards();
        $user = $this->employeeWith('issue_log_management', 'created_by', ['issue_status' => [0, 1, 3, 6]]) ?? $this->markTestSkipped('no employee with open issues');
        $cards = $this->homeCardsAs($user);
        $page = $this->pageJson(route('admin.issue-management.data', ['draw' => 1, 'start' => 0, 'length' => 10, 'raised_by' => 'self', 'status' => 'open']));

        $this->assertGreaterThan(0, $cards['my_open_issues']);
        $this->assertSame($page['recordsTotal'], $cards['my_open_issues']);
    }

    public function test_my_estate_requests_matches_request_for_estate_self_pending(): void
    {
        $this->seedModuleDashboardCards();
        $user = $this->employeeWith('estate_home_request_details', 'employee_pk', ['status' => 0]) ?? $this->markTestSkipped('no employee with a pending estate request');
        $cards = $this->homeCardsAs($user);
        $page = $this->pageJson(route('admin.estate.request-for-estate', ['draw' => 1, 'start' => 0, 'length' => 10, 'scope' => 'self', 'status_filter' => '0']));

        $this->assertSame($page['recordsTotal'], $cards['my_estate_requests_pending']);
    }

    public function test_my_idcard_requests_matches_the_pending_list(): void
    {
        $this->seedModuleDashboardCards();
        $user = $this->employeeWith('security_con_oth_id_apply', 'created_by', ['id_status' => 1]) ?? $this->markTestSkipped('no employee with a pending ID card request');
        $cards = $this->homeCardsAs($user);
        $listed = $this->get(route('admin.employee_idcard.index', ['list_status' => 'pending']))->assertOk()->viewData('allRequests')->total();

        $this->assertGreaterThan(0, $cards['my_idcard_requests_pending']);
        $this->assertSame($listed, $cards['my_idcard_requests_pending']);
    }
}
