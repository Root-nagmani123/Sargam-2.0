<?php

namespace App\DataTables;

use App\Models\Stream;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class StreamDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('stream_name', fn($row) => $row->stream_name ?? 'N/A')
            ->filterColumn('stream_name', function ($query, $keyword) {
                $query->where('stream_name', 'like', "%{$keyword}%");
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->where('stream_name', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->addColumn('status', function ($row) {
                return '<div class="form-check form-switch d-inline-block">
                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                        data-table="stream_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . ($row->active_inactive == 1 ? 'checked' : '') . '>
                </div>';
            })
            ->addColumn('actions', function ($row) {
                $updateUrl = route('stream.update', $row->pk);
                $deleteUrl = route('stream.destroy', $row->pk);
                $csrf = csrf_token();
                $isActive = $row->active_inactive  == 1;

              $deleteButton = $isActive
    ? '<span class="material-symbols-rounded fs-6 text-muted" title="Deactivate first">delete</span>'
    : '
    <a href="javascript:void(0)"
        class="delete-stream text-primary"
        data-url="'.$deleteUrl.'"
        data-token="'.$csrf.'"
        title="Delete Stream">

        <span class="material-symbols-rounded fs-6">delete</span>
    </a>
    ';

             return '<div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Stream actions">
                    <a href="javascript:void(0);" class="d-flex align-items-center gap-1 text-primary open-stream-modal" role="button"
                        data-id="' . e($row->pk) . '" data-name="' . e($row->stream_name) . '" data-url="' . e($updateUrl) . '" aria-label="Edit stream">
                        <span class="material-symbols-rounded fs-6" aria-hidden="true">edit</span>
                    </a>
                    ' . $deleteButton . '
                </div>';
            })
            ->rawColumns(['stream_name', 'status', 'actions']);
    }

    public function query(Stream $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('stream-table')
            ->addTableClass('table text-nowrap w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
				 'columnDefs' => [
                [
                    'responsivePriority' => 1,
                    'targets' => -1,     // Actions ALWAYS visible
                ],
                [
                    'responsivePriority' => 2,
                    'targets' => 2,      // Status second priority
                ],
				],
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [[1, 'asc']],
                'language' => [
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ streams',
                    'infoEmpty' => 'Showing 0 to 0 of 0 streams',
                    'infoFiltered' => '(filtered from _MAX_ total streams)',
                    'paginate' => [
                        'previous' => '&lsaquo;',
                        'next' => '&rsaquo;',
                    ],
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false)->width('80px'),
            Column::make('stream_name')->title('Stream Name')->orderable(true)->searchable(true),
            Column::computed('status')->title('Status')->addClass('text-center')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Action')->addClass('text-center')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'Stream_' . date('YmdHis');
    }
}
