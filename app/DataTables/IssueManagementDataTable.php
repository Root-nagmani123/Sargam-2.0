<?php

namespace App\DataTables;

use App\Models\IssueLogManagement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class IssueManagementDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('created_date', function ($row) {
                return $row->created_date ? $row->created_date->format('d M Y') : '—';
            })
            ->addColumn('category', function ($row) {
                return $row->category->issue_category ?? '—';
            })
            ->addColumn('description', function ($row) {
                return \Illuminate\Support\Str::limit($row->description, 60);
            })
            ->addColumn('priority', function ($row) {
                $p = $row->priority->priority ?? 'N/A';
                $priorityClass = $p == 'High' ? 'danger' : ($p == 'Medium' ? 'warning' : 'info');
                $textClass = $priorityClass == 'warning' ? 'text-dark' : '';
                return '<span class="badge badge-pill bg-' . $priorityClass . ' ' . $textClass . '">' . e($p) . '</span>';
            })
            ->addColumn('status', function ($row) {
                $s = (int) $row->issue_status;
                $statusClass = $s == 2 ? 'success' : ($s == 1 ? 'info' : ($s == 6 ? 'warning' : 'secondary'));
                $textClass = $statusClass == 'warning' ? 'text-dark' : '';
                return '<span class="badge badge-pill bg-' . $statusClass . ' ' . $textClass . '">' . e($row->status_label) . '</span>';
            })
            ->addColumn('actions', function ($row) {
                $showUrl = route('admin.issue-management.show', $row->pk);
                $editUrl = route('admin.issue-management.edit', $row->pk);
                $canEdit = $row->issue_logger == Auth::user()->user_id || $row->created_by == Auth::user()->user_id;
                $html = '<div class="d-flex gap-1">';
                $html .= '<a href="' . e($showUrl) . '" class="text-primary" title="View"><i class="material-icons material-symbols-rounded">visibility</i></a>';
                if ($canEdit) {
                    $html .= '<a href="' . e($editUrl) . '" class="text-primary" title="Edit"><i class="material-icons material-symbols-rounded">edit</i></a>';
                }
                $html .= '</div>';
                return $html;
            })
            ->orderColumn('created_date', 'issue_log_management.created_date $1')
            ->filterColumn('category', function ($query, $keyword) {
                $query->whereHas('category', function ($q) use ($keyword) {
                    $q->where('issue_category', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('description', function ($query, $keyword) {
                $query->where('issue_log_management.description', 'like', "%{$keyword}%");
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');
                if (empty($searchValue)) {
                    return;
                }
                $term = trim($searchValue);
                $query->where(function ($q) use ($term) {
                    if (is_numeric($term)) {
                        $q->orWhere('pk', $term);
                    }
                    $q->orWhere('description', 'like', "%{$term}%")
                        ->orWhereHas('category', function ($cq) use ($term) {
                            $cq->where('issue_category', 'like', "%{$term}%");
                        })
                        ->orWhereHas('subCategoryMappings.subCategory', function ($sq) use ($term) {
                            $sq->where('issue_sub_category', 'like', "%{$term}%");
                        });
                });
            }, true)
            ->rawColumns(['priority', 'status', 'actions'])
            ->setRowId('pk');
    }

    public function query(IssueLogManagement $model): QueryBuilder
    {
        $query = $model->newQuery()->with([
            'category',
            'priority',
            'subCategoryMappings.subCategory',
        ]);

        if (!hasRole('Admin') && !hasRole('SuperAdmin')) {
            $query->where(function ($q) {
                $q->where('employee_master_pk', Auth::user()->user_id)
                    ->orWhere('issue_logger', Auth::user()->user_id)
                    ->orWhere('assigned_to', Auth::user()->user_id)
                    ->orWhere('created_by', Auth::user()->user_id);
            });
        }

        if (request()->get('raised_by') === 'self') {
            $query->where('created_by', Auth::user()->user_id);
        }

        if (request()->filled('status') && request()->get('status') !== '') {
            $query->where('issue_status', (int) request()->get('status'));
        }

        if (request()->has('category') && request()->get('category') !== '') {
            $query->where('issue_category_master_pk', request()->get('category'));
        }

        if (request()->has('priority') && request()->get('priority') !== '') {
            $query->where('issue_priority_master_pk', request()->get('priority'));
        }

        if (request()->filled('date_from')) {
            $from = Carbon::parse(request()->get('date_from'))->startOfDay()->toDateTimeString();
            $query->where('created_date', '>=', $from);
        }
        if (request()->filled('date_to')) {
            $to = Carbon::parse(request()->get('date_to'))->endOfDay()->toDateTimeString();
            $query->where('created_date', '<=', $to);
        }

        return $query->orderBy('created_date', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('issueManagementTable')
            ->addTableClass('table issue-table text-nowrap w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [[1, 'desc']],
                'lengthMenu' => [[10, 20, 50, 100, -1], [10, 20, 50, 100, 'All']],
                'language' => [
                    'search' => 'Search within table:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'infoEmpty' => 'No issues found.',
                    'infoFiltered' => '(filtered from _MAX_ total entries)',
                    'paginate' => [
                        'first' => 'First',
                        'last' => 'Last',
                        'next' => 'Next',
                        'previous' => 'Previous',
                    ],
                    'emptyTable' => 'No issues. <a href="' . route('admin.issue-management.create') . '">Log New Issue</a>',
                ],
                'dom' => '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false),
            Column::computed('created_date')->title('Date')->orderable(true)->searchable(false),
            Column::computed('category')->title('Category')->orderable(true)->searchable(true),
            Column::computed('description')->title('Description')->orderable(true)->searchable(true),
            Column::computed('priority')->title('Priority')->orderable(false)->searchable(false),
            Column::computed('status')->title('Status')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Action')->addClass('pe-4')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'IssueManagement_' . date('YmdHis');
    }
}
