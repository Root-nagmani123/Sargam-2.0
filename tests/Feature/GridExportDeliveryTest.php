<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * How the branded-grid exports are DELIVERED, as opposed to what they contain.
 *
 * Two failures on this PR shared a shape: the export rendered perfectly and then
 * left the controller wrong, so every test that read the payload still passed.
 *
 *   - The CSV branch of the two sidebar controllers handed streamDownload() a
 *     bare `$filename`, with no `.csv`. The browser saves `Menus_20260917103000`
 *     with no extension; Windows offers no application for it and Excel will not
 *     open it by double-click. The sibling controllers (RoleController,
 *     SidebarCategoryController) pass `$filename.'.csv'`, so the module shipped
 *     two exports that behave one way and two that behave another.
 *
 *   - The Menus PDF asked for landscape via setPaper(), but the shared blade
 *     hard-coded `@page { size: A4 portrait; }`. DomPDF re-reads @page during
 *     render(), AFTER setPaper() has run, so the stylesheet wins and the widest
 *     grid in the module - 13 columns - came out squeezed onto portrait A4.
 *
 * Neither is visible from the rows, so both are asserted here against the
 * response and the rendered stylesheet rather than the export payload.
 */
class GridExportDeliveryTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Every branded-grid CSV export, and the menus.permission_name that opens it.
     *
     * @var array<string, string>
     */
    private const CSV_EXPORTS = [
        'sidebar.menus.export'       => 'menus',
        'sidebar.menu-groups.export' => 'sidemenu_groups',
        'sidebar.categories.export'  => 'topbar_category',
        'roles.export'               => 'roles',
    ];

    /**
     * An existing account holding exactly one permission.
     *
     * Not a freshly created row: user_credentials has no updated_at column, so
     * Eloquent inserts fail on it. DatabaseTransactions rolls the pivot changes
     * back, so the real user is unaffected once the test finishes.
     */
    private function actorHolding(string $permission): User
    {
        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('No user_credentials row to authenticate as.');
        }

        $user->syncRoles([]);
        $user->syncPermissions([]);
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    /**
     * Every rendered admin page leaves one output-buffer level open holding stray
     * whitespace - an application-wide condition CompressResponse absorbs in
     * production and unrelated to anything asserted here. Left alone it makes
     * PHPUnit report these as RISKY rather than passed.
     */
    private function unwindTo(int $baseline): void
    {
        while (ob_get_level() > $baseline) {
            ob_end_clean();
        }
    }

    public function test_every_grid_csv_export_is_delivered_with_a_csv_extension(): void
    {
        $baseline = ob_get_level();

        foreach (self::CSV_EXPORTS as $name => $permission) {
            $response = $this->actingAs($this->actorHolding($permission))
                ->get(route($name, ['format' => 'csv']));

            $this->unwindTo($baseline);
            $response->assertOk();

            $disposition = $response->headers->get('content-disposition');

            $this->assertNotNull($disposition, "{$name} sent no Content-Disposition header.");
            $this->assertMatchesRegularExpression(
                '/filename[^;=\n]*=(["\']?)[^"\';\n]+\.csv\1/i',
                $disposition,
                "{$name} offers its CSV with no .csv extension ({$disposition}) - the browser saves a "
                .'file the operating system cannot open. Pass $filename."csv" to streamDownload().'
            );
        }
    }

    /**
     * The shared PDF blade must render the orientation it is handed, because the
     * controller's setPaper() cannot make it do so.
     *
     * @dataProvider orientations
     */
    public function test_the_grid_pdf_blade_renders_the_orientation_it_is_given($given, string $expected): void
    {
        $data = [
            'reportTitle' => 'Orientation',
            'columns' => [['key' => 'sno', 'heading' => 'S.No', 'value' => fn ($r, $i) => $i + 1]],
            'rows' => [(object) ['x' => 1]],
            'filterLine' => null,
            'exportDate' => '17-09-2026 10:30 AM',
            'widths' => [],
            'orientation' => $given,
        ];

        // null here means "the caller passed nothing at all", which is the
        // default-to-portrait case - not a null $orientation.
        if ($given === null) {
            unset($data['orientation']);
        }

        $html = view('exports.branded_grid_pdf', $data)->render();

        $this->assertStringContainsString("@page { size: A4 {$expected};", $html);
    }

    /** @return array<string, array{0: string|null, 1: string}> */
    public static function orientations(): array
    {
        return [
            'landscape as asked'      => ['landscape', 'landscape'],
            'portrait as asked'       => ['portrait', 'portrait'],
            'omitted defaults to portrait' => [null, 'portrait'],
            'anything else is portrait'    => ['sideways', 'portrait'],
        ];
    }

    /**
     * The contract that actually broke: a controller calling setPaper(...,
     * 'landscape') must hand the blade the same word.
     *
     * Asserted against the source because the failure is a disagreement between
     * two call sites, and a rendered PDF would need to be measured to see it.
     */
    public function test_every_landscape_pdf_export_tells_the_blade_it_is_landscape(): void
    {
        $controllers = [
            'Http/Controllers/RoleController.php',
            'Http/Controllers/SidebarMenu/MenuController.php',
            'Http/Controllers/SidebarMenu/MenuGroupController.php',
            'Http/Controllers/SidebarMenu/SidebarCategoryController.php',
        ];

        foreach ($controllers as $relative) {
            $source = file_get_contents(app_path($relative));

            // Each loadView('exports.branded_grid_pdf', [...]) up to its setPaper().
            $offset = 0;
            while (($start = strpos($source, "loadView('exports.branded_grid_pdf'", $offset)) !== false) {
                $end = strpos($source, 'setPaper(', $start);
                $this->assertNotFalse($end, "{$relative}: a branded-grid PDF with no setPaper() call.");

                $block = substr($source, $start, ($end - $start) + 60);
                $isLandscape = str_contains($block, "setPaper('a4', 'landscape')");
                $declares = str_contains($block, "'orientation' => 'landscape'");

                $this->assertSame(
                    $isLandscape,
                    $declares,
                    "{$relative}: setPaper() and the blade's \$orientation disagree. DomPDF applies "
                    .'@page after setPaper(), so the blade wins - pass the same orientation to both.'
                );

                $offset = $end;
            }
        }
    }
}
