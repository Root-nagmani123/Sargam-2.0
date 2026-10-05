<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The Course Information sheet names every assistant coordinator (review finding F-020).
 *
 * course_coordinator_master holds one row per assistant coordinator, so the sheet
 * has to read all of a course's rows - not one of them. Assistants print in the
 * order their rows were entered (pk), once per person, and a person is identified
 * by their faculty pk, so two different people who share a name both print.
 *
 * Every request goes through the HTTP kernel. Writes run inside
 * DatabaseTransactions and are rolled back.
 */
class WeeklyInfoAssistantCoordinatorsTest extends TestCase
{
    use DatabaseTransactions;

    private const COURSE_MANY = 90330001;

    private const COURSE_ONE = 90330002;

    private const COURSE_NONE = 90330003;

    private const COORDINATOR = 90330011;

    private const ZARA = 90330012;

    private const ASHA = 90330013;

    private const MOHAN = 90330014;

    private const RAVI_1 = 90330015;

    private const RAVI_2 = 90330016;   // a different person with the same name as RAVI_1

    private const WEEK = '2035-03-05';

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('course_week_notes')) {
            $this->markTestSkipped('course_week_notes is not migrated on this database.');
        }

        foreach ([self::COURSE_MANY, self::COURSE_ONE, self::COURSE_NONE] as $pk) {
            DB::table('course_master')->insert([
                'pk' => $pk, 'course_name' => "Assistant fixture $pk", 'couse_short_name' => "AF$pk",
                'course_year' => 2035, 'start_year' => '2035-03-05', 'end_date' => '2035-03-30',
            ]);
        }

        $faculty = ['faculty_type' => '1', 'country_master_pk' => 0, 'state_master_pk' => 0,
            'state_district_mapping_pk' => 0, 'city_master_pk' => 0];
        foreach ([
            self::COORDINATOR => 'Cora Coordinator',
            self::ZARA => 'Zara Fixture',
            self::ASHA => 'Asha Fixture',
            self::MOHAN => 'Mohan   Fixture',   // stored with a run of spaces, printed squeezed
            self::RAVI_1 => 'Ravi Samename',
            self::RAVI_2 => 'Ravi Samename',
        ] as $pk => $name) {
            DB::table('faculty_master')->insert($faculty + ['pk' => $pk, 'first_name' => strtok($name, ' '), 'full_name' => $name]);
        }

        // Entered out of alphabetical order on purpose: the sheet follows entry order.
        foreach ([self::ZARA, self::ASHA, self::MOHAN, self::ASHA, self::RAVI_1, self::RAVI_2] as $assistant) {
            $this->coordinatorRow(self::COURSE_MANY, (string) $assistant);
        }
        $this->coordinatorRow(self::COURSE_ONE, (string) self::ASHA);
    }

    private function coordinatorRow(int $course, ?string $assistant, ?string $coordinator = null): void
    {
        DB::table('course_coordinator_master')->insert([
            'courses_master_pk' => $course,
            'Coordinator_name' => $coordinator ?? (string) self::COORDINATOR,
            'Assistant_Coordinator_name' => $assistant,
            'created_date' => now(),
        ]);
    }

    /** The data the Course Information PDF is rendered from, fetched through the real route. */
    private function printed(int $course): array
    {
        $data = null;
        View::composer('admin.calendar.pdf.weekly-info-pdf', function ($view) use (&$data) {
            $data = $view->getData();
        });

        $user = new User;
        $user->forceFill(['pk' => 90330099, 'user_id' => null, 'user_category' => 'E']);

        $this->actingAs($user)
            ->withSession(['user_roles' => ['Super Admin']])
            ->get('/calendar/weekly-info/pdf?course_id='.$course.'&week_start='.self::WEEK)
            ->assertOk();

        $this->assertNotNull($data, 'the Course Information view was rendered');

        return $data;
    }

    public function test_every_assistant_prints_once_in_entry_order(): void
    {
        $data = $this->printed(self::COURSE_MANY);

        // What the sheet prints, first: the defect is the printed text.
        $this->assertSame(
            'Zara Fixture, Asha Fixture, Mohan Fixture, Ravi Samename, Ravi Samename',
            $data['assistantCoordinator']
        );
        $this->assertSame(1, substr_count($data['assistantCoordinator'], 'Asha Fixture'), 'a repeated row prints once');
        $this->assertSame(2, substr_count($data['assistantCoordinator'], 'Ravi Samename'), 'two people who share a name both print');
        $this->assertSame('Cora Coordinator', $data['coordinator'], 'the coordinator repeated on every row prints once');
        $this->assertSame(
            ['Zara Fixture', 'Asha Fixture', 'Mohan Fixture', 'Ravi Samename', 'Ravi Samename'],
            $data['assistantCoordinators'] ?? null
        );
    }

    public function test_a_single_assistant_prints_as_before(): void
    {
        $data = $this->printed(self::COURSE_ONE);

        $this->assertSame('Asha Fixture', $data['assistantCoordinator']);
        $this->assertSame('Cora Coordinator', $data['coordinator']);
        $this->assertSame(['Asha Fixture'], $data['assistantCoordinators'] ?? null);
    }

    public function test_a_course_without_coordinator_rows_prints_no_assistant(): void
    {
        $data = $this->printed(self::COURSE_NONE);

        $this->assertNull($data['assistantCoordinator']);
        $this->assertNull($data['coordinator']);
        $this->assertSame([], $data['assistantCoordinators'] ?? null);
    }

    public function test_a_typed_legacy_name_and_a_blank_assistant_are_handled(): void
    {
        // Older rows hold a typed name instead of a faculty pk; a blank assistant is skipped.
        $this->coordinatorRow(self::COURSE_NONE, 'Shri Typed Name');
        $this->coordinatorRow(self::COURSE_NONE, '');
        $this->coordinatorRow(self::COURSE_NONE, null);

        $data = $this->printed(self::COURSE_NONE);

        $this->assertSame('Shri Typed Name', $data['assistantCoordinator']);
        $this->assertSame(['Shri Typed Name'], $data['assistantCoordinators'] ?? null);
    }

    public function test_a_comma_list_of_pks_names_each_and_a_typed_name_with_a_comma_stays_whole(): void
    {
        $this->coordinatorRow(self::COURSE_NONE, self::MOHAN.','.self::ZARA);
        $this->coordinatorRow(self::COURSE_NONE, 'Sharma, R.');

        $data = $this->printed(self::COURSE_NONE);

        $this->assertSame('Mohan Fixture, Zara Fixture, Sharma, R.', $data['assistantCoordinator']);
        $this->assertSame(['Mohan Fixture', 'Zara Fixture', 'Sharma, R.'], $data['assistantCoordinators'] ?? null);
    }
}
