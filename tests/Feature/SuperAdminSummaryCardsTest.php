<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Super Admin's Home summary: the estate / mess / Centcom cards are on it, "All Open
 * Complaints" matches the All Requests grid on Status "Open", and the self-service
 * "My Pending ID Card Requests" card is hidden (that page lists everyone's to an admin).
 * Read-only.
 */
class SuperAdminSummaryCardsTest extends TestCase
{
    public function test_super_admin_home_summary(): void
    {
        $pk = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->join('user_credentials as u', 'u.pk', '=', 'm.model_id')
            ->where('r.name', 'Super Admin')->where('u.user_category', '!=', 'S')->value('m.model_id');
        if (! $pk) {
            $this->markTestSkipped('no staff Super Admin login');
        }
        $user = User::findOrFail($pk);
        $roles = $user->roles()->pluck('name')->all();
        $this->actingAs($user)->withSession(['user_roles' => $roles]);
        session(['user_roles' => $roles]);
        request()->query->replace([]);

        $cards = app(UserController::class)->dashboardCards(request())->getData(true)['counts'];

        foreach (['estate_pending_requests', 'estate_hac_pending', 'estate_vacant_houses', 'mess_pending_purchase_orders', 'mess_low_stock_items', 'centcom_all_open_complaints'] as $key) {
            $this->assertArrayHasKey($key, $cards, "$key missing from Super Admin's Home");
        }
        $this->assertArrayNotHasKey('my_idcard_requests_pending', $cards);

        $grid = $this->getJson(
            route('admin.issue-management.data', ['draw' => 1, 'start' => 0, 'length' => 10, 'status' => 'open']),
            ['X-Requested-With' => 'XMLHttpRequest']
        )->assertOk()->json('recordsTotal');
        $this->assertSame($grid, $cards['centcom_all_open_complaints']);
    }
}
