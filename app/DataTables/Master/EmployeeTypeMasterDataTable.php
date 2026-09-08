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
            ->addColumn('category_type_name', fn ($row) => $row->category_type_name ?? '-')
            // Status: soft badge, display only. data-order lets a client-side
            // sort order by state (docs/new-design-index-page.md 3b).
            ->addColumn('status', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return '<span class="status-pill badge rounded-1 ' . ($isActive ? 'bg-success-subtle' : 'bg-danger-subtle') . '"'
                    . ' data-order="' . (int) $isActive . '">'
                    . ($isActive ? 'Active' : 'Inactive')
                    . '</span>';
            })
            ->addColumn('action', function ($row) {
                $isActive = (int) $row->active_inactive === 1;
                $checked = $isActive ? 'checked' : '';
                // The caption names the ACTION, not the state - the badge one column
                // over already shows the state (3b).
                $toggleLabel = $isActive ? 'Deactivate' : 'Activate';

                // Edit opens the page's modal rather than navigating: the row carries
                // its own values, so no second request is needed to populate the form.
                // The switch is the shared .status-toggle: public/admin_assets/js/custom.js
                // confirms, POSTs to routes.toggleStatus and reloads the grid, so this
                // page writes no toggle JS of its own. NOTE the deliberate absence of a
                // .form-check.form-switch wrapper - it would yank the input -2.375rem
                // left of its caption (3b, trap 1).
                return '
                <div class="mst-act-group" role="group" aria-label="Row actions">
                    <button type="button" class="mst-act mst-act--edit etm-edit-btn" title="Edit"
                        data-id="' . e(encrypt($row->pk)) . '"
                        data-name="' . e((string) $row->category_type_name) . '">
                        <span class="mst-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                        <span class="mst-act__label">Edit</span>
                    </button>
                    <label class="mst-act mst-act--toggle" title="' . $toggleLabel . ' employee type">
                        <span class="mst-act__icon">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                data-table="employee_type_master" data-column="active_inactive"
                                data-id="' . (int) $row->pk . '" ' . $checked . '>
                        </span>
                        <span class="mst-act__label">' . $toggleLabel . '</span>
                    </label>
                </div>';
            })
            ->setRowId('pk')
            ->filterColumn('category_type_name', function ($query, $keyword) {
                $query->where('category_type_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action', 'status']);
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
            // No `dom` here on purpose: the page uses the shared programme-dt
            // chrome, and public/js/datatable-global-ui.js relocates the search
            // box / pagination / count into the #etmDtSearch and #etmDtFooter
            // slots declared in admin/master/employee_type/index.blade.php.
            ->selectStyleSingle()
            // Responsive would collapse the Actions column into a "+" detail row.
            ->responsive(false)
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'order' => [],
                'paging' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'pagingType' => 'full_numbers',
                'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                'language' => [
                    'search' => '',
                    'searchPlaceholder' => 'Search',
                    'emptyTable' => 'No employee types found.',
                    'zeroRecords' => 'No matching employee types found.',
                    'lengthMenu' => 'Showing _MENU_',
                    'info' => 'of _TOTAL_ items',
                    'infoEmpty' => 'of 0 items',
                    'infoFiltered' => 'of _MAX_ items',
                    'paginate' => [
                        'previous' => '&lsaquo;',
                        'next' => '&rsaquo;',
                    ],
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
        // Status before Action: the badge is the display, the switch beside Edit
        // is the control (docs/new-design-index-page.md §3b).
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('category_type_name')->title('Category Type Name')->orderable(false)->addClass('text-start'),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center')
                ->exportable(false)->printable(false),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center')
                ->exportable(false)->printable(false),
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
