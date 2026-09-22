<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CalendarController;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pins what the printed time table (admin.calendar.pdf.ot-timetable-pdf) is given
 * for each cell, and the one structural rule the sheet cannot survive breaking.
 *
 * Four reported defects are covered here, all of them things the grid builder
 * decides rather than the template:
 *
 *   1. TIME was blank for any session whose slot is written "15:00 to 15:55".
 *      splitSessionTime() only knew the hyphen form, so the row was filed as
 *      untimed and printed in the trailing row with no hours at all.
 *   2. The cohort was named nowhere when the GROUP column was not drawn - which
 *      is most sheets, since the column is only drawn for a band split between
 *      two and four groups.
 *   3. Several session takers printed inside one pair of brackets,
 *      "(A Sharma, D Kumar)", instead of one pair each.
 *   4. The course line and its dates were missing from the header whenever the
 *      calendar had no course filter set, even where every session on the sheet
 *      belonged to one course.
 *
 * And the structural rule: every printed row must fill the grid exactly once -
 * no row short a cell, no cell past the last column. Three separate bugs broke
 * it, each visible only on particular data, all three of them ending with a day
 * column sliding sideways or falling off the sheet:
 *
 *   a. a band split by group whose FIRST group has no name printed a blank
 *      label, which was read as "no GROUP column here" and gave the TIME cell
 *      colspan 2 while the rows it spanned each still emitted a GROUP cell;
 *   b. two sessions overlapping in one day left rows marked skip that no cell
 *      spanned;
 *   c. a break split across two time bands printed as a one-row band, leaving
 *      the row below it skipped but unspanned.
 *
 * Per phpunit.xml this suite runs against the database .env points at. The group,
 * faculty and course rows are created inside a transaction and rolled back; the
 * timetable rows are plain objects and never touch the database.
 *
 * Run with:  php artisan test --filter=TimetablePdfGrid
 */
class TimetablePdfGridTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Fixture ids, high enough not to collide with real rows.
     *
     * The group rows mirror how a course really carries them: a group belongs
     * to a type AND a course, and the groups sharing a (type, course) are the
     * GROUP axis the sheet prints. Three types, each a different shape:
     *
     *   Lecture Group -> A, B, Full Group   (an axis of two, plus "everybody")
     *   Seminar       -> Workshop Group     (one group: no axis to draw)
     *   Language      -> "" , Z             (an axis led by a group with no name)
     */
    private const COURSE_PK        = 90100001;
    private const TYPE_LECTURE_PK  = 90100002;
    private const TYPE_SEMINAR_PK  = 90100009;
    private const TYPE_LANGUAGE_PK = 90100010;
    private const GROUP_A_PK       = 90100003;
    private const GROUP_B_PK       = 90100004;
    private const GROUP_NONE_PK    = 90100005;   // a group row with no name
    private const GROUP_Z_PK       = 90100011;   // its only sibling
    private const GROUP_SOLO_PK    = 90100012;   // the only group of its type
    private const GROUP_FULL_PK    = 90100008;   // the course's whole-cohort group
    private const GROUP_FULL_2_PK  = 90100016;   // a second course's, same name
    private const TYPE_ICE_PK      = 90100013;
    private const GROUP_N1_PK      = 90100014;
    private const GROUP_N2_PK      = 90100015;
    private const FACULTY_1_PK     = 90100006;
    private const FACULTY_2_PK     = 90100007;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('course_master')->insert([
            'pk'               => self::COURSE_PK,
            'course_name'      => 'Fixture Induction Programme',
            'couse_short_name' => 'FIP-2026',
            'course_year'      => 2026,
            'start_year'       => '2026-01-05',
            'end_date'         => '2026-01-30',
        ]);

        DB::table('course_group_type_master')->insert([
            ['pk' => self::TYPE_LECTURE_PK,  'type_name' => 'Lecture Group'],
            ['pk' => self::TYPE_SEMINAR_PK,  'type_name' => 'Seminar'],
            ['pk' => self::TYPE_LANGUAGE_PK, 'type_name' => 'Language Group'],
        ]);

        // type_name and course_name hold the type pk and the course pk, not names.
        DB::table('group_type_master_course_master_map')->insert([
            ['pk' => self::GROUP_A_PK,    'type_name' => self::TYPE_LECTURE_PK,  'course_name' => self::COURSE_PK, 'group_name' => 'Group A'],
            ['pk' => self::GROUP_B_PK,    'type_name' => self::TYPE_LECTURE_PK,  'course_name' => self::COURSE_PK, 'group_name' => 'Group B'],
            ['pk' => self::GROUP_FULL_PK, 'type_name' => self::TYPE_LECTURE_PK,  'course_name' => self::COURSE_PK, 'group_name' => 'Full Group'],
            ['pk' => self::GROUP_SOLO_PK, 'type_name' => self::TYPE_SEMINAR_PK,  'course_name' => self::COURSE_PK, 'group_name' => 'Workshop Group'],
            ['pk' => self::GROUP_NONE_PK, 'type_name' => self::TYPE_LANGUAGE_PK, 'course_name' => self::COURSE_PK, 'group_name' => ''],
            ['pk' => self::GROUP_Z_PK,    'type_name' => self::TYPE_LANGUAGE_PK, 'course_name' => self::COURSE_PK, 'group_name' => 'Z'],
        ]);

        $faculty = [
            'faculty_type'              => 'Internal',
            'country_master_pk'         => 0,
            'state_master_pk'           => 0,
            'state_district_mapping_pk' => 0,
            'city_master_pk'            => 0,
        ];
        DB::table('faculty_master')->insert([
            $faculty + ['pk' => self::FACULTY_1_PK, 'first_name' => 'Asha',  'full_name' => 'Asha Sharma'],
            $faculty + ['pk' => self::FACULTY_2_PK, 'first_name' => 'Deven', 'full_name' => 'Deven Kumar'],
        ]);
    }

    /** One timetable row as the PDF queries select it. */
    private function event(array $attrs): object
    {
        return (object) array_merge([
            'course_master_pk' => self::COURSE_PK,
            'subject_topic'    => 'Session',
            'class_session'    => '09:00 AM - 10:00 AM',
            'START_DATE'       => '2026-01-19',
            'faculty_master'   => json_encode([self::FACULTY_1_PK]),
            'faculty_details'  => null,
            'group_name'       => '[]',
            'is_break'         => 0,
            'break_type'       => null,
            'break_start_time' => null,
            'break_end_time'   => null,
            'venue_name'       => 'Main Hall',
            'venue_short_name' => 'MH',
        ], $attrs);
    }

    private function buildWeeks(array $events, string $start, string $end, $course = null): array
    {
        $method = new ReflectionMethod(CalendarController::class, 'buildWeeksGrid');
        $method->setAccessible(true);

        return $method->invoke(
            app(CalendarController::class),
            collect($events),
            Carbon::parse($start),
            Carbon::parse($end),
            $course
        );
    }

    /** Every printed cell of a week, flattened. */
    private function cells(array $week): array
    {
        $out = [];
        foreach ($week['rows'] as $row) {
            if ($row['type'] !== 'row') {
                continue;
            }
            foreach ($row['cells'] as $cell) {
                if ($cell['state'] === 'skip') {
                    continue;
                }
                foreach ($cell['events'] as $event) {
                    $out[] = $event + ['_from' => $row['from'], '_to' => $row['to'], '_group' => $row['groupLabel']];
                }
            }
        }

        return $out;
    }

    /**
     * Lay the week out the way an HTML table does - every cell takes the
     * leftmost free slot - and fail on any day cell that does not land under
     * its own day, or any row that needs more columns than the header declares.
     */
    private function assertGridIsSquare(array $week, string $context = ''): void
    {
        $cols     = 1 + ($week['showGroupCol'] ? 1 : 0) + count($week['days']);
        $leadCols = 1 + ($week['showGroupCol'] ? 1 : 0);
        $pending  = array_fill(0, $cols, 0);

        foreach ($week['rows'] as $i => $row) {
            $free = [];
            for ($x = 0; $x < $cols; $x++) {
                if ($pending[$x] > 0) {
                    $pending[$x]--;
                } else {
                    $free[] = $x;
                }
            }

            $take = function (int $span, int $rowspan, string $what) use (&$free, &$pending, $i, $context, $cols) {
                $this->assertGreaterThanOrEqual(
                    $span,
                    count($free),
                    "$context row $i: $what has no column left to print in - the row needs more than the $cols the header declares"
                );
                $at = $free[0];
                for ($k = 0; $k < $span; $k++) {
                    $pending[array_shift($free)] = $rowspan - 1;
                }
                return $at;
            };

            if ($row['type'] === 'band') {
                foreach ($row['segments'] as $segment) {
                    $take((int) $segment['colspan'], 1, 'break band');
                }
                continue;
            }

            if ($row['showTime']) {
                $take((int) $row['timeColspan'], (int) $row['timeRowspan'], 'TIME');
            }
            if ($week['showGroupCol'] && $row['timeColspan'] === 1) {
                $take(1, 1, 'GROUP');
            }

            foreach (array_values($week['days']) as $d => $day) {
                $cell = $row['cells'][$day['key']];
                if ($cell['state'] === 'skip') {
                    continue;
                }
                $at = $take(1, (int) $cell['rowspan'], "day {$day['dayName']}");
                $this->assertSame(
                    $leadCols + $d,
                    $at,
                    "$context row $i: {$day['dayName']} printed in column $at instead of its own"
                );
            }
        }
    }

    /** Defect 1: "15:00 to 15:55" is a time band, not an untimed session. */
    public function test_a_slot_written_with_to_is_read_as_a_time_band(): void
    {
        $weeks = $this->buildWeeks([
            $this->event(['class_session' => '15:00 to 15:55', 'subject_topic' => 'Role of CBI']),
        ], '2026-01-19', '2026-01-25');

        $cells = $this->cells($weeks[0]);

        $this->assertCount(1, $cells);
        $this->assertSame('Role of CBI', $cells[0]['topic']);
        $this->assertSame('1500', $cells[0]['_from'], 'the slot\'s start hour never reached the TIME cell');
        $this->assertSame('1555', $cells[0]['_to'], 'the slot\'s end hour never reached the TIME cell');
    }

    /** The hyphen form keeps working. */
    public function test_a_slot_written_with_a_hyphen_is_still_read_as_a_time_band(): void
    {
        $weeks = $this->buildWeeks([
            $this->event(['class_session' => '03:30 PM - 04:30 PM']),
        ], '2026-01-19', '2026-01-25');

        $cells = $this->cells($weeks[0]);

        $this->assertSame('1530', $cells[0]['_from']);
        $this->assertSame('1630', $cells[0]['_to']);
    }

    /** Defect 3: one pair of brackets per session taker, not one for all of them. */
    public function test_each_session_taker_is_handed_over_separately(): void
    {
        $weeks = $this->buildWeeks([
            $this->event(['faculty_master' => json_encode([self::FACULTY_1_PK, self::FACULTY_2_PK])]),
        ], '2026-01-19', '2026-01-25');

        $cells = $this->cells($weeks[0]);

        $this->assertSame(['Asha Sharma', 'Deven Kumar'], $cells[0]['faculty']);
    }

    /** A bare pk, as older rows store it, names its faculty too. */
    public function test_a_faculty_column_holding_a_bare_pk_still_names_the_faculty(): void
    {
        $weeks = $this->buildWeeks([
            $this->event(['faculty_master' => (string) self::FACULTY_1_PK]),
        ], '2026-01-19', '2026-01-25');

        $cells = $this->cells($weeks[0]);

        $this->assertSame(['Asha Sharma'], $cells[0]['faculty']);
    }

    /**
     * Defect 2, the reported shape: the GROUP column lists the course's groups
     * down the left even though no session singles one of them out, and the
     * session for everybody spans them all.
     *
     * Every session in this database is stored against its type's "Full Group"
     * row - not one names A or B - so an axis read off the sessions is always
     * empty and the column never appeared.
     */
    public function test_the_group_column_lists_the_courses_groups_and_a_whole_cohort_session_spans_them(): void
    {
        $weeks = $this->buildWeeks([
            $this->event([
                'group_name'    => json_encode([self::GROUP_FULL_PK]),
                'subject_topic' => 'Combating Drug Menace',
            ]),
        ], '2026-01-19', '2026-01-25');

        $this->assertTrue($weeks[0]['showGroupCol'], 'the course has A and B, so the sheet has a GROUP column');

        $rows = array_values(array_filter($weeks[0]['rows'], static fn ($r) => $r['type'] === 'row'));
        $this->assertSame(['A', 'B'], array_column($rows, 'groupLabel'), 'GROUP is a one-letter column: "Group A" prints as "A"');

        // One cell, spanning both group rows, printed on the first of them.
        $monday = 1;
        $this->assertSame(2, $rows[0]['cells'][$monday]['rowspan']);
        $this->assertSame('Combating Drug Menace', $rows[0]['cells'][$monday]['events'][0]['topic']);
        $this->assertSame('skip', $rows[1]['cells'][$monday]['state']);

        $this->assertGridIsSquare($weeks[0]);
    }

    /** A session singling out one group takes that group's row alone. */
    public function test_a_session_for_one_group_takes_only_that_row(): void
    {
        $weeks = $this->buildWeeks([
            $this->event(['group_name' => json_encode([self::GROUP_A_PK]), 'subject_topic' => 'A stream']),
        ], '2026-01-19', '2026-01-25');

        $rows   = array_values(array_filter($weeks[0]['rows'], static fn ($r) => $r['type'] === 'row'));
        $monday = 1;

        $this->assertSame(['A', 'B'], array_column($rows, 'groupLabel'), 'GROUP is a one-letter column: "Group A" prints as "A"');
        $this->assertSame('A stream', $rows[0]['cells'][$monday]['events'][0]['topic']);
        $this->assertSame(1, $rows[0]['cells'][$monday]['rowspan']);
        $this->assertSame([], $rows[1]['cells'][$monday]['events'], 'Group B has no session at this hour');
    }

    /**
     * GROUP is a narrow column, so a group's label is cut down to the token
     * that tells it from its siblings. "Group No.01" and "Group No.02" both cut
     * to "Group No" unless the number is what survives, and a column reading
     * the same on every row tells the reader nothing.
     */
    public function test_numbered_groups_keep_their_number_in_the_group_column(): void
    {
        DB::table('course_group_type_master')->insert([
            'pk' => self::TYPE_ICE_PK, 'type_name' => 'Ice breaking',
        ]);
        DB::table('group_type_master_course_master_map')->insert([
            ['pk' => self::GROUP_N1_PK, 'type_name' => self::TYPE_ICE_PK, 'course_name' => self::COURSE_PK, 'group_name' => 'Group No.01'],
            ['pk' => self::GROUP_N2_PK, 'type_name' => self::TYPE_ICE_PK, 'course_name' => self::COURSE_PK, 'group_name' => 'Group No.02'],
        ]);

        $weeks = $this->buildWeeks([
            $this->event(['group_name' => json_encode([self::GROUP_N1_PK])]),
        ], '2026-01-19', '2026-01-25');

        $rows = array_values(array_filter($weeks[0]['rows'], static fn ($r) => $r['type'] === 'row'));

        $this->assertSame(['01', '02'], array_column($rows, 'groupLabel'));
    }

    /**
     * A band with nothing to split still fills the GROUP column: every session
     * in it names the same group, so that name goes in the column rather than
     * the TIME cell swallowing it. This is most of the sheet - four fifths of
     * the sessions in this database are one group ("Full Group") all week.
     */
    public function test_an_unsplit_band_puts_its_group_in_the_group_column(): void
    {
        $weeks = $this->buildWeeks([
            $this->event(['group_name' => json_encode([self::GROUP_SOLO_PK])]),
        ], '2026-01-19', '2026-01-25');

        $this->assertTrue($weeks[0]['showGroupCol'], 'the group has to be visible somewhere');

        $rows = array_values(array_filter($weeks[0]['rows'], static fn ($r) => $r['type'] === 'row'));
        $this->assertSame('Workshop Group', $rows[0]['groupLabel']);
        $this->assertSame(1, $rows[0]['timeColspan'], 'TIME must not swallow a GROUP cell that has content');
        $this->assertSame('', $this->cells($weeks[0])[0]['groupNames'], 'the column says it, so the cell should not');
        $this->assertGridIsSquare($weeks[0]);
    }

    /**
     * Past TT_MAX_GROUP_ROWS - the week of eighteen parallel language classes -
     * one sub-row each is unreadable, so the band stays single, the column goes
     * blank and the cells name their own groups instead.
     */
    public function test_a_band_with_too_many_groups_names_them_in_the_cells(): void
    {
        $events = [];
        foreach (range(1, 5) as $n) {
            $pk = 90100020 + $n;
            DB::table('group_type_master_course_master_map')->insert([
                'pk' => $pk, 'type_name' => self::TYPE_LANGUAGE_PK,
                'course_name' => self::COURSE_PK, 'group_name' => 'Language ' . $n,
            ]);
            $events[] = $this->event([
                'group_name'    => json_encode([$pk]),
                'subject_topic' => 'Class ' . $n,
            ]);
        }

        $weeks = $this->buildWeeks($events, '2026-01-19', '2026-01-25');
        $rows  = array_values(array_filter($weeks[0]['rows'], static fn ($r) => $r['type'] === 'row'));

        $this->assertCount(1, $rows, 'seven groups must not become seven sub-rows');
        $this->assertSame('', $rows[0]['groupLabel']);

        $byTopic = [];
        foreach ($this->cells($weeks[0]) as $cell) {
            $byTopic[$cell['topic']] = $cell['groupNames'];
        }
        $this->assertSame('Language 1', $byTopic['Class 1']);
        $this->assertSame('Language 5', $byTopic['Class 5']);
    }

    /** ...and stays quiet where the GROUP column already says it. */
    public function test_a_cell_stays_quiet_about_groups_the_group_column_carries(): void
    {
        $weeks = $this->buildWeeks([
            $this->event([
                'class_session' => '09:00 AM - 10:00 AM',
                'group_name'    => json_encode([self::GROUP_A_PK]),
                'subject_topic' => 'A stream',
            ]),
            $this->event([
                'class_session' => '09:00 AM - 10:00 AM',
                'group_name'    => json_encode([self::GROUP_B_PK]),
                'subject_topic' => 'B stream',
            ]),
        ], '2026-01-19', '2026-01-25');

        $this->assertTrue($weeks[0]['showGroupCol'], 'a band split between two groups draws the GROUP column');

        foreach ($this->cells($weeks[0]) as $cell) {
            $this->assertSame('', $cell['groupNames'], "{$cell['topic']} repeated its group inside the cell");
        }
    }

    /**
     * Where the course offers nothing to split - a type holding only its
     * whole-cohort row - "Full Group" is still the answer to who the session is
     * for, and the GROUP column says it. Four fifths of the sessions in this
     * database are stored that way, and they used to say nothing at all.
     */
    public function test_a_whole_cohort_session_still_names_its_group(): void
    {
        DB::table('group_type_master_course_master_map')
            ->whereIn('pk', [self::GROUP_A_PK, self::GROUP_B_PK])
            ->delete();

        $weeks = $this->buildWeeks([
            $this->event(['group_name' => json_encode([self::GROUP_FULL_PK])]),
        ], '2026-01-19', '2026-01-25');

        $rows = array_values(array_filter($weeks[0]['rows'], static fn ($r) => $r['type'] === 'row'));

        $this->assertTrue($weeks[0]['showGroupCol']);
        $this->assertSame('Full Group', $rows[0]['groupLabel']);
    }

    /**
     * Two courses on one sheet each carry their own "Full Group" row, with
     * different pks. The column compares what it would print, so it still reads
     * "Full Group" instead of giving up and falling back to the cells.
     */
    public function test_two_courses_groups_of_the_same_name_read_as_one_label(): void
    {
        DB::table('group_type_master_course_master_map')->insert([
            'pk' => self::GROUP_FULL_2_PK, 'type_name' => self::TYPE_SEMINAR_PK,
            'course_name' => self::COURSE_PK + 1, 'group_name' => 'Workshop Group',
        ]);

        $weeks = $this->buildWeeks([
            $this->event(['group_name' => json_encode([self::GROUP_SOLO_PK])]),
            $this->event(['group_name' => json_encode([self::GROUP_FULL_2_PK]),
                          'course_master_pk' => self::COURSE_PK + 1, 'START_DATE' => '2026-01-20']),
        ], '2026-01-19', '2026-01-25');

        $rows = array_values(array_filter($weeks[0]['rows'], static fn ($r) => $r['type'] === 'row'));
        $this->assertSame('Workshop Group', $rows[0]['groupLabel']);
    }

    /** A session assigned no group at all still has nothing to say. */
    public function test_a_session_with_no_group_names_none(): void
    {
        $weeks = $this->buildWeeks([$this->event([])], '2026-01-19', '2026-01-25');

        $this->assertSame('', $this->cells($weeks[0])[0]['groupNames']);
    }

    /**
     * The issued sheet closes its grid with one row saying where each group
     * sits, by venue abbreviation, with the whole cohort first.
     */
    public function test_the_venues_row_names_a_venue_for_each_group(): void
    {
        $weeks = $this->buildWeeks([
            $this->event([
                'group_name'       => json_encode([self::GROUP_A_PK]),
                'venue_short_name' => 'TH',
                'subject_topic'    => 'A stream',
            ]),
            $this->event([
                'class_session'    => '11:00 AM - 12:00 PM',
                'group_name'       => json_encode([self::GROUP_FULL_PK]),
                'venue_short_name' => 'VH',
                'subject_topic'    => 'Everyone',
            ]),
        ], '2026-01-19', '2026-01-25');

        $this->assertSame('Full Group: VH, Group A: TH', $weeks[0]['venueLine']);
    }

    /** A week whose sessions record no venue prints no VENUES row at all. */
    public function test_no_venues_row_without_venue_data(): void
    {
        $weeks = $this->buildWeeks([
            $this->event(['venue_short_name' => '', 'venue_name' => '']),
        ], '2026-01-19', '2026-01-25');

        $this->assertSame('', $weeks[0]['venueLine']);
    }

    /** Defect 4: the header names the course even with no ?course_id. */
    public function test_the_course_header_is_derived_from_the_sessions_when_no_filter_is_given(): void
    {
        $method = new ReflectionMethod(CalendarController::class, 'timetableCourseContext');
        $method->setAccessible(true);

        $context = $method->invoke(
            app(CalendarController::class),
            collect([$this->event([]), $this->event(['START_DATE' => '2026-01-20'])]),
            null
        );

        $this->assertSame('Fixture Induction Programme', $context['course']->course_name);
        $this->assertSame('5th January, 2026 to 30th January, 2026', $context['duration']);
        $this->assertFalse($context['multiCourse'], 'one course across every session is not a multi-course sheet');
    }

    /** Sessions from two courses leave the header blank and name the course per cell. */
    public function test_a_period_spanning_two_courses_stays_a_multi_course_sheet(): void
    {
        $method = new ReflectionMethod(CalendarController::class, 'timetableCourseContext');
        $method->setAccessible(true);

        $context = $method->invoke(
            app(CalendarController::class),
            collect([$this->event([]), $this->event(['course_master_pk' => self::COURSE_PK + 1])]),
            null
        );

        $this->assertNull($context['course']);
        $this->assertTrue($context['multiCourse']);
    }

    /**
     * The structural rule, on data carrying all three of the shapes that used to
     * break it: a group with no name leading a split band, two sessions
     * overlapping in one day, and a break cut in two by another day's boundary.
     */
    public function test_every_printed_row_fills_the_grid_exactly(): void
    {
        $monday  = '2026-01-19';
        $tuesday = '2026-01-20';

        $events = [
            // A band split three ways, led by the group with no name (a).
            $this->event(['START_DATE' => $monday, 'class_session' => '09:00 AM - 10:00 AM',
                'group_name' => json_encode([self::GROUP_NONE_PK]), 'subject_topic' => 'Unnamed group']),
            $this->event(['START_DATE' => $monday, 'class_session' => '09:00 AM - 10:00 AM',
                'group_name' => json_encode([self::GROUP_A_PK]), 'subject_topic' => 'A stream']),
            $this->event(['START_DATE' => $monday, 'class_session' => '09:00 AM - 10:00 AM',
                'group_name' => json_encode([self::GROUP_B_PK]), 'subject_topic' => 'B stream']),

            // Two sessions overlapping in one day (b).
            $this->event(['START_DATE' => $tuesday, 'class_session' => '10:00 AM - 12:00 PM',
                'subject_topic' => 'Long session']),
            $this->event(['START_DATE' => $tuesday, 'class_session' => '11:00 AM - 01:00 PM',
                'subject_topic' => 'Overlapping session']),

            // A break every day, cut in two by Tuesday's boundary at 1310 (c).
            $this->event(['START_DATE' => $monday, 'class_session' => '02:00 PM - 03:00 PM',
                'subject_topic' => 'Afternoon', 'is_break' => 1, 'break_type' => 'lunch',
                'break_start_time' => '13:00', 'break_end_time' => '13:30']),
            $this->event(['START_DATE' => $tuesday, 'class_session' => '01:10 PM - 02:00 PM',
                'subject_topic' => 'Starts inside the break']),
        ];

        $weeks = $this->buildWeeks($events, '2026-01-19', '2026-01-25');

        $this->assertNotEmpty($weeks);
        foreach ($weeks as $week) {
            $this->assertGridIsSquare($week, "week {$week['weekNumber']}");
        }
    }

    /** No session in a day must not cost that day its cell. */
    public function test_a_week_with_one_session_still_fills_the_grid(): void
    {
        $weeks = $this->buildWeeks([$this->event([])], '2026-01-19', '2026-01-25');

        $this->assertGridIsSquare($weeks[0]);
    }

    /** An untimed slot keeps its label rather than printing an empty TIME cell. */
    public function test_an_untimed_slot_labels_its_own_time_cell(): void
    {
        $weeks = $this->buildWeeks([
            $this->event(['class_session' => 'Full Day']),
            $this->event(['class_session' => 'Full Day', 'START_DATE' => '2026-01-20']),
        ], '2026-01-19', '2026-01-25');

        $rows = array_values(array_filter($weeks[0]['rows'], static fn ($r) => $r['type'] === 'row'));

        $this->assertCount(1, $rows);
        $this->assertSame('Full Day', $rows[0]['from']);
        $this->assertGridIsSquare($weeks[0]);
    }
}
