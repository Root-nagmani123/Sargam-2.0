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
            ->addColumn('employee_id', function ($row) {
                return $row->emp_id ? e($row->emp_id) : '—';
            })
            ->addColumn('employee_name', function ($row) {
                $name = trim(
                    ($row->first_name ?? '') . ' ' . ($row->middle_name ?? '') . ' ' . ($row->last_name ?? '')
                );

                return $name !== '' ? e($name) : '—';
            })
            ->addColumn('mobile_no', function ($row) {
                return $row->mobile ? e($row->mobile) : '—';
            })
            ->addColumn('email', function ($row) {
                return $row->email ? e($row->email) : '—';
            })
            ->addColumn('actions', function ($row) {
                $deleteUrl = route('member.destroy', encrypt($row->pk));
                $editBtn = '<a href="' . route('member.edit', $row->pk) . '" class="mem-action-btn text-primary" title="Edit">'
                    . '<span class="material-symbols-rounded">edit</span></a>';
                $viewBtn = '<a href="' . route('member.show', encrypt($row->pk)) . '" class="mem-action-btn text-primary" title="View">'
                    . '<span class="material-symbols-rounded">visibility</span></a>';
                $deleteBtn = '<form action="' . $deleteUrl . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this member?\')">'
                    . csrf_field()
                    . method_field('DELETE')
                    . '<button type="submit" class="mem-action-btn text-danger border-0 bg-transparent p-0" title="Delete">'
                    . '<span class="material-symbols-rounded">delete</span></button></form>';

                return '<div class="d-inline-flex align-items-center justify-content-center gap-2">'
                    . $editBtn . $viewBtn . $deleteBtn
                    . '</div>';
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
            ->rawColumns(['employee_name', 'employee_id', 'actions', 'mobile_no', 'email']);
    }

    
    public function query(EmployeeMaster $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('pk', 'desc');
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
                    ->parameters([
                        'order' => [],
                        'ordering' => true,
                        'searching' => true,
                        'lengthChange' => true,
                        'pageLength' => 10,
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
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('employee_id')->title('Employee ID')->orderable(false)->searchable(false),
            Column::make('employee_name')->title('Employee Name')->orderable(false)->searchable(true),
            Column::make('mobile_no')->title('Mobile Number')->orderable(false)->searchable(true),
            Column::make('email')->title('Email')->orderable(false)->searchable(true),
            Column::computed('actions')->title('Action')->addClass('text-center')->orderable(false)->searchable(false),
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
