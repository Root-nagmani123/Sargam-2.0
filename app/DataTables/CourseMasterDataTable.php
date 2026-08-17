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
            // Status: soft badge, display only. data-order lets a client-side sort
            // order by state (docs/new-design-index-page.md §3b).
            ->addColumn('status', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return '<span class="status-pill badge rounded-1 ' . ($isActive ? 'bg-success-subtle' : 'bg-danger-subtle') . '"'
                    . ' data-order="' . (int) $isActive . '">'
                    . ($isActive ? 'Active' : 'Inactive')
                    . '</span>';
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('programme.edit', ['id' => encrypt($row->pk)]);
                $viewUrl = route('programme.show', ['id' => encrypt($row->pk)]);
                $deleteUrl = route('programme.destroy', ['id' => encrypt($row->pk)]);
                $isActive = (int) $row->active_inactive === 1;
                $checked = $isActive ? 'checked' : '';
                $csrf = csrf_token();

                // The caption names the ACTION, not the state — the badge one column
                // over already shows the state (§3b).
                $toggleLabel = $isActive ? 'Deactivate' : 'Activate';

                // View · Edit · switch · Delete as equal-width icon-over-label stacks.
                // NOTE the deliberate absence of a .form-check.form-switch wrapper
                // around the switch — it would yank the input -2.375rem left of its
                // caption (§3b, trap 1).
                $deleteHtml = '<form action="'.$deleteUrl.'" method="POST" class="prog-act prog-act--del programme-delete-form">'
                        .'<input type="hidden" name="_token" value="'.$csrf.'">'
                        .'<input type="hidden" name="_method" value="DELETE">'
                        .'<button type="submit" class="prog-act__btn programme-delete-btn" aria-label="Delete course">'
                        .'<span class="prog-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        .'<span class="prog-act__label">Delete</span>'
                        .'</button>'
                        .'</form>';

                return '
                <div class="prog-act-group" role="group" aria-label="Row actions">
                    <a href="'.$viewUrl.'" class="prog-act prog-act--view" aria-label="View course" title="View">
                        <span class="prog-act__icon"><i class="bi bi-eye" aria-hidden="true"></i></span>
                        <span class="prog-act__label">View</span>
                    </a>
                    <a href="'.$editUrl.'" class="prog-act prog-act--edit" aria-label="Edit course" title="Edit">
                        <span class="prog-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                        <span class="prog-act__label">Edit</span>
                    </a>
                    <label class="prog-act prog-act--toggle" title="'.$toggleLabel.' course">
                        <span class="prog-act__icon">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                data-table="course_master" data-column="active_inactive" data-id="'.(int) $row->pk.'" '.$checked.'>
                        </span>
                        <span class="prog-act__label">'.$toggleLabel.'</span>
                    </label>
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
    /**
     * The grid's own scoping — role-visible courses, the Active/Archived pill and
     * the Course Name filter.
     *
     * Shared with CourseController::export() so a download can never show a
     * different set of rows than the screen it was started from.
     *
     * @param  QueryBuilder  $query
     */
    public static function applyListingScope($query, ?string $statusFilter = null, ?string $courseFilter = null)
    {
        $data_course_id = get_Role_by_course();
        if (! empty($data_course_id)) {
            $query->whereIn('pk', $data_course_id);
        }

        $currentDate = Carbon::now()->format('Y-m-d');

        if ($statusFilter === 'archive') {
            // Archived courses: end_date has already passed (expired courses)
            $query->where('end_date', '<', $currentDate);
        } else {
            // Active courses: end_date is today or in the future (current and upcoming)
            $query->where('end_date', '>=', $currentDate);
        }

        if (! empty($courseFilter)) {
            $query->where('pk', $courseFilter);
        }

        return $query;
    }

    public function query(CourseMaster $model): QueryBuilder
    {
        $query = $model->newQuery();

        self::applyListingScope($query, request('status_filter'), request('course_filter'));

        // Default newest-first, but ONLY when the user hasn't clicked a column
        // to sort — otherwise this base order would dominate (pk is unique, so a
        // requested secondary sort would never take visible effect). When an
        // order is requested, Yajra applies it on the unordered query.
        if (empty(request('order'))) {
            $query->orderBy('pk', 'desc');
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
            // Responsive would collapse the Actions column into a "+" detail row
            // once the icon-over-label stacks widened it; the programme-dt chrome
            // scrolls inside .table-responsive instead.
            ->responsive(false)
            ->parameters([
                'responsive' => false,
                'scrollX' => false,
                'autoWidth' => false,
                'ordering' => true,
                // Keep DataTables' native server-side ordering (see
                // datatable-global-ui.js): clicking a header re-queries and
                // sorts the FULL dataset, not just the visible page.
                'sargamServerOrder' => true,
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
            ]);
            // NOTE: no ->buttons() here. Button::make('reset') / ('reload') are not
            // real button types; they throw during init, and jQuery then skips every
            // later init.dt handler — which silently kills the global enhancer, so
            // the search box, pagination and item count never get relocated. The
            // grid's exports are the server-side Download/Print pair instead (§1).
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
            Column::make('course_name')->title('Course Name')->orderable(true)->searchable(true),
            Column::make('couse_short_name')->title('Short Name')->orderable(true)->searchable(true),
            Column::make('course_year')->title('Course Year')->orderable(true)->searchable(true)->addClass('text-center'),
            Column::make('start_year')->title('Start Date')->orderable(true)->searchable(false)->addClass('text-center'),
            Column::make('end_date')->title('End Date')->orderable(true)->searchable(false)->addClass('text-center'),
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