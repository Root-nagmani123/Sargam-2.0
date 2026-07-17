<?php

namespace App\DataTables;

use App\Models\IssueLogManagement;
use App\Support\DataTableRedisCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class IssueManagementCentcomDataTable extends DataTable
{
    public const LISTING_CACHE_EPOCH_KEY = 'admin_issue_management_centcom_list_epoch';

    public static function bumpListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'IssueManagementCentcomDataTable');
    }

    /**
     * Server-side JSON for the CENTCOM issues list. .env: ISSUE_MANAGEMENT_CENTCOM_DATATABLE_CACHE_*.
     */
    public function ajax(): JsonResponse
    {
        return DataTableRedisCache::serveCachedAjax(
            $this->request(),
            'admin_issue_management_centcom_dt:v1:',
            self::LISTING_CACHE_EPOCH_KEY,
            [
                'enabled' => 'ISSUE_MANAGEMENT_CENTCOM_DATATABLE_CACHE_ENABLED',
                'seconds' => 'ISSUE_MANAGEMENT_CENTCOM_DATATABLE_CACHE_SECONDS',
            ],
            'IssueManagementCentcomDataTable',
            fn () => parent::ajax(),
            [
                'user_id' => Auth::user()->user_id,
                'search_filter' => (string) $this->request()->input('search_filter', ''),
                'status_filter' => (string) $this->request()->input('status_filter', ''),
                'category_filter' => (string) $this->request()->input('category_filter', ''),
                'priority_filter' => (string) $this->request()->input('priority_filter', ''),
                'date_from_filter' => (string) $this->request()->input('date_from_filter', ''),
                'date_to_filter' => (string) $this->request()->input('date_to_filter', ''),
            ]
        );
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('date', function ($row) {
                return $row->created_date->format('d-m-Y H:i');
            })
            ->orderColumn('date', 'created_date $1')
            ->addColumn('category', function ($row) {
                return $row->category->issue_category ?? 'N/A';
            })
            ->addColumn('description', function ($row) {
                return Str::limit($row->description, 60);
            })
            ->addColumn('status', function ($row) {
                $variant = $row->issue_status == 2 ? 'success' : ($row->issue_status == 1 ? 'info' : ($row->issue_status == 6 ? 'warning' : 'secondary'));

                return '<span class="badge bg-' . $variant . '">' . e($row->status_label) . '</span>';
            })
            ->addColumn('actions', function ($row) {
                $viewUrl = route('admin.issue-management.show', $row->pk);

                return '<a href="' . e($viewUrl) . '" class="btn btn-sm btn-info" title="View Details">
                        <iconify-icon icon="solar:eye-bold"></iconify-icon>
                    </a>';
            })
            ->rawColumns(['status', 'actions'])
            ->setRowId('pk');
    }

    public function query(IssueLogManagement $model): QueryBuilder
    {
        $userId = Auth::user()->user_id;
        $ids = getEmployeeIdsForUser($userId);
        if (empty($ids)) {
            $ids = [$userId];
        }

        $query = $model->newQuery()
            ->with('category')
            ->where(function ($q) use ($ids) {
                $q->whereIn('employee_master_pk', $ids)
                    ->orWhereIn('assigned_to', $ids);
            });

        $search = request('search_filter');
        if (filled($search)) {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                if (is_numeric($term)) {
                    $q->orWhere('pk', $term);
                }
                $q->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('category', fn ($cq) => $cq->where('issue_category', 'like', "%{$term}%"))
                    ->orWhereHas('subCategoryMappings.subCategory', fn ($sq) => $sq->where('issue_sub_category', 'like', "%{$term}%"));
            });
        }

        $status = request('status_filter');
        if ($status !== null && $status !== '') {
            $query->where('issue_status', (int) $status);
        }

        $category = request('category_filter');
        if (filled($category)) {
            $query->where('issue_category_master_pk', (int) $category);
        }

        $priority = request('priority_filter');
        if (filled($priority)) {
            $query->where('issue_priority_master_pk', (int) $priority);
        }

        $dateFrom = request('date_from_filter');
        if (filled($dateFrom)) {
            $query->where('created_date', '>=', Carbon::parse($dateFrom)->startOfDay()->toDateTimeString());
        }

        $dateTo = request('date_to_filter');
        if (filled($dateTo)) {
            $query->where('created_date', '<=', Carbon::parse($dateTo)->endOfDay()->toDateTimeString());
        }

        return $query->orderByDesc('created_date');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('centcomIssuesTable')
            ->addTableClass('table w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1, 'desc')
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'pageLength' => 10,
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ issues',
                    'infoEmpty' => 'No issues',
                    'infoFiltered' => '(filtered from _MAX_ total)',
                    'zeroRecords' => 'No complaints assigned to you',
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
            Column::make('pk')->title('ID'),
            Column::computed('date')->title('Date'),
            Column::computed('category')->title('Category')->orderable(false),
            Column::computed('description')->title('Description')->orderable(false)->searchable(false),
            Column::computed('status')->title('Status')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Actions')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'CentcomIssues_' . date('YmdHis');
    }
}
