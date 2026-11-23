<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\Feature\Concerns\SeedsModuleDashboardCards;
use Tests\TestCase;

/**
 * Each Mess dashboard card shows the number of rows the page behind its link lists, and
 * pending purchase orders show only to Mess Admin (the role that may approve them).
 * Rows are written inside a transaction that is rolled back.
 */
class MessDashboardCardsTest extends TestCase
{
    use RollsBackAgainstAppDatabase;
    use SeedsModuleDashboardCards;

    /** @return array<string, int> card key => count, as UserController::dashboard() renders them */
    private function cardsFor(User $user): array
    {
        $this->actingAs($user)->withSession(['user_roles' => $user->roles()->pluck('name')->all()]);
        session(['user_roles' => $user->roles()->pluck('name')->all()]);

        return app(UserController::class)->dashboardCards(request())->getData(true)['counts'];
    }

    private function pageTotal(string $url): int
    {
        return (int) $this->getJson($url, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('recordsTotal');
    }

    private function holderOf(string $role): User
    {
        $pk = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')->where('r.name', $role)->value('m.model_id');
        if (! $pk) {
            $this->markTestSkipped("no $role login");
        }

        return User::findOrFail($pk);
    }

    public function test_mess_cards_match_the_pages_they_open(): void
    {
        $this->seedModuleDashboardCards();
        config(['cache.default' => 'array']);
        $admin = $this->holderOf('Mess Admin');

        $vendor = DB::table('mess_vendors')->value('id');
        if (! $vendor) {
            $this->markTestSkipped('no mess vendor');
        }
        DB::table('mess_purchase_orders')->insert([
            'po_number' => 'PO-CARD-PROBE', 'po_date' => now()->toDateString(), 'vendor_id' => $vendor,
            'created_by' => $admin->pk, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $cards = $this->cardsFor($admin);
        $today = now()->toDateString();
        $page = fn (string $route, array $q) => $this->pageTotal(route($route, $q + ['draw' => 1, 'start' => 0, 'length' => 10]));

        $this->assertGreaterThanOrEqual(1, $cards['mess_pending_purchase_orders']);
        $this->assertSame($page('admin.mess.purchaseorders.index', ['status' => 'pending']), $cards['mess_pending_purchase_orders']);
        $this->assertSame($page('admin.mess.reports.low-stock', ['refresh' => 1]), $cards['mess_low_stock_items']);
        $this->assertSame($page('admin.mess.process-mess-bills-employee.index', ['status' => 'unpaid']), $cards['mess_unpaid_bills']);
        $this->assertSame($page('admin.mess.material-management.selling-vouchers-datatable', ['start_date' => $today, 'end_date' => $today]), $cards['mess_items_sold_today']);
    }

    public function test_mess_billing_does_not_get_the_approval_card(): void
    {
        $this->seedModuleDashboardCards();
        $cards = $this->cardsFor($this->holderOf('Mess Billing'));

        $this->assertArrayHasKey('mess_items_sold_today', $cards);
        $this->assertArrayNotHasKey('mess_pending_purchase_orders', $cards);
    }
}
