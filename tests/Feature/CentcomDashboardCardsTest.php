<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Centcom Admin's Setup cards show the number of complaints the Centcom Assigned grid
 * lists with the status their link selects ("open" = every status but Completed). Read-only.
 */
class CentcomDashboardCardsTest extends TestCase
{
    public function test_centcom_cards_match_the_grid_they_open(): void
    {
        $pk = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')->where('r.name', 'Centcom Admin')->value('m.model_id');
        if (! $pk) {
            $this->markTestSkipped('no Centcom Admin login');
        }
        $admin = User::findOrFail($pk);
        $roles = $admin->roles()->pluck('name')->all();
        $this->actingAs($admin)->withSession(['user_roles' => $roles]);
        session(['user_roles' => $roles]);

        request()->query->set('category', (string) DB::table('sidebar_categories')->where('slug', 'setup')->value('id'));
        $cards = app(UserController::class)->dashboardCards(request())->getData(true)['counts'];

        $grid = fn (string $status) => $this->getJson(
            route('admin.issue-management.centcom.data', ['draw' => 1, 'start' => 0, 'length' => 10, 'status' => $status]),
            ['X-Requested-With' => 'XMLHttpRequest']
        )->assertOk()->json('recordsTotal');

        $this->assertSame($grid('open'), $cards['centcom_open_complaints']);
        $this->assertSame($grid('0'), $cards['centcom_reported_complaints']);
        // "open" widens the Reported filter instead of being read as (int) 'open' = 0.
        $this->assertSame(
            $grid('0') + $grid('1') + $grid('3') + $grid('6'),
            $cards['centcom_open_complaints']
        );
    }
}
