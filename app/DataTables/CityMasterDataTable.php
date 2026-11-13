<?php

namespace App\DataTables;

use App\Models\City;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * City List grid (admin/city/index).
 *
 * Server-side, so search, sorting, page size and paging all work across the
 * whole table; the global enhancer (public/js/datatable-global-ui.js) puts the
 * search box, pager and "Showing N of M items" into the page's slots. Row
 * markup is the shared admin.master.partials.grid-status / grid-actions.
 */
class CityMasterDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('state_name', fn ($row) => $row->state_name ?? 'N/A')
            ->editColumn('district_name', fn ($row) => $row->district_name ?? 'N/A')

            // Status: display-only soft badge; the switch lives in the Action stack.
            ->addColumn('status', fn ($row) => view('admin.master.partials.grid-status', [
                'active' => (int) $row->active_inactive === 1,
            ])->render())
            ->orderColumn('status', 'city_master.active_inactive $1')

            // Action: Edit (opens the form in the shared modal) · switch · Delete.
            ->addColumn('actions', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return view('admin.master.partials.grid-actions', [
                    'name'   => $row->city_name ?? '',
                    'edit'   => [
                        'href'  => route('master.city.edit', $row->pk),
                        'attrs' => ['data-mst-modal-form' => true],
                    ],
                    'toggle' => [
                        'active' => $isActive,
                        'table'  => 'city_master',
                        'column' => 'active_inactive',
                        'id'     => $row->pk,
                    ],
                    // Active rows were never deletable from this grid — keep that rule.
                    'delete' => $isActive
                        ? ['disabled' => true, 'reason' => 'Cannot delete an active city. Deactivate it first.']
                        : ['action' => route('master.city.delete', $row->pk)],
                ])->render();
            })

            ->rawColumns(['status', 'actions']);
    }

    public function query(City $model): Builder
    {
        $query = $model->newQuery()
            ->leftJoin('state_master as sm', 'sm.pk', '=', 'city_master.state_master_pk')
            ->leftJoin('state_district_mapping as sdm', 'sdm.pk', '=', 'city_master.district_master_pk')
            ->select('city_master.*', 'sm.state_name', 'sdm.district_name');

        // Same first-load order as the old paginated list; a header click
        // sends `order` and Yajra sorts on that instead.
        if (empty(request('order'))) {
            $query->orderBy('city_master.pk');
        }

        return $query;
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('text-nowrap')->width('5.5rem'),
            Column::make('city_name')->name('city_master.city_name')->title('City Name'),
            Column::make('state_name')->name('sm.state_name')->title('State')->addClass('mst-col-wrap'),
            Column::make('district_name')->name('sdm.district_name')->title('District')->addClass('mst-col-wrap'),
            Column::computed('status')->title('Status')->orderable(true)->addClass('text-nowrap')->width('8rem'),
            Column::computed('actions')->title('Action')->addClass('text-nowrap')->width('13rem'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('city-master-table')
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
        return 'CityMasterDataTable_' . date('YmdHis');
    }
}
