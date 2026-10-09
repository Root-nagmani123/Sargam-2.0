<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * The notice edit form lists departments server-side and re-selects the saved
 * ones in the browser. A saved department that has since been deactivated used
 * not to be listed, so it was not re-posted, and a notice with no department
 * left saved as "every department" (PR #334 F-020). The edit form now lists the
 * notice's own departments even when inactive; the create form is unchanged.
 */
class NoticeEditInactiveDepartmentTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function payload(int $departmentPk, string $title): array
    {
        return [
            'notice_title' => $title,
            'description' => 'inactive department probe',
            'notice_type' => 'Office notice',
            'display_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'Staff/Faculty',
            'department_master_pks' => [(string) $departmentPk],
            'staff_scope' => 'all',
        ];
    }

    /** @return array{0: int, 1: string} a notice pinned to a department that is then deactivated */
    private function noticeOnDeactivatedDepartment(): array
    {
        $departmentPk = DB::table('department_master')->where('active_inactive', 1)->where('pk', '>', 0)->orderBy('pk')->value('pk');
        if ($departmentPk === null) {
            $this->markTestSkipped('no active department');
        }
        $departmentPk = (int) $departmentPk;

        $title = 'F-020 probe ' . Str::random(8);
        $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->post(route('admin.notice.store'), $this->payload($departmentPk, $title))
            ->assertSessionHasNoErrors();

        $noticePk = (int) DB::table('notices_notification')->where('notice_title', $title)->value('pk');
        $this->assertGreaterThan(0, $noticePk, 'the notice must be created');

        DB::table('department_master')->where('pk', $departmentPk)->update(['active_inactive' => 0]);

        return [$departmentPk, Crypt::encrypt($noticePk)];
    }

    public function test_the_edit_form_still_lists_a_saved_department_that_was_deactivated(): void
    {
        [$departmentPk, $enc] = $this->noticeOnDeactivatedDepartment();

        $html = $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->get(route('admin.notice.edit', $enc))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<option value="' . $departmentPk . '">[^<]*\(inactive\)<\/option>/',
            $html,
            'the deactivated saved department must be offered, marked inactive'
        );
    }

    public function test_the_create_form_does_not_list_an_inactive_department(): void
    {
        [$departmentPk] = $this->noticeOnDeactivatedDepartment();

        $html = $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->get(route('admin.notice.create'))
            ->assertOk()
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/<option value="' . $departmentPk . '">/', $html);
    }

    public function test_saving_the_edit_keeps_the_department_row(): void
    {
        [$departmentPk, $enc] = $this->noticeOnDeactivatedDepartment();
        $noticePk = Crypt::decrypt($enc);

        $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->put(route('admin.notice.update', $enc), $this->payload($departmentPk, 'F-020 probe edited ' . Str::random(4)))
            ->assertSessionHasNoErrors();

        $this->assertTrue(
            DB::table('notice_audience_map')->where('notices_notification_pk', $noticePk)
                ->where('audience_type', 'D')->where('reference_pk', $departmentPk)->exists(),
            'the department pick must survive the edit'
        );
    }
}
