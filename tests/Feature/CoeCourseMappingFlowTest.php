<?php

namespace Tests\Feature;

use App\Models\COE\ParticipantCode;
use App\Models\COE\RollNumber;
use App\Models\COE\RollNumberAllocation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * COE Roll Numbers / Unique Participant Codes for a course whose OTs are on it only
 * through student_master_course__map, with no batch on record (CourseParticipants):
 * the course is offered with its course year as batch, both can be generated, an OT
 * gets a roll number of its own on each course (the other course's is left alone), and
 * codes on another course do not block this one's. Development database, in a transaction.
 */
class CoeCourseMappingFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private int $course;

    private const YEAR = 2031;

    /** @var list<int> */
    private array $ots = [];

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::where('user_category', '!=', 'S')->first();
        $ots = DB::table('student_master as s')
            ->leftJoin('coe_participant_codes as p', 'p.student_master_pk', '=', 's.pk')
            ->leftJoin('coe_roll_numbers as r', 'r.student_master_pk', '=', 's.pk')
            ->whereNull('p.pk')->whereNull('r.pk')->whereNull('s.batch')
            ->limit(5)->pluck('s.pk');
        if ($admin === null || $ots->count() < 5) {
            $this->markTestSkipped('Needs a non-OT user and 5 OTs with no batch, code or roll number.');
        }

        $this->admin = $admin;
        $this->course = DB::table('course_master')->insertGetId([
            'course_name' => 'ZZ Mapped Course '.uniqid(),
            'couse_short_name' => 'ZZMAP',
            'course_year' => (string) self::YEAR,
            'start_year' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'active_inactive' => 1,
        ], 'pk');
        $this->ots = $ots->map(fn ($pk) => (int) $pk)->all();
        DB::table('student_master_course__map')->insert(array_map(fn ($pk) => [
            'student_master_pk' => $pk, 'course_master_pk' => $this->course, 'active_inactive' => 1,
        ], $this->ots));
    }

    private function generateCodes()
    {
        return $this->actingAs($this->admin)->postJson(route('admin.coe.unique-participant-code.generate'), [
            'course' => $this->course, 'batch' => self::YEAR, 'prefix' => 'ZZM-', 'count_start' => '1',
            'subject' => (int) DB::table('subject_master')->where('active_inactive', 1)->value('pk'),
        ]);
    }

    private function generateRolls()
    {
        return $this->actingAs($this->admin)->postJson(route('admin.coe.roll-number.generate'), [
            'course' => $this->course, 'batch' => self::YEAR, 'prefix' => 'ZZM', 'start_number' => '001',
            'strategy' => RollNumberAllocation::STRATEGY_ALLOCATION_RULES,
        ]);
    }

    public function test_a_mapped_course_is_offered_with_its_course_year_as_batch(): void
    {
        $cohorts = $this->actingAs($this->admin)->get(route('admin.coe.unique-participant-code.index'))->assertOk()->viewData('cohorts');
        $this->assertSame([['batch' => self::YEAR, 'count' => 5]], collect($cohorts)->firstWhere('key', (string) $this->course)['batches'] ?? null);

        $page = $this->actingAs($this->admin)->get(route('admin.coe.roll-number.index'))->assertOk();
        $this->assertContains($this->course, collect($page->viewData('courses'))->pluck('pk')->all());
        $this->assertSame([self::YEAR], $page->viewData('courseBatches')[$this->course]);

        // Generate offers the same active courses as Unique Participant Code (and Examination Drive).
        $this->assertEqualsCanonicalizing(
            collect($cohorts)->pluck('key')->map(fn ($k) => (int) $k)->all(),
            collect($page->viewData('activeCourses'))->pluck('pk')->all());
    }

    public function test_codes_and_roll_numbers_generate_for_the_mapped_ots(): void
    {
        $this->generateCodes()->assertOk()->assertJsonPath('written', 5);
        $this->assertEqualsCanonicalizing($this->ots, ParticipantCode::where('course_master_pk', $this->course)
            ->where('batch', self::YEAR)->pluck('student_master_pk')->all());

        $this->generateRolls()->assertOk()->assertJsonPath('written', 5);
        $this->assertEqualsCanonicalizing($this->ots, RollNumber::whereHas('allocation', fn ($q) => $q
            ->where('course_master_pk', $this->course)->where('batch', self::YEAR))->pluck('student_master_pk')->all());

        // The grid lists them under the mapped course, with their numbers.
        $rows = collect($this->actingAs($this->admin)->get(route('admin.coe.roll-number.index'))->viewData('rows'))
            ->where('course_pk', $this->course);
        $this->assertCount(5, $rows);
        $this->assertTrue($rows->every(fn ($r) => $r['batch'] === (string) self::YEAR && $r['roll_number'] !== null));
    }

    public function test_each_course_has_its_own_roll_number_and_other_codes_do_not_block(): void
    {
        $other = (int) DB::table('course_master')->where('pk', '!=', $this->course)->value('pk');
        $subject = (int) DB::table('subject_master')->where('active_inactive', 1)->value('pk');
        ParticipantCode::insert(['student_master_pk' => $this->ots[0], 'course_master_pk' => $other, 'subject_master_pk' => $subject,
            'batch' => 1999, 'unique_code' => 'ZZ-ELSEWHERE-'.uniqid(), 'status' => ParticipantCode::STATUS_PENDING]);

        // A code on another course is that course's: this course still codes every OT.
        $this->generateCodes()->assertOk()->assertJsonPath('written', 5);
        $this->assertSame(2, ParticipantCode::where('student_master_pk', $this->ots[0])->count());

        $allocation = RollNumberAllocation::create(['course_master_pk' => $other, 'batch' => 1999, 'prefix' => 'ZZE',
            'start_number' => 1, 'number_padding' => 3, 'strategy' => RollNumberAllocation::STRATEGY_ALLOCATION_RULES]);
        $elsewhere = 'ZZE-'.uniqid();
        RollNumber::insert(['roll_number_allocation_pk' => $allocation->pk, 'student_master_pk' => $this->ots[1],
            'serial_number' => 1, 'roll_number' => $elsewhere, 'status' => RollNumber::STATUS_GENERATED]);

        // One roll number per OT per course: every OT is numbered here, the other course's stays.
        $this->generateRolls()->assertOk()->assertJsonPath('written', 5);
        $this->assertSame(2, RollNumber::where('student_master_pk', $this->ots[1])->count());
        $this->assertSame($elsewhere, RollNumber::where('roll_number_allocation_pk', $allocation->pk)->value('roll_number'));

        $here = \App\Services\COE\CourseParticipants::rollNumbersOn($this->course, self::YEAR)->pluck('rn.roll_number', 'rn.student_master_pk');
        $this->assertCount(5, $here);
        $this->assertNotSame($elsewhere, $here[$this->ots[1]]);

        // The grid shows this course's number on this course, the other on the other.
        $row = collect($this->actingAs($this->admin)->get(route('admin.coe.roll-number.index'))->viewData('rows'))
            ->first(fn ($r) => $r['course_pk'] === $this->course && $r['student_pk'] === $this->ots[1]);
        $this->assertSame($here[$this->ots[1]], $row['roll_number']);

        // Regenerating this course never touches the other course's number.
        $this->generateRolls()->assertOk()->assertJsonPath('written', 5);
        $this->assertSame($elsewhere, RollNumber::where('roll_number_allocation_pk', $allocation->pk)->value('roll_number'));
    }
}
