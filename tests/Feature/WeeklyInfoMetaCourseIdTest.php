<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CalendarController::weeklyInfoMeta() must check and query the same course.
 * (int) of an array is 1, so an unvalidated course_id[]=X was checked as course 1
 * while the queries bound X: a coordinator of course 1 could read any course's
 * info sheet. A non-integer course_id is now refused with 422 before the check.
 *
 * Fixtures run inside DatabaseTransactions and are rolled back.
 */
class WeeklyInfoMetaCourseIdTest extends TestCase
{
    use DatabaseTransactions;

    private const FACULTY_PK = 90360011;   // coordinates course 1

    private const EMPLOYEE_PK = 90360021;

    private const COURSE_TARGET = 90360101;   // a course the coordinator does not coordinate

    private const WEEK = '2035-03-05';

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('course_week_notes')) {
            $this->markTestSkipped('course_week_notes is not migrated on this database.');
        }

        if (! DB::table('course_master')->where('pk', 1)->exists()) {
            DB::table('course_master')->insert([
                'pk' => 1, 'course_name' => 'Fixture course 1', 'couse_short_name' => 'FC1',
                'course_year' => 2035, 'start_year' => '2035-03-05', 'end_date' => '2035-03-30',
            ]);
        }
        DB::table('course_master')->insert([
            'pk' => self::COURSE_TARGET, 'course_name' => 'Fixture target', 'couse_short_name' => 'FCT',
            'course_year' => 2035, 'start_year' => '2035-03-05', 'end_date' => '2035-03-30',
            'participants_profile' => 'TARGET-PROFILE',
        ]);

        DB::table('faculty_master')->insert([
            'pk' => self::FACULTY_PK, 'faculty_type' => '1', 'first_name' => 'Cora', 'full_name' => 'Cora Coord',
            'employee_master_pk' => self::EMPLOYEE_PK,
            'country_master_pk' => 0, 'state_master_pk' => 0, 'state_district_mapping_pk' => 0, 'city_master_pk' => 0,
        ]);

        DB::table('course_coordinator_master')->insert([
            ['courses_master_pk' => 1, 'Coordinator_name' => (string) self::FACULTY_PK, 'Assistant_Coordinator_name' => '', 'created_date' => now()],
            ['courses_master_pk' => self::COURSE_TARGET, 'Coordinator_name' => '1', 'Assistant_Coordinator_name' => '', 'created_date' => now()],
        ]);

        DB::table('course_week_notes')->insert([
            'course_master_pk' => self::COURSE_TARGET, 'week_start' => self::WEEK,
            'mention_of_week' => 'TARGET-NOTE', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function meta(string $query)
    {
        $user = new User;
        $user->forceFill(['pk' => 90360099, 'user_id' => self::EMPLOYEE_PK, 'user_category' => 'E', 'user_name' => 'fixture']);

        return $this->actingAs($user)->withSession(['user_roles' => ['Faculty']])
            ->get('/calendar/weekly-info/meta?'.$query.'&week_start='.self::WEEK);
    }

    public function test_coordinator_reads_own_course(): void
    {
        $this->meta('course_id=1')->assertOk()->assertJson(['course_id' => 1, 'can_edit' => true]);
    }

    public function test_coordinator_is_refused_another_course(): void
    {
        $this->meta('course_id='.self::COURSE_TARGET)->assertStatus(403);
    }

    public function test_array_course_id_is_refused_and_reads_nothing(): void
    {
        $response = $this->meta('course_id[]='.self::COURSE_TARGET);

        $response->assertStatus(422);
        $this->assertStringNotContainsString('TARGET-NOTE', $response->getContent());
        $this->assertStringNotContainsString('TARGET-PROFILE', $response->getContent());
    }

    public function test_non_integer_course_id_is_refused(): void
    {
        $this->meta('course_id=1abc')->assertStatus(422);
        $this->meta('course_id=0')->assertStatus(422);
    }

    public function test_missing_course_id_is_refused(): void
    {
        $this->meta('')->assertStatus(422)->assertJson(['error' => 'Select a course first.']);
    }
}
