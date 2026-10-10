<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #334 F-071: the edit form's course list is empty until an AJAX call fills it,
 * and an empty course list is how "every course" is posted. Saving inside that window,
 * or after the call failed, turned a notice for one trainee, group or course into a
 * notice for every Officer Trainee. The server now refuses to drop a stored course
 * targeting unless the author ticks the explicit "every Officer Trainee" box.
 */
class NoticeTargetedAudienceEditTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** Two category-S logins enrolled in different courses: [user, student pk, course pk] each. */
    private function twoTraineesInDifferentCourses(): array
    {
        $rows = DB::table('user_credentials as u')
            ->join('student_master_course__map as m', 'm.student_master_pk', '=', 'u.user_id')
            ->where('u.user_category', 'S')
            ->orderByDesc('u.pk')
            ->limit(500)
            ->get(['u.pk', 'u.user_id', 'm.course_master_pk']);

        $first = $rows->first();
        $second = $first ? $rows->first(function ($r) use ($rows, $first) {
            // Not enrolled in the first trainee's course at all.
            return $rows->where('pk', $r->pk)->where('course_master_pk', $first->course_master_pk)->isEmpty();
        }) : null;
        if (! $first || ! $second) {
            $this->markTestSkipped('needs two officer-trainee logins in different courses');
        }

        return [
            [User::findOrFail($first->pk), (int) $first->user_id, (int) $first->course_master_pk],
            [User::findOrFail($second->pk), (int) $second->user_id, (int) $second->course_master_pk],
        ];
    }

    private function admin()
    {
        return $this->as($this->staffWithRole('Super Admin'), ['Super Admin']);
    }

    private function payload(string $title, array $audience): array
    {
        return [
            'notice_title' => $title,
            'description' => 'F-071 probe',
            'notice_type' => 'Personal',
            'display_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'Office trainee',
        ] + $audience;
    }

    /** An individual notice to one trainee of one course, stored through the form, live. */
    private function individualNotice(int $course, int $student): int
    {
        $title = 'F-071 probe '.random_int(100000, 999999);
        $this->admin()->post(route('admin.notice.store'), $this->payload($title, [
            'course_master_pks' => [(string) $course],
            'ot_scope' => 'individual',
            'student_pks' => [(string) $student],
        ]))->assertSessionHasNoErrors();

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
        session(['user_roles' => ['Student-OT']]);

        return notice_feed_query_by_role('live')->pluck('notices_notification.pk')->map(fn ($pk) => (int) $pk)->all();
    }

    private function update(int $pk, array $payload)
    {
        return $this->admin()->put(route('admin.notice.update', Crypt::encrypt($pk)), $payload);
    }

    public function test_saving_with_the_course_list_not_loaded_keeps_a_targeted_notice_targeted(): void
    {
        [[, $student, $course], [$otherOt]] = $this->twoTraineesInDifferentCourses();
        $notice = $this->individualNotice($course, $student);
        $stored = $this->audience($notice);
        $this->assertSame(['individual', ["C:{$course}", "S:{$student}"]], $stored);
        $this->assertNotContains($notice, $this->feedFor($otherOt), 'control: a trainee of another course does not see it');

        // What the form posts while #courseSelect is still empty, or after its request failed.
        $this->update($notice, $this->payload('F-071 renamed', ['ot_scope' => 'individual']))
            ->assertSessionHasErrors('course_master_pks');

        $this->assertSame($stored, $this->audience($notice), 'audience unchanged');
        $this->assertNotSame('F-071 renamed', DB::table('notices_notification')->where('pk', $notice)->value('notice_title'), 'nothing saved');
        $this->assertNotContains($notice, $this->feedFor($otherOt), 'still not shown to a trainee of another course');
    }

    public function test_the_explicit_every_officer_trainee_choice_still_widens_it(): void
    {
        [[, $student, $course], [$otherOt]] = $this->twoTraineesInDifferentCourses();
        $notice = $this->individualNotice($course, $student);

        $this->update($notice, $this->payload('F-071 widened', ['all_courses_confirmed' => '1']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.notice.index'));

        $this->assertSame(['all', []], $this->audience($notice));
        $this->assertContains($notice, $this->feedFor($otherOt), 'every Officer Trainee now sees it');
    }

    public function test_an_edit_that_reposts_the_saved_courses_saves_normally(): void
    {
        [[, $student, $course]] = $this->twoTraineesInDifferentCourses();
        $notice = $this->individualNotice($course, $student);

        $this->update($notice, $this->payload('F-071 title only', [
            'course_master_pks' => [(string) $course],
            'ot_scope' => 'individual',
            'student_pks' => [(string) $student],
        ]))->assertSessionHasNoErrors();

        $this->assertSame('F-071 title only', DB::table('notices_notification')->where('pk', $notice)->value('notice_title'));
        $this->assertSame(['individual', ["C:{$course}", "S:{$student}"]], $this->audience($notice));
    }

    public function test_changing_the_target_audience_is_not_refused(): void
    {
        [[, $student, $course]] = $this->twoTraineesInDifferentCourses();
        $notice = $this->individualNotice($course, $student);

        $this->update($notice, ['target_audience' => 'All'] + $this->payload('F-071 to everyone', []))
            ->assertSessionHasNoErrors();

        $this->assertSame('All', DB::table('notices_notification')->where('pk', $notice)->value('target_audience'));
        $this->assertSame(['all', []], $this->audience($notice));
    }

    public function test_a_notice_already_for_every_course_saves_with_the_list_empty(): void
    {
        $title = 'F-071 all '.random_int(100000, 999999);
        $this->admin()->post(route('admin.notice.store'), $this->payload($title, []))->assertSessionHasNoErrors();
        $notice = (int) DB::table('notices_notification')->where('notice_title', $title)->value('pk');

        $this->update($notice, $this->payload('F-071 all renamed', []))->assertSessionHasNoErrors();

        $this->assertSame(['all', []], $this->audience($notice));
        $this->assertSame('F-071 all renamed', DB::table('notices_notification')->where('pk', $notice)->value('notice_title'));
    }

    public function test_the_edit_form_offers_the_explicit_choice_for_a_targeted_notice(): void
    {
        [[, $student, $course]] = $this->twoTraineesInDifferentCourses();
        $notice = $this->individualNotice($course, $student);

        $this->admin()->get(route('admin.notice.edit', Crypt::encrypt($notice)))
            ->assertOk()
            ->assertSee('name="all_courses_confirmed"', false);
    }
}
