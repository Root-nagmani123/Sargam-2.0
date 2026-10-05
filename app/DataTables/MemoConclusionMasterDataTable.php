<?php

namespace App\DataTables;

use App\Models\MemoConclusionMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class MemoConclusionMasterDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
{
    return (new EloquentDataTable($query))
        ->addIndexColumn()

        // Escaped here: both columns stay in rawColumns below.
        ->editColumn('discussion_name', fn ($row) => e($row->discussion_name ?? 'N/A'))
        ->editColumn('pt_discusion', fn ($row) => e($row->pt_discusion ?? 'N/A'))

        ->filterColumn('discussion_name', function ($query, $keyword) {
            $query->where('discussion_name', 'like', "%{$keyword}%");
        })

        ->filterColumn('pt_discusion', function ($query, $keyword) {
            $query->where('pt_discusion', 'like', "%{$keyword}%");
        })

        ->filter(function ($query) {
            $searchValue = request()->input('search.value');
            if (!empty($searchValue)) {
                $query->where(function ($subQuery) use ($searchValue) {
                    $subQuery->where('discussion_name', 'like', "%{$searchValue}%")
                             ->orWhere('pt_discusion', 'like', "%{$searchValue}%");
                });
            }
        }, true)

        // Action: Edit · status switch · Delete (docs/new-design-index-page.md §3b).
        // .editshowConclusionAlert / .deleteBtn and their data-* are the hooks
        // the index page's SweetAlert handlers read — keep them.
        ->addColumn('actions', function ($row) {
            $isActive = (int) $row->active_inactive === 1;

            return view('admin.master.partials.grid-actions', [
                'name'   => $row->discussion_name ?? '',
                'edit'   => [
                    'class' => 'editshowConclusionAlert',
                    'attrs' => [
                        'data-pk'              => $row->pk,
                        'data-discussion_name' => $row->discussion_name,
                        'data-pt_discusion'    => $row->pt_discusion,
                        'data-active_inactive' => $row->active_inactive,
                    ],
                ],
                'toggle' => [
                    'active' => $isActive,
                    'table'  => 'memo_conclusion_master',
                    'column' => 'active_inactive',
                    'id'     => $row->pk,
                ],
                // destroy() refuses an active memo conclusion (403) — mirror it.
                'delete' => $isActive
                    ? ['disabled' => true, 'reason' => 'Active memo conclusions cannot be deleted. Deactivate it first.']
                    : [
                        'class' => 'deleteBtn',
                        'attrs' => [
                            'data-url' => route('master.memo.conclusion.master.delete', $row->pk),
                            'data-id'  => $row->pk,
                        ],
                    ],
            ])->render();
        })

        // Status: display-only soft badge. The switch lives in the Action stack.
        ->addColumn('status', fn ($row) => view('admin.master.partials.grid-status', [
            'active' => (int) $row->active_inactive === 1,
        ])->render())

        ->rawColumns(['discussion_name', 'pt_discusion', 'actions', 'status']);
}


    public function query(MemoConclusionMaster $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('memoconclusionmaster-table')
            ->addTableClass('table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('text-nowrap')->width('5.5rem')->orderable(false)->searchable(false),
            Column::make('discussion_name')->title('Conclusion Name')->orderable(false)->searchable(true),
            Column::make('pt_discusion')->title('PT Discussion')->addClass('mst-col-wrap')->orderable(false)->searchable(true),
            Column::computed('status')->title('Status')->addClass('text-nowrap')->width('8rem')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Action')->addClass('text-nowrap')->width('13rem')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'MemoConclusionMaster_' . date('YmdHis');
    }
}

