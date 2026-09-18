<?php

namespace App\DataTables\Master;

use App\Models\TermMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class TermMasterDataTable extends DataTable
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
            ->addColumn('term_name', fn($row) => $row->term_name ?? '-')
            ->addColumn('status', function ($row) {
                return (int) $row->active_inactive === 1
                    ? '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>'
                    : '<span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                $deleteUrl = route('master.term.destroy', ['id' => encrypt($row->pk)]);
                $isActive = (int) $row->active_inactive === 1;
                $checked = $isActive ? 'checked' : '';
                $csrf = csrf_token();

                $editBtn = '<button type="button" class="programme-action-btn tm-edit-btn" aria-label="Edit term"'
                        . ' data-id="' . encrypt($row->pk) . '"'
                        . ' data-name="' . e($row->term_name) . '"'
                        . ' data-status="' . (int) $row->active_inactive . '">'
                        . '<i class="bi bi-pencil" aria-hidden="true"></i>'
                        . '</button>';

                $deleteHtml = '<form action="' . $deleteUrl . '" method="POST" class="d-inline-flex m-0 tm-delete-form">'
                        . '<input type="hidden" name="_token" value="' . $csrf . '">'
                        . '<input type="hidden" name="_method" value="DELETE">'
                        . '<button type="submit" class="programme-action-btn programme-action-btn--danger" aria-label="Delete term">'
                        . '<i class="bi bi-trash3" aria-hidden="true"></i>'
                        . '</button>'
                        . '</form>';

                return '
                <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">
                    ' . $editBtn . '
                    <div class="form-check form-switch programme-action-switch mb-0">
                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                            data-table="term_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
                    </div>
                    ' . $deleteHtml . '
                </div>';
            })
            ->setRowId('pk')
            ->filterColumn('term_name', function ($query, $keyword) {
                $query->where('term_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['term_name', 'action', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\TermMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(TermMaster $model): QueryBuilder
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
                    ->setTableId('termmaster-table')
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
            Column::make('term_name')->title('Term')->orderable(false),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'TermMaster_' . date('YmdHis');
    }
}
