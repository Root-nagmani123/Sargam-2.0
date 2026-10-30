<?php

namespace App\DataTables\Master;

use App\Models\FloorMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class HostelFloorMasterDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     * @return \Yajra\DataTables\EloquentDataTable
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('floor_name', fn($row) => $row->floor_name ?? '-')
            // Status: display-only soft badge. The switch lives in the Action stack.
            ->addColumn('status', fn ($row) => view('admin.master.partials.grid-status', [
                'active' => (int) $row->active_inactive === 1,
            ])->render())
            // Action: Edit · status switch · Delete (docs/new-design-index-page.md §3b).
            // Edit stays a .hf-edit-btn button carrying the row's data-* so the
            // page's Add/Edit modal keeps prefilling from it.
            ->addColumn('action', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return view('admin.master.partials.grid-actions', [
                    'name'   => $row->floor_name ?? '',
                    'edit'   => [
                        'class' => 'hf-edit-btn',
                        'attrs' => [
                            'data-id'     => encrypt($row->pk),
                            'data-name'   => $row->floor_name,
                            'data-status' => (int) $row->active_inactive,
                        ],
                    ],
                    'toggle' => [
                        'active' => $isActive,
                        'table'  => 'floor_master',
                        'column' => 'active_inactive',
                        'id'     => $row->pk,
                    ],
                    // Active floors were never deletable from this grid — keep that rule.
                    'delete' => $isActive
                        ? ['disabled' => true, 'reason' => 'Active floors cannot be deleted. Deactivate it first.']
                        : ['action' => route('master.hostel.floor.destroy', ['id' => encrypt($row->pk)])],
                ])->render();
            })
            ->setRowId('pk')
            ->filterColumn('floor_name', function ($query, $keyword) {
                $query->where('floor_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action', 'status']); // floor_name is plain text — escaped
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\HostelFloorMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(FloorMaster $model): QueryBuilder
    {
        return $model->newQuery()->latest('pk');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('hostelfloormaster-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
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
                            'search'           => '',
                            'searchPlaceholder' => 'Search',
                            'paginate'         => [
                                'previous' => '‹',
                                'next'     => '›',
                            ],
                            'lengthMenu'   => 'Showing _MENU_',
                            'info'         => 'of _TOTAL_ items',
                            'infoEmpty'    => 'of 0 items',
                            'infoFiltered' => 'of _MAX_ items',
                        ],
                    ])
                    ->buttons([
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload'),
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false)->addClass('text-nowrap')->width('5.5rem'),
            Column::make('floor_name')->title('Floor Name')->orderable(false),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-nowrap')->width('8rem'),
            Column::make('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-nowrap')->width('13rem'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'HostelFloorMaster_' . date('YmdHis');
    }
}
