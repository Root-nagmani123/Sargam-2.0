<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Each Estate dashboard card (Setup tab) shows the number of rows the page behind its
 * link lists, read from that page's own endpoint with the card's URL filter. Read-only.
 */
class EstateDashboardCardsTest extends TestCase
{
    private function estateAdmin(): User
    {
        $pk = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')->where('r.name', 'Estate Admin')->value('m.model_id');
        if (! $pk) {
            $this->markTestSkipped('no Estate Admin login');
        }

        return User::findOrFail($pk);
    }

    /** JSON from one of the pages' data endpoints, as its own AJAX call asks for it. */
    private function pageJson(string $url): array
    {
        app()->forgetInstance('datatables.request'); // Yajra keeps the first request otherwise

        return $this->getJson($url, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json();
    }

    public function test_estate_cards_match_the_pages_they_open(): void
    {
        $admin = $this->estateAdmin();
        $roles = $admin->roles()->pluck('name')->all();
        $this->actingAs($admin)->withSession(['user_roles' => $roles]);
        session(['user_roles' => $roles]);

        $setupId = DB::table('sidebar_categories')->where('slug', 'setup')->value('id');
        request()->query->set('category', (string) $setupId);
        $cards = collect(app(UserController::class)->dashboard(request())->getData()['cardsToRender'])->pluck('count', 'key')->all();

        // dataTableRowCount() put the request's query string back.
        $this->assertSame(['category' => (string) $setupId], request()->query->all());

        $dt = ['draw' => 1, 'start' => 0, 'length' => 10];
        $month = now()->format('Y-m');

        $this->assertSame($this->pageJson(route('admin.estate.request-for-estate', $dt + ['status_filter' => '0']))['recordsTotal'], $cards['estate_pending_requests']);
        $this->assertSame($this->pageJson(route('admin.estate.put-in-hac', $dt))['recordsTotal'], $cards['estate_put_in_hac']);
        $this->assertSame($this->pageJson(route('admin.estate.change-request-hac-approved', $dt + ['status_filter' => 'pending']))['recordsTotal'], $cards['estate_hac_pending']);
        $this->assertSame(
            collect($this->pageJson(route('admin.estate.reports.house-status.data'))['data'])->where('status', 'Vacant')->count(),
            $cards['estate_vacant_houses']
        );
        $this->assertSame(
            count($this->pageJson(route('admin.estate.reports.pending-meter-reading.data', ['bill_month' => $month, 'bill_year' => now()->format('Y')]))['data']),
            $cards['estate_pending_meter_readings']
        );

        // The pending filter narrows the HAC queue rather than being ignored.
        $all = $this->pageJson(route('admin.estate.change-request-hac-approved', $dt))['recordsTotal'];
        $this->assertLessThanOrEqual($all, $cards['estate_hac_pending']);
    }
}
