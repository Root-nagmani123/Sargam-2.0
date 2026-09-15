<?php

namespace Tests\Unit;

use Illuminate\Support\Collection;
use Tests\TestCase;
use Yajra\DataTables\CollectionDataTable;

/**
 * Stored names must reach the sidebar grids as text, never as markup.
 *
 * The Menus and Menu Groups grids render the group name, the parent-menu name
 * and the category name through rawColumns(), so that an empty relation can show
 * a muted em-dash. rawColumns() switches OFF Yajra's escaping for the WHOLE
 * column, including the real value — so the moment those columns went raw, the
 * stored name had to be escaped by hand, and three of them were not.
 *
 * Category, group and menu names carry no character restriction (CategoryRequest,
 * MenuGroupRequest, MenuRequest), and a holder of topbar_category or
 * sidemenu_groups can set them. A name of `<img src=x onerror=...>` therefore ran
 * in the browser of every administrator who opened those screens, Super Admin
 * included.
 *
 * Exercised through the real DataTables pipeline rather than by reading the
 * closures, because "is this column escaped" is a property of the column SHAPE —
 * the closure and the rawColumns list together — and only the pipeline sees both.
 *
 * DB-free: CollectionDataTable takes rows straight from a Collection.
 */
class SidebarGridEscapingTest extends TestCase
{
    private const PAYLOAD = '<img src=x onerror=alert(1)>';

    /** Render one row through the column shape the grids use. */
    private function render(bool $escaped): array
    {
        $rows = new Collection([
            (object) ['id' => 1, 'related' => (object) ['name' => self::PAYLOAD]],
            (object) ['id' => 2, 'related' => null],
        ]);

        $table = (new CollectionDataTable($rows))
            ->addColumn('related_name', function ($e) use ($escaped) {
                $name = optional($e->related)->name;

                if (! $escaped) {
                    // The shape as shipped: raw column, unescaped value.
                    return $name ?: '<span class="sbm-muted">&mdash;</span>';
                }

                return filled($name) ? e($name) : '<span class="sbm-muted">&mdash;</span>';
            })
            ->rawColumns(['related_name']);

        // make(true) returns the JsonResponse the grid actually sends.
        return json_decode($table->make(true)->getContent(), true);
    }

    public function test_a_hostile_related_name_is_escaped_in_the_grid_json(): void
    {
        $data = $this->render(true)['data'];

        $this->assertStringNotContainsString(
            '<img',
            $data[0]['related_name'],
            'a stored name must never reach the grid as live markup'
        );
        $this->assertStringContainsString('&lt;img', $data[0]['related_name']);
    }

    /**
     * The empty case still renders the em-dash, so escaping did not cost the
     * behaviour the raw column was introduced for.
     */
    public function test_an_empty_relation_still_renders_the_muted_dash(): void
    {
        $data = $this->render(true)['data'];

        $this->assertStringContainsString('sbm-muted', $data[1]['related_name']);
        $this->assertStringContainsString('&mdash;', $data[1]['related_name']);
    }

    /**
     * The defect itself, pinned: the shipped shape emitted the payload verbatim.
     *
     * Without this the first test could pass for the wrong reason — for example
     * if Yajra were escaping the column anyway, which is exactly the assumption
     * that made the original code look safe.
     */
    public function test_the_unescaped_shape_really_did_emit_live_markup(): void
    {
        $data = $this->render(false)['data'];

        $this->assertStringContainsString('<img src=x onerror=alert(1)>', $data[0]['related_name']);
    }
}
