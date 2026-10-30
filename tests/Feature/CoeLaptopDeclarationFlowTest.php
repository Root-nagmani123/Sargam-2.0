<?php

namespace Tests\Feature;

use App\Models\COE\LaptopResponse;
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
