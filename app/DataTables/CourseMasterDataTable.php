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
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                $isActive = (int) $row->active_inactive === 1;
                $badge = $isActive
                    ? '<span class="programme-badge-active">Active</span>'
                    : '<span class="programme-badge-inactive">Inactive</span>';

                return $badge;
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('programme.edit', ['id' => encrypt($row->pk)]);
                $viewUrl = route('programme.show', ['id' => encrypt($row->pk)]);
                $deleteUrl = route('programme.destroy', ['id' => encrypt($row->pk)]);
                $isActive = $row->active_inactive == 1;
                $csrf = csrf_token();

                $statusToggle = '<a href="javascript:void(0)" class="programme-status-toggle-btn" data-table="course_master" data-column="active_inactive" data-id="'.$row->pk.'" data-status="'.($isActive ? '1' : '0').'" aria-label="'.($isActive ? 'Deactivate' : 'Activate').' course">'
                    .'<span class="material-icons material-symbols-rounded" style="font-size:18px; color:'.($isActive ? '#198754' : '#6c757d').';">'.($isActive ? 'toggle_on' : 'toggle_off').'</span></a>';

                $deleteBtn = '<a href="javascript:void(0)" class="programme-delete-btn" data-delete-url="'.$deleteUrl.'" data-csrf="'.$csrf.'" aria-label="Delete course">'
                    .'<span class="material-icons material-symbols-rounded" style="font-size:20px; color:#dc3545;">delete</span></a>';

                return '<div class="d-inline-flex align-items-center gap-3" role="group" aria-label="Row actions">'
                    .'<a href="'.$viewUrl.'" class="text-decoration-none" aria-label="View course">'
                    .'<span class="material-icons material-symbols-rounded" style="font-size:20px; color:#6c757d;">visibility</span></a>'
                    .'<a href="'.$editUrl.'" class="text-decoration-none" aria-label="Edit course">'
                    .'<span class="material-icons material-symbols-rounded" style="font-size:20px; color:#6c757d;">edit</span></a>'
                    .$statusToggle
                    .$deleteBtn
                    .'</div>';
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
        
        if ($statusFilter === 'archive') {
            // Archived courses: end_date has already passed
            $query->where('end_date', '<', $currentDate);
        } else {
            // Active courses (default): end_date is today or in the future
            $query->where('end_date', '>=', $currentDate);
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
            ->minifiedAjax('', null, ['status_filter' => 'active']) // Pass default status_filter on initial load
            // ->orderBy(1)
            ->selectStyleSingle()
            ->responsive(true)
            ->parameters([
                'responsive' => true,
                'scrollX' => true,
                'autoWidth' => false,
                'ordering' => false,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 200,
                'lengthMenu' => [[10, 25, 50, 100, 200, -1], [10, 25, 50, 100, 200, 'All']],
                'order' => [],
                'dom' => '<"d-flex flex-wrap justify-content-end gap-2 mb-2 programme-dt-export"B>rt<"d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 border-top"<"programme-dt-paginate-wrap d-flex align-items-center"p><"programme-dt-meta d-flex align-items-center gap-1 flex-wrap justify-content-end"li>>',
                'language' => [
                    'info' => 'of _TOTAL_ items',
                    'infoEmpty' => 'of 0 items',
                    'infoFiltered' => '(filtered from _MAX_)',
                    'lengthMenu' => 'Showing _MENU_',
                    'paginate' => [
                        'previous' => '&lsaquo;',
                        'next' => '&rsaquo;',
                    ],
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
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false),
            Column::make('course_name')->title('Course Name')->orderable(false)->searchable(true),
            Column::make('couse_short_name')->title('Short Name')->orderable(false)->searchable(true),
            Column::make('course_year')->title('Course Year')->orderable(false)->searchable(true),
            Column::make('start_year')->title('Start Date')->orderable(false)->searchable(false),
            Column::make('end_date')->title('End Date')->orderable(false)->searchable(false),
                Column::computed('status')->orderable(false)->searchable(false),
            Column::computed('action')->orderable(false)->searchable(false),
        
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