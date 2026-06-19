<?php

namespace App\DataTables;

use App\Models\CourseMaster;
use App\Support\DataTableRedisCache;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class CourseMasterDataTable extends DataTable
{
    public const LISTING_CACHE_EPOCH_KEY = 'programme_course_master_dt_list_epoch';

    public static function bumpListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'CourseMasterDataTable');
    }

    /**
     * Server-side JSON for /programme listing. .env: PROGRAMME_DATATABLE_CACHE_*.
     * Extra fingerprint: programme index sends status_filter and course_filter on each XHR.
     */
    public function ajax(): JsonResponse
    {
        return DataTableRedisCache::serveCachedAjax(
            $this->request(),
            'programme_course_master_dt:v1:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'PROGRAMME_DATATABLE_CACHE_ENABLED',
                'seconds' => 'PROGRAMME_DATATABLE_CACHE_SECONDS',
            ],
            'CourseMasterDataTable',
            fn () => parent::ajax(),
            [
                'status_filter' => (string) $this->request()->input('status_filter', ''),
                'course_filter' => (string) $this->request()->input('course_filter', ''),
            ]
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
            ->addColumn('course_name', fn($row) => $row->course_name)
            ->addColumn('couse_short_name', fn($row) => $row->couse_short_name)
            ->addColumn('course_year', fn($row) => $row->course_year)
            ->addColumn('start_year', function ($row) {
                return $row->start_year ? Carbon::parse($row->start_year)->format('d-m-Y') : '';
            })
            ->addColumn('end_date', function ($row) {
                return $row->end_date ? Carbon::parse($row->end_date)->format('d-m-Y') : '';
            })
            ->addColumn('status', function ($row) {
                if ((int) $row->active_inactive === 1) {
                    return '<span class="badge rounded-pill programme-status-badge programme-status-badge--active">Active</span>';
                }

                return '<span class="badge rounded-pill programme-status-badge programme-status-badge--inactive">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('programme.edit', ['id' => encrypt($row->pk)]);
                $viewUrl = route('programme.show', ['id' => encrypt($row->pk)]);
                $deleteUrl = route('programme.destroy', ['id' => encrypt($row->pk)]);
                $isActive = (int) $row->active_inactive === 1;
                $checked = $isActive ? 'checked' : '';
                $csrf = csrf_token();

                $deleteHtml = '<form action="'.$deleteUrl.'" method="POST" class="d-inline-flex m-0 programme-delete-form">'
                        .'<input type="hidden" name="_token" value="'.$csrf.'">'
                        .'<input type="hidden" name="_method" value="DELETE">'
                        .'<button type="submit" class="programme-action-btn programme-action-btn--danger programme-delete-btn" aria-label="Delete course">'
                        .'<i class="bi bi-trash3" aria-hidden="true"></i>'
                        .'</button>'
                        .'</form>';

                return '
                <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">
                    <a href="'.$viewUrl.'" class="programme-action-btn" aria-label="View course"><i class="bi bi-eye" aria-hidden="true"></i></a>
                    <a href="'.$editUrl.'" class="programme-action-btn" aria-label="Edit course"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                    <div class="form-check form-switch programme-action-switch mb-0">
                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                            data-table="course_master" data-column="active_inactive" data-id="'.$row->pk.'" '.$checked.'>
                    </div>
                    '.$deleteHtml.'
                </div>';
            })
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->where('course_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('couse_short_name', function ($query, $keyword) {
                $query->where('couse_short_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('course_year', function ($query, $keyword) {
                $query->where('course_year', 'like', "%{$keyword}%");
            })
            ->filterColumn('start_year', function ($query, $keyword) {
                $query->where('start_year', 'like', "%{$keyword}%");
            })
            ->filterColumn('end_date', function ($query, $keyword) {
                $query->where('end_date', 'like', "%{$keyword}%");
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->where('course_name', 'like', "%{$searchValue}%")
                            ->orWhere('couse_short_name', 'like', "%{$searchValue}%")
                            ->orWhere('course_year', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->rawColumns(['action', 'status'])
            ->setRowId('pk');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\CourseMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(CourseMaster $model): QueryBuilder
    {
        $data_course_id =  get_Role_by_course();
         if(!empty($data_course_id))
        {
            $model = $model->whereIn('pk',$data_course_id);
        }
        $query = $model->orderBy('pk', 'desc')->newQuery();
        
        // Apply status filter if provided
        $statusFilter = request('status_filter');
        $courseFilter = request('course_filter');
        $currentDate = Carbon::now()->format('Y-m-d');
        
        if ($statusFilter === 'active' || !$statusFilter) {
            // Active courses: end_date is today or in the future (current and upcoming courses)
            $query->where('end_date', '>=', $currentDate);
        } elseif ($statusFilter === 'archive') {
            // Archived courses: end_date has already passed (expired courses)
            $query->where('end_date', '<', $currentDate);
        }
        
        // Apply course filter if provided
        if (!empty($courseFilter)) {
            $query->where('pk', $courseFilter);
        }
        
        return $query;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('coursemaster-table')
            ->columns($this->getColumns())
            ->minifiedAjax() // This will use the current route for ajax
            // ->orderBy(1)
            ->selectStyleSingle()
            ->responsive(true)
            ->parameters([
                'responsive' => true,
                'scrollX' => false,
                'autoWidth' => false,
                'ordering' => false,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                'order' => [],
                'language' => [
                    'search' => '',
                    'searchPlaceholder' => 'Search',
                    'paginate' => [
                        'previous' => '‹',
                        'next' => '›',
                    ],
                    'lengthMenu' => 'Showing _MENU_',
                    'info' => 'of _TOTAL_ items',
                    'infoEmpty' => 'of 0 items',
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
            Column::make('course_name')->title('Course Name')->orderable(false)->searchable(true),
            Column::make('couse_short_name')->title('Short Name')->orderable(false)->searchable(true),
            Column::make('course_year')->title('Course Year')->orderable(false)->searchable(true)->addClass('text-center'),
            Column::make('start_year')->title('Start Date')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::make('end_date')->title('End Date')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::computed('status')->title('Status')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::computed('action')->title('Action')->orderable(false)->searchable(false)->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'CourseMaster_' . date('YmdHis');
    }
}