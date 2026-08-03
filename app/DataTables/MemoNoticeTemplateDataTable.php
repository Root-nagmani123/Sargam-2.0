<?php

namespace App\DataTables;

use App\Models\MemoNoticeTemplate;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Memo/Notice template listing (/admin/memo-notice). Single Eloquent model,
 * mirroring the pre-DataTable MemoNoticeController::index().
 */
class MemoNoticeTemplateDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('course_name', fn ($row) => $row->course->course_name ?? 'General')
            ->addColumn('status', function ($row) {
                $checked = (int) $row->active_inactive === 1 ? 'checked' : '';

                return '<div class="form-check form-switch d-inline-block ms-2">'
                    . '<input class="form-check-input status-toggle-data" type="checkbox" role="switch"'
                    . ' data-id="' . $row->pk . '" data-course="' . $row->course_master_pk . '" data-type="' . e($row->memo_notice_type) . '" ' . $checked . '>'
                    . '</div>';
            })
            ->addColumn('action', function ($row) {
                return $this->renderActionsColumn($row);
            })
            ->rawColumns(['status', 'action']);
    }

    /**
     * Mirrors resources/views/admin/courseAttendanceNoticeMap/memo_notice_index.blade.php's
     * previous Actions cell (Edit always shown; Delete only for inactive templates).
     */
    private function renderActionsColumn(object $row): string
    {
        $editUrl = route('admin.memo-notice.edit', $row->pk);
        $html = '<div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Memo notice template actions">'
            . '<a href="' . e($editUrl) . '" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 bg-transparent border-0 p-0 text-primary" aria-label="Edit memo notice template">'
            . '<i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">edit</i></a>';

        if ((int) $row->active_inactive === 0) {
            $deleteUrl = route('admin.memo-notice.destroy', $row->pk);
            $token = csrf_token();
            $html .= '<form action="' . e($deleteUrl) . '" method="POST" class="d-inline"'
                . ' onsubmit="return confirm(\'Are you sure you want to delete this template?\')">'
                . '<input type="hidden" name="_token" value="' . e($token) . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 bg-transparent border-0 p-0 text-danger" aria-label="Delete memo notice template">'
                . '<i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i></button>'
                . '</form>';
        } else {
            $html .= '<span title="Deactivate first to delete" style="cursor:not-allowed; opacity:0.4;">'
                . '<i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i></span>';
        }

        $html .= '</div>';

        return $html;
    }

    public function query(MemoNoticeTemplate $model): QueryBuilder
    {
        $request = $this->request();
        $data_course_id = get_Role_by_course();

        $query = $model->newQuery()->with('course');

        if (!empty($data_course_id)) {
            $query->whereIn('course_master_pk', $data_course_id);
        }

        if ($request->filled('course_master_pk')) {
            $query->where('course_master_pk', $request->input('course_master_pk'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Default newest-first, but only when the user hasn't requested a column sort
        // (clicking a header would otherwise never take visible effect over this).
        if (empty($request->input('order'))) {
            $query->orderBy('created_date', 'desc');
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('memoTemplatesTable')
            ->addTableClass('table text-nowrap')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'searching' => false,
                'ordering' => false,
                'language' => [
                    'zeroRecords' => $this->emptyStateHtml(),
                    'emptyTable' => $this->emptyStateHtml(),
                ],
            ]);
    }

    private function emptyStateHtml(): string
    {
        return '<div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i> No templates found. Create your first template!</div>';
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#')->orderable(false)->searchable(false),
            Column::computed('course_name')->title('Course')->orderable(false)->searchable(false),
            Column::make('title')->title('Title')->orderable(false)->searchable(false),
            Column::make('memo_notice_type')->title('Type')->orderable(false)->searchable(false),
            Column::computed('status')->title('Status')->orderable(false)->searchable(false),
            Column::computed('action')->title('Actions')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'MemoNoticeTemplates_' . date('YmdHis');
    }
}
