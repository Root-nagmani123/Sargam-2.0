<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Five rewritten forms read old input with (string) old(...). After a failed
 * validation that input is flashed back as it was posted, so a field sent as
 * name[]=x comes back as an array and the cast threw "Array to string
 * conversion" (500). The form must render, falling back to its default.
 *
 * The flashed state is set directly (_old_input), which is exactly what a
 * redirect()->back()->withInput() leaves behind.
 */
class FormOldInputArrayTest extends TestCase
{
    private bool $inTransaction = false;

    private int $obLevel = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->obLevel = ob_get_level();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No database connection: ' . $e->getMessage());
        }

        DB::beginTransaction();
        $this->inTransaction = true;
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > $this->obLevel) {
            ob_end_clean();
        }

        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    private function admin(): User
    {
        $id = DB::table('model_has_roles as mr')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->where('r.name', 'Super Admin')->value('mr.model_id');

        if (! $id) {
            $this->markTestSkipped('no Super Admin in this database');
        }

        return User::find($id);
    }

    /** @return array<string, array{string, array}> label => [url, old input] */
    private function forms(): array
    {
        $country = DB::table('country_master')->value('pk');
        if (! $country) {
            $this->markTestSkipped('no country_master row to edit');
        }

        return [
            'city create' => [route('master.city.create'), ['active_inactive' => ['1']]],
            'country edit' => [route('master.country.edit', $country), ['active_inactive' => ['1']]],
            'floor-map import' => [route('hostel.building.map.import'), ['course_master_pk' => ['1']]],
            'room-map create' => [route('hostel.building.floor.room.map.create'), [
                'building_master_pk' => ['1'], 'floor_master_pk' => ['1'], 'room_type' => ['Room'],
            ]],
            'discipline create' => [route('master.discipline.create'), ['active_inactive' => ['1']]],
        ];
    }

    public function test_array_old_input_renders_the_form_instead_of_a_500(): void
    {
        foreach ($this->forms() as $label => [$url, $old]) {
            $status = $this->actingAs($this->admin())
                ->withSession(['_old_input' => $old])
                ->get($url)
                ->getStatusCode();

            $this->assertSame(200, $status, "$label with array old input");
        }
    }

    /**
     * PR #335 review F-010: every other (string) old(...) the PR added, plus
     * country create's per-entry country_name echo. The first eleven are the
     * review's list; the rest share the root cause.
     *
     * @return array<string, array{string, array, 2?: array}> label => [url, old input, headers]
     */
    private function remainingForms(): array
    {
        $id = fn (string $table) => DB::table($table)->value('pk')
            ?? $this->markTestSkipped("no {$table} row to edit");
        $active = ['active_inactive' => ['1']];
        $xhr = ['X-Requested-With' => 'XMLHttpRequest'];

        return [
            'state create' => [route('master.state.create'), $active],
            'state edit' => [route('master.state.edit', $id('state_master')), $active],
            'district create' => [route('master.district.create'), $active],
            'district edit' => [route('master.district.edit', $id('state_district_mapping')), $active],
            'course memo create' => [route('course.memo.decision.create'), $active],
            'exemption category create' => [route('master.exemption.category.master.create'), $active],
            'medical speciality create' => [route('master.exemption.medical.speciality.create'), $active],
            'MDO duty type create' => [route('master.mdo_duty_type.create'), $active],
            'MDO duty type _form' => [route('master.mdo_duty_type.create'), $active, $xhr],
            'memo type create' => [route('master.memo.type.master.create'), $active],
            'memo conclusion create' => [route('master.memo.conclusion.master.create'), $active],
            'country create, nested country_name' => [route('master.country.create'), ['country_name' => ['ok', ['x']], 'active_inactive' => ['1']]],
            'city edit' => [route('master.city.edit', $id('city_master')), $active],
            'PT exemption create' => [route('admin.pt-exemption-master.create'), ['course_master_pk' => ['1']]],
            'hostel building create' => [route('master.hostel.building.create'), ['building_type' => ['1']]],
            'MDO/escort exemption create' => [route('mdo-escrot-exemption.create'), ['course_master_pk' => ['1'], 'mdo_duty_type_master_pk' => ['1']]],
            'MDO/escort exemption edit' => [route('mdo-escrot-exemption.edit', $id('mdo_escot_duty_map')), ['mdo_duty_type_master_pk' => ['1']]],
            'subject add modal' => [route('subject.index'), ['subject_form' => 'add', 'status' => ['1']]],
            'subject module add modal' => [route('subject-module.index'), ['module_form' => 'add', 'active_inactive' => ['1']]],
        ];
    }

    public function test_array_old_input_renders_every_remaining_rewritten_form(): void
    {
        $failed = [];
        foreach ($this->remainingForms() as $label => $form) {
            $status = $this->actingAs($this->admin())
                ->withSession(['_old_input' => $form[1]])
                ->withHeaders($form[2] ?? [])
                ->get($form[0])
                ->getStatusCode();
            $this->flushHeaders();

            if ($status !== 200) {
                $failed[] = "$label: $status";
            }
        }

        $this->assertSame([], $failed, 'forms that did not render with array old input');
    }

    public function test_scalar_old_input_is_still_reselected(): void
    {
        // Inactive (2) flashed back is still the selected option.
        $html = $this->actingAs($this->admin())
            ->withSession(['_old_input' => ['active_inactive' => '2']])
            ->get(route('master.city.create'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/<option value="2"[^>]*selected/', $html);
    }
}
