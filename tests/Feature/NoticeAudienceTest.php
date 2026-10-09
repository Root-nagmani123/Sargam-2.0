<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Notice audience targeting (PR #334 F-008, F-009, F-014).
 *
 * department_master holds pk 0 (NIAR). A notice aimed at NIAR must store a D
 * row for 0 and reach NIAR's staff only — not every department, which is what
 * "no D rows" means to the feed. The notice and its audience rows are written
 * atomically, and rolling back the backfill migration must not strip audiences.
 */
class NoticeAudienceTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const NIAR = 0;

    private function postStaffNotice(array $departments, string $title)
    {
        return $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])->post(route('admin.notice.store'), [
            'notice_title' => $title,
            'description' => 'audience probe',
            'notice_type' => 'Office notice',
            'display_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'Staff/Faculty',
            'department_master_pks' => $departments,
            'staff_scope' => 'all',
        ]);
    }

    /** Notice pks the staff login sees in its live feed. */
    private function feedFor(User $employee): array
    {
        $this->actingAs($employee);
        session(['user_roles' => []]);

        return notice_feed_query_by_role('live')->pluck('notices_notification.pk')->map(fn ($pk) => (int) $pk)->all();
    }

    private function employeeLogin(?int $departmentPk = null): User
    {
        $row = DB::table('user_credentials as u')
            ->join('employee_master as e', 'e.pk', '=', 'u.user_id')
            ->where('u.user_category', 'E')
            ->where('e.department_master_pk', '>', 0)
            ->orderBy('u.pk')
            ->first(['u.pk', 'e.pk as employee_pk']);

        if (! $row) {
            $this->markTestSkipped('no staff login with a department');
        }

        if ($departmentPk !== null) {
            DB::table('employee_master')->where('pk', $row->employee_pk)->update(['department_master_pk' => $departmentPk]);
        }

        return User::findOrFail($row->pk);
    }

    public function test_a_niar_only_notice_stores_department_zero_and_reaches_only_niar(): void
    {
        if (! DB::table('department_master')->where('pk', self::NIAR)->exists()) {
            $this->markTestSkipped('department_master has no pk 0 row');
        }

        $title = 'NIAR probe ' . Str::random(8);
        $this->postStaffNotice([(string) self::NIAR], $title)->assertSessionHasNoErrors()->assertRedirect();

        $notice = DB::table('notices_notification')->where('notice_title', $title)->first();
        $this->assertNotNull($notice, 'the notice must be created');
        DB::table('notices_notification')->where('pk', $notice->pk)->update(['active_inactive' => 1]);

        $this->assertTrue(
            DB::table('notice_audience_map')->where('notices_notification_pk', $notice->pk)
                ->where('audience_type', 'D')->where('reference_pk', self::NIAR)->exists(),
            'department 0 must be stored as a D row, not dropped'
        );

        $outsider = $this->employeeLogin();
        $this->assertNotContains((int) $notice->pk, $this->feedFor($outsider), 'a non-NIAR employee must not see a NIAR-only notice');

        $niarStaff = $this->employeeLogin(self::NIAR);
        $this->assertContains((int) $notice->pk, $this->feedFor($niarStaff), 'NIAR staff must see their notice');
    }

    public function test_a_failed_audience_insert_leaves_no_notice_behind(): void
    {
        $failInserts = true;
        DB::connection()->beforeExecuting(function ($query) use (&$failInserts) {
            if ($failInserts && stripos($query, 'insert into `notice_audience_map`') === 0) {
                throw new \RuntimeException('simulated audience insert failure');
            }
        });

        $title = 'Atomic probe ' . Str::random(8);
        $department = (int) DB::table('department_master')->where('pk', '>', 0)->value('pk');

        $response = $this->postStaffNotice([(string) $department], $title);
        $failInserts = false;

        $this->assertSame(500, $response->getStatusCode(), 'the failure must surface, not be swallowed');
        $this->assertFalse(
            DB::table('notices_notification')->where('notice_title', $title)->exists(),
            'a notice whose audience could not be written must not be committed (it would read as "every department")'
        );
    }

    public function test_rolling_back_the_backfill_keeps_every_audience_row(): void
    {
        $migration = require base_path('database/migrations/2026_09_28_120000_backfill_notice_audience_map_scalars.php');

        DB::table('notice_audience_map')->insert([
            'notices_notification_pk' => (int) (DB::table('notices_notification')->value('pk') ?? 1),
            'audience_type' => 'C',
            'reference_pk' => 999999,
            'active_inactive' => 1,
        ]);
        $before = DB::table('notice_audience_map')->count();

        $migration->down();

        $this->assertSame($before, DB::table('notice_audience_map')->count(), 'down() must not delete form-written audience rows');
    }
}
