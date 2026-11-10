<?php

namespace App\Http\Controllers\Admin\COE\Concerns;

use App\Http\Controllers\Concerns\ExportsMasterGrid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CSV / Excel / PDF / Print for the COE Master Data grids (shared
 * admin.coe.examination_term.index page), using the Master module's
 * ExportsMasterGrid so the files and the printout match the rest of Sargam
 * (docs/new-design-index-page.md §1).
 *
 * The export is the screen the user is looking at: the grid's own query (the
 * caller passes it already ordered like the DataTable), the Active / Inactive
 * filter (?status_filter=), the search box (?q=, matched by $search the way the
 * grid's search matches) and the visible columns (?cols=).
 */
trait ExportsCoeMaster
{
    use ExportsMasterGrid;

    /**
     * @param  array<string, array<string, mixed>>  $columns  keyed defs, see ExportsMasterGrid
     * @param  callable(Builder, string): void  $search  narrows the query to the search term
     */
    protected function exportCoeMaster(
        Request $request,
        string $format,
        Builder $query,
        array $columns,
        callable $search,
        string $reportTitle,
        string $slug
    ): Response {
        $format = strtolower($format);
        abort_unless(in_array($format, self::$exportFormats, true), 404);

        $term = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status_filter', '');

        // Inactive is 0 on some masters and 2 on the shared building / floor / room ones.
        if ($status === '1') {
            $query->where('active_inactive', 1);
        } elseif ($status === '0') {
            $query->where('active_inactive', '!=', 1);
        }
        if ($term !== '') {
            $search($query, $term);
        }

        $filters = array_values(array_filter([
            $term !== '' ? 'Search: ' . $term : null,
            $status === '1' ? 'Status: Active' : ($status === '0' ? 'Status: Inactive' : null),
        ]));

        return $this->renderMasterExport(
            $format,
            $query->get(),
            $this->resolveExportColumns($request, $columns),
            $reportTitle,
            $slug,
            $filters === [] ? null : implode('  |  ', $filters),
            'No ' . strtolower($reportTitle) . ' records to export'
        );
    }

    /** The S. No. and Status columns every COE master export carries. */
    protected function coeSnoColumn(): array
    {
        return ['heading' => 'S. No.', 'width' => '12%', 'align' => 'center', 'value' => fn ($row, int $index) => $index + 1];
    }

    protected function coeStatusColumn(): array
    {
        return ['heading' => 'Status', 'width' => '20%', 'align' => 'center',
            'value' => fn ($row) => ((int) $row->active_inactive === 1) ? 'Active' : 'Inactive'];
    }
}
