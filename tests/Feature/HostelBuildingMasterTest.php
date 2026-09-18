<?php

namespace Tests\Feature;

use App\Models\BuildingMaster;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Hostel Building Master: the store() validation contract.
 *
 * Guards F-058. `building_master.building_type` is
 * enum('Administration','Hostel','Resident','Guest') — not a varchar — but the
 * rule was `required|string|max:255`, which accepts any short string. The value
 * then reached MySQL, which rejects an off-list ENUM value with error 1265
 * under STRICT_TRANS_TABLES (this connection sets `'strict' => true`
 * unconditionally in config/database.php). store() has no try/catch, so the
 * QueryException surfaced as an unhandled HTTP 500 instead of a 422 the form
 * could render.
 *
 * The same class of defect as F-054, one level up: that one was a column WIDTH
 * the rule disagreed with, this one is a column DOMAIN.
 */
class HostelBuildingMasterTest extends TestCase
{
    private const STORE = '/master/hostel-building-master/store';

    private function actor(): User
    {
        $user = User::query()->orderBy('pk')->first();
        if (! $user) {
            $this->markTestSkipped('no user available to authenticate as');
        }

        return $user;
    }

    /** The modal posts over AJAX and reads JSON back. */
    private function submit(User $user, array $payload)
    {
        return $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->postJson(self::STORE, $payload);
    }

    public function test_building_type_outside_the_enum_is_refused_by_validation(): void
    {
        $user = $this->actor();

        DB::beginTransaction();

        try {
            $name = 'Gate Probe Building ' . uniqid();

            // The defect: this used to pass validation and 500 at the INSERT.
            $this->submit($user, [
                'building_name' => $name,
                'no_of_floors'  => 1,
                'no_of_rooms'   => 1,
                'building_type' => 'Warehouse',
            ])
                ->assertStatus(422)
                ->assertJsonValidationErrors('building_type');

            // Nothing may have been written on the refused attempt.
            $this->assertNull(
                BuildingMaster::where('building_name', $name)->first(),
                'a refused building_type still wrote a row'
            );

            fwrite(STDERR, "off-enum building_type refused with 422, no row written\n");
        } finally {
            DB::rollBack();
        }
    }

    /**
     * The rule must be tighter, not merely different: every value the form
     * actually offers has to keep working. Without this a `Rule::in` typo would
     * turn a 500 into a screen nobody can submit at all.
     */
    public function test_every_offered_building_type_is_still_accepted(): void
    {
        $user = $this->actor();

        DB::beginTransaction();

        try {
            $types = array_keys(BuildingMaster::$buildingType);
            $this->assertNotEmpty($types, 'BuildingMaster::$buildingType is empty');

            foreach ($types as $i => $type) {
                $name = 'Gate Probe Building ' . uniqid() . ' ' . $i;

                $this->submit($user, [
                    'building_name' => $name,
                    'no_of_floors'  => 1,
                    'no_of_rooms'   => 1,
                    'building_type' => $type,
                ])->assertOk()->assertJson(['status' => 'success']);

                $row = BuildingMaster::where('building_name', $name)->first();
                $this->assertNotNull($row, "the row was not written for type {$type}");
                $this->assertSame($type, $row->building_type);
            }

            fwrite(STDERR, 'all ' . count($types) . " offered building types accepted\n");
        } finally {
            DB::rollBack();
        }
    }

    /**
     * The option list the controller hands the view and the list it validates
     * against must be the same one, or the form can offer a value the validator
     * refuses. Both read BuildingMaster::$buildingType; this pins that they
     * agree with the column itself.
     */
    public function test_offered_types_match_the_database_enum(): void
    {
        $column = DB::selectOne(
            'SELECT COLUMN_TYPE AS t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['building_master', 'building_type']
        );

        if (! $column || stripos($column->t, 'enum(') !== 0) {
            $this->markTestSkipped('building_master.building_type is not an ENUM on this connection');
        }

        preg_match_all("/'((?:[^']|'')*)'/", $column->t, $m);
        $dbValues = array_map(static fn ($v) => str_replace("''", "'", $v), $m[1]);

        sort($dbValues);
        $offered = array_keys(BuildingMaster::$buildingType);
        sort($offered);

        $this->assertSame(
            $dbValues,
            $offered,
            'BuildingMaster::$buildingType has drifted from the column definition — '
            . 'the form would offer a value the column rejects, or hide one it allows'
        );

        fwrite(STDERR, 'enum matches offered list: ' . implode(', ', $dbValues) . "\n");
    }
}
