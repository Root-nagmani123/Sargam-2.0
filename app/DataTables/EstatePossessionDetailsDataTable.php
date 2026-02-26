<?php

namespace App\DataTables;

use App\Models\EstateHomeRequestDetails;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstatePossessionDetailsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('request_id', fn ($row) => e($row->request_id ?? '—'))
            ->editColumn('emp_name', fn ($row) => e($row->emp_name ?? '—'))
            ->editColumn('employee_id', fn ($row) => e($row->employee_id ?? '—'))
            ->editColumn('emp_designation', fn ($row) => e($row->emp_designation ?? '—'))
            ->editColumn('estate_name', fn ($row) => e($row->estate_name ?? '—'))
            ->editColumn('building_name', fn ($row) => e($row->building_name ?? '—'))
            ->editColumn('unit_type', fn ($row) => e($row->unit_type ?? '—'))
            ->editColumn('unit_sub_type', fn ($row) => e($row->unit_sub_type ?? '—'))
            ->editColumn('house_no', fn ($row) => e($row->house_no ?? '—'))
            ->editColumn('allotment_date', function ($row) {
                $d = $row->allotment_date ?? null;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('possession_date', function ($row) {
                $d = $row->possession_date ?? null;
                return $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '—';
            })
            ->editColumn('electric_meter_reading', fn ($row) => $row->electric_meter_reading ?? '—')
            ->addColumn('actions', function ($row) {
                $editUrl = route('admin.estate.possession-details.create', [
                    'requester_id' => $row->estate_home_request_details_pk,
                ]);
                $deleteUrl = route('admin.estate.possession-details.delete', ['id' => $row->pk]);

                return '<div class="d-inline-flex align-items-center gap-1" role="group">
                    <a href="' . e($editUrl) . '" class="text-primary" title="Edit">
                        <i class="material-symbols-rounded">edit</i>
                    </a>
                    <a href="javascript:void(0);" class="text-primary btn-delete-possession-details" data-url="' . e($deleteUrl) . '" data-id="' . (int) $row->pk . '" title="Delete">
                        <i class="material-symbols-rounded">delete</i>
                    </a>
                </div>';
            })
            ->filter(function ($query) {
                $searchValue = trim((string) request()->input('search.value', ''));
                if ($searchValue === '') {
                    return;
                }
                $searchLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $searchValue) . '%';
                $query->where(function ($q) use ($searchLike) {
                    $q->where('ehrd.req_id', 'like', $searchLike)
                        ->orWhere('ehrd.emp_name', 'like', $searchLike)
                        ->orWhere('ehrd.employee_id', 'like', $searchLike)
                        ->orWhere('ehrd.emp_designation', 'like', $searchLike)
                        ->orWhere('ec.campus_name', 'like', $searchLike)
                        ->orWhere('eb.block_name', 'like', $searchLike)
                        ->orWhere('eut.unit_type', 'like', $searchLike)
                        ->orWhere('eust.unit_sub_type', 'like', $searchLike)
                        ->orWhere('ehm.house_no', 'like', $searchLike);
                });
            }, true)
            ->rawColumns(['actions'])
            ->setRowId('pk');
    }

    public function query(EstateHomeRequestDetails $model): QueryBuilder
    {
        return $model->newQuery()
            ->from('estate_home_request_details as ehrd')
            ->join('estate_possession_details as epd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->leftJoin('estate_campus_master as ec', 'ehm.estate_campus_master_pk', '=', 'ec.pk')
            ->leftJoin('estate_block_master as eb', 'ehm.estate_block_master_pk', '=', 'eb.pk')
            ->leftJoin('estate_unit_type_master as eut', 'ehm.estate_unit_master_pk', '=', 'eut.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->select([
                'epd.pk as pk',
                'ehrd.pk as estate_home_request_details_pk',
                'ehrd.req_id as request_id',
                'ehrd.emp_name',
                'ehrd.employee_id',
                'ehrd.emp_designation',
                'ec.campus_name as estate_name',
                'eb.block_name as building_name',
                'eut.unit_type',
                'eust.unit_sub_type',
                'ehm.house_no',
                'epd.allotment_date',
                'epd.possession_date',
                'epd.electric_meter_reading',
            ])
            ->where('epd.estate_change_id', -1)
            ->orderBy('epd.pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('estatePossessionDetailsTable')
            ->addTableClass('table table-bordered table-striped table-hover align-middle text-nowrap mb-0 w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'scrollX' => true,
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
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.NO.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            Column::make('request_id')->title('REQUEST ID')->orderable(true)->searchable(true),
            Column::make('emp_name')->title('NAME')->orderable(true)->searchable(true),
            Column::make('employee_id')->title('EMPLOYEE ID')->orderable(true)->searchable(true),
            Column::make('emp_designation')->title('DESIGNATION')->orderable(true)->searchable(true),
            Column::make('estate_name')->title('ESTATE NAME')->orderable(true)->searchable(true),
            Column::make('building_name')->title('BUILDING NAME')->orderable(true)->searchable(true),
            Column::make('unit_type')->title('UNIT TYPE')->orderable(true)->searchable(true),
            Column::make('unit_sub_type')->title('UNIT SUB TYPE')->orderable(true)->searchable(true),
            Column::make('house_no')->title('HOUSE NO.')->orderable(true)->searchable(true),
            Column::make('allotment_date')->title('ALLOTMENT DATE')->orderable(true)->searchable(false),
            Column::make('possession_date')->title('POSSESSION DATE')->orderable(true)->searchable(false),
            Column::make('electric_meter_reading')->title('ELECTRIC METER READING')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false)->searchable(false)->width('120px'),
        ];
    }

    protected function filename(): string
    {
        return 'EstatePossessionDetails_' . date('YmdHis');
    }
}
