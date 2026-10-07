<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * course_week_notes.signatory_date is a DATE column, which strict MySQL accepts
 * only as Y-m-d. The 'date' rule also admitted 21-08-2026, 08/21/2026 and
 * "21 August 2026", and the insert then failed with a 500 that rolled back the
 * whole sheet. saveWeeklyInfo() now refuses those with 422 and stores Y-m-d.
 *
 * Writes run inside DatabaseTransactions and are rolled back.
 */
class WeeklyInfoSignatoryDateTest extends TestCase
{
    use DatabaseTransactions;

    private const COURSE = 90380001;

    private const WEEK = '2035-03-05';

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('course_week_notes')) {
            $this->markTestSkipped('course_week_notes is not migrated on this database.');
        }

        DB::table('course_master')->insert([
            'pk' => self::COURSE, 'course_name' => 'Fixture signatory course', 'couse_short_name' => 'FSC',
            'course_year' => 2035, 'start_year' => '2035-03-05', 'end_date' => '2035-03-30',
        ]);
    }

    private function save(string $signatoryDate)
    {
        $user = new User;
        $user->forceFill(['pk' => 90380099, 'user_id' => null, 'user_category' => 'E']);

        return $this->actingAs($user)->withSession(['user_roles' => ['Super Admin']])
            ->postJson('/calendar/weekly-info/save', [
                'course_id' => self::COURSE, 'week_start' => self::WEEK,
                'mention_of_week' => 'signatory date test', 'signatory_date' => $signatoryDate,
            ]);
    }

    private function storedDate(): ?string
    {
        return DB::table('course_week_notes')->where('course_master_pk', self::COURSE)->value('signatory_date');
    }

    public function test_a_y_m_d_date_is_stored(): void
    {
        $this->save('2035-08-21')->assertOk();

        $this->assertSame('2035-08-21', substr((string) $this->storedDate(), 0, 10));
    }

    /** @dataProvider shapesTheDateColumnRejects */
    public function test_other_date_shapes_are_refused_not_a_500(string $shape): void
    {
        $this->save($shape)->assertStatus(422)->assertJsonValidationErrors('signatory_date');

        $this->assertNull($this->storedDate(), 'nothing of the sheet is stored');
    }

    public static function shapesTheDateColumnRejects(): array
    {
        return [
            'd-m-Y' => ['21-08-2035'],
            'm/d/Y' => ['08/21/2035'],
            'd.m.Y' => ['21.08.2035'],
            'long form' => ['21 August 2035'],
            'impossible' => ['2035-02-30'],
        ];
    }
}
