<?php

namespace App\DataTables;

use App\Models\IssueReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class IssueReportDataTable extends DataTable
{
    /**
     * Human labels for the tinyint status codes on issue_reports.status.
     */
    private const STATUS_LABELS = [
        IssueReport::STATUS_OPEN        => 'Open',
        IssueReport::STATUS_IN_PROGRESS => 'In Progress',
        IssueReport::STATUS_RESOLVED    => 'Resolved',
        IssueReport::STATUS_CLOSED      => 'Closed',
    ];

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('reference', fn ($row) => '#' . $row->id)
            ->addColumn('reporter', function ($row) {
                $name = trim(($row->reporter_first ?? '') . ' ' . ($row->reporter_last ?? ''));
                $name = $name !== '' ? $name : ($row->reporter_username ?? ('User #' . $row->reported_by));

                return e($name);
            })
            ->addColumn('module', fn ($row) => e($row->module_name))
            ->addColumn('sub_module', fn ($row) => $row->sub_module ? e($row->sub_module) : '<span class="text-body-secondary">—</span>')
            ->addColumn('description', function ($row) {
                $full = (string) $row->description;

                return '<span title="' . e($full) . '">' . e(Str::limit($full, 70)) . '</span>';
            })
            ->addColumn('reported_on', fn ($row) => $row->created_at ? Carbon::parse($row->created_at)->format('d-m-Y h:i A') : '')
            ->addColumn('status', function ($row) {
                return $this->statusBadge((int) $row->status);
            })
            ->addColumn('action', function ($row) {
                $viewUrl = route('admin.issue-reports.show', ['id' => $row->id]);

                return '<div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">'
                    . '<button type="button" class="programme-action-btn issue-view-btn" data-url="' . $viewUrl . '" aria-label="View issue #' . $row->id . '">'
                    . '<i class="bi bi-eye" aria-hidden="true"></i>'
                    . '</button>'
                    . '</div>';
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');
                if (!empty($searchValue)) {
                    $query->where(function ($q) use ($searchValue) {
                        $like = "%{$searchValue}%";
                        $q->where('issue_reports.module_name', 'like', $like)
                            ->orWhere('issue_reports.sub_module', 'like', $like)
                            ->orWhere('issue_reports.description', 'like', $like)
                            ->orWhere('uc.first_name', 'like', $like)
                            ->orWhere('uc.last_name', 'like', $like)
                            ->orWhere('uc.user_name', 'like', $like);
                    });
                }
            }, true)
            ->orderColumn('reference', 'issue_reports.id $1')
            ->orderColumn('module', 'issue_reports.module_name $1')
            ->orderColumn('reported_on', 'issue_reports.created_at $1')
            ->orderColumn('status', 'issue_reports.status $1')
            ->rawColumns(['sub_module', 'description', 'status', 'action'])
            ->setRowId('id');
    }

    public function query(IssueReport $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->leftJoin('user_credentials as uc', 'uc.user_id', '=', 'issue_reports.reported_by')
            ->select([
                'issue_reports.*',
                'uc.first_name as reporter_first',
                'uc.last_name as reporter_last',
                'uc.user_name as reporter_username',
            ]);

        $statusFilter = request('status_filter');
        if ($statusFilter !== null && $statusFilter !== '' && $statusFilter !== 'all') {
            $query->where('issue_reports.status', (int) $statusFilter);
        }

        // Newest first by default; a clicked column header overrides this.
        if (empty(request('order'))) {
            $query->orderBy('issue_reports.id', 'desc');
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('issue-reports-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->selectStyleSingle()
            ->responsive(true)
            ->parameters([
                'responsive'        => true,
                'scrollX'           => false,
                'autoWidth'         => false,
                'ordering'          => true,
                'sargamServerOrder' => true,
                'searching'         => true,
                'lengthChange'      => true,
                'pageLength'        => 10,
                'lengthMenu'        => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                'order'             => [],
                'language'          => [
                    'search'            => '',
                    'searchPlaceholder' => 'Search',
                    'paginate'          => ['previous' => '‹', 'next' => '›'],
                    'lengthMenu'        => 'Showing _MENU_',
                    'info'              => 'of _TOTAL_ items',
                    'infoEmpty'         => 'of 0 items',
                    'infoFiltered'      => 'of _MAX_ items',
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

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('reference')->title('Ref')->searchable(false)->orderable(true)->addClass('text-center'),
            Column::make('reporter')->title('Reported By')->orderable(false)->searchable(true),
            Column::make('module')->title('Module')->orderable(true)->searchable(true),
            Column::make('sub_module')->title('Sub-Module')->orderable(false)->searchable(true),
            Column::make('description')->title('Description')->orderable(false)->searchable(true),
            Column::make('reported_on')->title('Reported On')->orderable(true)->searchable(false)->addClass('text-center'),
            Column::computed('status')->title('Status')->orderable(true)->searchable(false)->addClass('text-center'),
            Column::computed('action')->title('Action')->orderable(false)->searchable(false)->addClass('text-center'),
        ];
    }

    private function statusBadge(int $status): string
    {
        $label = self::STATUS_LABELS[$status] ?? 'Open';
        $modifier = [
            IssueReport::STATUS_OPEN        => 'issue-status-badge--open',
            IssueReport::STATUS_IN_PROGRESS => 'issue-status-badge--progress',
            IssueReport::STATUS_RESOLVED    => 'issue-status-badge--resolved',
            IssueReport::STATUS_CLOSED      => 'issue-status-badge--closed',
        ][$status] ?? 'issue-status-badge--open';

        return '<span class="badge rounded-1 issue-status-badge ' . $modifier . '">' . e($label) . '</span>';
    }

    protected function filename(): string
    {
        return 'IssueReports_' . date('YmdHis');
    }
}
