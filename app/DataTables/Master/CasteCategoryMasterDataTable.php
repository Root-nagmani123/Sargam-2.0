<?php

namespace App\DataTables\Master;

use App\Models\CasteCategoryMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CasteCategoryMasterDataTable extends DataTable
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
            // English and Hindi names render as a single "Caste Name" cell.
            ->editColumn('Seat_name', function ($row) {
                $english = $row->Seat_name ?? '-';
                return filled($row->Seat_name_hindi)
                    ? e($english) . ' - ' . e($row->Seat_name_hindi)
                    : e($english);
            })
            ->addColumn('status', function ($row) {
                return (int) $row->active_inactive === 1
                    ? '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>'
                    : '<span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                $checked = (int) $row->active_inactive === 1 ? 'checked' : '';

                $editBtn = '<button type="button" class="programme-action-btn cc-edit-btn" aria-label="Edit caste"'
                        . ' data-id="' . encrypt($row->pk) . '"'
                        . ' data-name="' . e($row->Seat_name) . '"'
                        . ' data-name-hindi="' . e($row->Seat_name_hindi ?? '') . '">'
                        . '<i class="bi bi-pencil" aria-hidden="true"></i>'
                        . '</button>';

                return '
                <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">
                    ' . $editBtn . '
                    <div class="form-check form-switch programme-action-switch mb-0">
                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                            data-table="caste_category_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
                    </div>
                </div>';
            })
            ->setRowId('pk')
            // The cell shows both languages, so searching it must match either.
            ->filterColumn('Seat_name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('Seat_name', 'like', "%{$keyword}%")
                      ->orWhere('Seat_name_hindi', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['Seat_name', 'status', 'action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\CasteCategoryMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(CasteCategoryMaster $model): QueryBuilder
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
            ->setTableId('castecategorymaster-table')
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
            ])
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload')
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
            Column::make('Seat_name')->title('Caste Name')->orderable(false),
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
        return 'CasteCategoryMaster_' . date('YmdHis');
    }
}
