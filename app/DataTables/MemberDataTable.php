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
use App\Models\EmployeeMaster;

class MemberDataTable extends DataTable
{
    private const LISTING_CACHE_EPOCH_KEY = 'member_dt_list_epoch';

    /**
     * Bump after any change that should refresh the /member listing (create, edit steps, update, delete).
     */
    public static function bumpListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'MemberDataTable');
    }

    /**
     * Status pill above the grid (All / Active / Inactive). Whitelisted here so an
     * arbitrary ?status_filter= can neither reach the query nor fragment the cache.
     */
    public static function resolveStatusFilter(): string
    {
        $value = strtolower(trim((string) request('status_filter', '')));

        return in_array($value, ['active', 'inactive'], true) ? $value : '';
    }

    /**
     * The toolbar's Type / Group / Department dropdowns.
     *
     * Each is a foreign key, so an id is all that can ever be legal — anything
     * else is dropped rather than passed to the query. Returned as one array so
     * the grid, the export and the cache key cannot disagree about what is
     * applied.
     *
     * @return array{status:string, type:int|null, group:int|null, department:int|null}
     */
    public static function resolveFilters(): array
    {
        $id = function (string $key): ?int {
            $raw = trim((string) request($key, ''));

            return ($raw !== '' && ctype_digit($raw) && (int) $raw > 0) ? (int) $raw : null;
        };

        return [
            'status' => self::resolveStatusFilter(),
            'type' => $id('type_filter'),
            'group' => $id('group_filter'),
            'department' => $id('department_filter'),
        ];
    }

    /**
     * The grid's own scoping — the toolbar filters plus free-text search.
     *
     * Shared with MemberController::export() so a download can never show a
     * different set of rows than the screen it was started from.
     *
     * @param  QueryBuilder  $query
     * @param  array{status?:string, type?:int|null, group?:int|null, department?:int|null}  $filters
     */
    public static function applyListingFilters($query, array $filters, string $search = '')
    {
        $statusFilter = $filters['status'] ?? '';

        if ($statusFilter === 'active') {
            $query->where('status', 1);
        } elseif ($statusFilter === 'inactive') {
            // "Inactive" is everything that is not explicitly active, NULL included.
            $query->where(function ($sub) {
                $sub->where('status', '!=', 1)->orWhereNull('status');
            });
        }

        // employee_type_master.pk, employee_group_master.pk, department_master.pk.
        foreach (['type' => 'emp_type', 'group' => 'emp_group_pk', 'department' => 'department_master_pk'] as $key => $column) {
            if (! empty($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }

        $search = trim($search);
        if ($search !== '') {
            // Same columns the DataTable's global filter searches.
            $query->where(function ($sub) use ($search) {
                $sub->where('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Server-side JSON for /member listing. Tune via .env: MEMBER_DATATABLE_CACHE_*.
     * Cached HTML rows contain CSRF; tokens are refreshed in {@see DataTableRedisCache::refreshCsrfInDataTablePayload()}.
     */
    public function ajax(): JsonResponse
    {
        return DataTableRedisCache::serveCachedAjax(
            $this->request(),
            'member_dt:v1:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'MEMBER_DATATABLE_CACHE_ENABLED',
                'seconds' => 'MEMBER_DATATABLE_CACHE_SECONDS',
            ],
            'MemberDataTable',
            fn () => parent::ajax(),
            [
                // Not part of the standard DataTables fingerprint — without these
                // every filter combination would share one cached payload, and the
                // grid would answer a Department pick with the previous rows.
                'listing_filters' => self::resolveFilters(),
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
            ->addColumn('employee_name', function ($row) {
                $appellation = $row->appellation ? ($row->appellationMaster->appettation_name ?? null) : null;

                $parts = array_filter(
                    array_map(
                        fn ($part) => trim((string) $part),
                        [$appellation, $row->first_name, $row->middle_name, $row->last_name]
                    ),
                    fn ($part) => $part !== ''
                );

                return implode(' ', $parts);
            })
            ->addColumn('employee_id', fn ($row) => (string) $row->emp_id)
            ->addColumn('mobile_no', fn ($row) => (string) $row->mobile)
            ->addColumn('email', fn ($row) => (string) $row->email)
            ->addColumn('actions', function ($row) {
                $isActive = (int) $row->status === 1;
                $editUrl = route('member.edit', $row->pk);
                $viewUrl = route('member.show', encrypt($row->pk));
                $printUrl = route('member.print', encrypt($row->pk));
                $deleteUrl = route('member.destroy', encrypt($row->pk));
                $checked = $isActive ? 'checked' : '';
                $toggleLabel = $isActive ? 'Deactivate' : 'Activate';

                // MemberController@destroy refuses an active member, so the delete
                // action is rendered disabled rather than red-and-always-failing.
                $delete = $isActive
                    ? '<span class="mbr-act mbr-act--del is-disabled" aria-disabled="true"
                            title="Set this member to inactive before deleting">
                            <span class="mbr-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                            <span class="mbr-act__label">Delete</span>
                       </span>'
                    : '<button type="button" class="mbr-act mbr-act--del member-delete-btn"
                            data-delete-url="' . e($deleteUrl) . '" title="Delete member">
                            <span class="mbr-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                            <span class="mbr-act__label">Delete</span>
                       </button>';

                return '<div class="mbr-act-group" role="group" aria-label="Row actions">
                    <a href="' . e($editUrl) . '" class="mbr-act mbr-act--edit" title="Edit member">
                        <span class="mbr-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                        <span class="mbr-act__label">Edit</span>
                    </a>
                    <a href="' . e($viewUrl) . '" class="mbr-act mbr-act--view" title="View member">
                        <span class="mbr-act__icon"><i class="bi bi-eye" aria-hidden="true"></i></span>
                        <span class="mbr-act__label">View</span>
                    </a>
                    <a href="' . e($printUrl) . '" class="mbr-act mbr-act--print" target="_blank" rel="noopener"
                        title="Print this member\'s details">
                        <span class="mbr-act__icon"><i class="bi bi-printer" aria-hidden="true"></i></span>
                        <span class="mbr-act__label">Print</span>
                    </a>
                    <label class="mbr-act mbr-act--toggle" title="' . $toggleLabel . ' member">
                        <span class="mbr-act__icon">
                            <input class="form-check-input plain-status-toggle member-status-toggle" type="checkbox"
                                role="switch" data-id="' . (int) $row->pk . '" ' . $checked . '>
                        </span>
                        <span class="mbr-act__label">' . $toggleLabel . '</span>
                    </label>
                    ' . $delete . '
                </div>';
            })
            ->filterColumn('employee_name', function ($query, $keyword) {
                $query->where('first_name', 'like', "%{$keyword}%")
                      ->orWhere('middle_name', 'like', "%{$keyword}%")
                      ->orWhere('last_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('mobile_no', function ($query, $keyword) {
                $query->where('mobile', 'like', "%{$keyword}%");
            })
            ->filterColumn('email', function ($query, $keyword) {
                $query->where('email', 'like', "%{$keyword}%");
            })
            // Display only — the switch that changes it lives in the Actions stack.
            ->addColumn('status', function ($row) {
                $isActive = (int) $row->status === 1;

                return '<span class="status-pill badge rounded-1 ' . ($isActive ? 'bg-success-subtle' : 'bg-danger-subtle') . '">'
                    . ($isActive ? 'Active' : 'Inactive')
                    . '</span>';
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->where('first_name', 'like', "%{$searchValue}%")
                            ->orWhere('middle_name', 'like', "%{$searchValue}%")
                            ->orWhere('last_name', 'like', "%{$searchValue}%")
                            ->orWhere('mobile', 'like', "%{$searchValue}%")
                            ->orWhere('email', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->rawColumns(['actions', 'status']);
    }


    public function query(EmployeeMaster $model): QueryBuilder
    {
        $query = $model->newQuery()->with('appellationMaster');

        // Search is left to Yajra here (it owns the DataTables request); only the
        // toolbar filters are applied, through the same helper the exports use.
        self::applyListingFilters($query, self::resolveFilters());

        return $query->orderBy('pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('member-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    // ->dom('Bfrtip')
                    // ->orderBy(1)
                    ->selectStyleSingle()
                    // Responsive is loaded globally and would collapse the Actions
                    // column into a "+" detail row on a normal 1440px screen. The
                    // programme-dt chrome scrolls inside .table-responsive instead.
                    ->responsive(false)
                    // No `dom` here on purpose: this grid uses the shared programme-dt
                    // chrome, and public/js/datatable-global-ui.js relocates the search
                    // box / pagination / count into the #memberDtSearch and
                    // #memberDtFooter slots declared in admin/member/index.blade.php.
                    ->parameters([
                        'responsive' => false,
                        'autoWidth' => false,
                        'order' => [],
                        'ordering' => true,
                        'searching' => true,
                        'lengthChange' => true,
                        'pageLength' => 10,
                        'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                        'language' => [
                            'search' => '',
                            'searchPlaceholder' => 'Search',
                            'emptyTable' => 'No members found.',
                            'zeroRecords' => 'No matching members found.',
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
                        [
                            'text' => 'Reload',
                            'action' => 'function ( e, dt, node, config ) {
                                dt.ajax.reload();
                            }'
                        ]
                    ]);
    }


    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('employee_name')->title('Employee Name')->addClass('text-start')->orderable(false)->searchable(true),
            Column::make('employee_id')->title('Employee ID')->addClass('text-start')->orderable(false)->searchable(false),
            Column::make('mobile_no')->title('Mobile No')->addClass('text-start')->orderable(false)->searchable(true),
            Column::make('email')->title('Email')->addClass('text-start')->orderable(false)->searchable(true),
            Column::computed('status')->title('Status')->addClass('text-center')->orderable(false)->searchable(false)
                ->exportable(false)->printable(false),
            Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false)->searchable(false)
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
        return 'Member_' . date('YmdHis');
    }
}
