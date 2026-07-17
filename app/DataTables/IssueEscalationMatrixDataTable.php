<?php

namespace App\DataTables;

use App\Models\IssueCategoryMaster;
use App\Support\DataTableRedisCache;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class IssueEscalationMatrixDataTable extends DataTable
{
    public const LISTING_CACHE_EPOCH_KEY = 'admin_issue_escalation_matrix_index_list_epoch';

    public static function bumpListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'IssueEscalationMatrixDataTable');
    }

    /**
     * Server-side JSON for the escalation matrix list. .env: ISSUE_ESCALATION_MATRIX_DATATABLE_CACHE_*.
     */
    public function ajax(): JsonResponse
    {
        return DataTableRedisCache::serveCachedAjax(
            $this->request(),
            'admin_issue_escalation_matrix_dt:v1:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'ISSUE_ESCALATION_MATRIX_DATATABLE_CACHE_ENABLED',
                'seconds' => 'ISSUE_ESCALATION_MATRIX_DATATABLE_CACHE_SECONDS',
            ],
            'IssueEscalationMatrixDataTable',
            fn () => parent::ajax()
        );
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $levelColumn = function (int $priority) {
            return function ($row) use ($priority) {
                $level = $row->employeeMappings->firstWhere('priority', $priority);
                if (! $level) {
                    return '<span class="text-muted">—</span>';
                }
                $name = e($level->employee->name ?? 'N/A');

                return $name . ' <span class="badge bg-info">' . (int) $level->days_notify . ' days</span>';
            };
        };

        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('level1', $levelColumn(1))
            ->addColumn('level2', $levelColumn(2))
            ->addColumn('level3', $levelColumn(3))
            ->addColumn('actions', function ($row) {
                $level1 = $row->employeeMappings->firstWhere('priority', 1);
                $level2 = $row->employeeMappings->firstWhere('priority', 2);
                $level3 = $row->employeeMappings->firstWhere('priority', 3);
                $categoryName = e(addslashes($row->issue_category));

                return '<button type="button" class="btn btn-sm btn-warning" onclick="editMatrix(' . $row->pk . ', \'' . $categoryName . '\', '
                    . ($level1->employee_master_pk ?? 'null') . ', ' . ($level1->days_notify ?? 0) . ', '
                    . ($level2->employee_master_pk ?? 'null') . ', ' . ($level2->days_notify ?? 0) . ', '
                    . ($level3->employee_master_pk ?? 'null') . ', ' . ($level3->days_notify ?? 0) . ')">
                        <iconify-icon icon="solar:pen-bold"></iconify-icon> Edit
                    </button>';
            })
            ->rawColumns(['level1', 'level2', 'level3', 'actions'])
            ->setRowId('pk');
    }

    public function query(IssueCategoryMaster $model): QueryBuilder
    {
        return $model->newQuery()
            ->active()
            ->with(['employeeMappings' => function ($q) {
                $q->orderBy('priority')->with('employee');
            }])
            ->orderBy('issue_category');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('escalationMatrixTable')
            ->addTableClass('table w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'order' => [],
                'pageLength' => 10,
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ categories',
                    'infoEmpty' => 'No categories',
                    'infoFiltered' => '(filtered from _MAX_ total)',
                    'zeroRecords' => 'No categories found. Add mapping to get started.',
                    'paginate' => [
                        'first' => 'First',
                        'last' => 'Last',
                        'next' => 'Next',
                        'previous' => 'Previous',
                    ],
                ],
                'dom' => '<"row align-items-center mb-3"<"col-12 col-md-4"l><"col-12 col-md-8"f>>rt<"row align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#')->addClass('text-center')->orderable(false)->searchable(false)->width('60px'),
            Column::make('issue_category')->title('Complaint Category'),
            Column::computed('level1')->title('Level 1 (Employee / Days)')->orderable(false)->searchable(false),
            Column::computed('level2')->title('Level 2 (Employee / Days)')->orderable(false)->searchable(false),
            Column::computed('level3')->title('Level 3 (Employee / Days)')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Actions')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'EscalationMatrix_' . date('YmdHis');
    }
}
