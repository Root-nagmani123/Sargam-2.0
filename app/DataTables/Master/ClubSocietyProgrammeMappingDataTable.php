<?php

namespace App\DataTables\Master;

use App\Http\Controllers\Admin\Master\ClubSocietyProgrammeMappingController;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

/**
 * One line per COURSE, with every club mapped to it collapsed into a single
 * comma-separated cell. The underlying table is a (course, club) pivot, so the
 * grouping happens in SQL via GROUP_CONCAT rather than in PHP — that keeps
 * server-side pagination honest.
 */
class ClubSocietyProgrammeMappingDataTable extends DataTable
{
    public function dataTable($query): QueryDataTable
    {
        return (new QueryDataTable($query))
            ->addIndexColumn()
            ->editColumn('course_name', fn ($row) => e($row->course_name ?? '-'))
            ->editColumn('club_names', fn ($row) => e($row->club_names ?: '-'))
            ->addColumn('action', function ($row) {
                $encryptedPk = encrypt($row->course_master_pk);

                $editBtn = '<button type="button" class="cs-action-btn cspm-edit-btn" aria-label="Edit mapping"'
                    . ' data-id="' . $encryptedPk . '"'
                    . ' data-course="' . e($row->course_name) . '">'
                    . '<i class="bi bi-pencil" aria-hidden="true"></i>'
                    . '<span>Edit</span>'
                    . '</button>';

                $deleteBtn = '<button type="button" class="cs-action-btn cs-action-btn--danger cspm-delete-btn" aria-label="Delete mapping"'
                    . ' data-id="' . $encryptedPk . '"'
                    . ' data-course="' . e($row->course_name) . '">'
                    . '<i class="bi bi-trash3" aria-hidden="true"></i>'
                    . '<span>Delete</span>'
                    . '</button>';

                return '<div class="d-inline-flex align-items-start justify-content-center cs-action-group" role="group" aria-label="Row actions">'
                    . $editBtn . $deleteBtn
                    . '</div>';
            })
            // Both visible columns are aggregates or joined values, so the
            // default WHERE-based search would target the wrong table. Search
            // the course name and the concatenated club list explicitly.
            // The grid displays the short code, so search both it and the
            // full name — users type either.
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('c.course_name', 'like', "%{$keyword}%")
                      ->orWhere('c.couse_short_name', 'like', "%{$keyword}%");
                });
            })
            // Must be a WHERE-compatible predicate, not HAVING on the alias:
            // Yajra ORs the per-column filters together for the global search
            // box, and a HAVING clause cannot join that OR group — searching a
            // club name would silently return nothing.
            ->filterColumn('club_names', function ($query, $keyword) {
                $query->whereExists(function ($sub) use ($keyword) {
                    $sub->selectRaw('1')
                        ->from('club_society_programme_mapping as m2')
                        ->join('club_society_master as cs2', 'cs2.pk', '=', 'm2.club_society_master_pk')
                        ->whereColumn('m2.course_master_pk', 'm.course_master_pk')
                        ->where('m2.active_inactive', 1)
                        ->where('cs2.club_society_name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['action']);
    }

    /**
     * @return QueryBuilder
     */
    public function query()
    {
        // Single source of truth for the grid, shared with the Excel export and
        // the print sheet so all three stay in step.
        return ClubSocietyProgrammeMappingController::baseMappingQuery(
            request('status_filter', 'active'),
            request('course_master_pk')
        );
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('clubsocietyprogrammemapping-table')
            ->columns($this->getColumns())
            // Keep the Active/Archived pill and the course filter on every draw,
            // paging included. This MUST go through minifiedAjax()'s $script
            // (raw JS) argument — the $data array quotes its values, which would
            // send the literal source text as the filter value and match nothing.
            ->minifiedAjax('', <<<'JS'
            data.status_filter = window.cspmStatusFilter ? window.cspmStatusFilter() : 'active';
            data.course_master_pk = window.cspmCourseFilter ? window.cspmCourseFilter() : '';
JS)
            ->selectStyleSingle()
            ->responsive(true)
            ->parameters([
                'responsive'   => true,
                'scrollX'      => false,
                'autoWidth'    => false,
                'ordering'     => false,
                'searching'    => true,
                'lengthChange' => true,
                'pageLength'   => 10,
                'lengthMenu'   => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                'order'        => [],
                'language'     => [
                    'search'            => '',
                    'searchPlaceholder' => 'Search',
                    'paginate'          => [
                        'previous' => '‹',
                        'next'     => '›',
                    ],
                    'lengthMenu'   => 'Showing _MENU_',
                    'info'         => 'of _TOTAL_ items',
                    'infoEmpty'    => 'of 0 items',
                    'infoFiltered' => 'of _MAX_ items',
                ],
            ]);
        // NOTE: no ->buttons([...]) on purpose — Button::make('reset')/('reload')
        // are not real button types and break the global enhancer's init.dt hook.
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false),
            Column::make('course_name')->title('Course Name')->orderable(false),
            Column::make('club_names')->title('Club/Society/Association')->orderable(false)->addClass('cspm-club-cell'),
            Column::make('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'ClubSocietyProgrammeMapping_' . date('YmdHis');
    }
}
