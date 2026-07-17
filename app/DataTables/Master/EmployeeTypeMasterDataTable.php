<?php

namespace App\DataTables\Master;

use App\Models\EmployeeTypeMaster;
use App\Support\DataTableRedisCache;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
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
            ->addColumn('category_type_name', fn($row) => e($row->category_type_name ?? '-'))
            ->addColumn('status', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return '<span class="badge rounded-1 programme-status-badge etm-status-badge '
                    . ($isActive ? 'programme-status-badge--active">Active' : 'programme-status-badge--inactive">Inactive')
                    . '</span>';
            })
            ->addColumn('action', function ($row) {
                $isActive = (int) $row->active_inactive === 1;
                $name = e($row->category_type_name ?? '');
                $encryptedPk = encrypt($row->pk);

                $editBtn = '<button type="button" class="etm-action-btn etm-action-edit etm-edit-btn"'
                    . ' aria-label="Edit employee type"'
                    . ' data-pk="' . e($encryptedPk) . '" data-name="' . $name . '">'
                    . '<i class="bi bi-pencil" aria-hidden="true"></i>'
                    . '</button>';

                $toggle = '<div class="form-check form-switch etm-action-switch-wrap mb-0">'
                    . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    . ' data-table="employee_type_master" data-column="active_inactive"'
                    . ' data-id="' . $row->pk . '" ' . ($isActive ? 'checked' : '') . '>'
                    . '</div>';

                // Active rows are delete-guarded, matching the other master screens.
                $deleteBtn = '<button type="button" class="etm-action-btn etm-action-delete etm-delete-btn"'
                    . ' aria-label="Delete employee type"'
                    . ' data-url="' . e(route('master.employee.type.delete', ['id' => $encryptedPk])) . '"'
                    . ' data-name="' . $name . '"'
                    . ($isActive ? ' disabled aria-disabled="true" title="Deactivate this employee type before deleting it"' : '')
                    . '>'
                    . '<i class="bi bi-trash3" aria-hidden="true"></i>'
                    . '</button>';

                return '<div class="etm-type-actions" role="group" aria-label="Employee type actions">'
                    . $editBtn . $toggle . $deleteBtn
                    . '</div>';
            })

            ->setRowId('pk')
            ->setRowClass('text-center')
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
            // No ->dom(): the global datatable-global-ui.js default renders the
            // length element the footer needs for "Showing [N] of M items".
            ->selectStyleSingle()
            ->parameters([
                'responsive' => true,
                'scrollX' => false,
                'autoWidth' => false,
                'order' => [],
                'paging' => true,
                'pagingType' => 'full_numbers',
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
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
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false)->addClass('etm-col-sno'),
            Column::make('category_type_name')->title('Category Type Name')->orderable(false)->addClass('etm-col-name'),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('etm-col-status'),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('etm-col-action'),
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
