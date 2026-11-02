<?php

namespace App\DataTables\Master;

use App\Http\Controllers\Admin\Master\ClubSocietyRoleProgrammeMappingController;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

/**
 * One line per (course, club) pair, with that pair's roles collapsed into a
 * single comma-separated cell. Grouping happens in SQL via GROUP_CONCAT so
 * server-side pagination still counts pairs rather than role rows.
 */
class ClubSocietyRoleProgrammeMappingDataTable extends DataTable
{
    public function dataTable($query): QueryDataTable
    {
        return (new QueryDataTable($query))
            ->addIndexColumn()
            ->editColumn('course_name', fn ($row) => e($row->course_name ?? '-'))
            ->editColumn('club_society_name', fn ($row) => e($row->club_society_name ?? '-'))
            ->editColumn('role_names', fn ($row) => e($row->role_names ?: '-'))
            ->addColumn('action', function ($row) {
                // A row is a (course, club) PAIR, so the token carries both.
                $token = ClubSocietyRoleProgrammeMappingController::encodeGroupKey(
                    $row->course_master_pk,
                    $row->club_society_master_pk
                );

                $label = e($row->club_society_name) . ' — ' . e($row->course_name);

                $viewBtn = '<button type="button" class="cs-action-btn csrpm-view-btn" aria-label="View mapping"'
                    . ' data-id="' . $token . '" data-label="' . $label . '">'
                    . '<i class="bi bi-eye" aria-hidden="true"></i>'
                    . '<span>View</span>'
                    . '</button>';

                $editBtn = '<button type="button" class="cs-action-btn csrpm-edit-btn" aria-label="Edit mapping"'
                    . ' data-id="' . $token . '" data-label="' . $label . '">'
                    . '<i class="bi bi-pencil" aria-hidden="true"></i>'
                    . '<span>Edit</span>'
                    . '</button>';

                $deleteBtn = '<button type="button" class="cs-action-btn cs-action-btn--danger csrpm-delete-btn" aria-label="Delete mapping"'
                    . ' data-id="' . $token . '" data-label="' . $label . '">'
                    . '<i class="bi bi-trash3" aria-hidden="true"></i>'
                    . '<span>Delete</span>'
                    . '</button>';

                return '<div class="d-inline-flex align-items-start justify-content-center cs-action-group" role="group" aria-label="Row actions">'
                    . $viewBtn . $editBtn . $deleteBtn
                    . '</div>';
            })
            // The grid displays the short code, so search both it and the
            // full name — users type either.
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('c.course_name', 'like', "%{$keyword}%")
                      ->orWhere('c.couse_short_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('club_society_name', function ($query, $keyword) {
                $query->where('cs.club_society_name', 'like', "%{$keyword}%");
            })
            // Must be WHERE-compatible, not HAVING on the alias: Yajra ORs the
            // per-column filters for the global search box, and a HAVING clause
            // cannot join that OR group — searching a role would return nothing.
            ->filterColumn('role_names', function ($query, $keyword) {
                $query->whereExists(function ($sub) use ($keyword) {
                    $sub->selectRaw('1')
                        ->from('club_society_role_programme_mapping as m2')
                        ->join('club_society_role_master as r2', 'r2.pk', '=', 'm2.club_society_role_master_pk')
                        ->whereColumn('m2.course_master_pk', 'm.course_master_pk')
                        ->whereColumn('m2.club_society_master_pk', 'm.club_society_master_pk')
                        ->where('m2.active_inactive', 1)
                        ->where('r2.club_society_role_name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['action']);
    }

    /**
     * @return QueryBuilder
     */
    public function query()
    {
        return ClubSocietyRoleProgrammeMappingController::baseMappingQuery(
            request('status_filter', 'active'),
            request('course_master_pk')
        );
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('clubsocietyroleprogrammemapping-table')
            ->columns($this->getColumns())
            // Raw JS MUST go through minifiedAjax()'s $script argument — the
            // $data array quotes its values, which would send the literal source
            // text as the filter and silently match nothing.
            ->minifiedAjax('', <<<'JS'
            data.status_filter = window.csrpmStatusFilter ? window.csrpmStatusFilter() : 'active';
            data.course_master_pk = window.csrpmCourseFilter ? window.csrpmCourseFilter() : '';
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
        // No ->buttons([...]) on purpose — Button::make('reset')/('reload') are
        // not real button types and break the global enhancer's init.dt hook.
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false),
            Column::make('course_name')->title('Course Name')->orderable(false),
            Column::make('club_society_name')->title('Club/Society/Association')->orderable(false),
            Column::make('role_names')->title('Role')->orderable(false)->addClass('csrpm-role-cell'),
            Column::make('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'ClubSocietyRoleProgrammeMapping_' . date('YmdHis');
    }
}
