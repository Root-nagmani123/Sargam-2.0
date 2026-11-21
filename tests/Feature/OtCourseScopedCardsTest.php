<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\MedicalExceptionOTViewController;
use App\Http\Controllers\Admin\OTMDOEscrotExemptionController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * An OT's Medical Exception and MDO/Escort cards count what the page they open lists: only
 * active courses that have not ended. They counted every course, so an OT of a finished
 * course saw "7" over an empty page. Card assignments go to a throwaway role, rolled back.
 */
class OtCourseScopedCardsTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** An OT login whose student pk has rows in $table.$column, holding $cardKey on Home. */
    private function otWithRows(string $table, string $column, string $cardKey): User
    {
        $pk = DB::table('user_credentials as u')
            ->where('u.user_category', 'S')
            ->whereIn('u.user_id', DB::table($table)->select($column))
            ->orderByDesc('u.pk')
            ->value('u.pk');
        $card = DB::table('dashboard_cards')->where('key', $cardKey)->value('id');
        $home = DB::table('sidebar_categories')->where('slug', 'home')->value('id');
        if (! $pk || ! $card || ! $home) {
            $this->markTestSkipped("needs an OT login with $table rows, the $cardKey card and the home module");
        }
        $role = DB::table('roles')->insertGetId([
            'name' => 'OT course scope test '.uniqid(), 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('model_has_roles')->insert(['role_id' => $role, 'model_type' => User::class, 'model_id' => $pk]);
        DB::table('role_dashboard_cards')->insert(['role_id' => $role, 'dashboard_card_id' => $card, 'sidebar_category_id' => $home]);

        return User::find($pk);
    }

    private function cardCount(User $user, string $key): mixed
    {
        return $this->as($user, ['Student-OT'])->getJson(route('admin.dashboard.cards'))->assertOk()->json("counts.$key");
    }

    public function test_medical_exception_counts_the_rows_its_page_lists(): void
    {
        $user = $this->otWithRows('student_medical_exemption', 'student_master_pk', 'medical_exception');
        $page = new \ReflectionMethod(MedicalExceptionOTViewController::class, 'studentExemptions');
        $page->setAccessible(true);
        $listed = $page->invoke(app(MedicalExceptionOTViewController::class), (int) $user->user_id)->count();

        $this->assertSame($listed, $this->cardCount($user, 'medical_exception'));
    }

    public function test_mdo_escort_counts_the_rows_its_page_lists(): void
    {
        $user = $this->otWithRows('mdo_escot_duty_map', 'selected_student_list', 'ot_mdo_escort');
        $page = new \ReflectionMethod(OTMDOEscrotExemptionController::class, 'buildDutyMaps');
        $page->setAccessible(true);
        $listed = count($page->invoke(app(OTMDOEscrotExemptionController::class), $user->user_id, now()->format('Y-m-d')));

        $this->assertSame($listed, $this->cardCount($user, 'ot_mdo_escort'));
    }
}
