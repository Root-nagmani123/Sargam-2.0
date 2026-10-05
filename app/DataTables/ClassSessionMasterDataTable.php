<?php

namespace App\DataTables;

use App\Models\ClassSessionMaster;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Class Session Master grid (admin/master/class_session_master/index).
 *
 * Server-side, so search, sorting, page size and paging all work across the
 * whole table; the global enhancer (public/js/datatable-global-ui.js) puts the
 * search box, pager and "Showing N of M items" into the page's slots. Row
 * markup is the shared admin.master.partials.grid-status / grid-actions.
 */
class ClassSessionMasterDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('shift_name', fn ($row) => $row->shift_name ?? 'N/A')
            ->editColumn('start_time', fn ($row) => $row->start_time ?? 'N/A')
            ->editColumn('end_time', fn ($row) => $row->end_time ?? 'N/A')

            // Status: display-only soft badge; the switch lives in the Action stack.
            ->addColumn('status', fn ($row) => view('admin.master.partials.grid-status', [
                'active' => (int) $row->active_inactive === 1,
            ])->render())
            ->orderColumn('status', 'class_session_master.active_inactive $1')

            // Action: Edit (opens the form in the shared modal) · switch · Delete.
            ->addColumn('actions', function ($row) {
                $isActive = (int) $row->active_inactive === 1;
                $encId = encrypt($row->pk);

                return view('admin.master.partials.grid-actions', [
                    'name'   => $row->shift_name ?? '',
                    'edit'   => [
                        'href'  => route('master.class.session.edit', ['id' => $encId]),
                        'attrs' => ['data-mst-modal-form' => true],
                    ],
                    'toggle' => [
                        'active' => $isActive,
                        'table'  => 'class_session_master',
                        'column' => 'active_inactive',
                        'id'     => $row->pk,
                    ],
                    // Active rows were never deletable from this grid — keep that rule.
                    'delete' => $isActive
                        ? ['disabled' => true, 'reason' => 'Cannot delete an active class session. Deactivate it first.']
                        : ['action' => route('master.class.session.delete', ['id' => $encId])],
                ])->render();
            })

            ->rawColumns(['status', 'actions']);
    }

    public function query(ClassSessionMaster $model): Builder
    {
        $query = $model->newQuery()
            ->select('class_session_master.*');

        // Same first-load order as the old paginated list; a header click
        // sends `order` and Yajra sorts on that instead.
        if (empty(request('order'))) {
            $query->orderBy('class_session_master.pk');
        }

        return $query;
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('text-nowrap')->width('5.5rem'),
            Column::make('shift_name')->name('class_session_master.shift_name')->title('Shift Name'),
            Column::make('start_time')->name('class_session_master.start_time')->title('Start Time')->addClass('text-nowrap'),
            Column::make('end_time')->name('class_session_master.end_time')->title('End Time')->addClass('text-nowrap'),
            Column::computed('status')->title('Status')->orderable(true)->addClass('text-nowrap')->width('8rem'),
            Column::computed('actions')->title('Action')->addClass('text-nowrap')->width('13rem'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('class-session-master-table')
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
        return 'ClassSessionMasterDataTable_' . date('YmdHis');
    }
}
