<?php

namespace App\DataTables;

use App\Services\Estate\ContractualEmployeeUtilityService;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateContractualEmployeeUtilityDataTable extends DataTable
{
    public function __construct(
        private readonly ContractualEmployeeUtilityService $contractualUtilityService
    ) {}

    public function dataTable(QueryBuilder $query)
    {
        return DataTables::of($query)
            ->filter(function () {})
            ->addIndexColumn()
            ->editColumn('employee_name', fn ($row) => e($row->employee_name ?: '—'))
            ->editColumn('department', fn ($row) => e($row->department ?: '—'))
            ->editColumn('house_code', fn ($row) => e($row->house_code ?: '—'))
            ->editColumn('bill_month', fn ($row) => e(trim(($row->bill_month ?? '') . ' ' . ($row->bill_year ?? ''))))
            ->editColumn('electricity_charges', fn ($row) => number_format((float) ($row->electricity_charges ?? 0), 2))
            ->editColumn('water_charges', fn ($row) => number_format((float) ($row->water_charges ?? 0), 2))
            ->editColumn('licence_fee', fn ($row) => number_format((float) ($row->licence_fee ?? 0), 2))
            ->editColumn('total_amount', fn ($row) => number_format((float) ($row->total_amount ?? 0), 2))
            ->editColumn('bill_number', function ($row) {
                $val = e($row->bill_number ?? $this->contractualUtilityService->resolveBillNumber($row->bill_no ?? null, $row->pk ?? null));
                $pk = (int) $row->pk;

                return '<input type="text" class="form-control form-control-sm contractual-inline" data-field="bill_number" data-pk="' . $pk . '" value="' . $val . '" maxlength="100">';
            })
            ->rawColumns(['bill_number'])
            ->setRowId('pk');
    }

    public function query(): QueryBuilder
    {
        if (! Schema::hasTable('estate_month_reading_details_other')) {
            return DB::table('estate_month_reading_details_other')->whereRaw('1 = 0');
        }

        $q = $this->contractualUtilityService->listQuery()
            ->orderByDesc('emro.bill_year')
            ->orderBy('emro.bill_month')
            ->orderBy('employee_name');

        $req = request();
        $this->contractualUtilityService->applyFilters($q, $req);

        $search = strtolower(trim((string) data_get($req->input('search'), 'value', '')));
        $this->contractualUtilityService->applySearch($q, $search);

        return $q;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('contractualEmployeeUtilityTable')
            ->addTableClass('table table-striped table-hover align-middle mb-0 text-nowrap w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 25,
                'order' => [[1, 'asc']],
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
                'dom' => '<"row align-items-center mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                'scrollX' => true,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            Column::make('employee_name')->title('Employee Name'),
            Column::make('department')->title('Section'),
            Column::make('house_code')->title('House Code'),
            Column::make('bill_month')->title('Month'),
            Column::make('electricity_charges')->title('Electricity Charges')->addClass('text-end'),
            Column::make('water_charges')->title('Water Charges')->addClass('text-end'),
            Column::make('licence_fee')->title('Licence Fee')->addClass('text-end'),
            Column::make('total_amount')->title('Total Amount')->addClass('text-end')->orderable(false)->searchable(false),
            Column::make('bill_number')->title('Bill Number')->orderable(false),
        ];
    }

    protected function filename(): string
    {
        return 'ContractualEmployeeUtility_' . date('YmdHis');
    }
}
