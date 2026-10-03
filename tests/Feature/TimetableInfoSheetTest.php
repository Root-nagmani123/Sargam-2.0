<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CalendarController;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The printed week beyond its grid: the stored VENUES line and notes, and the
 * P.T.O. info sheet built by WeeklyInfoSheetBuilder from the masters plus the
 * week's course_week_notes row.
 */
class TimetableInfoSheetTest extends TestCase
{
    use DatabaseTransactions;

    private const COURSE_PK   = 90200001;
    private const GROUP_A_PK  = 90200002;
    private const GROUP_B_PK  = 90200003;
    private const TYPE_PK     = 90200004;
    private const GUEST_1_PK  = 90200011;   // teaches second
    private const GUEST_2_PK  = 90200012;   // teaches first
    private const INHOUSE_PK  = 90200013;   // in-house speaker with a moderator
    private const COUNSEL_1   = 90200014;
    private const COUNSEL_2   = 90200015;
    private const VENUE_PK    = 90200016;
    private const WEEK        = '2026-01-19';

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('course_week_notes') || !Schema::hasColumn('course_week_notes', 'counsellor_meta')) {
            $this->markTestSkipped('course_week_notes info-sheet columns are not migrated on this database.');
        }

        DB::table('course_master')->insert([
            'pk' => self::COURSE_PK, 'course_name' => 'Fixture Phase', 'couse_short_name' => 'FP',
            'course_year' => 2026, 'start_year' => '2026-01-05', 'end_date' => '2026-01-30',
        ]);
        DB::table('course_group_type_master')->insert(['pk' => self::TYPE_PK, 'type_name' => 'Lecture Group']);

        $faculty = ['country_master_pk' => 0, 'state_master_pk' => 0, 'state_district_mapping_pk' => 0, 'city_master_pk' => 0,
            'abbreviation' => null, 'current_designation' => null, 'current_department' => null];
        DB::table('faculty_master')->insert([
            array_merge($faculty, ['pk' => self::GUEST_1_PK, 'faculty_type' => '2', 'first_name' => 'Asha', 'full_name' => 'Mr Asha Rao',
                'faculty_code' => 'GUE-90001', 'current_designation' => 'Secretary,', 'current_department' => 'Dept of Fixtures']),
            array_merge($faculty, ['pk' => self::GUEST_2_PK, 'faculty_type' => '2', 'first_name' => 'Bina', 'full_name' => 'Ms Bina Das',
                'faculty_code' => 'GUE-90002']),
            array_merge($faculty, ['pk' => self::INHOUSE_PK, 'faculty_type' => '1', 'first_name' => 'Chitra', 'full_name' => 'Chitra Lal',
                'faculty_code' => 'INT-90003', 'abbreviation' => 'CL']),
            array_merge($faculty, ['pk' => self::COUNSEL_1, 'faculty_type' => '1', 'first_name' => 'Dev', 'full_name' => 'Dev Roy',
                'faculty_code' => 'INT-90004', 'abbreviation' => 'DR']),
            array_merge($faculty, ['pk' => self::COUNSEL_2, 'faculty_type' => '1', 'first_name' => 'Esha', 'full_name' => 'Esha Sen',
                'faculty_code' => 'INT-90005', 'abbreviation' => 'ES']),
        ]);

        // Counsellor Groups (type 8): Dev counsels two cadres, Esha one.
        DB::table('group_type_master_course_master_map')->insert([
            ['pk' => self::GROUP_A_PK, 'type_name' => self::TYPE_PK, 'course_name' => self::COURSE_PK, 'group_name' => 'A', 'facility_id' => null, 'active_inactive' => 1],
            ['pk' => self::GROUP_B_PK, 'type_name' => self::TYPE_PK, 'course_name' => self::COURSE_PK, 'group_name' => 'B', 'facility_id' => null, 'active_inactive' => 1],
            ['pk' => 90200021, 'type_name' => 8, 'course_name' => self::COURSE_PK, 'group_name' => 'Assam', 'facility_id' => self::COUNSEL_1, 'active_inactive' => 1],
            ['pk' => 90200022, 'type_name' => 8, 'course_name' => self::COURSE_PK, 'group_name' => 'Bihar', 'facility_id' => self::COUNSEL_1, 'active_inactive' => 1],
            ['pk' => 90200023, 'type_name' => 8, 'course_name' => self::COURSE_PK, 'group_name' => 'Uttarakhand', 'facility_id' => self::COUNSEL_2, 'active_inactive' => 1],
        ]);
    }

    private function event(array $attrs): object
    {
        return (object) array_merge([
            'course_master_pk' => self::COURSE_PK, 'subject_topic' => 'Session', 'START_DATE' => self::WEEK,
            'class_session' => '09:40 AM - 10:40 AM', 'faculty_master' => '[]', 'faculty_details' => null,
            'group_name' => json_encode([self::GROUP_A_PK, self::GROUP_B_PK]),
            'is_break' => 0, 'break_type' => null, 'break_start_time' => null, 'break_end_time' => null,
            'venue_name' => 'Vivekananda Hall', 'venue_short_name' => 'VH',
        ], $attrs);
    }

    private function week(array $events): array
    {
        $controller = app(CalendarController::class);
        $course     = DB::table('course_master')->where('pk', self::COURSE_PK)->first();

        $grid = new ReflectionMethod(CalendarController::class, 'buildWeeksGrid');
        $grid->setAccessible(true);
        $attach = new ReflectionMethod(CalendarController::class, 'attachWeekSheets');
        $attach->setAccessible(true);

        $weeks = $attach->invoke($controller,
            $grid->invoke($controller, collect($events), Carbon::parse(self::WEEK), Carbon::parse('2026-01-25'), $course),
            collect($events), $course);

        return $weeks[0];
    }

    private function storeNote(array $values): void
    {
        DB::table('course_week_notes')->insert($values + [
            'course_master_pk' => self::COURSE_PK, 'week_start' => self::WEEK, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function events(): array
    {
        return [
            $this->event(['class_session' => '02:20 PM - 03:20 PM', 'faculty_master' => json_encode([(string) self::GUEST_1_PK])]),
            $this->event(['class_session' => '09:40 AM - 10:40 AM', 'faculty_master' => json_encode([(string) self::GUEST_2_PK])]),
            $this->event(['class_session' => '12:20 PM - 01:20 PM', 'faculty_master' => json_encode([(string) self::INHOUSE_PK])]),
        ];
    }

    public function test_a_stored_venue_line_and_notes_print_under_the_grid(): void
    {
        $this->storeNote([
            'venue_line' => 'Full Group: VH, Group-A: VH, Group B: TH',
            'notes'      => json_encode(['First note.', '', 'Second note.']),
        ]);

        $week = $this->week($this->events());

        $this->assertSame('Full Group: VH, Group-A: VH, Group B: TH', $week['venueLine']);
        $this->assertSame("1. First note.\n2. Second note.", $week['footerNote']);
    }

    public function test_guest_speakers_list_in_teaching_order_with_moderators(): void
    {
        $this->storeNote(['guest_moderators' => json_encode([
            (string) self::GUEST_1_PK => 'Moderator One, B01',
            (string) self::INHOUSE_PK => 'Moderator Three, B03',
        ])]);

        $guests = $this->week($this->events())['sheet']['guestSpeakers'];

        // Chitra (in-house, moderated) teaches at 1220, Asha at 1420. Bina, a
        // guest at 0940 with no moderator - a panellist - is left out, as the
        // issued sheet leaves out its unmoderated panellist.
        $this->assertSame(['Chitra Lal', 'Mr Asha Rao'], array_column($guests, 'name'));
        $this->assertSame('Moderator One, B01', $guests[1]['moderator']);
        $this->assertSame('Secretary, Dept of Fixtures', $guests[1]['designation'], 'a trailing comma in the master is not doubled');
    }

    /**
     * Through the real PDF action and its own query: a speaker named only in
     * timetable.internal_faculty, with a stored moderator, prints under Guest
     * Speakers. The editor offers these speakers for a moderator, so the PDF's
     * select list must carry the column the builder reads them from.
     */
    public function test_an_internal_faculty_only_speaker_with_a_moderator_prints(): void
    {
        $this->storeNote(['guest_moderators' => json_encode([(string) self::INHOUSE_PK => 'Moderator Three, B03'])]);

        $this->insertTimetableRow([
            'subject_topic' => 'In-house talk', 'class_session' => '12:20 PM - 01:20 PM',
            'internal_faculty' => json_encode([(string) self::INHOUSE_PK]),
        ]);

        $guests = $this->printViaWeeklyPdf()['weeks'][0]['sheet']['guestSpeakers'] ?? [];
        $this->assertSame(['Chitra Lal'], array_column($guests, 'name'));
        $this->assertSame('Moderator Three, B03', $guests[0]['moderator']);
    }

    public function test_a_week_without_moderators_lists_every_guest_in_teaching_order(): void
    {
        $this->storeNote(['outdoor_activities' => 'PT at 0630']);

        $guests = $this->week($this->events())['sheet']['guestSpeakers'];

        $this->assertSame(['Ms Bina Das', 'Mr Asha Rao'], array_column($guests, 'name'), 'guests only, in teaching order');
    }

    public function test_the_faculty_legend_follows_the_weeks_printed_order(): void
    {
        $this->storeNote(['faculty_legend_order' => json_encode(['ES', 'CL'])]);

        $legend = array_column($this->week($this->events())['sheet']['facultyLegend'], 'abbreviation');

        $this->assertSame(['ES', 'CL'], array_slice($legend, 0, 2), 'listed codes first, in the stored order');
        $this->assertContains('DR', array_slice($legend, 2), 'unlisted codes still printed, after them');
    }

    public function test_an_in_house_speaker_without_a_moderator_is_not_a_guest(): void
    {
        $this->storeNote(['outdoor_activities' => 'PT at 0630']);

        $names = array_column($this->week($this->events())['sheet']['guestSpeakers'], 'name');

        $this->assertNotContains('Chitra Lal', $names);
    }

    public function test_counsellors_take_the_weeks_label_cadres_and_order(): void
    {
        $this->storeNote(['counsellor_meta' => json_encode([
            (string) self::COUNSEL_2 => ['label' => 'JD(ES)', 'venue' => 'Session Design Lab', 'order' => 1],
            (string) self::COUNSEL_1 => ['label' => 'DD (DR)', 'cadres' => 'Assam/ Bihar', 'order' => 2],
        ])]);

        $rows = $this->week($this->events())['sheet']['counsellors'];

        $this->assertSame(['JD(ES)', 'DD (DR)'], array_column($rows, 'label'), 'numbered rows print in that order');
        $this->assertSame('Uttarakhand', $rows[0]['cadres'], 'cadres default to the Counsellor Groups');
        $this->assertSame('Assam/ Bihar', $rows[1]['cadres'], 'the week can reword them');
        $this->assertSame('Session Design Lab', $rows[0]['venue']);
    }

    public function test_the_faculty_legend_lists_every_curated_code_not_only_the_weeks(): void
    {
        $this->storeNote(['outdoor_activities' => 'PT at 0630']);

        // Esha teaches nothing this week and counsels nobody in it; the sheet's
        // legend is academy-wide, so her code is still listed.
        $legend = array_column($this->week($this->events())['sheet']['facultyLegend'], 'abbreviation');

        foreach (['CL', 'DR', 'ES'] as $code) {
            $this->assertContains($code, $legend);
        }
        $sorted = $legend;
        sort($sorted);
        $this->assertSame($sorted, $legend, 'listed by code');
    }

    public function test_a_stored_venue_legend_replaces_the_derived_one(): void
    {
        $this->storeNote(['venue_legend' => json_encode([
            ['abbreviation' => 'AH', 'name' => 'Ambedkar Hall'],
            ['abbreviation' => 'TH', 'name' => 'Tagore Hall'],
        ])]);

        $venues = $this->week($this->events())['sheet']['venueLegend'];

        $this->assertSame(['AH', 'TH'], array_column($venues, 'abbreviation'));
    }

    /**
     * Physical Activity sessions always print on the grid, also in a week whose
     * back page fills "Outdoor and Other Activities" (product decision, F-008a).
     */
    public function test_physical_activity_prints_even_when_the_back_page_describes_it(): void
    {
        $outdoor = (int) (DB::table('subject_master')->where('subject_name', 'Physical Activity')->value('pk') ?? 0);
        if (!$outdoor) {
            $this->markTestSkipped('No "Physical Activity" subject on this database.');
        }
        $this->storeNote(['outdoor_activities' => 'Time: Outdoors- Morning 06:30 - 07:30']);
        $this->insertTimetableRow(['subject_master_pk' => $outdoor, 'subject_topic' => 'Morning Activity', 'class_session' => '06:30 AM - 07:30 AM']);
        $this->insertTimetableRow(['subject_topic' => 'Lecture', 'class_session' => '09:40 AM - 10:40 AM']);

        $topics = [];
        foreach ($this->printViaWeeklyPdf()['weeks'][0]['rows'] as $row) {
            foreach ($row['cells'] ?? [] as $cell) {
                $topics = array_merge($topics, array_column($cell['events'] ?? [], 'topic'));
            }
        }

        $this->assertContains('Morning Activity', $topics);
        $this->assertContains('Lecture', $topics);
    }

    private function insertTimetableRow(array $attrs): void
    {
        DB::table('timetable')->insert(array_merge([
            'course_master_pk' => self::COURSE_PK, 'subject_master_pk' => 0, 'subject_module_master_pk' => 0,
            'venue_id' => 0, 'course_group_type_master' => self::TYPE_PK,
            'group_name' => json_encode([(string) self::GROUP_A_PK, (string) self::GROUP_B_PK]),
            'subject_topic' => 'Session', 'START_DATE' => self::WEEK, 'class_session' => '09:40 AM - 10:40 AM',
            'faculty_master' => '[]',
        ], $attrs));
    }

    /** The data the printed sheet is rendered from, via the real weekly PDF action and its own query. */
    private function printViaWeeklyPdf(): array
    {
        $printed = null;
        \Illuminate\Support\Facades\View::composer('admin.calendar.pdf.ot-timetable-pdf', function ($view) use (&$printed) {
            if (empty($view->getData()['measure'])) {
                $printed = $view->getData();
            }
        });

        app(CalendarController::class)->weeklyTimetablePdf(\Illuminate\Http\Request::create('/calendar/weekly-timetable/pdf', 'GET', [
            'course_id' => self::COURSE_PK, 'week_start' => self::WEEK,
        ]));

        $this->assertNotNull($printed, 'the PDF view was rendered');

        return $printed;
    }

    public function test_a_week_with_no_stored_note_keeps_its_derived_venue_line(): void
    {
        $week = $this->week($this->events());

        $this->assertStringContainsString('VH', $week['venueLine']);
        $this->assertArrayNotHasKey('footerNote', $week);
    }
}
