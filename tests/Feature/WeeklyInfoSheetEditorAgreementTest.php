<?php

namespace Tests\Feature;

use App\Models\CourseCordinatorMaster;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The info-sheet editor and the Course Information PDF read
 * course_coordinator_master by one rule (review finding F-021).
 *
 * - Director / Joint Director: the editor (weeklyInfoMeta) opens on the value
 *   the PDF prints - the first non-empty row in pk order - not on whichever
 *   row the database returns first.
 * - Coordinator lists: a faculty member is granted the editor for a course
 *   (CourseCordinatorMaster::courseIdsForUser) exactly when the PDF prints
 *   their name for it.
 *
 * Every request goes through the HTTP kernel. Writes run inside
 * DatabaseTransactions and are rolled back.
 */
class WeeklyInfoSheetEditorAgreementTest extends TestCase
{
    use DatabaseTransactions;

    private const COURSE_DIRECTOR = 90340001;

    private const COURSE_FIRST = 90340101;   // list-shape courses are numbered from here

    private const PERSON = 90340011;

    private const OTHER = 90340012;

    private const LOOKALIKE = 903400111;   // PERSON's digits with one more: a different pk

    private const EMPLOYEE_PK = 90340021;   // user_credentials.user_id of PERSON

    private const WEEK = '2035-03-05';

    /** Coordinator_name / Assistant_Coordinator_name shapes, and whether they name PERSON. */
    private const SHAPES = [
        'coordinator alone' => [self::PERSON, '', true],
        'coordinator list, PERSON first' => [self::PERSON.','.self::OTHER, '', true],
        'coordinator list, PERSON second' => [self::OTHER.','.self::PERSON, '', true],
        'assistant list, no space' => [self::OTHER, self::OTHER.','.self::PERSON, true],
        'assistant list, with spaces' => [self::OTHER, self::OTHER.' , '.self::PERSON, true],
        'a longer pk containing PERSON' => [self::OTHER, self::LOOKALIKE.'', false],
        'typed name with a comma' => [self::OTHER, 'Sharma, '.self::PERSON, false],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('course_week_notes')) {
            $this->markTestSkipped('course_week_notes is not migrated on this database.');
        }

        $courses = [self::COURSE_DIRECTOR];
        foreach (array_keys(self::SHAPES) as $i => $label) {
            $courses[] = self::COURSE_FIRST + $i;
        }
        foreach ($courses as $pk) {
            DB::table('course_master')->insert([
                'pk' => $pk, 'course_name' => "Agreement fixture $pk", 'couse_short_name' => "AG$pk",
                'course_year' => 2035, 'start_year' => '2035-03-05', 'end_date' => '2035-03-30',
            ]);
        }

        $faculty = ['faculty_type' => '1', 'country_master_pk' => 0, 'state_master_pk' => 0,
            'state_district_mapping_pk' => 0, 'city_master_pk' => 0];
        DB::table('faculty_master')->insert($faculty + ['pk' => self::PERSON, 'first_name' => 'Pia',
            'full_name' => 'Pia Person', 'employee_master_pk' => self::EMPLOYEE_PK]);
        DB::table('faculty_master')->insert($faculty + ['pk' => self::OTHER, 'first_name' => 'Omar', 'full_name' => 'Omar Other']);
        DB::table('faculty_master')->insert($faculty + ['pk' => self::LOOKALIKE, 'first_name' => 'Lena', 'full_name' => 'Lena Lookalike']);

        // The first row (lower pk) has a blank director; the second holds the real one.
        DB::table('course_coordinator_master')->insert([
            'courses_master_pk' => self::COURSE_DIRECTOR, 'Coordinator_name' => (string) self::OTHER,
            'Assistant_Coordinator_name' => (string) self::PERSON,
            'director_name' => '', 'joint_director_name' => null, 'created_date' => now(),
        ]);
        DB::table('course_coordinator_master')->insert([
            'courses_master_pk' => self::COURSE_DIRECTOR, 'Coordinator_name' => (string) self::OTHER,
            'Assistant_Coordinator_name' => (string) self::LOOKALIKE,
            'director_name' => 'Dr. Filled Director', 'joint_director_name' => 'Shri Filled Joint', 'created_date' => now(),
        ]);

        foreach (array_values(self::SHAPES) as $i => [$coordinator, $assistant]) {
            DB::table('course_coordinator_master')->insert([
                'courses_master_pk' => self::COURSE_FIRST + $i, 'Coordinator_name' => (string) $coordinator,
                'Assistant_Coordinator_name' => (string) $assistant, 'created_date' => now(),
            ]);
        }
    }

    private function admin(): User
    {
        $user = new User;
        $user->forceFill(['pk' => 90340099, 'user_id' => null, 'user_category' => 'E']);

        return $user;
    }

    /** The data the Course Information PDF is rendered from, fetched through the real route. */
    private function printed(int $course): array
    {
        $data = null;
        View::composer('admin.calendar.pdf.weekly-info-pdf', function ($view) use (&$data) {
            $data = $view->getData();
        });

        $this->actingAs($this->admin())
            ->withSession(['user_roles' => ['Super Admin']])
            ->get('/calendar/weekly-info/pdf?course_id='.$course.'&week_start='.self::WEEK)
            ->assertOk();

        $this->assertNotNull($data, 'the Course Information view was rendered');

        return $data;
    }

    public function test_editor_and_pdf_show_the_same_director_when_the_first_row_is_blank(): void
    {
        $meta = $this->actingAs($this->admin())
            ->withSession(['user_roles' => ['Super Admin']])
            ->getJson('/calendar/weekly-info/meta?course_id='.self::COURSE_DIRECTOR.'&week_start='.self::WEEK)
            ->assertOk()
            ->json();

        $pdf = $this->printed(self::COURSE_DIRECTOR);

        $this->assertSame('Dr. Filled Director', $pdf['director']);
        $this->assertSame('Shri Filled Joint', $pdf['jointDirector']);
        $this->assertSame($pdf['director'], $meta['director_name'], 'the editor opens on the director the PDF prints');
        $this->assertSame($pdf['jointDirector'], $meta['joint_director_name'], 'the editor opens on the joint director the PDF prints');
    }

    public function test_the_editor_is_granted_exactly_where_the_sheet_names_the_person(): void
    {
        $person = new User;
        $person->forceFill(['pk' => 90340098, 'user_id' => self::EMPLOYEE_PK, 'user_category' => 'E']);
        $granted = CourseCordinatorMaster::courseIdsForUser($person);

        foreach (array_keys(self::SHAPES) as $i => $label) {
            $course = self::COURSE_FIRST + $i;
            $data = $this->printed($course);
            $named = in_array('Pia Person', array_merge(
                $data['coordinator'] ? explode(', ', $data['coordinator']) : [],
                $data['assistantCoordinators']
            ), true);

            $this->assertSame(self::SHAPES[$label][2], $named, "$label: the sheet names PERSON");
            $this->assertSame($named, in_array($course, $granted, true), "$label: the editor is granted where the sheet names PERSON");
        }
    }
}
