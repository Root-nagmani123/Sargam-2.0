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
                return $row->start_unit_range . ' – ' . $row->end_unit_range;
            })
            ->orderColumn('unit_range', 'start_unit_range $1')
            ->filterColumn('unit_range', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('start_unit_range', 'like', "%{$keyword}%")
                        ->orWhere('end_unit_range', 'like', "%{$keyword}%");
                });
            })
            ->editColumn('rate_per_unit', function ($row) {
                return number_format((float) $row->rate_per_unit, 2);
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('admin.estate.define-electric-slab.edit', $row->pk);
                $deleteUrl = route('admin.estate.define-electric-slab.destroy', $row->pk);
                $token = csrf_token();

                return '<div class="d-inline-flex align-items-center gap-1" role="group">
                    <a href="' . e($editUrl) . '" class="text-primary" title="Edit"><i class="material-icons material-symbols-rounded">edit</i></a>
                    <form action="' . e($deleteUrl) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this electric slab?\');">
                        <input type="hidden" name="_token" value="' . e($token) . '">
                        <input type="hidden" name="_method" value="DELETE">
                        <a href="javascript:void(0)" class="text-primary" title="Delete"> <i class="material-icons material-symbols-rounded">delete</i></a>
                       
                    </form>
                </div>';
            })
            ->rawColumns(['action'])
            ->setRowId('pk');
    }

    public function query(EstateElectricSlab $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('start_unit_range');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('electricSlabTable')
            ->addTableClass('table align-middle mb-0')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [[1, 'asc']],
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'search' => 'Search within table:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'infoEmpty' => 'Showing 0 to 0 of 0 entries',
                    'infoFiltered' => '(filtered from _MAX_ total entries)',
                    'paginate' => [
                        'first' => 'First',
                        'last' => 'Last',
                        'next' => 'Next',
                        'previous' => 'Previous',
                    ],
                ],
                'dom' => '<"row flex-wrap align-items-center gap-2"<"col-12 col-md-6"l><"col-12 col-md-6"f>>rt<"row align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.NO.')->addClass('text-center')->orderable(false)->searchable(false)->width('60px'),
            Column::computed('unit_range')->title('UNIT RANGE')->orderable(true)->searchable(true),
            Column::make('rate_per_unit')->title('RATE/UNIT')->orderable(true)->searchable(true),
            Column::computed('action')->title('EDIT')->addClass('text-center')->orderable(false)->searchable(false)->width('120px'),
        ];
    }

    protected function filename(): string
    {
        return 'EstateElectricSlab_' . date('YmdHis');
    }
}
