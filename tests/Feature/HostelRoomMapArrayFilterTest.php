<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Hostel Floor Room Map grid, Download (csv | excel | pdf) and Print.
 *
 * A filter that arrives as an array (room_type[]=Room, building_id[]=1,
 * search[]=x, status[]=1) narrows by its first element, not a 500: the export used to
 * concatenate it into the filter line and look up BuildingMaster::find([..])
 * ->building_name. A plain scalar filter still narrows every format.
 */
class HostelRoomMapArrayFilterTest extends TestCase
{
    private const PROBE = 'ArrFilterProbe-7Q3';

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

    /** Rendering the admin layout / a PDF can leave output buffers open. */
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
        $user = User::query()->orderBy('pk')->first();

        if (! $user) {
            $this->markTestSkipped('No user rows in this database to act as.');
        }

        return $user;
    }

    private function probeRow(): void
    {
        $building = DB::table('building_master')->value('pk');
        $floor = DB::table('floor_master')->value('pk');

        if (! $building || ! $floor) {
            $this->markTestSkipped('No building_master / floor_master rows to attach a probe room to.');
        }

        DB::table('building_floor_room_mapping')->insert([
            'building_master_pk' => $building,
            'floor_master_pk' => $floor,
            'room_name' => self::PROBE,
            'room_type' => 'Gym',
            'capacity' => 1,
            'active_inactive' => 1,
            'comment' => self::PROBE,
        ]);
    }

    /** Drain a streamed/attachment response into a string. */
    private function bodyOf($response): string
    {
        $base = $response->baseResponse;

        if ($base instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            $level = ob_get_level();
            ob_start();
            $base->sendContent();

            $out = '';
            while (ob_get_level() > $level) {
                $out = ob_get_clean() . $out;
            }

            return $out;
        }

        if ($base instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            return (string) file_get_contents($base->getFile()->getPathname());
        }

        return (string) $response->getContent();
    }

    /** @return array<string, string> label => URL, every format plus the grid itself */
    private function urls(array $query): array
    {
        $qs = '?' . http_build_query($query);

        return [
            'grid' => route('hostel.building.floor.room.map.index') . $qs,
            'csv' => route('hostel.building.floor.room.map.export', ['format' => 'csv']) . $qs,
            'excel' => route('hostel.building.floor.room.map.export', ['format' => 'excel']) . $qs,
            'pdf' => route('hostel.building.floor.room.map.export', ['format' => 'pdf']) . $qs,
            'print (export)' => route('hostel.building.floor.room.map.export', ['format' => 'print']) . $qs,
            'print' => route('hostel.building.floor.room.map.print') . $qs,
        ];
    }

    public function test_array_filters_are_ignored_not_a_500_in_every_format(): void
    {
        $payloads = [
            'room_type[]' => ['room_type' => ['Room']],
            'building_id[]' => ['building_id' => ['1']],
            'search[]' => ['search' => ['x']],
            'status[]' => ['status' => ['1']],
            'all at once' => ['room_type' => ['Room', 'Gym'], 'building_id' => ['1', '2'], 'search' => ['a', 'b'], 'status' => ['0', '1']],
        ];

        foreach ($payloads as $label => $query) {
            foreach ($this->urls($query) as $format => $url) {
                $response = $this->actingAs($this->admin())->get($url);
                $this->bodyOf($response);

                $this->assertSame(200, $response->getStatusCode(), "$label on $format must be ignored, not a 500");
            }
        }
    }

    /**
     * PR #335 review F-014: building_id[]=10 used to drop the building filter
     * (483 rooms instead of 8). An array filter narrows like its first element,
     * as the query did before this PR, on the grid and every text format.
     */
    public function test_an_array_building_filter_still_narrows_the_grid_and_every_export(): void
    {
        $this->probeRow();
        $probeBuilding = (int) DB::table('building_floor_room_mapping')->where('room_name', self::PROBE)->value('building_master_pk');
        $otherBuilding = (int) DB::table('building_master')->where('pk', '<>', $probeBuilding)->value('pk');
        if (! $otherBuilding) {
            $this->markTestSkipped('needs a second building_master row');
        }

        $grid = $this->actingAs($this->admin())
            ->get(route('hostel.building.floor.room.map.index') . '?' . http_build_query(['building_id' => [(string) $otherBuilding]]))
            ->assertOk();
        $this->assertSame(
            DB::table('building_floor_room_mapping')->where('building_master_pk', $otherBuilding)->count(),
            $grid->viewData('mappings')->total(),
            'the grid must list only the requested building'
        );

        foreach ([[$otherBuilding, false], [$probeBuilding, true]] as [$building, $expected]) {
            $query = ['building_id' => [(string) $building], 'search' => self::PROBE];
            foreach (['csv', 'print (export)', 'print'] as $format) {
                $body = $this->bodyOf($this->actingAs($this->admin())->get($this->urls($query)[$format])->assertOk());
                // A row cell, not the "Search: …" filter line: "PROBE," in CSV, ">PROBE<" in print.
                $row = $format === 'csv' ? str_contains($body, self::PROBE . ',') : (bool) preg_match('/>\s*' . self::PROBE . '\s*</', $body);
                $this->assertSame($expected, $row,
                    "$format with building_id[]=$building must " . ($expected ? '' : 'not ') . 'list the probe room');
            }
        }
    }

    public function test_a_scalar_filter_still_narrows_the_export(): void
    {
        $this->probeRow();

        $hit = $this->bodyOf($this->actingAs($this->admin())
            ->get(route('hostel.building.floor.room.map.export', ['format' => 'csv']) . '?' . http_build_query(['search' => self::PROBE, 'room_type' => 'Gym']))
            ->assertOk());
        $this->assertStringContainsString(self::PROBE, $hit);
        $this->assertStringContainsString('Room Type: Gym', $hit);
        $this->assertStringContainsString('Search: ' . self::PROBE, $hit);

        $miss = $this->bodyOf($this->actingAs($this->admin())
            ->get(route('hostel.building.floor.room.map.export', ['format' => 'csv']) . '?' . http_build_query(['search' => self::PROBE, 'room_type' => 'Room']))
            ->assertOk());
        $this->assertStringNotContainsString(self::PROBE . ',', $miss, 'room_type=Room must exclude the Gym probe');

        $print = $this->actingAs($this->admin())
            ->get(route('hostel.building.floor.room.map.print') . '?' . http_build_query(['search' => self::PROBE]))
            ->assertOk();
        $this->assertStringContainsString(self::PROBE, $this->bodyOf($print));
    }
}
