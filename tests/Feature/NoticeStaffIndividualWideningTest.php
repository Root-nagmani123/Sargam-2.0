<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #334 F-079: changing the departments of a notice for named staff reset Recipients
 * to All, so the next save posted staff_scope=all and stored a personal notice as one
 * for everyone in those departments. The server now refuses that unless the author
 * ticks the explicit "everyone in the selected departments" box.
 */
class NoticeStaffIndividualWideningTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** [employee pk, its department pk, another department pk, a login in that other department] */
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

        return [(int) $first->employee, (int) $first->department, (int) $second->department, User::findOrFail($second->pk)];
    }

    private function admin()
    {
        return $this->as($this->staffWithRole('Super Admin'), ['Super Admin']);
    }

    private function payload(string $title, array $audience): array
    {
        return [
            'notice_title' => $title,
            'description' => 'F-079 probe',
            'notice_type' => 'Personal',
            'display_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'Staff/Faculty',
        ] + $audience;
    }

    private function staffNotice(array $audience): int
    {
        $title = 'F-079 probe '.random_int(100000, 999999);
        $this->admin()->post(route('admin.notice.store'), $this->payload($title, $audience))->assertSessionHasNoErrors();

        $pk = (int) DB::table('notices_notification')->where('notice_title', $title)->value('pk');
        $this->assertGreaterThan(0, $pk, 'notice was stored');
        DB::table('notices_notification')->where('pk', $pk)->update(['active_inactive' => 1]);

        return $pk;
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

    private function personalNotice(int $employee, int $department): int
    {
        return $this->staffNotice([
            'department_master_pks' => [(string) $department], 'staff_scope' => 'individual',
            'employee_pks' => [(string) $employee],
        ]);
    }

    public function test_a_department_change_posting_all_does_not_widen_a_personal_notice(): void
    {
        [$employee, $department, $other, $elsewhere] = $this->staffInTwoDepartments();
        $notice = $this->personalNotice($employee, $department);
        $stored = ['individual', ["D:{$department}", "E:{$employee}"]];
        $this->assertSame($stored, $this->audience($notice));

        // What the form posted after a department change reset Recipients to All.
        $this->update($notice, $this->payload('F-079 widened silently', [
            'department_master_pks' => [(string) $department, (string) $other], 'staff_scope' => 'all',
        ]))->assertSessionHasErrors('staff_scope');

        $this->assertSame($stored, $this->audience($notice), 'audience unchanged');
        $this->assertNotSame('F-079 widened silently', DB::table('notices_notification')->where('pk', $notice)->value('notice_title'));
        $this->assertNotContains($notice, $this->feedFor($elsewhere), 'not shown to the other department');
    }

    public function test_the_explicit_box_still_widens_it_to_the_departments(): void
    {
        [$employee, $department, $other, $elsewhere] = $this->staffInTwoDepartments();
        $notice = $this->personalNotice($employee, $department);

        $this->update($notice, $this->payload('F-079 widened on purpose', [
            'department_master_pks' => [(string) $department, (string) $other], 'staff_scope' => 'all',
            'all_department_staff_confirmed' => '1',
        ]))->assertSessionHasNoErrors();

        $expected = [$department, $other];
        sort($expected);
        $this->assertSame(['all', array_map(fn ($d) => "D:{$d}", $expected)], $this->audience($notice));
        $this->assertContains($notice, $this->feedFor($elsewhere), 'the other department now sees it');
    }

    public function test_changing_departments_and_keeping_individual_saves_normally(): void
    {
        [$employee, $department, $other] = $this->staffInTwoDepartments();
        $notice = $this->personalNotice($employee, $department);

        $this->update($notice, $this->payload('F-079 kept individual', [
            'department_master_pks' => [(string) $department, (string) $other], 'staff_scope' => 'individual',
            'employee_pks' => [(string) $employee],
        ]))->assertSessionHasNoErrors();

        $this->assertSame('individual', $this->audience($notice)[0]);
        $this->assertContains("E:{$employee}", $this->audience($notice)[1]);
    }

    public function test_a_department_notice_for_all_changes_departments_normally(): void
    {
        [, $department, $other] = $this->staffInTwoDepartments();
        $notice = $this->staffNotice(['department_master_pks' => [(string) $department], 'staff_scope' => 'all']);

        $this->update($notice, $this->payload('F-079 all moved', [
            'department_master_pks' => [(string) $other], 'staff_scope' => 'all',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(['all', ["D:{$other}"]], $this->audience($notice));
    }

    public function test_the_edit_form_offers_the_box_only_for_a_personal_notice(): void
    {
        [$employee, $department] = $this->staffInTwoDepartments();
        $personal = $this->personalNotice($employee, $department);
        $departmentOnly = $this->staffNotice(['department_master_pks' => [(string) $department], 'staff_scope' => 'all']);

        $this->admin()->get(route('admin.notice.edit', Crypt::encrypt($personal)))
            ->assertOk()->assertSee('name="all_department_staff_confirmed"', false);
        $this->admin()->get(route('admin.notice.edit', Crypt::encrypt($departmentOnly)))
            ->assertOk()->assertDontSee('name="all_department_staff_confirmed"', false);
    }
}
