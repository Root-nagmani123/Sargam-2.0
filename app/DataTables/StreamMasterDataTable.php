<?php

namespace App\DataTables;

use App\Models\Stream;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Stream grid (admin/stream/index).
 *
 * Server-side, so search, sorting, page size and paging all work across the
 * whole table; the global enhancer (public/js/datatable-global-ui.js) puts the
 * search box, pager and "Showing N of M items" into the page's slots. Row
 * markup is the shared admin.master.partials.grid-status / grid-actions.
 */
class StreamMasterDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            // Status: display-only soft badge; the switch lives in the Action stack.
            ->addColumn('status', fn ($row) => view('admin.master.partials.grid-status', [
                'active' => (int) $row->active_inactive === 1,
            ])->render())
            ->orderColumn('status', 'stream_master.active_inactive $1')

            // Action: Edit (opens the form in the shared modal) · switch · Delete.
            ->addColumn('actions', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return view('admin.master.partials.grid-actions', [
                    'name'   => $row->stream_name ?? '',
                    'edit'   => [
                        'href'  => route('stream.edit', $row->pk),
                        'attrs' => ['data-mst-modal-form' => true],
                    ],
                    'toggle' => [
                        'active' => $isActive,
                        'table'  => 'stream_master',
                        'column' => 'active_inactive',
                        'id'     => $row->pk,
                    ],
                    // Active rows were never deletable from this grid — keep that rule.
                    'delete' => $isActive
                        ? ['disabled' => true, 'reason' => 'Cannot delete an active stream. Deactivate it first.']
                        : ['action' => route('stream.destroy', $row->pk)],
                ])->render();
            })

            ->rawColumns(['status', 'actions']);
    }

    public function query(Stream $model): Builder
    {
        $query = $model->newQuery()
            ->select('stream_master.*');

        // Same first-load order as the old paginated list; a header click
        // sends `order` and Yajra sorts on that instead.
        if (empty(request('order'))) {
            $query->orderBy('stream_master.pk');
        }

        return $query;
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('text-nowrap')->width('5.5rem'),
            Column::make('stream_name')->name('stream_master.stream_name')->title('Stream Name'),
            Column::computed('status')->title('Status')->orderable(true)->addClass('text-nowrap')->width('8rem'),
            Column::computed('actions')->title('Action')->addClass('text-nowrap')->width('13rem'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('stream-master-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->pageLength(10)
            ->parameters([
                // Real server-side ordering over the whole table, not just the
                // loaded page (datatable-global-ui.js opt-in); no initial sort,
                // so the first load keeps the query's own order.
                'sargamServerOrder' => true,
                'order'             => [],
            ]);
    }

    protected function filename(): string
    {
        return 'StreamMasterDataTable_' . date('YmdHis');
    }
}
