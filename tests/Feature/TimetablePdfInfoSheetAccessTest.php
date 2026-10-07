<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CalendarController;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The timetable PDFs print a course's week notes and P.T.O. info sheet only to a
 * reader of that course (review finding F-019).
 *
 * The sheet carries counsellors, faculty codes, guest speakers with their trainee
 * moderators, notes and the signatory - the content weekly-info/pdf and
 * weekly-info/meta already gate. Three routes attach it: calendar/weekly-timetable/pdf,
 * calendar/timetable/pdf and calendar/ot/download. For a reader outside the course
 * each still returns its timetable, without the back page and without the week's
 * stored notes. Who counts as a reader is canViewCourseInfoSheet(), the rule
 * weekly-info/pdf applies.
 *
 * Content is checked, not only status: the data handed to the PDF view, and whether
 * the info-sheet component was rendered into it at all. Every request goes through
 * the HTTP kernel. Writes run inside DatabaseTransactions and are rolled back.
 */
class TimetablePdfInfoSheetAccessTest extends TestCase
{
    use DatabaseTransactions;

    private const COURSE_IN = 90340001;    // the trainee attends this course

    private const COURSE_OUT = 90340002;   // ...and has nothing to do with this one

    private const GROUP_IN = 90340021;

    private const GROUP_OUT = 90340022;

    private const STUDENT_PK = 90340041;

    private const WEEK = '2035-03-05';       // both courses have sessions and notes

    private const SOLO_WEEK = '2035-03-12';  // only COURSE_OUT has a session

    private const MARK = 'F019-SIGNATORY-';

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('course_week_notes') || ! Schema::hasColumn('course_week_notes', 'signatory_name')) {
            $this->markTestSkipped('course_week_notes info-sheet columns are not migrated on this database.');
        }
        if (DB::table('timetable')->whereBetween('START_DATE', [self::WEEK, '2035-03-18'])->exists()) {
            $this->markTestSkipped('this database already has sessions in the fixture weeks.');
        }

        $sessionPk = 90340031;
        foreach ([self::COURSE_IN => self::GROUP_IN, self::COURSE_OUT => self::GROUP_OUT] as $course => $group) {
            DB::table('course_master')->insert([
                'pk' => $course, 'course_name' => "Sheet access fixture $course", 'couse_short_name' => "SA$course",
                'course_year' => 2035, 'start_year' => '2035-03-05', 'end_date' => '2035-03-30',
            ]);
            DB::table('group_type_master_course_master_map')->insert([
                'pk' => $group, 'type_name' => 1, 'course_name' => $course, 'group_name' => 'Full Group',
            ]);

            $weeks = $course === self::COURSE_OUT ? [self::WEEK, self::SOLO_WEEK] : [self::WEEK];
            foreach ($weeks as $week) {
                DB::table('timetable')->insert([
                    'pk' => $sessionPk, 'course_master_pk' => $course, 'subject_master_pk' => 0,
                    'subject_module_master_pk' => 0, 'course_group_type_master' => 1,
                    'group_name' => json_encode([(string) $group]), 'venue_id' => 0,
                    'subject_topic' => "Sheet access session $course", 'START_DATE' => $week,
                    'class_session' => '09:00 AM - 10:00 AM', 'faculty_master' => '[]',
                ]);
                DB::table('course_group_timetable_mapping')->insert(['group_pk' => $group, 'timetable_pk' => $sessionPk]);
                $sessionPk++;

                DB::table('course_week_notes')->insert([
                    'course_master_pk' => $course, 'week_start' => $week,
                    'notes' => json_encode(["F019 note $course"]),
                    'venue_line' => "F019 venues $course",
                    'outdoor_activities' => "F019 outdoor $course",
                    'signatory_name' => self::MARK.$course,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
        DB::table('student_course_group_map')->insert([
            'student_master_pk' => self::STUDENT_PK, 'group_type_master_course_master_map_pk' => self::GROUP_IN,
        ]);
    }

    /**
     * GET a PDF route as the given actor; returns the status, the data the printed
     * (not measuring) timetable view received, and whether the info-sheet component rendered.
     */
    private function fetch(string $uri, array $roles, ?int $userId, ?string $category): array
    {
        $printed = null;
        $sheetRendered = false;
        View::composer('admin.calendar.pdf.ot-timetable-pdf', function ($view) use (&$printed) {
            if (empty($view->getData()['measure'])) {
                $printed = $view->getData();
            }
        });
        View::composer('components.timetable.info-sheet', function () use (&$sheetRendered) {
            $sheetRendered = true;
        });

        $user = new User;
        $user->forceFill(['pk' => 90340099, 'user_id' => $userId, 'user_category' => $category]);

        $response = $this->actingAs($user)->withSession(['user_roles' => $roles])->get($uri);

        return [$response->getStatusCode(), $printed, $sheetRendered];
    }

    private function weekly(int $course): string
    {
        return '/calendar/weekly-timetable/pdf?course_id='.$course.'&week_start='.self::WEEK;
    }

    private function range(string $route, int $course): string
    {
        return "/calendar/$route?course_id=$course&start=".self::WEEK.'&end=2035-03-11';
    }

    /** Assert the printed week carries (or does not carry) the course's stored notes and back page. */
    private function assertSheet(bool $expected, array $result, int $course): void
    {
        [$status, $printed, $sheetRendered] = $result;

        $this->assertSame(200, $status, 'the timetable itself is still served');
        $this->assertNotNull($printed, 'the timetable view was rendered');
        $week = $printed['weeks'][0];
        $flat = json_encode($printed['weeks']);

        $this->assertSame($expected, $sheetRendered, 'info-sheet component rendered into the PDF');
        $this->assertSame($expected, ! empty($week['sheet']), 'P.T.O. sheet attached');
        $this->assertSame($expected, str_contains($flat, self::MARK.$course), 'signatory present in the printed data');
        $this->assertSame($expected, str_contains($flat, "F019 note $course"), 'stored week note present in the printed data');
        $this->assertSame($expected, str_contains($flat, "F019 venues $course"), 'stored venue line present in the printed data');
    }

    public function test_super_admin_still_gets_any_courses_sheet(): void
    {
        $this->assertSheet(true, $this->fetch($this->weekly(self::COURSE_OUT), ['Super Admin'], null, 'E'), self::COURSE_OUT);
        $this->assertSheet(true, $this->fetch($this->range('timetable/pdf', self::COURSE_OUT), ['Super Admin'], null, 'E'), self::COURSE_OUT);
    }

    public function test_a_trainee_gets_their_own_courses_sheet(): void
    {
        $this->assertSheet(true, $this->fetch($this->weekly(self::COURSE_IN), ['Student-OT'], self::STUDENT_PK, 'S'), self::COURSE_IN);
        $this->assertSheet(true, $this->fetch($this->range('timetable/pdf', self::COURSE_IN), ['Student-OT'], self::STUDENT_PK, 'S'), self::COURSE_IN);
        $this->assertSheet(true, $this->fetch($this->range('ot/download', self::COURSE_IN), ['Student-OT'], self::STUDENT_PK, 'S'), self::COURSE_IN);
    }

    public function test_a_trainee_does_not_get_another_courses_sheet_from_any_timetable_pdf(): void
    {
        $this->assertSheet(false, $this->fetch($this->weekly(self::COURSE_OUT), ['Student-OT'], self::STUDENT_PK, 'S'), self::COURSE_OUT);
        $this->assertSheet(false, $this->fetch($this->range('timetable/pdf', self::COURSE_OUT), ['Student-OT'], self::STUDENT_PK, 'S'), self::COURSE_OUT);
        $this->assertSheet(false, $this->fetch($this->range('ot/download', self::COURSE_OUT), ['Student-OT'], self::STUDENT_PK, 'S'), self::COURSE_OUT);
    }

    public function test_staff_without_a_role_covering_the_course_get_no_sheet(): void
    {
        $this->assertSheet(false, $this->fetch($this->weekly(self::COURSE_IN), ['FC Reports Viewer'], null, 'E'), self::COURSE_IN);
        $this->assertSheet(false, $this->fetch($this->range('timetable/pdf', self::COURSE_IN), ['FC Reports Viewer'], null, 'E'), self::COURSE_IN);
    }

    public function test_a_course_derived_from_a_single_course_week_is_gated_too(): void
    {
        // No course named: the week holds one course's sessions only, so the sheet
        // resolves that course from the sessions themselves.
        $uri = '/calendar/weekly-timetable/pdf?week_start='.self::SOLO_WEEK;

        $this->assertSheet(false, $this->fetch($uri, ['FC Reports Viewer'], null, 'E'), self::COURSE_OUT);
        $this->assertSheet(true, $this->fetch($uri, ['Super Admin'], null, 'E'), self::COURSE_OUT);
    }

    public function test_the_permission_helper_fails_closed_without_a_signed_in_user(): void
    {
        $check = new \ReflectionMethod(CalendarController::class, 'canViewCourseInfoSheet');
        $check->setAccessible(true);

        $this->assertFalse($check->invoke(app(CalendarController::class), self::COURSE_IN));
    }
}
