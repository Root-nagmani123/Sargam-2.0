<?php

namespace Tests\Feature;

use App\Exports\LbsnaaTableExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * The faculty contact pages moved from a client-side export (which honoured the
 * DataTables search) to a server URL that always exported every faculty member,
 * so searching "Sharma" and exporting gave the whole list (PR #334 F-053).
 */
class FacultyContactExportSearchTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** The faculty-name cells of the exported sheet. */
    private function exportedNames(array $query): array
    {
        Excel::fake();
        Excel::matchByRegex();

        $this->as($this->userWithRole('Super Admin'), ['Super Admin'])
            ->get(route('admin.dashboard.guest_faculty.export', $query))
            ->assertOk();

        $names = null;
        Excel::assertDownloaded('/^Guest_Faculty_.*\.xlsx$/', function (LbsnaaTableExport $export) use (&$names) {
            $p = new \ReflectionProperty(LbsnaaTableExport::class, 'rows');
            $p->setAccessible(true);
            $names = collect($p->getValue($export))->pluck(1)->all();

            return true;
        });

        return $names;
    }

    public function test_the_excel_export_holds_only_the_faculty_matching_the_search(): void
    {
        $guests = DB::table('faculty_master')->where('faculty_type', 2)->where('active_inactive', 1)
            ->whereNotNull('full_name')->where('full_name', '!=', '')->count();
        $name = 'Zq Searchable Guest ' . random_int(100000, 999999);
        // Copy the NOT NULL location keys from an existing row; only the name matters here.
        $like = DB::table('faculty_master')->where('faculty_type', 2)->first();
        if ($guests < 1 || ! $like) {
            $this->markTestSkipped('needs another guest faculty for the search to exclude');
        }
        DB::table('faculty_master')->insert([
            'full_name' => $name, 'first_name' => $name, 'faculty_type' => 2, 'active_inactive' => 1,
            'country_master_pk' => $like->country_master_pk, 'state_master_pk' => $like->state_master_pk,
            'state_district_mapping_pk' => $like->state_district_mapping_pk, 'city_master_pk' => $like->city_master_pk,
        ]);

        $this->assertSame([$name], $this->exportedNames(['format' => 'excel', 'search' => $name]));
    }

    public function test_without_a_search_the_export_holds_everyone(): void
    {
        $all = DB::table('faculty_master')->where('faculty_type', 2)->where('active_inactive', 1)->count();

        $this->assertCount($all, $this->exportedNames(['format' => 'excel']));
    }
}
