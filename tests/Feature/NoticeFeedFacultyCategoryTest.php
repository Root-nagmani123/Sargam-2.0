<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * user_category 'F' is a faculty login (is_faculty_portal_user()). The notice feed
 * read only E as staff/faculty, so a category-F login fell through to "All"
 * notices and lost every Staff/Faculty notice it saw before categories were read
 * (PR #334 F-021). Its user_id is a faculty_master.pk, so its department comes
 * from the faculty row's employee.
 */
class NoticeFeedFacultyCategoryTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** @return array{0: User, 1: int|null} a category-F login and its employee's department */
    private function facultyLogin(): array
    {
        $faculty = DB::table('faculty_master as f')
            ->join('employee_master as e', 'e.pk', '=', 'f.employee_master_pk')
            ->whereNotNull('e.department_master_pk')
            ->orderBy('f.pk')
            ->first(['f.pk', 'e.department_master_pk']);
        $loginPk = DB::table('user_credentials')->where('user_category', 'E')->orderBy('pk')->value('pk');

        if (! $faculty || ! $loginPk) {
            $this->markTestSkipped('need a faculty linked to an employee with a department, and a login to re-point');
        }

        // Rolled back with the test.
        DB::table('user_credentials')->where('pk', $loginPk)->update(['user_category' => 'F', 'user_id' => $faculty->pk]);

        return [User::findOrFail($loginPk), (int) $faculty->department_master_pk];
    }

    private function staffNotice(array $departments): int
    {
        $title = 'F-021 probe ' . Str::random(8);

        $this->as($this->userWithRole('Super Admin'), ['Super Admin'])->post(route('admin.notice.store'), [
            'notice_title' => $title,
            'description' => 'faculty category probe',
            'notice_type' => 'Office notice',
            'display_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'Staff/Faculty',
            'department_master_pks' => array_map('strval', $departments),
            'staff_scope' => 'all',
        ])->assertSessionHasNoErrors();

        return (int) DB::table('notices_notification')->where('notice_title', $title)->value('pk');
    }

    private function feedFor(User $user): array
    {
        $this->actingAs($user);
        session(['user_roles' => []]);

        return notice_feed_query_by_role('live')->pluck('notices_notification.pk')->map(fn ($pk) => (int) $pk)->all();
    }

    public function test_a_faculty_login_sees_a_staff_faculty_notice_for_every_department(): void
    {
        [$login] = $this->facultyLogin();
        $notice = $this->staffNotice([]);

        $this->assertContains($notice, $this->feedFor($login));
    }

    public function test_a_faculty_login_is_narrowed_by_its_employee_department(): void
    {
        [$login, $department] = $this->facultyLogin();

        $other = DB::table('department_master')->where('active_inactive', 1)->where('pk', '!=', $department)->value('pk');
        if ($other === null) {
            $this->markTestSkipped('need a second department');
        }

        $own = $this->staffNotice([$department]);
        $foreign = $this->staffNotice([(int) $other]);

        $feed = $this->feedFor($login);
        $this->assertContains($own, $feed, 'a notice pinned to the faculty\'s own department must reach it');
        $this->assertNotContains($foreign, $feed, 'a notice pinned to another department must not');
    }
}
