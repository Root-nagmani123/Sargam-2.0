<?php

namespace App\DataTables;

use App\Models\MemoNoticeTemplate;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class MemoNoticeTemplateDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('course', function ($row) {
                if ($row->course) {
                    return '<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">' . e($row->course->course_name) . '</span>';
                }
                return '<span class="text-muted small">—</span>';
            })
            ->editColumn('title', fn($row) => '<span class="fw-medium small">' . e($row->title) . '</span>')
            ->editColumn('memo_notice_type', fn($row) => '<span class="text-body-secondary small">' . e($row->memo_notice_type) . '</span>')
            ->filterColumn('course', function ($query, $keyword) {
                $query->whereHas('course', function ($q) use ($keyword) {
                    $q->where('course_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('title', function ($query, $keyword) {
                $query->where('title', 'like', "%{$keyword}%");
            })
            ->filterColumn('memo_notice_type', function ($query, $keyword) {
                $query->where('memo_notice_type', 'like', "%{$keyword}%");
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');
                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->where('title', 'like', "%{$searchValue}%")
                            ->orWhere('memo_notice_type', 'like', "%{$searchValue}%")
                            ->orWhereHas('course', function ($q) use ($searchValue) {
                                $q->where('course_name', 'like', "%{$searchValue}%");
                            });
                    });
                }
            }, true)
            ->orderColumn('course', function ($query, $order) {
                $dir = strtolower($order) === 'asc' ? 'asc' : 'desc';
                $query->orderByRaw('(SELECT course_name FROM course_master WHERE course_master.pk = memo_notice_templates.course_master_pk) ' . $dir);
            })
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch mb-0">
                    <input class="form-check-input status-toggle-data" type="checkbox" role="switch"
                        data-id="' . (int) $row->pk . '"
                        data-course="' . (int) ($row->course_master_pk ?? 0) . '"
                        data-type="' . e($row->memo_notice_type) . '"
                        ' . $checked . '>
                </div>';
            })
            ->addColumn('actions', function ($row) {
                $editUrl = route('admin.memo-notice.edit', $row->pk);
                $destroyUrl = route('admin.memo-notice.destroy', $row->pk);
                $csrf = csrf_token();

                $html = '<div class="btn-group btn-group-sm flex-nowrap" role="group" aria-label="Template actions">';
                $html .= '<a href="' . e($editUrl) . '" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" aria-label="Edit template">';
                $html .= '<i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">edit</i>';
                $html .= '<span class="d-none d-lg-inline">Edit</span></a>';

                if ($row->active_inactive == 1) {
                    $html .= '<button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1" disabled aria-disabled="true" title="Deactivate before deleting">';
                    $html .= '<i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">delete</i>';
                    $html .= '<span class="d-none d-lg-inline">Delete</span></button>';
                } else {
                    $html .= '<form action="' . e($destroyUrl) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this template?\');">';
                    $html .= '<input type="hidden" name="_token" value="' . $csrf . '">';
                    $html .= '<input type="hidden" name="_method" value="DELETE">';
                    $html .= '<button type="submit" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1" aria-label="Delete template">';
                    $html .= '<i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">delete</i>';
                    $html .= '<span class="d-none d-lg-inline">Delete</span></button></form>';
                }
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['course', 'title', 'memo_notice_type', 'status', 'actions']);
    }

    public function query(MemoNoticeTemplate $model): QueryBuilder
    {
        $query = $model->newQuery()->with('course')->orderBy('memo_notice_templates.created_date', 'desc');

        $data_course_id = get_Role_by_course();
        if (!empty($data_course_id)) {
            $query->whereIn('memo_notice_templates.course_master_pk', $data_course_id);
        }

        if (request()->filled('course_master_pk')) {
            $query->where('memo_notice_templates.course_master_pk', request('course_master_pk'));
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('memo-notice-template-table')
            ->addTableClass('table table-hover align-middle mb-0 w-100')
            ->columns($this->getColumns())
            ->ajax([
                'url' => route('admin.memo-notice.index'),
                'data' => 'function(d) { d.course_master_pk = $("#course_master_pk").val(); }',
            ])
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'order' => [[2, 'desc']],
                'language' => [
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ templates',
                    'infoEmpty' => 'Showing 0 to 0 of 0 templates',
                    'infoFiltered' => '(filtered from _MAX_ total templates)',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'search' => 'Search:',
                    'paginate' => [
                        'first' => 'First',
                        'last' => 'Last',
                        'previous' => '&lsaquo;',
                        'next' => '&rsaquo;',
                    ],
                    'zeroRecords' => 'No matching templates found.',
                ],
                'columnDefs' => [
                    ['orderable' => false, 'searchable' => false, 'targets' => [0, 4, 5]],
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#')->addClass('text-center')->orderable(false)->searchable(false)->width('60px'),
            Column::make('course')->title('Course')->orderable(true)->searchable(true),
            Column::make('title')->title('Title')->orderable(true)->searchable(true),
            Column::make('memo_notice_type')->title('Type')->orderable(true)->searchable(true),
            Column::computed('status')->title('Status')->addClass('text-center')->orderable(false)->searchable(false)->width('80px'),
            Column::computed('actions')->title('Actions')->addClass('text-end')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'MemoNoticeTemplate_' . date('YmdHis');
    }
}
