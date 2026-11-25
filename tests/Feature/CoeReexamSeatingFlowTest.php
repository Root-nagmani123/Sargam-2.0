<?php

namespace Tests\Feature;

use App\Models\BuildingFloorRoomMapping;
use App\Models\BuildingMaster;
use App\Models\COE\Reexam;
use App\Models\COE\ReexamCandidate;
use App\Models\COE\RollNumberAllocation;
use App\Models\COE\SeatAllocation;
use App\Models\COE\SeatPlan;
use App\Models\ExaminationDrive;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * COE Re-exam, step 4: Venue Allocation (Map Seating by drive) on a re-exam drive seats
 * only its re-exam OTs, per subject; a normal drive is unchanged (whole cohort).
 * Development database, in a transaction.
 *
 * Fixture (as CoeVenueAllocationFlowTest): roll numbers for an unnumbered batch of a course,
 * a throwaway examination room; then a main drive with one component and its re-exam
 * (the link and candidates written directly: creating a re-exam is CoeReexamFlowTest's job),
 * whose candidates are the first two OTs.
 */
class CoeReexamSeatingFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private int $venueId;

    private int $building;

    private int $floor;

    private ExaminationDrive $main;

    private ExaminationDrive $reexam;

    private int $mainMapId;

    private int $reexamMapId;

    /** @var list<int> */
    private array $cohort = [];

    /** @var list<int> the re-exam's candidates */
    private array $candidates = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('coe_reexams')) {
            $this->markTestSkipped('Run the migrations first (coe_reexams).');
        }
        $admin = User::role('Super Admin')->first();
        $building = DB::table('building_master')->where('building_type', BuildingMaster::TYPE_CLASSROOM)
            ->where('active_inactive', 1)->value('pk')
            ?? DB::table('building_master')->insertGetId([
                'building_name' => 'PHPUnit Exam Block', 'building_type' => BuildingMaster::TYPE_CLASSROOM, 'active_inactive' => 1,
            ], 'pk');
        $floor = DB::table('floor_master')->where('active_inactive', 1)->value('pk');
        $cohort = DB::table('student_master as sm')
            ->leftJoin('coe_roll_number_allocations as rna', fn ($j) => $j
                ->on('rna.course_master_pk', '=', 'sm.course_master_pk')->on('rna.batch', '=', 'sm.batch'))
            ->whereNull('rna.pk')
            ->whereNotNull('sm.course_master_pk')
            ->where('sm.batch', '>', 0)
            ->whereNotIn('sm.course_master_pk', DB::table('coe_roll_number_allocations')->select('course_master_pk'))
            ->groupBy('sm.course_master_pk', 'sm.batch')
            ->havingRaw('count(*) between 4 and 60')
            ->first(['sm.course_master_pk', 'sm.batch']);
        $subject = DB::table('subject_master')->value('pk');
        $component = DB::table('component_master')->value('pk');
        $term = DB::table('term_master')->value('pk');
        $type = DB::table('examination_type_master')->value('pk');
        if (! $admin || ! $floor || ! $cohort || ! $subject || ! $component || ! $term || ! $type) {
            $this->markTestSkipped('Needs a Super Admin, a floor, an unnumbered batch of 4–60 OTs, a subject, a component and a term.');
        }
        $this->admin = $admin;
        $this->building = (int) $building;
        $this->floor = (int) $floor;
        $course = (int) $cohort->course_master_pk;
        $batch = (int) $cohort->batch;

        // Roll numbers are generated only for a running course.
        DB::table('course_master')->where('pk', $course)
            ->update(['active_inactive' => 1, 'end_date' => now()->addYear()->toDateString()]);
        $this->actingAs($this->admin)->postJson(route('admin.coe.roll-number.generate'), [
            'course' => $course, 'batch' => $batch, 'prefix' => 'RX', 'start_number' => '001',
            'strategy' => RollNumberAllocation::STRATEGY_SEQUENTIAL_BY_NAME,
        ])->assertOk();
        $this->cohort = DB::table('coe_roll_numbers as r')
            ->join('coe_roll_number_allocations as a', 'a.pk', '=', 'r.roll_number_allocation_pk')
            ->where('a.course_master_pk', $course)->where('a.batch', $batch)
            ->orderBy('r.serial_number')->pluck('r.student_master_pk')->map(fn ($pk) => (int) $pk)->all();
        if (count($this->cohort) < 4) {
            $this->markTestSkipped('The batch got fewer than 4 roll numbers.');
        }
        $this->candidates = array_slice($this->cohort, 0, 2);

        $drive = fn (string $name, string $day) => ExaminationDrive::create([
            'drive_name' => $name, 'examination_type_master_pk' => $type, 'term_master_pk' => $term, 'course_master_pk' => $course,
            'phase' => (string) $batch, 'academic_session' => $batch,
            'start_date' => $day, 'end_date' => $day,
            'status' => ExaminationDrive::STATUS_ACTIVE, 'cc_status' => ExaminationDrive::CC_APPROVED,
        ]);
        $map = fn (ExaminationDrive $d, string $day) => DB::table('examination_drive_component_maps')->insertGetId([
            'examination_drive_id' => $d->id, 'subject_master_pk' => $subject, 'component_master_pk' => $component,
            'max_marks' => 100, 'passing_marks' => 50, 'source' => 'Faculty', 'weightage' => 100,
            'exam_date' => $day, 'exam_end_date' => $day,
        ]);
        $mainDay = now()->addDays(300)->toDateString();
        $reexamDay = now()->addDays(320)->toDateString();
        $this->main = $drive('ZZ Seating Main', $mainDay);
        $this->mainMapId = $map($this->main, $mainDay);
        $this->reexam = $drive('ZZ Seating Main — Re-exam 1', $reexamDay);
        $this->reexamMapId = $map($this->reexam, $reexamDay);

        $link = Reexam::create(['main_drive_id' => $this->main->id, 'reexam_drive_id' => $this->reexam->id, 'reexam_no' => 1, 'created_at' => now()]);
        foreach ($this->candidates as $pk) {
            ReexamCandidate::create(['reexam_id' => $link->id, 'subject_master_pk' => $subject, 'student_master_pk' => $pk,
                'reason' => ReexamCandidate::REASON_FAILED, 'score_percent' => 20, 'created_at' => now()]);
        }

        $this->venueId = $this->addRoom('PHPUNITRX', count($this->cohort) + 5);
    }

    /** A throwaway examination room: a venue and its Room Master row, joined by the short name. */
    private function addRoom(string $code, int $capacity): int
    {
        $venueId = (int) DB::table('venue_master')->insertGetId([
            'venue_name' => 'PHPUnit Exam Hall '.$code, 'venue_short_name' => $code, 'room_number' => $code,
            'capacity' => $capacity, 'laptop_capacity' => 0, 'seating_capacity' => $capacity,
            'active_inactive' => 1, 'created_date' => now()->toDateString(),
        ], 'venue_id');
        DB::table('building_floor_room_mapping')->insert([
            'building_master_pk' => $this->building, 'floor_master_pk' => $this->floor, 'room_name' => $code,
            'room_type' => array_key_first(BuildingFloorRoomMapping::$roomTypes), 'capacity' => $capacity,
            'active_inactive' => 1, 'created_date' => now(), 'updated_date' => now(),
        ]);

        return $venueId;
    }

    private function driveData(ExaminationDrive $drive): array
    {
        return $this->actingAs($this->admin)->getJson(route('admin.coe.venue-allocation.drive', $drive->id))->assertOk()->json();
    }

    private function mapDrive(ExaminationDrive $drive, int $mapId, array $ots)
    {
        return $this->actingAs($this->admin)->postJson(route('admin.coe.venue-allocation.store-drive'), [
            'drive_id' => $drive->id,
            'rows' => [['component_map_id' => $mapId, 'venue_id' => $this->venueId, 'ots' => $ots]],
        ]);
    }

    public function test_a_reexam_drive_lists_only_its_reexam_ots(): void
    {
        $data = $this->driveData($this->reexam);

        $this->assertSame(2, $data['ot_count']);
        $this->assertEqualsCanonicalizing($this->candidates, collect($data['ots'])->pluck('pk')->all());
        $component = $data['subjects'][0]['components'][0];
        $this->assertSame($this->reexamMapId, $component['id']);
        $this->assertEqualsCanonicalizing($this->candidates, $component['only']);
    }

    public function test_a_normal_drive_is_unchanged(): void
    {
        $data = $this->driveData($this->main);

        $this->assertSame(count($this->cohort), $data['ot_count']);
        $this->assertArrayNotHasKey('only', $data['subjects'][0]['components'][0]);
    }

    public function test_a_reexam_paper_seats_only_its_reexam_ots(): void
    {
        $this->mapDrive($this->reexam, $this->reexamMapId, $this->candidates)->assertOk();

        $plan = SeatPlan::where('examination_drive_component_map_id', $this->reexamMapId)->firstOrFail();
        $seated = SeatAllocation::where('seat_plan_pk', $plan->pk)->pluck('student_master_pk')->map(fn ($pk) => (int) $pk)->all();
        $this->assertEqualsCanonicalizing($this->candidates, $seated);
        $this->assertSame(2, (int) $plan->allocated_count);
        $this->assertSame(0, (int) $plan->unallocated_count);
    }

    public function test_an_ot_not_on_the_reexam_cannot_be_seated_on_it(): void
    {
        $this->mapDrive($this->reexam, $this->reexamMapId, [$this->candidates[0], $this->cohort[3]])
            ->assertStatus(422)->assertJsonFragment(['1 selected OT(s) do not sit this re-exam paper, or have no roll number.']);
    }

    public function test_every_reexam_ot_needs_a_seat(): void
    {
        $this->mapDrive($this->reexam, $this->reexamMapId, [$this->candidates[0]])
            ->assertStatus(422)->assertJsonValidationErrors('rows.0.ots');
        $this->assertFalse(SeatPlan::where('examination_drive_component_map_id', $this->reexamMapId)->exists());
    }

    public function test_the_main_paper_still_seats_the_whole_cohort(): void
    {
        $this->mapDrive($this->main, $this->mainMapId, $this->cohort)->assertOk();

        $plan = SeatPlan::where('examination_drive_component_map_id', $this->mainMapId)->firstOrFail();
        $this->assertSame(count($this->cohort), SeatAllocation::where('seat_plan_pk', $plan->pk)->count());
    }
}
