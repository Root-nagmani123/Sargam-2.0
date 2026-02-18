<?php

namespace App\DataTables;

use App\Models\EstatePossessionOther;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstatePossessionOtherDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('request_id', fn($row) => $row->estateOtherRequest->request_no_oth ?? 'N/A')
            ->editColumn('name', fn($row) => $row->estateOtherRequest->emp_name ?? 'N/A')
            ->editColumn('section_name', fn($row) => $row->estateOtherRequest->section ?? 'N/A')
            ->editColumn('estate_name', fn($row) => $row->campus_name ?? 'N/A')
            ->editColumn('unit_type', fn($row) => $row->unit_type_name ?? 'N/A')
            ->editColumn('building_name', fn($row) => $row->block_name ?? 'N/A')
            ->editColumn('unit_sub_type', fn($row) => $row->unit_sub_type_name ?? 'N/A')
            ->editColumn('house_no', fn($row) => $row->house_no ?? $row->house_no_display ?? 'N/A')
            ->editColumn('allotment_date', fn($row) => $row->allotment_date ? $row->allotment_date->format('Y-m-d') : 'N/A')
            ->editColumn('possession_date_oth', fn($row) => $row->possession_date_oth ? $row->possession_date_oth->format('Y-m-d') : 'N/A')
            ->editColumn('meter_reading_oth', fn($row) => $row->meter_reading_oth ?? 'N/A')
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');
                if (!empty($searchValue)) {
                    $query->where(function ($q) use ($searchValue) {
                        $q->whereHas('estateOtherRequest', function ($sq) use ($searchValue) {
                            $sq->where('emp_name', 'like', "%{$searchValue}%")
                                ->orWhere('request_no_oth', 'like', "%{$searchValue}%")
                                ->orWhere('section', 'like', "%{$searchValue}%");
                        })
                        ->orWhere('estate_possession_other.house_no', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->addColumn('actions', function ($row) {
                $viewUrl = route('admin.estate.possession-view', ['id' => $row->pk]);
                $editUrl = route('admin.estate.possession-view', ['id' => $row->pk]);
                $deleteUrl = route('admin.estate.possession-delete', ['id' => $row->pk]);

                return '<div class="d-inline-flex align-items-center gap-1" role="group">
                    <a href="' . $editUrl . '" class="text-primary" title="Edit">
                        <i class="material-symbols-rounded">edit</i>
                    </a>
                    <a href="javascript:void(0);" class="text-primary btn-delete-possession" data-url="' . $deleteUrl . '" data-id="' . $row->pk . '" title="Delete">
                        <i class="material-symbols-rounded">delete</i>
                    </a>
                </div>';
            })
            ->rawColumns(['actions'])
            ->setRowId('pk');
    }

    public function query(EstatePossessionOther $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['estateOtherRequest'])
            ->select([
                'estate_possession_other.*',
                'ec.campus_name',
                'eb.block_name',
                'eut.unit_type as unit_type_name',
                'eust.unit_sub_type as unit_sub_type_name',
                'ehm.house_no as house_no_display',
            ])
            ->leftJoin('estate_campus_master as ec', 'estate_possession_other.estate_campus_master_pk', '=', 'ec.pk')
            ->leftJoin('estate_block_master as eb', 'estate_possession_other.estate_block_master_pk', '=', 'eb.pk')
            ->leftJoin('estate_unit_type_master as eut', 'estate_possession_other.estate_unit_type_master_pk', '=', 'eut.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'estate_possession_other.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->leftJoin('estate_house_master as ehm', 'estate_possession_other.estate_house_master_pk', '=', 'ehm.pk')
            ->orderBy('estate_possession_other.pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('estatePossessionTable')
            ->addTableClass('table table-bordered table-hover text-nowrap w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [[1, 'desc']],
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'search' => 'Search:',
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
                'dom' => '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                'scrollX' => true,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.NO.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            Column::make('request_id')->title('REQUEST ID')->orderable(false)->searchable(false),
            Column::make('name')->title('NAME')->orderable(false)->searchable(false),
            Column::make('section_name')->title('SECTION NAME')->orderable(false)->searchable(false),
            Column::make('estate_name')->title('ESTATE NAME')->orderable(false)->searchable(false),
            Column::make('unit_type')->title('UNIT TYPE')->orderable(false)->searchable(false),
            Column::make('building_name')->title('BUILDING NAME')->orderable(false)->searchable(false),
            Column::make('unit_sub_type')->title('UNIT SUB TYPE')->orderable(false)->searchable(false),
            Column::make('house_no')->title('HOUSE NO.')->orderable(false)->searchable(true),
            Column::make('allotment_date')->title('ALLOTMENT DATE')->orderable(false)->searchable(false),
            Column::make('possession_date_oth')->title('POSSESSION DATE')->orderable(false)->searchable(false),
            Column::make('meter_reading_oth')->title('LAST MONTH ELECTRIC METER READING')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false)->searchable(false)->width('120px'),
        ];
    }

    protected function filename(): string
    {
        return 'EstatePossessionOther_' . date('YmdHis');
    }
}
