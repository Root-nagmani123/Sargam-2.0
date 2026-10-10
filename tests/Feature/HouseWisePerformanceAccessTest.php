<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * House wise Performance lists trainees' discipline deductions. Its page and
 * downloads are admitted to exactly whoever the dashboard shows the panel to:
 * Super Admin, or a role the widget_house_performance card is assigned to
 * (PR #334 F-002). The route itself carries only `auth`.
 */
class HouseWisePerformanceAccessTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const URL = '/dashboard/house-wise-performance';

    public function test_an_officer_trainee_is_refused_the_page_and_both_downloads(): void
    {
        $ot = $this->officerTrainee();

        $this->as($ot, ['Student-OT'])->get(self::URL)->assertForbidden();
        $this->as($ot, ['Student-OT'])->get(self::URL . '?format=excel')->assertForbidden();
        $this->as($ot, ['Student-OT'])->get(self::URL . '?format=pdf')->assertForbidden();
    }

    public function test_a_super_admin_sees_the_page(): void
    {
        $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])->get(self::URL)->assertOk();
    }

    public function test_a_role_the_widget_is_assigned_to_sees_the_page(): void
    {
        $pk = DB::table('dashboard_cards as c')
            ->join('role_dashboard_cards as rc', 'rc.dashboard_card_id', '=', 'c.id')
            ->join('model_has_roles as m', 'm.role_id', '=', 'rc.role_id')
            ->join('roles as r', 'r.id', '=', 'rc.role_id')
            ->join('user_credentials as u', 'u.pk', '=', 'm.model_id')
            ->where('c.key', 'widget_house_performance')
            ->where('m.model_type', User::class)
            // A staff holder of the widget role: a trainee login is refused whatever
            // it holds (PR #334 F-034), and Super Admin is its own test above.
            ->where('r.name', '!=', 'Super Admin')
            ->where(fn ($q) => $q->whereNull('u.user_category')->orWhere('u.user_category', '!=', 'S'))
            ->value('m.model_id');

        if (! $pk) {
            $this->markTestSkipped('no user holds a role with the House wise Performance widget');
        }

        $this->as(User::findOrFail($pk), [])->get(self::URL)->assertOk();
    }
}
