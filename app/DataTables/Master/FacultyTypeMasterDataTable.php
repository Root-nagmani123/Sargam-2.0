<?php

namespace App\DataTables\Master;

use App\Http\Controllers\Admin\Master\FacultyTypeMasterController;
use App\Models\FacultyTypeMaster;
use App\Support\DataTableRedisCache;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class FacultyTypeMasterDataTable extends DataTable
{
    /**
     * Server-side JSON. .env: FACULTY_TYPE_MASTER_LIST_CACHE_*; store via {@see \App\Support\RedisBackedCache} through {@see DataTableRedisCache}.
     *
     * Reuses the controller's epoch key so the existing invalidation points
     * (store, delete, and the global status toggle in UserController) all
     * still bust this cache.
     */
    public function ajax(): JsonResponse
    {
        return DataTableRedisCache::serveCachedAjax(
            $this->request(),
            'master_fac_type_dt:v1:',
            FacultyTypeMasterController::LIST_CACHE_EPOCH_KEY,
            [
                'enabled' => 'FACULTY_TYPE_MASTER_LIST_CACHE_ENABLED',
                'seconds' => 'FACULTY_TYPE_MASTER_LIST_CACHE_SECONDS',
            ],
            'FacultyTypeMasterDataTable',
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
            ->addColumn('faculty_type_name', fn($row) => $row->faculty_type_name ?? '-')
            ->addColumn('shot_faculty_type_name', fn($row) => $row->shot_faculty_type_name ?? '-')
            ->addColumn('status', function ($row) {
                return (int) $row->active_inactive === 1
                    ? '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>'
                    : '<span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                $isActive  = (int) $row->active_inactive === 1;
                $checked   = $isActive ? 'checked' : '';
                $deleteUrl = route('master.faculty.type.master.delete', ['id' => encrypt($row->pk)]);
                $csrf      = csrf_token();

                $editBtn = '<button type="button" class="programme-action-btn ft-edit-btn" aria-label="Edit faculty type"'
                        . ' data-id="' . encrypt($row->pk) . '"'
                        . ' data-name="' . e($row->faculty_type_name ?? '') . '"'
                        . ' data-short-name="' . e($row->shot_faculty_type_name ?? '') . '">'
                        . '<i class="bi bi-pencil" aria-hidden="true"></i>'
                        . '</button>';

                // Active records stay undeletable, same rule as the old list.
                $deleteHtml = '<form action="' . $deleteUrl . '" method="POST" class="d-inline-flex m-0 ft-delete-form">'
                        . '<input type="hidden" name="_token" value="' . $csrf . '">'
                        . '<input type="hidden" name="_method" value="DELETE">'
                        . '<button type="submit" class="programme-action-btn programme-action-btn--danger"'
                        . ' aria-label="Delete faculty type" ' . ($isActive ? 'disabled title="Cannot delete active Faculty Type"' : '') . '>'
                        . '<i class="bi bi-trash3" aria-hidden="true"></i>'
                        . '</button>'
                        . '</form>';

                return '
                <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">
                    ' . $editBtn . '
                    <div class="form-check form-switch programme-action-switch mb-0">
                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                            data-table="faculty_type_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
                    </div>
                    ' . $deleteHtml . '
                </div>';
            })
            ->setRowId('pk')
            ->filterColumn('faculty_type_name', function ($query, $keyword) {
                $query->where('faculty_type_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('shot_faculty_type_name', function ($query, $keyword) {
                $query->where('shot_faculty_type_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['faculty_type_name', 'shot_faculty_type_name', 'status', 'action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\FacultyTypeMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(FacultyTypeMaster $model): QueryBuilder
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
            ->setTableId('facultytypemaster-table')
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
            Column::make('faculty_type_name')->title('Faculty Type')->orderable(false),
            Column::make('shot_faculty_type_name')->title('Short Name')->orderable(false),
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
        return 'FacultyTypeMaster_' . date('YmdHis');
    }
}
