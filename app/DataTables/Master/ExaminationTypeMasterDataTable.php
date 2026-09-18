<?php

namespace App\DataTables\Master;

use App\Models\ExaminationTypeMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ExaminationTypeMasterDataTable extends DataTable
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
            ->addColumn('exam_type_name', fn($row) => $row->exam_type_name ?? '-')
            ->addColumn('status', function ($row) {
                return (int) $row->active_inactive === 1
                    ? '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>'
                    : '<span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                $deleteUrl = route('master.examination.type.destroy', ['id' => encrypt($row->pk)]);
                $isActive = (int) $row->active_inactive === 1;
                $checked = $isActive ? 'checked' : '';
                $csrf = csrf_token();

                $editBtn = '<button type="button" class="programme-action-btn et-edit-btn" aria-label="Edit examination type"'
                        . ' data-id="' . encrypt($row->pk) . '"'
                        . ' data-name="' . e($row->exam_type_name) . '"'
                        . ' data-status="' . (int) $row->active_inactive . '">'
                        . '<i class="bi bi-pencil" aria-hidden="true"></i>'
                        . '</button>';

                $deleteHtml = '<form action="' . $deleteUrl . '" method="POST" class="d-inline-flex m-0 et-delete-form">'
                        . '<input type="hidden" name="_token" value="' . $csrf . '">'
                        . '<input type="hidden" name="_method" value="DELETE">'
                        . '<button type="submit" class="programme-action-btn programme-action-btn--danger" aria-label="Delete examination type">'
                        . '<i class="bi bi-trash3" aria-hidden="true"></i>'
                        . '</button>'
                        . '</form>';

                return '
                <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">
                    ' . $editBtn . '
                    <div class="form-check form-switch programme-action-switch mb-0">
                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                            data-table="examination_type_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
                    </div>
                    ' . $deleteHtml . '
                </div>';
            })
            ->setRowId('pk')
            ->filterColumn('exam_type_name', function ($query, $keyword) {
                $query->where('exam_type_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['exam_type_name', 'action', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\ExaminationTypeMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(ExaminationTypeMaster $model): QueryBuilder
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
                    ->setTableId('examinationtypemaster-table')
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
            Column::make('exam_type_name')->title('Examination Type')->orderable(false),
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
        return 'ExaminationTypeMaster_' . date('YmdHis');
    }
}
