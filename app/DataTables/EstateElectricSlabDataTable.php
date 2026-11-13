<?php

namespace App\DataTables;

use App\DataTables\Concerns\AppliesHeaderSort;
use App\Models\EstateElectricSlab;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateElectricSlabDataTable extends DataTable
{
    use AppliesHeaderSort;

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('unit_range', function ($row) {
                return e($row->start_unit_range . '-' . $row->end_unit_range);
            })
            ->filterColumn('unit_range', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('start_unit_range', 'like', "%{$keyword}%")
                        ->orWhere('end_unit_range', 'like', "%{$keyword}%");
                });
            })
            ->editColumn('rate_per_unit', function ($row) {
                return number_format((float) $row->rate_per_unit, 2) . ' INR';
            })
            ->addColumn('merge_with_house', function ($row) {
                return $row->unitType ? e($row->unitType->unit_type) : '-';
            })
            // Merge with House sorts via the joined alias rather than the relation.
            ->order(fn ($query) => $this->sortQuery($query))
            ->filterColumn('merge_with_house', function ($query, $keyword) {
                $query->where('ut.unit_type', 'like', "%{$keyword}%");
            })
            ->addColumn('action', function ($row) {
                // Edit opens the modal from these attributes — no extra fetch,
                // since the row already holds every field the form needs.
                return view('admin.estate.define_electric_slab._row_actions', [
                    'row' => $row,
                ])->render();
            })
            ->rawColumns(['action'])
            ->setRowId('pk');
    }

    public function query(EstateElectricSlab $model): QueryBuilder
    {
        return $model->newQuery()
            ->with('unitType')
            ->leftJoin('estate_unit_type_master as ut', 'estate_electric_slab.estate_unit_type_master_pk', '=', 'ut.pk')
            ->select('estate_electric_slab.*', 'ut.unit_type as unit_type_name')
            // Toolbar "Merge with House" filter (the slab's unit type).
            ->when(is_string($unitType = request('unit_type_filter')) && ctype_digit($unitType), fn ($q) => $q
                ->where('estate_electric_slab.estate_unit_type_master_pk', (int) request('unit_type_filter')))
            ->orderBy('start_unit_range');
    }

    /** Header sort, shared by the grid and its Download / Print, so both list rows in the same order. */
    public function sortQuery($query): void
    {
        $this->applyHeaderSort($query, 'estate_electric_slab.pk', [
            'unit_range' => 'start_unit_range',
            'merge_with_house' => 'unit_type_name',
        ]);
    }

    public function html(): HtmlBuilder
    {
        // No dom / language / lengthMenu here: datatable-global-ui.js owns the
        // toolbar and footer chrome, and hand-rolling them stops it relocating
        // the search and pager (new-design-index-page.md §3, §5).
        return $this->builder()
            ->setTableId('electricSlabTable')
            ->addTableClass('table table-hover align-middle mb-0 w-100 programme-dt-table')
            ->columns($this->getColumns())
            // A value starting with "$" is emitted as raw JS (Yajra Helper::isJavascript),
            // so the filter's current value rides along with every draw.
            ->minifiedAjax('', null, [
                'unit_type_filter' => '$("#esUnitTypeFilter").val()',
            ])
            ->parameters([
                // Responsive would inject a control column and shift every
                // Column-Visibility index; .table-responsive handles overflow.
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
                // Keep header sorting on the server (whole list, not just the loaded page);
                // datatable-global-ui.js turns it off for server-side tables without this.
                'sargamServerOrder' => true,
                'searching' => true,
                'order' => [[1, 'asc']],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->orderable(false)->searchable(false)->width('64px'),
            Column::computed('unit_range')->title('Unit Range')->orderable(true)->searchable(true),
            Column::make('rate_per_unit')->title('Rate/ Unit')->orderable(true)->searchable(true),
            Column::computed('merge_with_house')->title('Merge with House')->orderable(true)->searchable(true),
            Column::computed('action')->title('Action')->orderable(false)->searchable(false)->width('120px'),
        ];
    }

    protected function filename(): string
    {
        return 'EstateElectricSlab_' . date('YmdHis');
    }
}
