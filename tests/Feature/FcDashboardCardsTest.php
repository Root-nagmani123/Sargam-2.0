<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FC-Sec-Audit's Setup cards show the rows Applications Registration Exemption lists for
 * the application type their link selects. Read-only.
 */
class FcDashboardCardsTest extends TestCase
{
    public function test_fc_cards_match_the_page_they_open(): void
    {
        $pk = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')->where('r.name', 'FC-Sec-Audit')->value('m.model_id');
        if (! $pk) {
            $this->markTestSkipped('no FC-Sec-Audit login');
        }
        $user = User::findOrFail($pk);
        $roles = $user->roles()->pluck('name')->all();
        $this->actingAs($user)->withSession(['user_roles' => $roles]);
        session(['user_roles' => $roles]);

        request()->query->set('category', (string) DB::table('sidebar_categories')->where('slug', 'setup')->value('id'));
        $cards = app(UserController::class)->dashboardCards(request())->getData(true)['counts'];

        $page = fn (int $type) => $this->get(route('exemptions.datalist', ['application_type' => $type]))->assertOk()->viewData('submissions')->count();

        $this->assertSame($page(1), $cards['fc_registration_applications']);
        $this->assertSame($page(2), $cards['fc_exemption_applications']);
    }
}
