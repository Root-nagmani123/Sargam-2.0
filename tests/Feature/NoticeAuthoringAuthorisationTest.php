<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Notice authoring and its audience lookups carried only `auth` (PR #334 F-003,
 * F-013): an Officer Trainee could publish a notice into every dashboard, edit or
 * delete anyone's, and read the staff and OT directory through the lookups. The
 * description was also rendered unescaped in every reader's feed.
 *
 * Now every NoticeNotificationController action needs a notice author — Super
 * Admin, or a holder of a Notice menu permission (admin_notice / notice_sidebar),
 * trainees refused first — and the description is sanitised on save and at render.
 *
 * Every request goes through the real HTTP kernel; writes roll back.
 */
class NoticeAuthoringAuthorisationTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const PAYLOAD = '<p>PR334-SAFE-TEXT</p><img src=x onerror=alert(334334)><script>window.pr334=1</script>';

    /** A staff login whose ONLY role is $role. */
    private function staffWithOnlyRole(string $role): User
    {
        $roleId = DB::table('roles')->where('name', $role)->value('id');
        $pk = $roleId ? DB::table('user_credentials as u')
            ->where('u.user_category', '!=', 'S')
            ->whereExists(fn ($q) => $q->from('model_has_roles as m')->whereColumn('m.model_id', 'u.pk')
                ->where('m.model_type', User::class)->where('m.role_id', $roleId))
            ->whereNotExists(fn ($q) => $q->from('model_has_roles as m')->whereColumn('m.model_id', 'u.pk')
                ->where('m.model_type', User::class)->where('m.role_id', '!=', $roleId))
            ->orderBy('u.pk')
            ->value('u.pk') : null;

        if (! $pk) {
            $this->markTestSkipped("no staff login holding only the '{$role}' role");
        }

        return User::findOrFail($pk);
    }

    /** A staff login holding admin_notice through a role (Training-Induction on the dev copy), not Super Admin. */
    private function noticeAuthor(): User
    {
        $pk = DB::table('user_credentials as u')
            ->where('u.user_category', '!=', 'S')
            ->whereExists(fn ($q) => $q->from('model_has_roles as m')
                ->join('role_has_permissions as rp', 'rp.role_id', '=', 'm.role_id')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->whereColumn('m.model_id', 'u.pk')->where('m.model_type', User::class)
                ->where('p.name', 'admin_notice'))
            ->whereNotExists(fn ($q) => $q->from('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
                ->whereColumn('m.model_id', 'u.pk')->where('m.model_type', User::class)->where('r.name', 'Super Admin'))
            ->orderBy('u.pk')
            ->value('u.pk');

        if (! $pk) {
            $this->markTestSkipped('no non-Super-Admin staff login holds admin_notice');
        }

        return User::findOrFail($pk);
    }

    private function asUser(User $user)
    {
        return $this->as($user, $user->user_category === 'S'
            ? ['Student-OT']
            : $user->roles()->pluck('name')->all());
    }

    private function validNotice(string $title, string $description = 'authorisation probe'): array
    {
        return [
            'notice_title' => $title,
            'description' => $description,
            'notice_type' => 'Office notice',
            'display_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'All',
        ];
    }

    /** A live, everyone-addressed notice written straight to the table, as a pre-fix row would be. */
    private function rawNotice(string $description, int $active = 1, ?User $author = null): int
    {
        return (int) DB::table('notices_notification')->insertGetId([
            'notice_title' => 'PR334 raw notice',
            'description' => $description,
            'notice_type' => 'Office notice',
            'display_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'All',
            'audience_mode' => 'all',
            'active_inactive' => $active,
            'created_by' => (int) ($author ?? $this->staffWithRole('Super Admin'))->pk,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function lookupUrls(): array
    {
        $course = (int) DB::table('student_master_course__map')->orderByDesc('pk')->value('course_master_pk');
        $department = (int) DB::table('employee_master')->whereNotNull('department_master_pk')->value('department_master_pk');

        return [
            route('admin.notice.getCourses'),
            route('admin.notice.getGroupTypes', ['course_master_pks' => [$course]]),
            route('admin.notice.getStudents', ['course_master_pks' => [$course]]),
            route('admin.notice.getDepartments'),
            route('admin.notice.getEmployees', ['department_master_pks' => [$department]]),
        ];
    }

    private function assertRefusedEverywhere(User $actor): void
    {
        $existing = $this->rawNotice('<p>existing</p>', 0);
        $enc = Crypt::encrypt($existing);
        $before = DB::table('notices_notification')->count();

        $this->asUser($actor)->get(route('admin.notice.index'))->assertForbidden();
        $this->asUser($actor)->get(route('admin.notice.create'))->assertForbidden();
        $this->asUser($actor)->get(route('admin.notice.edit', $enc))->assertForbidden();
        $this->asUser($actor)->post(route('admin.notice.store'), $this->validNotice('PR334 denied store'))->assertForbidden();
        $this->asUser($actor)->put(route('admin.notice.update', $enc), $this->validNotice('PR334 denied update'))->assertForbidden();
        $this->asUser($actor)->delete(route('admin.notice.destroy', $enc))->assertForbidden();

        foreach ($this->lookupUrls() as $url) {
            $this->asUser($actor)->getJson($url)->assertForbidden();
        }

        // A 403 that still wrote would pass the status checks above.
        $this->assertSame($before, DB::table('notices_notification')->count(), 'nothing created');
        $this->assertSame('PR334 raw notice', DB::table('notices_notification')->where('pk', $existing)->value('notice_title'), 'not updated');
    }

    public function test_an_officer_trainee_is_refused_every_notice_action_and_lookup(): void
    {
        $this->assertRefusedEverywhere($this->officerTrainee());
    }

    public function test_an_employee_is_refused_every_notice_action_and_lookup(): void
    {
        $this->assertRefusedEverywhere($this->staffWithOnlyRole('Employee'));
    }

    public function test_a_notice_author_may_create_a_notice(): void
    {
        $before = DB::table('notices_notification')->count();

        $this->asUser($this->noticeAuthor())
            ->post(route('admin.notice.store'), $this->validNotice('PR334 author store'))
            ->assertRedirect(route('admin.notice.index'));

        $this->assertSame($before + 1, DB::table('notices_notification')->count());
    }

    public function test_a_notice_author_may_use_the_lookups_and_open_the_screens(): void
    {
        $author = $this->noticeAuthor();

        $this->asUser($author)->get(route('admin.notice.index'))->assertOk();
        $this->asUser($author)->get(route('admin.notice.create'))->assertOk();

        foreach ($this->lookupUrls() as $url) {
            $this->asUser($author)->getJson($url)->assertOk()->assertJsonPath('status', true);
        }
    }

    public function test_a_staff_super_admin_may_open_the_notice_list(): void
    {
        $this->asUser($this->staffWithRole('Super Admin'))->get(route('admin.notice.index'))->assertOk();
    }

    public function test_a_stored_description_is_sanitised_on_save(): void
    {
        $this->asUser($this->noticeAuthor())
            ->post(route('admin.notice.store'), $this->validNotice('PR334 xss store', self::PAYLOAD))
            ->assertRedirect();

        $stored = (string) DB::table('notices_notification')->where('notice_title', 'PR334 xss store')->value('description');

        $this->assertStringContainsString('PR334-SAFE-TEXT', $stored);
        $this->assertStringNotContainsString('onerror', $stored);
        $this->assertStringNotContainsString('<script', $stored);
    }

    public function test_a_payload_already_stored_is_neutralised_in_the_feed(): void
    {
        $this->rawNotice(self::PAYLOAD);

        $html = $this->asUser($this->staffWithRole('Super Admin'))
            ->get(route('admin.dashboard.feed', ['tab' => 'notices']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('PR334-SAFE-TEXT', $html, 'the notice must render, or this test proves nothing');
        $this->assertStringNotContainsString('onerror=alert(334334)', $html);
        $this->assertStringNotContainsString('window.pr334=1', $html);
    }

    public function test_a_payload_already_stored_is_neutralised_in_the_dashboard_modal_data(): void
    {
        $this->rawNotice(self::PAYLOAD);

        $html = $this->asUser($this->staffWithRole('Super Admin'))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('PR334-SAFE-TEXT', $html, 'the notice must render, or this test proves nothing');
        $this->assertStringNotContainsString('onerror=alert(334334)', $html);
        $this->assertStringNotContainsString('window.pr334=1', $html);
    }

    public function test_the_edit_form_cannot_be_broken_out_of_by_a_stored_description(): void
    {
        // The author's own notice: an author opens only their own (PR #334 F-039).
        $author = $this->noticeAuthor();
        $pk = $this->rawNotice('<p>PR334-SAFE-TEXT</p></textarea><script>window.pr334edit=1</script>', 1, $author);

        $html = $this->asUser($author)
            ->get(route('admin.notice.edit', Crypt::encrypt($pk)))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('PR334-SAFE-TEXT', $html);
        $this->assertStringNotContainsString('<script>window.pr334edit', $html);
    }
}
