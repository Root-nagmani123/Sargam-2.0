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
                return (int) $row->active_inactive === 1
                    ? '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>'
                    : '<span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                $deleteUrl = route('master.hostel.floor.destroy', ['id' => encrypt($row->pk)]);
                $isActive  = (int) $row->active_inactive === 1;
                $checked   = $isActive ? 'checked' : '';
                $toggleLabel = $isActive ? 'Deactivate' : 'Activate';
                $csrf = csrf_token();

                // Edit · the status switch · Delete, each an icon over a caption
                // (docs/new-design-index-page.md §3b). The .hf-edit-btn hook and the
                // data-* payload the modal reads are unchanged.
                $editBtn = '<button type="button" class="oth-act oth-act--edit hf-edit-btn" title="Edit" aria-label="Edit floor"'
                        . ' data-id="' . encrypt($row->pk) . '"'
                        . ' data-name="' . e($row->floor_name) . '"'
                        . ' data-status="' . (int) $row->active_inactive . '">'
                        . '<span class="oth-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
                        . '<span class="oth-act__label">Edit</span>'
                        . '</button>';

                // No .form-check/.form-switch wrapper (§3b trap 1). custom.js binds
                // .status-toggle globally off these data-* attributes.
                $toggle = '<label class="oth-act oth-act--toggle" title="' . $toggleLabel . '">'
                        . '<span class="oth-act__icon">'
                        . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                        . ' data-table="floor_master" data-column="active_inactive"'
                        . ' data-id="' . (int) $row->pk . '" ' . $checked
                        . ' aria-label="' . $toggleLabel . ' floor">'
                        . '</span>'
                        . '<span class="oth-act__label">' . $toggleLabel . '</span>'
                        . '</label>';

                // An active floor cannot be deleted — a muted, inert stack says why,
                // instead of a live control that would fail.
                if ($isActive) {
                    $deleteHtml = '<span class="oth-act oth-act--del is-disabled" aria-disabled="true"'
                            . ' title="Deactivate this floor before deleting">'
                            . '<span class="oth-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                            . '<span class="oth-act__label">Delete</span>'
                            . '</span>';
                } else {
                    $deleteHtml = '<form action="' . $deleteUrl . '" method="POST" class="oth-act oth-act--del" onsubmit="return confirm(\'Are you sure you want to delete this floor?\')">'
                            . '<input type="hidden" name="_token" value="' . $csrf . '">'
                            . '<input type="hidden" name="_method" value="DELETE">'
                            . '<button type="submit" class="oth-act__btn" title="Delete" aria-label="Delete floor">'
                            . '<span class="oth-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                            . '<span class="oth-act__label">Delete</span>'
                            . '</button>'
                            . '</form>';
                }

                return '<div class="oth-act-group" role="group" aria-label="Floor actions">'
                        . $editBtn . $toggle . $deleteHtml
                        . '</div>';
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
