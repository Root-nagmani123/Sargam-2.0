<?php

namespace App\DataTables;

use App\Http\Controllers\Admin\IssueReportController;
use App\Models\IssueReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * The current user's own complaints.
 *
 * Mirrors IssueReportDataTable rather than extending it: that class declares
 * STATUS_LABELS and statusBadge() private, so a subclass could reuse neither.
 * The status map is taken from IssueReportController, which exposes it publicly,
 * so the labels stay defined in exactly one place.
 *
 * Two deliberate differences from the admin table: there is no reporter column
 * or user_credentials join (every row belongs to the viewer), and the row scope
 * fails closed rather than falling back to an unfiltered query.
 */
class MyComplaintDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('reference', fn ($row) => '#' . $row->id)
            ->addColumn('module', fn ($row) => e($row->module_name))
            ->addColumn('sub_module', fn ($row) => $row->sub_module ? e($row->sub_module) : '<span class="text-body-secondary">—</span>')
            ->addColumn('description', function ($row) {
                $full = (string) $row->description;

                return '<span title="' . e($full) . '">' . e(Str::limit($full, 70)) . '</span>';
            })
            ->addColumn('reported_on', fn ($row) => $row->created_at ? Carbon::parse($row->created_at)->format('d-m-Y h:i A') : '')
            ->addColumn('status', fn ($row) => $this->statusBadge((int) $row->status))
            ->addColumn('action', function ($row) {
                $viewUrl = route('admin.my-complaints.show', ['id' => $row->id]);

                return '<div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">'
                    . '<button type="button" class="programme-action-btn complaint-view-btn" data-url="' . $viewUrl . '" aria-label="View complaint #' . $row->id . '">'
                    . '<i class="bi bi-eye" aria-hidden="true"></i>'
                    . '</button>'
                    . '</div>';
            })
            // No uc.* terms here: this query has no user_credentials join, and
            // referencing them would fail with an unresolved-alias SQL error.
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');
                if (!empty($searchValue)) {
                    $query->where(function ($q) use ($searchValue) {
                        $like = "%{$searchValue}%";
                        $q->where('issue_reports.module_name', 'like', $like)
                            ->orWhere('issue_reports.sub_module', 'like', $like)
                            ->orWhere('issue_reports.description', 'like', $like);
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
        $query = $model->newQuery()->select('issue_reports.*');

        $identity = IssueReportController::reporterIdentity();

        // Fail closed. reported_by can legitimately hold 0 or '' for legacy
        // user_credentials rows whose user_id is empty, and matching on those
        // would show every such user each other's complaints.
        if (filled($identity)) {
            $query->where('issue_reports.reported_by', $identity);
        } else {
            $query->whereRaw('1 = 0');
        }

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
            ->setTableId('my-complaints-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->selectStyleSingle()
            ->responsive(true)
            ->parameters([
                'responsive'        => true,
                'scrollX'           => false,
                'autoWidth'         => false,
                'ordering'          => true,
                // Without this, datatable-global-ui.js disables server-side ordering
                // and substitutes a sorter that only reorders the visible page.
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
                    'emptyTable'        => 'You have not reported any issues yet.',
                    'zeroRecords'       => 'No complaints match your search.',
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('reference')->title('Ref')->searchable(false)->orderable(true)->addClass('text-center'),
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
        $label = IssueReportController::STATUS_LABELS[$status] ?? 'Open';
        $modifier = [
            IssueReport::STATUS_OPEN        => 'complaint-status-badge--open',
            IssueReport::STATUS_IN_PROGRESS => 'complaint-status-badge--progress',
            IssueReport::STATUS_RESOLVED    => 'complaint-status-badge--resolved',
            IssueReport::STATUS_CLOSED      => 'complaint-status-badge--closed',
        ][$status] ?? 'complaint-status-badge--open';

        return '<span class="badge rounded-1 complaint-status-badge ' . $modifier . '">' . e($label) . '</span>';
    }

    protected function filename(): string
    {
        return 'MyComplaints_' . date('YmdHis');
    }
}
