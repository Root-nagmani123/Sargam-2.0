<?php

namespace App\DataTables;

use App\Models\EstateMigrationReport;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateMigrationReportDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('allotment_year', fn ($row) => $row->allotment_year ?? '—')
            ->editColumn('campus_name', fn ($row) => $row->campus_name ?? '—')
            ->editColumn('building_name', fn ($row) => $row->building_name ?? '—')
            ->editColumn('type_of_building', fn ($row) => $row->type_of_building ?? '—')
            ->editColumn('house_no', fn ($row) => $row->house_no ?? '—')
            ->editColumn('employee_name', fn ($row) => $row->employee_name ?? '—')
            ->editColumn('department_name', fn ($row) => $row->department_name ?? '—')
            ->editColumn('employee_type', fn ($row) => $row->employee_type ?? '—')
            ->setRowId('id')
            ->filter(function ($query) {
                $req = request();
                if ($req->filled('filter_allotment_year')) {
                    $query->where('allotment_year', (int) $req->filter_allotment_year);
                }
                if ($req->filled('filter_campus_name')) {
                    $query->where('campus_name', 'like', '%' . $req->filter_campus_name . '%');
                }
                if ($req->filled('filter_building_name')) {
                    $query->where('building_name', 'like', '%' . $req->filter_building_name . '%');
                }
                if ($req->filled('filter_type_of_building')) {
                    $query->where('type_of_building', 'like', '%' . $req->filter_type_of_building . '%');
                }
                if ($req->filled('filter_house_no')) {
                    $query->where('house_no', 'like', '%' . $req->filter_house_no . '%');
                }
                if ($req->filled('filter_employee_name')) {
                    $query->where('employee_name', 'like', '%' . $req->filter_employee_name . '%');
                }
                if ($req->filled('filter_department_name')) {
                    $query->where('department_name', 'like', '%' . $req->filter_department_name . '%');
                }
                if ($req->filled('filter_employee_type')) {
                    $query->where('employee_type', 'like', '%' . $req->filter_employee_type . '%');
                }
                $searchValue = $req->input('search.value');
                if (! empty($searchValue)) {
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('allotment_year', 'like', "%{$searchValue}%")
                            ->orWhere('campus_name', 'like', "%{$searchValue}%")
                            ->orWhere('building_name', 'like', "%{$searchValue}%")
                            ->orWhere('type_of_building', 'like', "%{$searchValue}%")
                            ->orWhere('house_no', 'like', "%{$searchValue}%")
                            ->orWhere('employee_name', 'like', "%{$searchValue}%")
                            ->orWhere('department_name', 'like', "%{$searchValue}%")
                            ->orWhere('employee_type', 'like', "%{$searchValue}%");
                    });
                }
            }, true);
    }

    public function query(EstateMigrationReport $model): QueryBuilder
    {
        return $model->newQuery()
            ->select([
                'id',
                'allotment_year',
                'campus_name',
                'building_name',
                'type_of_building',
                'house_no',
                'employee_name',
                'department_name',
                'employee_type',
                'created_at',
                'updated_at',
            ])
            ->orderBy('allotment_year', 'desc')
            ->orderBy('id', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('estateMigrationReportTable')
            ->addTableClass('table table-bordered table-striped table-hover text-nowrap align-middle mb-0')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'scrollX' => true,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 25,
                'order' => [[1, 'desc']],
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
                'dom' => '<"row flex-wrap align-items-center gap-2 mb-3"<"col-12 col-sm-6 col-md-4"l><"col-12 col-sm-6 col-md-5"f>>rt<"row align-items-center mt-3"<"col-12 col-sm-6 col-md-5"i><"col-12 col-sm-6 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            Column::make('allotment_year')->title('Allotment Year')->orderable(true)->searchable(true),
            Column::make('campus_name')->title('Campus Name')->orderable(true)->searchable(true),
            Column::make('building_name')->title('Building Name')->orderable(true)->searchable(true),
            Column::make('type_of_building')->title('Type of Building')->orderable(true)->searchable(true),
            Column::make('house_no')->title('House No.')->orderable(true)->searchable(true),
            Column::make('employee_name')->title('Employee Name')->orderable(true)->searchable(true),
            Column::make('department_name')->title('Department')->orderable(true)->searchable(true),
            Column::make('employee_type')->title('Employee Type')->orderable(true)->searchable(true),
        ];
    }

    protected function filename(): string
    {
        return 'EstateMigrationReport_' . date('YmdHis');
    }
}
