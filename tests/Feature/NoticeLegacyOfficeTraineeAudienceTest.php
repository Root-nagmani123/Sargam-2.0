<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * "No course rows" means every course to the OT feed. A notice saved before
 * audience targeting (audience_mode NULL) with no course reached nobody on base,
 * and the backfill wrote no course row for it, so after deploy it would have
 * reached every OT (PR #334 F-046). Such a notice now still reaches nobody; a
 * notice saved through the targeting form keeps the every-course meaning.
 *
 * Notices are copies of an existing row, written inside the rolled-back
 * transaction.
 */
class NoticeLegacyOfficeTraineeAudienceTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function otWithCourse(): array
    {
        $row = DB::table('user_credentials as u')
            ->join('student_master_course__map as m', 'm.student_master_pk', '=', 'u.user_id')
            ->where('u.user_category', 'S')
            ->orderByDesc('u.pk')
            ->first(['u.pk', 'm.course_master_pk']);
        if (! $row) {
            $this->markTestSkipped('no officer trainee login with a course');
        }

        return [User::findOrFail($row->pk), (int) $row->course_master_pk];
    }

    private function notice(?string $mode, ?int $coursePk, ?int $courseRow = null): int
    {
        $template = DB::table('notices_notification')->orderByDesc('pk')->first();
        if (! $template) {
            $this->markTestSkipped('no notice row to copy');
        }
        $row = (array) $template;
        unset($row['pk']);

        $pk = (int) DB::table('notices_notification')->insertGetId(array_merge($row, [
            'notice_title' => 'F-046 probe',
            'target_audience' => 'Office trainee',
            'active_inactive' => 1,
            'display_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
            'course_master_pk' => $coursePk,
            'group_type_map_pk' => null,
            'department_master_pk' => null,
            'audience_mode' => $mode,
        ]));

        if ($courseRow !== null) {
            DB::table('notice_audience_map')->insert([
                'notices_notification_pk' => $pk,
                'audience_type' => 'C',
                'reference_pk' => $courseRow,
                'active_inactive' => 1,
            ]);
        }

        return $pk;
    }

    private function feedFor(User $user): array
    {
        $this->actingAs($user);
        session(['user_roles' => ['Student-OT']]);

        return notice_feed_query_by_role('live')->pluck('notices_notification.pk')->map(fn ($pk) => (int) $pk)->all();
    }

    public function test_a_pre_targeting_notice_with_no_course_reaches_no_ot(): void
    {
        [$ot] = $this->otWithCourse();
        $legacy = $this->notice(null, null);

        $this->assertNotContains($legacy, $this->feedFor($ot));
    }

    public function test_a_pre_targeting_notice_for_the_ots_course_still_reaches_them(): void
    {
        [$ot, $course] = $this->otWithCourse();
        $legacy = $this->notice(null, $course, $course);

        $this->assertContains($legacy, $this->feedFor($ot));
    }

    public function test_a_form_saved_notice_with_no_course_reaches_every_ot(): void
    {
        [$ot] = $this->otWithCourse();
        $selectAll = $this->notice('all', null);

        $this->assertContains($selectAll, $this->feedFor($ot));
    }

    /* ------------------------------------------------------------------
     | Editing such a notice (PR #334 F-032). The edit form shows its course
     | list empty, which the update path read as "Select All": a title-only
     | save wrote audience_mode 'all' and published it to every OT.
     * ----------------------------------------------------------------- */

    private function editPayload(string $title, array $extra = []): array
    {
        return array_merge([
            'notice_title' => $title,
            'description' => 'F-032 probe',
            'notice_type' => 'Office notice',
            'display_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'Office trainee',
        ], $extra);
    }

    private function update(int $pk, array $payload)
    {
        $author = $this->staffWithRole('Super Admin');

        return $this->as($author, ['Super Admin'])
            ->put(route('admin.notice.update', \Illuminate\Support\Facades\Crypt::encrypt($pk)), $payload);
    }

    public function test_a_title_only_edit_of_a_courseless_legacy_notice_keeps_it_from_every_ot(): void
    {
        [$ot] = $this->otWithCourse();
        $legacy = $this->notice(null, null);

        $this->update($legacy, $this->editPayload('F-032 renamed'))->assertRedirect(route('admin.notice.index'));

        $row = DB::table('notices_notification')->where('pk', $legacy)->first();
        $this->assertSame('F-032 renamed', $row->notice_title, 'the edit itself must land');
        $this->assertNull($row->audience_mode, 'audience left as stored');
        $this->assertNotContains($legacy, $this->feedFor($ot));
    }

    public function test_the_explicit_every_course_choice_does_publish_a_legacy_notice(): void
    {
        [$ot] = $this->otWithCourse();
        $legacy = $this->notice(null, null);

        $this->update($legacy, $this->editPayload('F-032 widened', ['all_courses_confirmed' => '1']))
            ->assertRedirect(route('admin.notice.index'));

        $this->assertSame('all', DB::table('notices_notification')->where('pk', $legacy)->value('audience_mode'));
        $this->assertContains($legacy, $this->feedFor($ot));
    }

    public function test_the_edit_form_and_list_say_a_courseless_legacy_notice_reaches_no_ot(): void
    {
        $legacy = $this->notice(null, null);

        $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->get(route('admin.notice.edit', \Illuminate\Support\Facades\Crypt::encrypt($legacy)))
            ->assertOk()
            ->assertSee('not shown to any Officer Trainee', false)
            ->assertSee('name="all_courses_confirmed"', false);

        $this->assertSame(
            'No course (not shown to Officer Trainees)',
            \App\Models\NoticeNotification::findOrFail($legacy)->course_label
        );
    }
}
