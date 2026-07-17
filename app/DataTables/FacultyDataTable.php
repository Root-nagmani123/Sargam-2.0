<?php

namespace App\DataTables;

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
use App\Models\FacultyMaster;

class FacultyDataTable extends DataTable
{
    private const LISTING_CACHE_EPOCH_KEY = 'faculty_dt_list_epoch';

    public static function bumpListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'FacultyDataTable');
    }

    /**
     * Server-side JSON for /faculty listing. .env: FACULTY_DATATABLE_CACHE_*.
     */
    public function ajax(): JsonResponse
    {
        return DataTableRedisCache::serveCachedAjax(
            $this->request(),
            'faculty_dt:v1:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'FACULTY_DATATABLE_CACHE_ENABLED',
                'seconds' => 'FACULTY_DATATABLE_CACHE_SECONDS',
            ],
            'FacultyDataTable',
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
            ->addColumn('faculty_code', function($row) {
                return $row->faculty_code ?? '';
            })
            ->addColumn('full_name', function($row) {
                return $row->full_name ?? '';
            })
            ->addColumn('faculty_email', function($row) {
                return $row->email_id ?? '';
            })
            ->addColumn('mobile_number', function($row) {
                return $row->mobile_no ?? '';
            })
            ->addColumn('action', function ($row) {
                $id = encrypt($row->pk);
                $csrf = csrf_token();

                $editUrl = route('faculty.edit', ['id' => $id]);
                $viewUrl = route('faculty.show', ['id' => $id]);
                $deleteUrl = route('faculty.destroy', ['id' => $id]);
                $isActive = $row->active_inactive == 1;
                $checked = $isActive ? 'checked' : '';
                // Active faculty stay undeletable, same rule as before.
                $disabledAttr = $isActive ? 'disabled' : '';
                $deleteTitle = $isActive ? 'Deactivate faculty first to enable deletion' : 'Delete';

                return '
                    <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">
                        <a href="'.$viewUrl.'" class="programme-action-btn" title="View" aria-label="View faculty">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </a>
                        <a href="'.$editUrl.'" class="programme-action-btn" title="Edit" aria-label="Edit faculty">
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </a>
                        <div class="form-check form-switch programme-action-switch mb-0">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                data-table="faculty_master" data-column="active_inactive" data-id="'.$row->pk.'" '.$checked.'>
                        </div>
                        <button type="button" class="programme-action-btn programme-action-btn--danger delete-faculty-btn"
                            data-url="'.$deleteUrl.'"
                            data-name="'.htmlspecialchars($row->full_name ?? '', ENT_QUOTES).'"
                            data-token="'.$csrf.'"
                            title="'.$deleteTitle.'" '.$disabledAttr.' aria-label="Delete faculty">
                            <i class="bi bi-trash3" aria-hidden="true"></i>
                        </button>
                    </div>
                ';
            })
            ->addColumn('status', function ($row) {
                return (int) $row->active_inactive === 1
                    ? '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>'
                    : '<span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>';
            })
            ->filterColumn('full_name', function ($query, $keyword) {
                $query->where('full_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('faculty_code', function ($query, $keyword) {
                $query->where('faculty_code', 'like', "%{$keyword}%");
            })
            ->filterColumn('faculty_email', function ($query, $keyword) {
                $query->where('email_id', 'like', "%{$keyword}%");
            })
            ->filterColumn('mobile_number', function ($query, $keyword) {
                $query->where('mobile_no', 'like', "%{$keyword}%");
            })

        ->addColumn('last_update', function($row) {
                return $row->last_update ? \Carbon\Carbon::parse($row->last_update)->format('d-m-Y H:i') : 'N/A';
            })
            ->addColumn('created_by', function($row) {
                return $row->createdByUser?->name ?? 'N/A';
            })

            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->where('full_name', 'like', "%{$searchValue}%")
                            ->orWhere('mobile_no', 'like', "%{$searchValue}%")
                            ->orWhere('faculty_code', 'like', "%{$searchValue}%")
                            ->orWhere('email_id', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->rawColumns(['action', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\FacultyMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(FacultyMaster $model): QueryBuilder
    {
        // return $model->newQuery();
        return $model->orderBy('pk', 'desc')->newQuery();

    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('faculty-table')
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
            Column::make('faculty_code')
                ->title('Faculty Code')
                ->searchable(true)
                ->orderable(false),
            Column::make('full_name')
                ->title('Faculty Name')
                ->searchable(true)
                ->orderable(false),
            Column::make('faculty_email')
                ->title('Faculty Email')
                ->searchable(true)
                ->orderable(false),
            Column::make('mobile_number')
                ->title('Mobile Number')
                ->searchable(true)
                ->orderable(false),

            Column::make('last_update')
                ->title('Modified Date')
                ->addClass('text-center')
                ->searchable(false)
                ->orderable(false),
            Column::make('created_by')
                ->title('Modified By')
                ->addClass('text-center')
                ->searchable(true)
                ->orderable(false),
            Column::computed('status')
                ->title('Status')
                ->exportable(false)
                ->printable(false)
                ->searchable(false)
                ->orderable(false)
                ->addClass('text-center'),
            Column::computed('action')
                ->title('Action')
                ->exportable(false)
                ->printable(false)
                ->searchable(false)
                ->orderable(false)
                ->addClass('text-center')
                ->width(140)
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'Faculty_' . date('YmdHis');
    }
}
