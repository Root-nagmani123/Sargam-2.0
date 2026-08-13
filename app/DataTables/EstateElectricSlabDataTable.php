<?php

namespace App\DataTables;

use App\Models\EstateElectricSlab;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateElectricSlabDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('unit_range', function ($row) {
                return e($row->start_unit_range . '-' . $row->end_unit_range);
            })
            ->orderColumn('unit_range', 'start_unit_range $1')
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
            // Sortable via the joined alias rather than the relation, so the
            // header arrow the design shows actually does something.
            ->orderColumn('merge_with_house', 'unit_type_name $1')
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
            ->orderBy('start_unit_range');
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
            ->minifiedAjax()
            ->parameters([
                // Responsive would inject a control column and shift every
                // Column-Visibility index; .table-responsive handles overflow.
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
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
