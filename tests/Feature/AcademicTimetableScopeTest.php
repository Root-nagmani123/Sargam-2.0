<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * ?scope=academy — the Academic Timetable view — is the whole Academy's session
 * feed. It was meant for faculty-portal logins, but also admitted every officer
 * trainee and dropped their group narrowing, so an OT read every course's sessions
 * (topic, venue, faculty, groups) by adding ?scope=academy (PR #334 F-047).
 *
 * Fixture: two sessions on one day, one for the OT's own group and one for a
 * course the OT is not in. Rows roll back.
 */
class AcademicTimetableScopeTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const DAY = '2031-04-15';

    /** @return array{ot: User, own: int, foreign: int} */
    private function fixture(): array
    {
        $row = DB::table('user_credentials as u')
            ->join('student_course_group_map as scgm', 'scgm.student_master_pk', '=', 'u.user_id')
            ->join('group_type_master_course_master_map as g', 'g.pk', '=', 'scgm.group_type_master_course_master_map_pk')
            ->where('u.user_category', 'S')
            ->where('scgm.active_inactive', 1)
            ->orderByDesc('scgm.pk')
            ->first(['u.pk as user_pk', 'u.user_id as student', 'g.pk as grp', 'g.course_name as course']);
        $venue = DB::table('venue_master')->value('venue_id');

        if (! $row || $venue === null) {
            $this->markTestSkipped('needs an OT in a group and a venue');
        }

        // Every course the OT is tied to, by enrolment or by group; nulls dropped, as
        // one NULL in a NOT IN list matches nothing.
        $mine = DB::table('student_master_course__map')->where('student_master_pk', $row->student)->pluck('course_master_pk')
            ->merge(DB::table('student_course_group_map as s')
                ->join('group_type_master_course_master_map as g', 'g.pk', '=', 's.group_type_master_course_master_map_pk')
                ->where('s.student_master_pk', $row->student)->pluck('g.course_name'))
            ->filter(fn ($pk) => $pk !== null && $pk !== '')->map(fn ($pk) => (int) $pk)->unique()->values()->all();

        $foreign = DB::table('group_type_master_course_master_map as g')
            ->join('course_master as cm', 'cm.pk', '=', 'g.course_name')
            ->whereNotIn('cm.pk', $mine ?: [-1])
            ->orderByDesc('g.pk')
            ->first(['g.pk as grp', 'cm.pk as course']);
        $foreignCourse = $foreign->course ?? null;
        $foreignGroup = $foreign->grp ?? null;

        if (! $foreignCourse || ! $foreignGroup) {
            $this->markTestSkipped('needs a course with a group the OT is not in');
        }

        $event = function (int $course, int $group, string $topic) use ($venue): int {
            $pk = (int) DB::table('timetable')->insertGetId([
                'course_master_pk' => $course,
                'subject_master_pk' => 0,
                'subject_module_master_pk' => 0,
                'subject_topic' => $topic,
                'course_group_type_master' => 0,
                'group_name' => json_encode([(string) $group]),
                'venue_id' => $venue,
                'class_session' => '10:00 to 11:00',
                'START_DATE' => self::DAY,
                'END_DATE' => self::DAY,
                'active_inactive' => 1,
            ]);
            DB::table('course_group_timetable_mapping')->insert(['group_pk' => $group, 'Programme_pk' => $course, 'timetable_pk' => $pk]);

            return $pk;
        };

        return [
            'ot' => User::findOrFail($row->user_pk),
            'own' => $event((int) $row->course, (int) $row->grp, 'PR334 F-047 own'),
            'foreign' => $event((int) $foreignCourse, (int) $foreignGroup, 'PR334 F-047 foreign'),
        ];
    }

    /** Event ids the feed returns for the day. */
    private function feedIds($as, array $query = []): array
    {
        $response = $as->getJson(route('calendar.event.calendar-details', $query + ['start' => self::DAY, 'end' => '2031-04-16']));
        $response->assertOk();

        return collect($response->json())->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
    }

    public function test_an_ot_asking_for_the_academy_scope_gets_only_their_own_groups_sessions(): void
    {
        $f = $this->fixture();

        $ids = $this->feedIds($this->as($f['ot'], ['Student-OT']), ['scope' => 'academy']);

        $this->assertContains($f['own'], $ids, 'their own session');
        $this->assertNotContains($f['foreign'], $ids, 'a course they are not enrolled in');
    }

    public function test_an_ot_asking_for_the_academy_page_is_sent_to_their_own_calendar(): void
    {
        $f = $this->fixture();

        $this->as($f['ot'], ['Student-OT'])
            ->get(route('calendar.index', ['scope' => 'academy']))
            ->assertRedirect(route('calendar.ot.index'));
    }

    /** Control: a faculty-portal login still gets the whole Academy. */
    public function test_a_faculty_login_still_gets_the_whole_academy(): void
    {
        $f = $this->fixture();
        $faculty = User::where('user_category', 'F')->orderBy('pk')->first();
        if (! $faculty) {
            $this->markTestSkipped('no faculty login');
        }

        $ids = $this->feedIds($this->as($faculty, ['Faculty']), ['scope' => 'academy']);

        $this->assertContains($f['own'], $ids);
        $this->assertContains($f['foreign'], $ids);
    }

    public function test_an_array_or_unreadable_range_does_not_fail_the_feed(): void
    {
        $f = $this->fixture();
        $as = $this->as($f['ot'], ['Student-OT']);

        $this->assertLessThan(500, $as->getJson(route('calendar.event.calendar-details', ['start' => ['x'], 'end' => ['y']]))->getStatusCode());
        $this->assertLessThan(500, $as->getJson(route('calendar.event.calendar-details', ['start' => 'junk', 'end' => 'junk']))->getStatusCode());
    }

    /** A range wider than any calendar view is clamped rather than answered whole. */
    public function test_a_wide_range_is_clamped(): void
    {
        $f = $this->fixture();
        $faculty = User::where('user_category', 'F')->orderBy('pk')->first();
        if (! $faculty) {
            $this->markTestSkipped('no faculty login');
        }

        $ids = $this->feedIds($this->as($faculty, ['Faculty']), ['scope' => 'academy', 'start' => '2030-01-01', 'end' => '2032-12-31']);

        $this->assertNotContains($f['foreign'], $ids, 'DAY is far beyond start + the feed limit');
    }
}
