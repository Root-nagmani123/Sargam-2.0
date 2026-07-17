<?php

namespace App\DataTables\Master;

use App\Models\ClassSessionMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ClassSessionMasterDataTable extends DataTable
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
            ->addColumn('shift_name', fn($row) => $row->shift_name ?? 'N/A')
            ->addColumn('start_time', fn($row) => $row->start_time ?? 'N/A')
            ->addColumn('end_time', fn($row) => $row->end_time ?? 'N/A')
            ->addColumn('status', function ($row) {
                $checked = (int) $row->active_inactive === 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block">
                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                        data-table="class_session_master" data-column="active_inactive"
                        data-id="' . $row->pk . '" ' . $checked . '>
                </div>';
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('master.class.session.edit', ['id' => encrypt($row->pk)]);
                $isActive = (int) $row->active_inactive === 1;
                $csrf = csrf_token();

                $editBtn = '<a href="' . $editUrl . '" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" aria-label="Edit class session">'
                        . '<span class="material-symbols-rounded fs-6" aria-hidden="true">edit</span>'
                        . '<span class="d-none d-md-inline">Edit</span>'
                        . '</a>';

                if ($isActive) {
                    $deleteBtn = '<button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"'
                            . ' disabled aria-disabled="true" title="Cannot delete active class session">'
                            . '<span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>'
                            . '<span class="d-none d-md-inline">Delete</span>'
                            . '</button>';
                } else {
                    $deleteUrl = route('master.class.session.delete', ['id' => encrypt($row->pk)]);
                    $deleteBtn = '<form action="' . $deleteUrl . '" method="POST" class="d-inline"'
                            . ' onsubmit="return confirm(\'Are you sure you want to delete this record?\');">'
                            . '<input type="hidden" name="_token" value="' . $csrf . '">'
                            . '<input type="hidden" name="_method" value="DELETE">'
                            . '<button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" aria-label="Delete class session">'
                            . '<span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>'
                            . '<span class="d-none d-md-inline">Delete</span>'
                            . '</button>'
                            . '</form>';
                }

                return '<div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Class session actions">'
                        . $editBtn . $deleteBtn . '</div>';
            })
            ->orderColumn('shift_name', function ($query, $order) {
                $query->orderBy('shift_name', $order);
            })
            ->orderColumn('start_time', function ($query, $order) {
                $query->orderBy('start_time', $order);
            })
            ->orderColumn('end_time', function ($query, $order) {
                $query->orderBy('end_time', $order);
            })
            ->filterColumn('shift_name', function ($query, $keyword) {
                $query->where('shift_name', 'like', "%{$keyword}%");
            })
            ->setRowId('pk')
            ->rawColumns(['status', 'action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\ClassSessionMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(ClassSessionMaster $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('classsessionmaster-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->selectStyleSingle()
                    ->responsive(true)
                    ->parameters([
                        'responsive'   => true,
                        'scrollX'      => false,
                        'autoWidth'    => false,
                        'ordering'     => true,
                        // Keep DataTables' native server-side ordering (see
                        // datatable-global-ui.js): clicking a header re-queries and
                        // sorts the FULL dataset, not just the visible page.
                        'sargamServerOrder' => true,
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
    }

    /**
     * Get the dataTable columns definition.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('shift_name')->title('Shift Name')->orderable(true),
            Column::make('start_time')->title('Start Time')->orderable(true),
            Column::make('end_time')->title('End Time')->orderable(true),
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
        return 'ClassSessionMaster_' . date('YmdHis');
    }
}
