<?php

namespace App\DataTables\Master;

use App\Models\EmployeeTypeMaster;
use App\Support\DataTableRedisCache;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class EmployeeTypeMasterDataTable extends DataTable
{
    private const LISTING_CACHE_EPOCH_KEY = 'master_employee_type_dt_list_epoch';

    public static function bumpListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'EmployeeTypeMasterDataTable');
    }

    /**
     * Server-side JSON. .env: EMPLOYEE_TYPE_MASTER_DATATABLE_CACHE_*; store via {@see \App\Support\RedisBackedCache} through {@see DataTableRedisCache}.
     */
    public function ajax(): JsonResponse
    {
        return DataTableRedisCache::serveCachedAjax(
            $this->request(),
            'master_etm_dt:v1:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'EMPLOYEE_TYPE_MASTER_DATATABLE_CACHE_ENABLED',
                'seconds' => 'EMPLOYEE_TYPE_MASTER_DATATABLE_CACHE_SECONDS',
            ],
            'EmployeeTypeMasterDataTable',
            fn () => parent::ajax()
        );
    }

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
            ->addColumn('category_type_name', fn($row) => $row->category_type_name ?? '-')
            ->addColumn('status', function ($row) {
                return $row->active_inactive == 1
                    ? '<span class="etm-badge-active">Active</span>'
                    : '<span class="etm-badge-inactive">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('master.employee.type.edit', ['id' => encrypt($row->pk)]);
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                $editBtn = '<a href="' . $editUrl . '" class="etm-action-btn text-primary" title="Edit">'
                    . '<span class="material-symbols-rounded">edit</span></a>';
                $toggleBtn = '<div class="form-check form-switch d-inline-block mb-0 etm-status-switch">'
                    . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    . ' data-table="employee_type_master" data-column="active_inactive" data-id="' . $row->pk . '" '
                    . $checked . '></div>';

                return '<div class="d-inline-flex align-items-center justify-content-center gap-2">'
                    . $editBtn . $toggleBtn
                    . '</div>';
            })

            ->setRowId('pk')
            ->filterColumn('category_type_name', function ($query, $keyword) {
                $query->where('category_type_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['category_type_name', 'action', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\EmployeeTypeMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(EmployeeTypeMaster $model): QueryBuilder
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
            ->setTableId('employeetypemaster-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            //->dom('Bfrtip')
            // ->orderBy(1)
            ->selectStyleSingle()
            ->parameters([
                'responsive' => true,
                'scrollX' => true,
                'autoWidth' => false,
                'order' => [],
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
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false),
            Column::make('category_type_name')->title('Category Type Name')->orderable(false),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false),
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
        return 'EmployeeTypeMaster_' . date('YmdHis');
    }
}
