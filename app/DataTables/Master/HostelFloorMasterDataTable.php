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
            ->addColumn('status', function ($row) {
                // Soft status badge — the canonical country/index pattern (new-design-index-page.md §3b).
                return (int) $row->active_inactive === 1
                    ? '<span class="status-pill badge bg-success-subtle">Active</span>'
                    : '<span class="status-pill badge bg-danger-subtle">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                // Icon-over-label actions (§3b): Edit (blue) · toggle · Delete (red, guarded).
                // `hf-edit-btn` is kept for the modal JS click handler.
                $isActive = (int) $row->active_inactive === 1;
                $checked  = $isActive ? 'checked' : '';

                $editBtn = '<button type="button" class="hf-act hf-act--edit hf-edit-btn" aria-label="Edit floor"'
                        . ' data-id="' . encrypt($row->pk) . '"'
                        . ' data-name="' . e($row->floor_name) . '"'
                        . ' data-status="' . (int) $row->active_inactive . '">'
                        . '<i class="bi bi-pencil-square" aria-hidden="true"></i><span>Edit</span>'
                        . '</button>';

                if ($isActive) {
                    $deleteHtml = '<span class="hf-act hf-act--del is-disabled" title="Set the floor inactive before deleting" aria-disabled="true">'
                        . '<i class="bi bi-trash3" aria-hidden="true"></i><span>Delete</span></span>';
                } else {
                    $deleteUrl = route('master.hostel.floor.destroy', ['id' => encrypt($row->pk)]);
                    $csrf = csrf_token();
                    $deleteHtml = '<form action="' . $deleteUrl . '" method="POST" class="d-inline m-0" onsubmit="return confirm(\'Are you sure you want to delete this floor?\')">'
                        . '<input type="hidden" name="_token" value="' . $csrf . '">'
                        . '<input type="hidden" name="_method" value="DELETE">'
                        . '<button type="submit" class="hf-act hf-act--del" aria-label="Delete floor">'
                        . '<i class="bi bi-trash3" aria-hidden="true"></i><span>Delete</span></button>'
                        . '</form>';
                }

                return '
                <div class="d-inline-flex align-items-center justify-content-center gap-3" role="group" aria-label="Row actions">
                    ' . $editBtn . '
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                            data-table="floor_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
                    </div>
                    ' . $deleteHtml . '
                </div>';
            })
            ->setRowId('pk')
            ->filterColumn('floor_name', function ($query, $keyword) {
                $query->where('floor_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['floor_name', 'action', 'status']);
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
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('floor_name')->title('Floor Name')->orderable(false),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center'),
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
