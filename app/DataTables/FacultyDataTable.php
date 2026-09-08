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
            // Action: Edit · View · status switch · Delete, as equal-width
            // icon-over-label stacks (public/css/master-admin.css, `.mst-act`).
            ->addColumn('action', function ($row) {
                $id = encrypt($row->pk);
                $csrf = csrf_token();

                $editUrl = route('faculty.edit', ['id' => $id]);
                $viewUrl = route('faculty.show', ['id' => $id]);
                $deleteUrl = route('faculty.destroy', ['id' => $id]);
                $isActive = $row->active_inactive == 1;
                $name = htmlspecialchars((string) $row->full_name, ENT_QUOTES);

                $html = '<div class="mst-act-group" role="group" aria-label="Row actions">';

                $html .= '<a href="' . $editUrl . '" class="mst-act mst-act--edit" title="Edit ' . $name . '">'
                    . '<span class="mst-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
                    . '<span class="mst-act__label">Edit</span>'
                    . '</a>';

                $html .= '<a href="' . $viewUrl . '" class="mst-act mst-act--view" title="View ' . $name . '">'
                    . '<span class="mst-act__icon"><i class="bi bi-eye" aria-hidden="true"></i></span>'
                    . '<span class="mst-act__label">View</span>'
                    . '</a>';

                // No .form-check/.form-switch wrapper here — custom.css pulls the
                // input -2.375rem left inside one, which breaks the stacked layout.
                $html .= '<label class="mst-act mst-act--toggle">'
                    . '<span class="mst-act__icon">'
                    . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    . ' data-table="faculty_master" data-column="active_inactive"'
                    . ' data-id="' . $row->pk . '"' . ($isActive ? ' checked' : '')
                    . ' aria-label="' . ($isActive ? 'Deactivate' : 'Activate') . ' ' . $name . '">'
                    . '</span>'
                    . '<span class="mst-act__label">' . ($isActive ? 'Deactivate' : 'Activate') . '</span>'
                    . '</label>';

                // Deletion is refused while the record is active — show that
                // rather than a red icon that always fails.
                if ($isActive) {
                    $html .= '<span class="mst-act mst-act--del is-disabled" aria-disabled="true"'
                        . ' title="Deactivate faculty first to enable deletion">'
                        . '<span class="mst-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>'
                        . '<span class="mst-act__label">Delete</span>'
                        . '</span>';
                } else {
                    $html .= '<button type="button" class="mst-act mst-act--del delete-faculty-btn"'
                        . ' data-url="' . $deleteUrl . '"'
                        . ' data-name="' . $name . '"'
                        . ' data-token="' . $csrf . '"'
                        . ' title="Delete ' . $name . '">'
                        . '<span class="mst-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>'
                        . '<span class="mst-act__label">Delete</span>'
                        . '</button>';
                }

                return $html . '</div>';
            })
            // Status: display-only soft badge. The control lives in the action stack.
            ->addColumn('status', function ($row) {
                $isActive = $row->active_inactive == 1;

                return '<span class="status-pill badge rounded-1 ' . ($isActive ? 'bg-success-subtle' : 'bg-danger-subtle') . '">'
                    . ($isActive ? 'Active' : 'Inactive')
                    . '</span>';
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
        // `createdByUser` is eager-loaded because the Modified By column calls
        // $row->createdByUser?->name for every row — without this that is one
        // query per row on every page of the grid.
        //
        // The chain used to read `$model->orderBy(...)->newQuery()`, which only
        // worked by accident: Eloquent\Builder::__call forwards newQuery() to the
        // base query builder and then returns $this anyway, so the ordering
        // survived and a whole base builder was created and thrown away. Written
        // the way it was meant to be read.
        return $model->newQuery()
            ->with('createdByUser')
            ->orderBy('pk', 'desc');
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
                    //->dom('Bfrtip')
                    ->orderBy(1)
                    ->selectStyleSingle()
                    // No `dom` / `sargamDtUi` here on purpose: this table uses the
                    // shared programme-dt chrome, which public/js/datatable-global-ui.js
                    // relocates into the #facultyDtSearch / #facultyDtFooter slots
                    // declared in admin/faculty/index.blade.php.
                    //
                    // Deliberately NOT `sargamServerOrder => true`. Every visible column
                    // here is an addColumn() closure computed in PHP (full_name,
                    // faculty_code, faculty_email, mobile_number, last_update,
                    // created_by, status, action) — none of them exist in the result set
                    // for SQL to ORDER BY, which is why they are all orderable(false).
                    // Opting into server ordering would ask the database to sort by
                    // columns it cannot see. The global script instead gives the loaded
                    // page a client-side header sort, same as hostel_floor/hostel_building.
                    ->parameters([
                        'order' => [],
                        // footer.blade.php turns DataTables Responsive on globally. With
                        // nine columns it collapses from the right, which puts Status and
                        // the whole Action stack behind a "+" expander — the row controls
                        // must never be hidden. Off here; the .table-responsive wrapper in
                        // the panel scrolls instead, and the Columns modal trims the rest.
                        'responsive' => false,
                        'ordering' => true,
                        'searching' => true,
                        'lengthChange' => true,
                        'pageLength' => 10,
                        'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                        'language' => [
                            'search' => '',
                            'searchPlaceholder' => 'Search',
                            'lengthMenu' => 'Showing _MENU_',
                            'info' => 'of _TOTAL_ items',
                            'infoEmpty' => 'of 0 items',
                            'infoFiltered' => 'of _MAX_ items',
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
            Column::computed('DT_RowIndex')->title('S.No.'),
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
                ->addClass('text-nowrap'),
            Column::computed('action')
                ->title('Action')
                ->exportable(false)
                ->printable(false)
                ->addClass('text-nowrap')
                ->width(260)
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
