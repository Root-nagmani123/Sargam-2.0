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
