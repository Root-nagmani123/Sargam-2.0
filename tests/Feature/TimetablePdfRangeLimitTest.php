<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The range timetable PDFs (calendar/timetable/pdf and calendar/ot/download)
 * lay every week of ?start..?end out through DomPDF several times, so a span
 * is capped at the calendar's widest visible view: the month grid's six weeks.
 * A wider span, or a start/end that is not a date, is refused with 422 before
 * any query or render runs.
 *
 * The served controls use a course with no sessions, so they render an empty
 * timetable quickly. Nothing is written; DatabaseTransactions is a guard only.
 */
class TimetablePdfRangeLimitTest extends TestCase
{
    use DatabaseTransactions;

    private const NO_SESSIONS_COURSE = 90370001;

    private const STUDENT_PK = 90370041;

    private function getAs(string $uri, array $roles, ?int $userId, string $category)
    {
        $user = new User;
        $user->forceFill(['pk' => 90370099, 'user_id' => $userId, 'user_category' => $category]);

        return $this->actingAs($user)->withSession(['user_roles' => $roles])->getJson($uri);
    }

    private function adminPdf(string $query)
    {
        return $this->getAs('/calendar/timetable/pdf?course_id='.self::NO_SESSIONS_COURSE.'&'.$query, ['Super Admin'], null, 'E');
    }

    private function traineePdf(string $query)
    {
        return $this->getAs('/calendar/ot/download?course_id='.self::NO_SESSIONS_COURSE.'&'.$query, ['Student-OT'], self::STUDENT_PK, 'S');
    }

    public function test_an_eight_year_range_is_refused(): void
    {
        $this->adminPdf('start=2020-01-01&end=2027-12-31')->assertStatus(422)->assertJsonValidationErrors('end');
        $this->traineePdf('start=2020-01-01&end=2027-12-31')->assertStatus(422)->assertJsonValidationErrors('end');
    }

    public function test_a_start_alone_far_from_the_default_end_is_refused(): void
    {
        // ?end defaults to the end of the current month, so an old ?start alone is just as wide.
        $this->adminPdf('start=2000-01-01')->assertStatus(422)->assertJsonValidationErrors('end');
    }

    public function test_one_day_past_the_cap_is_refused(): void
    {
        $this->adminPdf('start=2035-03-01&end=2035-04-13')->assertStatus(422);
        $this->traineePdf('start=2035-03-01&end=2035-04-13')->assertStatus(422);
    }

    public function test_the_month_views_six_week_range_is_served(): void
    {
        // FullCalendar's month grid: activeStart to activeEnd - 1, 41 days.
        $this->adminPdf('start=2035-02-25&end=2035-04-07')->assertOk();
        $this->traineePdf('start=2035-02-25&end=2035-04-07')->assertOk();
    }

    public function test_the_cap_itself_is_served(): void
    {
        $this->adminPdf('start=2035-03-01&end=2035-04-12')->assertOk();
    }

    public function test_a_start_or_end_that_is_not_a_date_is_refused(): void
    {
        $this->adminPdf('start=not-a-date&end=2035-03-11')->assertStatus(422)->assertJsonValidationErrors('start');
        $this->adminPdf('start[]=2035-03-05&end=2035-03-11')->assertStatus(422)->assertJsonValidationErrors('start');
        $this->traineePdf('start=2035-03-05&end[]=2035-03-11')->assertStatus(422)->assertJsonValidationErrors('end');
    }
}
