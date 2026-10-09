<?php

namespace Tests\Feature;

use App\Models\MDOEscotDutyMap;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * MDO Duty Type master: the three system rows.
 *
 * MDOEscotDutyMap::getMdoDutyTypes() finds MDO / Escort / Other by name, and
 * MDOEscrotExemptionRequest uses the 'escort' key to require faculty on an
 * escort exemption. Renaming the Escort row from the grid made that key null,
 * so escort exemptions were accepted without faculty. The system rows now keep
 * their name (case aside), stay active and cannot be deleted; other rows are
 * edited as before.
 */
class MdoDutyTypeSystemRowsTest extends TestCase
{
    private const XHR = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];

    private bool $inTransaction = false;

    private array $types = [];

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No database connection: ' . $e->getMessage());
        }

        DB::beginTransaction();
        $this->inTransaction = true;

        $this->types = MDOEscotDutyMap::getMdoDutyTypes();
        if (empty($this->types['escort'])) {
            $this->markTestSkipped('no Escort duty type row in this database');
        }
    }

    protected function tearDown(): void
    {
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

    private function store(array $data)
    {
        return $this->actingAs($this->admin())->withHeaders(self::XHR)->post(route('master.mdo_duty_type.store'), $data);
    }

    private function escortRow()
    {
        return DB::table('mdo_duty_type_master')->where('pk', $this->types['escort'])->first();
    }

    public function test_renaming_the_escort_row_is_refused_and_the_escort_lookup_survives(): void
    {
        $before = $this->escortRow();

        $this->store(['id' => $this->types['escort'], 'mdo_duty_type_name' => 'Escort Duty', 'active_inactive' => 1])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame($this->types['escort'], MDOEscotDutyMap::getMdoDutyTypes()['escort']);
        $this->assertSame($before->mdo_duty_type_name, $this->escortRow()->mdo_duty_type_name);
    }

    public function test_every_system_row_keeps_its_name(): void
    {
        foreach (['mdo', 'escort', 'other'] as $key) {
            if (empty($this->types[$key])) {
                continue;
            }

            $this->store(['id' => $this->types[$key], 'mdo_duty_type_name' => 'Renamed ' . $key, 'active_inactive' => 1])
                ->assertStatus(422);
        }

        $this->assertSame($this->types, MDOEscotDutyMap::getMdoDutyTypes());
    }

    public function test_a_system_row_cannot_be_deactivated_or_deleted(): void
    {
        $pk = $this->types['escort'];

        // Through the edit form, keeping the name.
        $this->store(['id' => $pk, 'mdo_duty_type_name' => $this->escortRow()->mdo_duty_type_name, 'active_inactive' => 0])
            ->assertStatus(422);

        // Through the grid's status switch.
        $this->actingAs($this->admin())->withHeaders(self::XHR)
            ->post(route('master.mdo_duty_type.status'), ['pk' => $pk, 'active_inactive' => 0])
            ->assertStatus(422);
        $this->assertSame(1, (int) $this->escortRow()->active_inactive);

        // Through Delete.
        $this->actingAs($this->admin())->withHeaders(self::XHR)
            ->post(route('master.mdo_duty_type.delete'), ['id' => $pk])
            ->assertStatus(422);
        $this->assertNotNull($this->escortRow());
        $this->assertSame($pk, MDOEscotDutyMap::getMdoDutyTypes()['escort']);
    }

    public function test_a_case_only_rename_of_a_system_row_is_still_allowed(): void
    {
        $this->store(['id' => $this->types['escort'], 'mdo_duty_type_name' => 'ESCORT', 'active_inactive' => 1])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame($this->types['escort'], MDOEscotDutyMap::getMdoDutyTypes()['escort']);
    }

    public function test_other_rows_are_edited_deactivated_and_deleted_as_before(): void
    {
        $pk = DB::table('mdo_duty_type_master')->insertGetId([
            'mdo_duty_type_name' => 'Fme Probe Duty Type', 'active_inactive' => 1,
        ]);

        $this->store(['id' => $pk, 'mdo_duty_type_name' => 'Fme Probe Duty Type Renamed', 'active_inactive' => 0])
            ->assertOk()
            ->assertJson(['success' => true]);
        $this->assertSame('Fme Probe Duty Type Renamed', DB::table('mdo_duty_type_master')->where('pk', $pk)->value('mdo_duty_type_name'));

        $this->actingAs($this->admin())->withHeaders(self::XHR)
            ->post(route('master.mdo_duty_type.status'), ['pk' => $pk, 'active_inactive' => 1])
            ->assertOk();

        $this->actingAs($this->admin())
            ->post(route('master.mdo_duty_type.delete'), ['id' => $pk])
            ->assertRedirect(route('master.mdo_duty_type.index'));
        $this->assertFalse(DB::table('mdo_duty_type_master')->where('pk', $pk)->exists());
    }
}
