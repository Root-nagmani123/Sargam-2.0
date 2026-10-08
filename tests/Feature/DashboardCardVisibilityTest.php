<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Every card definition carried a 'visible' flag, and nothing read it: a card
 * showed whenever role_dashboard_cards mapped it to one of the viewer's roles,
 * so a non-OT login with an OT card mapped saw "Total Marks Deducted", whose
 * link returns 403 (PR #334 F-063). The flag is now enforced for the cards this
 * PR added; the older cards still follow their role mapping alone.
 */
class DashboardCardVisibilityTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public function test_a_non_ot_login_mapped_to_an_ot_card_does_not_see_it(): void
    {
        $gated = DB::table('dashboard_cards')->where('key', 'discipline_marks_deducted')->value('id');
        $ungated = DB::table('dashboard_cards')->where('key', 'upcoming_events')->value('id');
        if (! $gated || ! $ungated) {
            $this->markTestSkipped('needs the discipline_marks_deducted and upcoming_events cards');
        }

        $pk = DB::table('user_credentials')->where('user_category', '!=', 'S')
            ->whereNotNull('user_id')->orderBy('pk')->value('pk');
        if (! $pk) {
            $this->markTestSkipped('no non-trainee login');
        }
        $user = User::findOrFail($pk);

        $roleId = DB::table('roles')->insertGetId([
            'name' => 'F063 test role '.uniqid(),
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $user->pk)->delete();
        DB::table('model_has_roles')->insert(['role_id' => $roleId, 'model_type' => User::class, 'model_id' => $user->pk]);
        DB::table('role_dashboard_cards')->insert([
            ['role_id' => $roleId, 'dashboard_card_id' => $gated],
            ['role_id' => $roleId, 'dashboard_card_id' => $ungated],
        ]);

        $response = $this->as($user->fresh(), [])->get(route('admin.dashboard'));

        $response->assertOk();
        $keys = collect($response->viewData('cardsToRender'))->pluck('key')->all();
        $this->assertNotContains('discipline_marks_deducted', $keys);
        $this->assertContains('upcoming_events', $keys, 'an older card still follows its role mapping');
    }
}
