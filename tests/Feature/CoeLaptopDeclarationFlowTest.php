<?php

namespace Tests\Feature;

use App\Models\COE\LaptopResponse;
use App\Models\COE\ScriberMaster;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * COE → Laptop: the OT's own Laptop Availability Form (session-bound, one-shot, scriber
 * half gated by disability) and the COE Admin's "Add Single Record" on OT Laptop Status.
 * Development database, in a transaction.
 */
class CoeLaptopDeclarationFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $ot;

    private int $studentPk;

    private int $coursePk;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // An OT with a login, a course, and no laptop answer yet.
        $row = DB::table('user_credentials as uc')
            ->join('student_master as sm', 'sm.pk', '=', 'uc.user_id')
            ->leftJoin('coe_laptop_responses as lr', 'lr.student_master_pk', '=', 'sm.pk')
            ->where('uc.user_category', 'S')
            ->whereNotNull('sm.course_master_pk')
            ->whereNull('lr.pk')
            ->first(['uc.pk as user_pk', 'sm.pk as student_pk', 'sm.course_master_pk']);
        $admin = User::where('user_category', '!=', 'S')->first();

        if ($row === null || $admin === null) {
            $this->markTestSkipped('Needs an OT login without a laptop answer, and a non-OT user.');
        }

        $this->ot = User::findOrFail($row->user_pk);
        $this->studentPk = (int) $row->student_pk;
        $this->coursePk = (int) $row->course_master_pk;
        $this->admin = $admin;
    }

    private function declare(array $data)
    {
        return $this->actingAs($this->ot)->post(route('admin.coe.laptop-declaration.store'), $data);
    }

    /** A scriber is only for an OT whose profile records a disability (PwBD) category. */
    private function setPwbd(?string $ph): void
    {
        DB::table('student_master')->where('pk', $this->studentPk)->update(['ph' => $ph]);
    }

    /** Laptop status is recorded only for an OT with a roll number on the course. */
    private function giveRollNumber(): void
    {
        // coe_roll_numbers is unique per OT; the test transaction puts back any number this replaces.
        DB::table('coe_roll_numbers')->where('student_master_pk', $this->studentPk)->delete();

        $allocation = DB::table('coe_roll_number_allocations')->insertGetId([
            'course_master_pk' => $this->coursePk,
            'batch' => 0,
            'prefix' => 'LT',
            'created_date' => now(),
        ]);
        DB::table('coe_roll_numbers')->insert([
            'roll_number_allocation_pk' => $allocation,
            'student_master_pk' => $this->studentPk,
            'serial_number' => 1,
            'roll_number' => 'LT-PHPUNIT-001',
            'created_date' => now(),
        ]);
    }

    private function answer(): ?LaptopResponse
    {
        return LaptopResponse::where('student_master_pk', $this->studentPk)->first();
    }

    public function test_the_ot_sees_an_open_form(): void
    {
        $this->actingAs($this->ot)->get(route('admin.coe.laptop-declaration.index'))
            ->assertOk()
            ->assertSee('Laptop &amp; Other Assets Availability Form', false)
            ->assertSee('id="coeDeclForm"', false)
            ->assertDontSee('already submitted');
    }

    public function test_a_non_ot_is_sent_away(): void
    {
        $this->actingAs($this->admin)->get(route('admin.coe.laptop-declaration.index'))
            ->assertRedirect(route('admin.dashboard'));
        $this->actingAs($this->admin)->post(route('admin.coe.laptop-declaration.store'), [
            'laptop_required' => '0', 'has_disability' => '0',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_laptop_yes_needs_a_reason(): void
    {
        $this->declare(['laptop_required' => '1', 'has_disability' => '0', 'reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertNull($this->answer());
    }

    public function test_scriber_yes_needs_remarks(): void
    {
        $this->declare(['laptop_required' => '0', 'has_disability' => '1', 'scriber_required' => '1'])
            ->assertSessionHasErrors('scriber_remarks');

        $this->assertNull($this->answer());
    }

    public function test_the_ot_submits_their_own_answer_once(): void
    {
        $this->setPwbd('VI');

        $this->declare([
            'laptop_required' => '1',
            'reason' => 'Do not own a laptop',
            'has_disability' => '1',
            'scriber_required' => '1',
            'scriber_remarks' => 'Need a fast writer',
            // Ignored: the participant always comes from the session.
            'student_master_pk' => $this->studentPk + 1,
            'additional_remarks' => 'not the OT\'s field',
        ])->assertRedirect(route('admin.coe.laptop-declaration.index'))->assertSessionHas('success');

        $answer = $this->answer();
        $this->assertNotNull($answer);
        $this->assertTrue((bool) $answer->laptop_required);
        $this->assertSame('Do not own a laptop', $answer->reason);
        $this->assertTrue((bool) $answer->has_disability);
        $this->assertTrue((bool) $answer->scriber_required);
        $this->assertSame('Need a fast writer', $answer->scriber_remarks);
        $this->assertNull($answer->additional_remarks);
        $this->assertSame(LaptopResponse::SOURCE_TRAINEE, (int) $answer->source);
        $this->assertFalse(LaptopResponse::where('student_master_pk', $this->studentPk + 1)
            ->where('responded_by', $this->ot->pk)->exists());

        // Locked from here: a second submit changes nothing and the page says why.
        $this->declare(['laptop_required' => '0', 'has_disability' => '0'])->assertSessionHas('error');
        $this->assertTrue((bool) $this->answer()->laptop_required);

        $this->actingAs($this->ot)->get(route('admin.coe.laptop-declaration.index'))
            ->assertOk()->assertSee('already submitted');
    }

    public function test_no_disability_clears_the_scriber_answer(): void
    {
        $this->declare([
            'laptop_required' => '0',
            'has_disability' => '0',
            'scriber_required' => '1',
            'scriber_remarks' => 'should not be kept',
        ])->assertSessionHas('success');

        $answer = $this->answer();
        $this->assertFalse((bool) $answer->has_disability);
        $this->assertNull($answer->scriber_required);
        $this->assertNull($answer->scriber_remarks);
    }

    public function test_an_admin_answer_locks_the_ot_form(): void
    {
        $this->giveRollNumber();

        $this->actingAs($this->admin)->postJson(route('admin.coe.ot-laptop-status.store'), [
            'course' => $this->coursePk,
            'student_master_pks' => [$this->studentPk],
            'laptop_required' => '1',
            'reason' => 'Recorded by the Examination Section',
            'has_disability' => '0',
        ])->assertOk();

        $this->assertSame(LaptopResponse::SOURCE_ADMIN, (int) $this->answer()->source);

        $this->actingAs($this->ot)->get(route('admin.coe.laptop-declaration.index'))
            ->assertOk()->assertSee('The Examination Section has already recorded');
        $this->declare(['laptop_required' => '0', 'has_disability' => '0'])->assertSessionHas('error');
        $this->assertSame(LaptopResponse::SOURCE_ADMIN, (int) $this->answer()->source);
    }

    public function test_admin_refuses_an_ot_without_a_roll_number(): void
    {
        DB::table('coe_roll_numbers')->where('student_master_pk', $this->studentPk)->delete();

        $this->actingAs($this->admin)->getJson(route('admin.coe.ot-laptop-status.participants', ['course' => $this->coursePk]))
            ->assertOk()->assertJsonMissing(['pk' => $this->studentPk]);

        $this->actingAs($this->admin)->postJson(route('admin.coe.ot-laptop-status.store'), [
            'course' => $this->coursePk,
            'student_master_pks' => [$this->studentPk],
            'laptop_required' => '0',
            'has_disability' => '0',
        ])->assertStatus(422)->assertJsonPath('message', 'That OT has no roll number on the selected course.');

        $this->assertNull($this->answer());
    }

    public function test_admin_records_scribers_for_an_ot_on_a_mapped_course(): void
    {
        // An OT on a phase course through student_master_course__map, whose own course is another.
        $ot = DB::table('student_master_course__map as m')
            ->join('student_master as sm', 'sm.pk', '=', 'm.student_master_pk')
            ->leftJoin('coe_laptop_responses as lr', 'lr.student_master_pk', '=', 'sm.pk')
            ->where('m.active_inactive', 1)
            ->whereColumn('sm.course_master_pk', '!=', 'm.course_master_pk')
            ->whereNull('lr.pk')
            ->first(['sm.pk', 'm.course_master_pk']);
        $scriber = DB::table('coe_scriber_master')->where('scriber_role', ScriberMaster::ROLE_PRIMARY)->value('pk');
        if ($ot === null || $scriber === null) {
            $this->markTestSkipped('Needs an OT on a course through the course map, and a primary scriber.');
        }

        $this->studentPk = (int) $ot->pk;
        $this->coursePk = (int) $ot->course_master_pk;
        $this->giveRollNumber();
        $this->setPwbd('LD');

        $this->actingAs($this->admin)->postJson(route('admin.coe.ot-laptop-status.store'), [
            'course' => $this->coursePk,
            'student_master_pks' => [$this->studentPk],
            'laptop_required' => '1',
            'reason' => 'No laptop',
            'has_disability' => '1',
            'scriber_required' => '1',
            'scriber_remarks' => 'Partially blind',
            'scribers_present' => '1',
            'scribers' => [[
                'student_master_pk' => $this->studentPk,
                'primary_scriber_pk' => $scriber,
                'secondary_scriber_pk' => '',
                'disability_type' => (string) array_key_first(LaptopResponse::disabilityTypes()),
            ]],
        ])->assertOk();

        $answer = $this->answer();
        $this->assertSame(1, (int) $answer->laptop_required);
        $this->assertSame(1, (int) $answer->scriber_required);
        $this->assertSame((int) $scriber, (int) $answer->primary_scriber_pk);

        // Listed under the course of its roll number, not the OT's own course.
        $course = DB::table('course_master')->where('pk', $this->coursePk)->first(['active_inactive', 'end_date']);
        $scope = (int) $course->active_inactive === 1 && $course->end_date >= now()->toDateString() ? 'active' : 'archived';
        $this->actingAs($this->admin)->get(route('admin.coe.ot-laptop-status.index', ['scope' => $scope]))
            ->assertOk()->assertSee('LT-PHPUNIT-001');
    }

    public function test_an_ot_without_a_pwbd_category_cannot_request_a_scriber(): void
    {
        $this->setPwbd(null);

        $this->declare([
            'laptop_required' => '0',
            'has_disability' => '1',
            'scriber_required' => '1',
            'scriber_remarks' => 'Need a fast writer',
        ])->assertSessionHasErrors('scriber_required');
        $this->assertNull($this->answer());

        // Without a scriber the same OT can still answer.
        $this->declare(['laptop_required' => '0', 'has_disability' => '1', 'scriber_required' => '0'])
            ->assertSessionHas('success');
    }

    public function test_admin_cannot_give_a_scriber_to_an_ot_without_a_pwbd_category(): void
    {
        $this->giveRollNumber();
        $this->setPwbd('');
        $scriber = DB::table('coe_scriber_master')->where('scriber_role', ScriberMaster::ROLE_PRIMARY)->value('pk');
        $base = [
            'course' => $this->coursePk,
            'student_master_pks' => [$this->studentPk],
            'laptop_required' => '0',
            'has_disability' => '1',
            'scribers_present' => '1',
        ];
        $row = ['student_master_pk' => $this->studentPk, 'secondary_scriber_pk' => '',
            'disability_type' => (string) array_key_first(LaptopResponse::disabilityTypes())];

        $this->actingAs($this->admin)->postJson(route('admin.coe.ot-laptop-status.store'), $base + [
            'scriber_required' => '1', 'scriber_remarks' => 'Partially blind',
            'scribers' => [$row + ['primary_scriber_pk' => '']],
        ])->assertStatus(422)->assertJsonValidationErrors('scriber_required');
        $this->assertNull($this->answer());

        if ($scriber !== null) {
            // Scriber Required: No, but a scriber picked in the table anyway.
            $this->actingAs($this->admin)->postJson(route('admin.coe.ot-laptop-status.store'), $base + [
                'scriber_required' => '0',
                'scribers' => [$row + ['primary_scriber_pk' => (string) $scriber]],
            ])->assertStatus(422)->assertJsonValidationErrors('scribers');
            $this->assertNull($this->answer());
        }

        // No scriber: saved, with the disability answer kept.
        $this->actingAs($this->admin)->postJson(route('admin.coe.ot-laptop-status.store'), $base + [
            'scriber_required' => '0',
            'scribers' => [$row + ['primary_scriber_pk' => '']],
        ])->assertOk();
        $this->assertTrue((bool) $this->answer()->has_disability);
        $this->assertNull($this->answer()->primary_scriber_pk);
    }

    public function test_admin_refuses_an_ot_from_another_course(): void
    {
        $otherCourse = (int) DB::table('course_master')->where('pk', '!=', $this->coursePk)->value('pk');

        $this->actingAs($this->admin)->postJson(route('admin.coe.ot-laptop-status.store'), [
            'course' => $otherCourse,
            'student_master_pks' => [$this->studentPk],
            'laptop_required' => '0',
            'has_disability' => '0',
        ])->assertStatus(422);

        $this->assertNull($this->answer());
    }

    public function test_admin_screens_render(): void
    {
        $this->actingAs($this->admin)->get(route('admin.coe.ot-laptop-status.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.coe.scriber-master.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.coe.laptop-scriber-seats.index'))->assertOk();
    }
}
