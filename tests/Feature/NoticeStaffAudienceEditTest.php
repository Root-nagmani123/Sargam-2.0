<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #334 F-073: F-071's shape on the Staff/Faculty half of the notice form. The saved
 * departments, scope and employees are applied by script, so saving before it runs (or
 * after it fails) posts no department and scope "all", which stored a department- or
 * employee-targeted notice as one for every staff member. The server now refuses to
 * drop stored department / employee rows unless the author ticks the explicit "every
 * staff / faculty member" box.
 */
class NoticeStaffAudienceEditTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /**
     * Two category-E logins in different departments:
     * [target login, its employee pk, its department pk, login in another department].
     */
    private function staffInTwoDepartments(): array
    {
        $rows = DB::table('user_credentials as u')
            ->join('employee_master as e', 'e.pk', '=', 'u.user_id')
            ->where('u.user_category', 'E')
            ->whereNotNull('e.department_master_pk')
            ->orderBy('u.pk')
            ->limit(500)
            ->get(['u.pk', 'e.pk as employee', 'e.department_master_pk as department']);

        $first = $rows->first();
        $second = $first ? $rows->first(fn ($r) => (string) $r->department !== (string) $first->department) : null;
        if (! $first || ! $second) {
            $this->markTestSkipped('needs two staff logins in different departments');
        }

        return [User::findOrFail($first->pk), (int) $first->employee, (int) $first->department, User::findOrFail($second->pk)];
    }

    private function admin()
    {
        return $this->as($this->staffWithRole('Super Admin'), ['Super Admin']);
    }

    private function payload(string $title, array $audience): array
    {
        return [
            'notice_title' => $title,
            'description' => 'F-073 probe',
            'notice_type' => 'Personal',
            'display_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'Staff/Faculty',
        ] + $audience;
    }

    /** A Staff/Faculty notice stored through the form, live. */
    private function staffNotice(array $audience): int
    {
        $title = 'F-073 probe '.random_int(100000, 999999);
        $this->admin()->post(route('admin.notice.store'), $this->payload($title, $audience))->assertSessionHasNoErrors();

        $pk = (int) DB::table('notices_notification')->where('notice_title', $title)->value('pk');
        $this->assertGreaterThan(0, $pk, 'notice was stored');
        DB::table('notices_notification')->where('pk', $pk)->update(['active_inactive' => 1]);

        return $pk;
    }

    /** [kind => [notice pk, expected stored audience]] for a department and a one-employee notice. */
    private function targetedNotices(int $employee, int $department): array
    {
        return [
            'department' => [
                $this->staffNotice(['department_master_pks' => [(string) $department], 'staff_scope' => 'all']),
                ['all', ["D:{$department}"]],
            ],
            'one employee' => [
                $this->staffNotice([
                    'department_master_pks' => [(string) $department], 'staff_scope' => 'individual',
                    'employee_pks' => [(string) $employee],
                ]),
                ['individual', ["D:{$department}", "E:{$employee}"]],
            ],
        ];
    }

    private function audience(int $pk): array
    {
        $rows = DB::table('notice_audience_map')->where('notices_notification_pk', $pk)
            ->orderBy('audience_type')->orderBy('reference_pk')
            ->get(['audience_type', 'reference_pk'])
            ->map(fn ($r) => $r->audience_type.':'.$r->reference_pk)->all();

        return [DB::table('notices_notification')->where('pk', $pk)->value('audience_mode'), $rows];
    }

    private function feedFor(User $user): array
    {
        $this->actingAs($user);
        session(['user_roles' => ['Employee']]);

        return notice_feed_query_by_role('live')->pluck('notices_notification.pk')->map(fn ($pk) => (int) $pk)->all();
    }

    private function update(int $pk, array $payload)
    {
        return $this->admin()->put(route('admin.notice.update', Crypt::encrypt($pk)), $payload);
    }

    public function test_saving_before_the_saved_selection_is_applied_keeps_the_notice_targeted(): void
    {
        [$target, $employee, $department, $elsewhere] = $this->staffInTwoDepartments();

        foreach ($this->targetedNotices($employee, $department) as $kind => [$notice, $stored]) {
            $this->assertSame($stored, $this->audience($notice), "{$kind}: stored as targeted");
            $this->assertContains($notice, $this->feedFor($target), "{$kind}: control, the addressee sees it");
            $this->assertNotContains($notice, $this->feedFor($elsewhere), "{$kind}: control, another department does not");

            // What the form posts before its script applies the saved selection, or after it failed.
            $this->update($notice, $this->payload("F-073 renamed {$kind}", ['staff_scope' => 'all']))
                ->assertSessionHasErrors('department_master_pks');

            $this->assertSame($stored, $this->audience($notice), "{$kind}: audience unchanged");
            $this->assertNotSame("F-073 renamed {$kind}",
                DB::table('notices_notification')->where('pk', $notice)->value('notice_title'), "{$kind}: nothing saved");
            $this->assertNotContains($notice, $this->feedFor($elsewhere), "{$kind}: still not shown to another department");
        }
    }

    public function test_the_explicit_every_staff_member_choice_still_widens_it(): void
    {
        [, $employee, $department, $elsewhere] = $this->staffInTwoDepartments();

        foreach ($this->targetedNotices($employee, $department) as $kind => [$notice]) {
            $this->update($notice, $this->payload("F-073 widened {$kind}", ['staff_scope' => 'all', 'all_staff_confirmed' => '1']))
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('admin.notice.index'));

            $this->assertSame(['all', []], $this->audience($notice), "{$kind}: widened on purpose");
            $this->assertContains($notice, $this->feedFor($elsewhere), "{$kind}: every staff member now sees it");
        }
    }

    public function test_an_edit_that_reposts_the_saved_selection_saves_normally(): void
    {
        [, $employee, $department] = $this->staffInTwoDepartments();
        $notice = $this->staffNotice([
            'department_master_pks' => [(string) $department], 'staff_scope' => 'individual',
            'employee_pks' => [(string) $employee],
        ]);

        $this->update($notice, $this->payload('F-073 title only', [
            'department_master_pks' => [(string) $department], 'staff_scope' => 'individual',
            'employee_pks' => [(string) $employee],
        ]))->assertSessionHasNoErrors();

        $this->assertSame('F-073 title only', DB::table('notices_notification')->where('pk', $notice)->value('notice_title'));
        $this->assertSame(['individual', ["D:{$department}", "E:{$employee}"]], $this->audience($notice));
    }

    public function test_a_notice_already_for_every_staff_member_saves_with_the_list_empty(): void
    {
        $notice = $this->staffNotice(['staff_scope' => 'all']);

        $this->update($notice, $this->payload('F-073 all renamed', ['staff_scope' => 'all']))->assertSessionHasNoErrors();

        $this->assertSame(['all', []], $this->audience($notice));
        $this->assertSame('F-073 all renamed', DB::table('notices_notification')->where('pk', $notice)->value('notice_title'));
    }

    public function test_changing_the_target_audience_is_not_refused(): void
    {
        [, , $department] = $this->staffInTwoDepartments();
        $notice = $this->staffNotice(['department_master_pks' => [(string) $department], 'staff_scope' => 'all']);

        $this->update($notice, ['target_audience' => 'All'] + $this->payload('F-073 to everyone', []))
            ->assertSessionHasNoErrors();

        $this->assertSame('All', DB::table('notices_notification')->where('pk', $notice)->value('target_audience'));
        $this->assertSame(['all', []], $this->audience($notice));
    }

    public function test_the_edit_form_offers_the_explicit_choice_for_a_targeted_notice(): void
    {
        [, , $department] = $this->staffInTwoDepartments();
        $notice = $this->staffNotice(['department_master_pks' => [(string) $department], 'staff_scope' => 'all']);

        $this->admin()->get(route('admin.notice.edit', Crypt::encrypt($notice)))
            ->assertOk()
            ->assertSee('name="all_staff_confirmed"', false);
    }
}
