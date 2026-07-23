<?php

namespace App\DataTables;

use App\Models\IssueReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class UserIssueReportDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('date', fn ($row) => $row->created_at
                ? Carbon::parse($row->created_at)->format('d-m-Y')
                : '')
            ->addColumn('dept_name', fn ($row) => e($row->module_name))
            ->addColumn('sub_module_name', fn ($row) => $row->sub_module
                ? e($row->sub_module)
                : '<span class="text-body-secondary">—</span>')
            ->addColumn('description', function ($row) {
                $full = (string) $row->description;
                return '<span title="' . e($full) . '">' . e(Str::limit($full, 70)) . '</span>';
            })
            ->addColumn('attachment', function ($row) {
                if ($row->attachment) {
                    $url = url('storage/' . $row->attachment);
                    return '<a href="' . e($url) . '" target="_blank" rel="noopener" class="attachment-view">View</a>';
                }
                return '<span class="text-body-secondary">—</span>';
            })
            ->addColumn('status', fn ($row) => $this->statusBadge((int) $row->status))
            ->filter(function ($query) {
                $search = request()->input('search.value');
                if (!empty($search)) {
                    $like = "%{$search}%";
                    $query->where(function ($q) use ($like) {
                        $q->where('issue_reports.module_name', 'like', $like)
                            ->orWhere('issue_reports.sub_module', 'like', $like)
                            ->orWhere('issue_reports.description', 'like', $like);
                    });
                }
            }, true)
            ->orderColumn('date', 'issue_reports.created_at $1')
            ->orderColumn('dept_name', 'issue_reports.module_name $1')
            ->orderColumn('sub_module_name', 'issue_reports.sub_module $1')
            ->orderColumn('description', 'issue_reports.description $1')
            ->orderColumn('status', 'issue_reports.status $1')
            ->rawColumns(['sub_module_name', 'description', 'attachment', 'status'])
            ->setRowId('id');
    }

    public function query(IssueReport $model): QueryBuilder
    {
        $userId = Auth::user()->user_id ?? Auth::id();

        $query = $model->newQuery()
            ->where('issue_reports.reported_by', $userId)
            ->select(['issue_reports.*']);

        $statusFilter = request('status_filter');
        if ($statusFilter === 'active') {
            $query->whereIn('issue_reports.status', [IssueReport::STATUS_OPEN, IssueReport::STATUS_IN_PROGRESS]);
        } elseif ($statusFilter === 'fixed') {
            $query->whereIn('issue_reports.status', [IssueReport::STATUS_RESOLVED, IssueReport::STATUS_CLOSED]);
        }

        $dept = request('dept_filter');
        if ($dept !== null && $dept !== '') {
            $query->where('issue_reports.module_name', $dept);
        }

        $sub = request('submodule_filter');
        if ($sub !== null && $sub !== '') {
            $query->where('issue_reports.sub_module', $sub);
        }

        $from = request('date_from');
        if ($from !== null && $from !== '') {
            $query->whereDate('issue_reports.created_at', '>=', $from);
        }

        $to = request('date_to');
        if ($to !== null && $to !== '') {
            $query->whereDate('issue_reports.created_at', '<=', $to);
        }

        if (empty(request('order'))) {
            $query->orderBy('issue_reports.id', 'desc');
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('my-issue-reports-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->responsive(true)
            ->parameters([
                'responsive'   => true,
                'scrollX'      => false,
                'autoWidth'    => false,
                'ordering'     => true,
                'searching'    => true,
                'lengthChange' => true,
                'pageLength'   => 10,
                'lengthMenu'   => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                'order'        => [],
                'sargamDtUi'   => false,
                'dom'          => "<'d-none sargam-dt-hidden-controls'lfip>rt",
                'language'     => [
                    'search'            => '',
                    'searchPlaceholder' => 'Search…',
                    'paginate'          => ['previous' => '‹', 'next' => '›'],
                    'lengthMenu'        => 'Showing _MENU_',
                    'info'              => 'of _TOTAL_ items',
                    'infoEmpty'         => 'of 0 items',
                    'infoFiltered'      => 'of _MAX_ items',
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false)->width(60)->addClass('text-center'),
            Column::computed('date')->title('Date')->searchable(false)->orderable(true)->width(110)->addClass('text-center'),
            Column::make('dept_name')->title('Department Name')->orderable(true)->searchable(true),
            Column::make('sub_module_name')->title('Sub-Module Name')->orderable(true)->searchable(true),
            Column::make('description')->title('Issue Description')->orderable(false)->searchable(true),
            Column::computed('attachment')->title('Attachment')->orderable(false)->searchable(false)->width(100)->addClass('text-center'),
            Column::computed('status')->title('Status')->orderable(true)->searchable(false)->width(120)->addClass('text-center'),
        ];
    }

    private function statusBadge(int $status): string
    {
        if (in_array($status, [IssueReport::STATUS_OPEN, IssueReport::STATUS_IN_PROGRESS])) {
            return '<span class="badge rounded-1 issue-status-badge issue-status-badge--active">Active</span>';
        }
        return '<span class="badge rounded-1 issue-status-badge issue-status-badge--fixed">Fixed Issue</span>';
    }

    protected function filename(): string
    {
        return 'MyIssueReports_' . date('YmdHis');
    }
}
