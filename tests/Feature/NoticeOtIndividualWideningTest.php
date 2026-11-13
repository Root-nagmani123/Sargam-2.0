<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #334 F-083, the Officer Trainee half of F-079: removing and re-adding the course
 * of a Personal (or Group) OT notice reset Recipients to All, and the save stored it
 * for the whole course. The server now refuses that unless the author ticks the
 * explicit "every Officer Trainee in the selected courses" box.
 */
class NoticeOtIndividualWideningTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** [course, student, another student of that course's category-S login, an active group of the course] */
    private function twoTraineesOfOneCourse(): array
    {
        $enrolled = DB::table('student_master_course__map as c')
            ->join('user_credentials as u', function ($j) {
                $j->on('u.user_id', '=', 'c.student_master_pk')->where('u.user_category', 'S');
            })
            // The trainee and course round 11 executed on, when this copy has them.
            ->orderByRaw('c.course_master_pk = 1996272429 desc, c.student_master_pk = 42305 desc')
            ->orderBy('c.course_master_pk')
            ->limit(2000)
            ->get(['c.course_master_pk as course', 'c.student_master_pk as student', 'u.pk as user_pk']);

        foreach ($enrolled->groupBy('course') as $course => $rows) {
            $first = $rows->first();
            $other = $rows->first(fn ($r) => (int) $r->student !== (int) $first->student);
            if ($other) {
                $group = (int) DB::table('group_type_master_course_master_map')->where('course_name', $course)->orderBy('pk')->value('pk');

                return [(int) $course, (int) $first->student, User::findOrFail($other->user_pk), $group];
            }
        }

        $this->markTestSkipped('needs two category-S trainees enrolled in one course');
    }

    private function admin()
    {
        return $this->as($this->staffWithRole('Super Admin'), ['Super Admin']);
    }

    private function payload(string $title, array $audience): array
    {
        return [
            'notice_title' => $title,
            'description' => 'F-083 probe',
            'notice_type' => 'Personal',
            'display_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
            'target_audience' => 'Office trainee',
        ] + $audience;
    }

    private function otNotice(array $audience): int
    {
        $title = 'F-083 probe '.random_int(100000, 999999);
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
        session(['user_roles' => ['Student-OT']]);

        return notice_feed_query_by_role('live')->pluck('notices_notification.pk')->map(fn ($pk) => (int) $pk)->all();
    }

    private function update(int $pk, array $payload)
    {
        return $this->admin()->put(route('admin.notice.update', Crypt::encrypt($pk)), $payload);
    }

    private function personalNotice(int $course, int $student): int
    {
        return $this->otNotice([
            'course_master_pks' => [(string) $course], 'ot_scope' => 'individual', 'student_pks' => [(string) $student],
        ]);
    }

    public function test_posting_all_without_the_box_does_not_widen_a_personal_notice(): void
    {
        [$course, $student, $classmate] = $this->twoTraineesOfOneCourse();
        $notice = $this->personalNotice($course, $student);
        $stored = ['individual', ["C:{$course}", "S:{$student}"]];
        $this->assertSame($stored, $this->audience($notice));
        $this->assertNotContains($notice, $this->feedFor($classmate), 'control: the classmate does not see it');

        // What the form posted after the course was removed and added back.
        $this->update($notice, $this->payload('F-083 widened silently', [
            'course_master_pks' => [(string) $course], 'ot_scope' => 'all',
        ]))->assertSessionHasErrors('ot_scope');

        $this->assertSame($stored, $this->audience($notice), 'audience unchanged');
        $this->assertNotSame('F-083 widened silently', DB::table('notices_notification')->where('pk', $notice)->value('notice_title'));
        $this->assertNotContains($notice, $this->feedFor($classmate), 'the classmate still does not see it');
    }

    public function test_a_crafted_save_with_no_or_a_malformed_scope_is_refused_too(): void
    {
        [$course, $student] = $this->twoTraineesOfOneCourse();
        $notice = $this->personalNotice($course, $student);
        $stored = $this->audience($notice);

        foreach ([[], ['ot_scope' => ['individual']], ['ot_scope' => 'all', 'all_course_trainees_confirmed' => '0']] as $crafted) {
            $this->update($notice, $this->payload('F-083 crafted', ['course_master_pks' => [(string) $course]] + $crafted))
                ->assertSessionHasErrors('ot_scope');
            $this->assertSame($stored, $this->audience($notice), 'audience unchanged for '.json_encode($crafted));
        }
    }

    public function test_a_group_notice_is_protected_the_same_way(): void
    {
        [$course, , $classmate, $group] = $this->twoTraineesOfOneCourse();
        if (! $group) {
            $this->markTestSkipped('the course has no group');
        }
        $notice = $this->otNotice(['course_master_pks' => [(string) $course], 'ot_scope' => 'group', 'group_type_map_pks' => [(string) $group]]);
        $stored = $this->audience($notice);

        $this->update($notice, $this->payload('F-083 group widened', [
            'course_master_pks' => [(string) $course], 'ot_scope' => 'all',
        ]))->assertSessionHasErrors('ot_scope');

        $this->assertSame($stored, $this->audience($notice));
    }

    public function test_the_explicit_box_still_widens_it_to_the_course(): void
    {
        [$course, $student, $classmate] = $this->twoTraineesOfOneCourse();
        $notice = $this->personalNotice($course, $student);

        $this->update($notice, $this->payload('F-083 widened on purpose', [
            'course_master_pks' => [(string) $course], 'ot_scope' => 'all', 'all_course_trainees_confirmed' => '1',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(['all', ["C:{$course}"]], $this->audience($notice));
        $this->assertContains($notice, $this->feedFor($classmate), 'the classmate now sees it');
    }

    public function test_keeping_individual_and_editing_an_all_notice_save_normally(): void
    {
        [$course, $student] = $this->twoTraineesOfOneCourse();
        $personal = $this->personalNotice($course, $student);

        $this->update($personal, $this->payload('F-083 kept individual', [
            'course_master_pks' => [(string) $course], 'ot_scope' => 'individual', 'student_pks' => [(string) $student],
        ]))->assertSessionHasNoErrors();
        $this->assertSame(['individual', ["C:{$course}", "S:{$student}"]], $this->audience($personal));

        $all = $this->otNotice(['course_master_pks' => [(string) $course], 'ot_scope' => 'all']);
        $this->update($all, $this->payload('F-083 all edited', [
            'course_master_pks' => [(string) $course], 'ot_scope' => 'all',
        ]))->assertSessionHasNoErrors();
        $this->assertSame(['all', ["C:{$course}"]], $this->audience($all));
    }

    public function test_the_edit_form_offers_the_box_only_for_a_personal_or_group_notice(): void
    {
        [$course, $student] = $this->twoTraineesOfOneCourse();
        $personal = $this->personalNotice($course, $student);
        $all = $this->otNotice(['course_master_pks' => [(string) $course], 'ot_scope' => 'all']);

        $this->admin()->get(route('admin.notice.edit', Crypt::encrypt($personal)))
            ->assertOk()->assertSee('name="all_course_trainees_confirmed"', false);
        $this->admin()->get(route('admin.notice.edit', Crypt::encrypt($all)))
            ->assertOk()->assertDontSee('name="all_course_trainees_confirmed"', false);
    }
}
