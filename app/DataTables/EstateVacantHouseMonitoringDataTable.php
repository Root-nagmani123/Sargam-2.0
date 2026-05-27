<?php

namespace App\DataTables;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateVacantHouseMonitoringDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query)
    {
        return DataTables::of($query)
            ->filter(function () {})
            ->addIndexColumn()
            ->editColumn('house_code', fn ($row) => e($row->house_code ?: '—'))
            ->editColumn('house_name', fn ($row) => e($row->house_name ?: '—'))
            ->editColumn('meter_number', function ($row) {
                $m1 = $row->meter_number ?? 0;
                $m2 = (int) ($row->meter_number_two ?? 0);
                if ($m2 > 0) {
                    return e($m1 . ' / ' . $m2);
                }

                return e((string) $m1);
            })
            ->editColumn('last_meter_reading_before_vacancy', function ($row) {
                $r1 = $row->last_meter_reading_before_vacancy ?? 0;
                $r2 = (int) ($row->last_meter_reading_two_before_vacancy ?? 0);
                if ($r2 > 0) {
                    return e($r1 . ' / ' . $r2);
                }

                return e((string) $r1);
            })
            ->editColumn('last_allottee_employee_name', fn ($row) => e($row->last_allottee_employee_name ?: '—'))
            ->editColumn('vacancy_date', fn ($row) => $row->vacancy_date ? e(\Carbon\Carbon::parse($row->vacancy_date)->format('d/m/Y')) : '—')
            ->setRowId('pk');
    }

    public function query(): QueryBuilder
    {
        if (! Schema::hasTable('estate_vacant_house_monitoring')) {
            return DB::table('estate_vacant_house_monitoring')->whereRaw('1 = 0');
        }

        $q = DB::table('estate_vacant_house_monitoring')
            ->where('is_active', 1)
            ->select([
                'pk',
                'house_code',
                'house_name',
                'meter_number',
                'meter_number_two',
                'last_meter_reading_before_vacancy',
                'last_meter_reading_two_before_vacancy',
                'last_allottee_employee_name',
                'vacancy_date',
            ])
            ->orderBy('house_code');

        $search = strtolower(trim((string) data_get($this->request()->get('search'), 'value', '')));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $q->where(function ($sub) use ($like) {
                $sub->whereRaw('LOWER(house_code) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(house_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(last_allottee_employee_name) LIKE ?', [$like])
                    ->orWhereRaw('CAST(meter_number AS CHAR) LIKE ?', [$like])
                    ->orWhereRaw('CAST(last_meter_reading_before_vacancy AS CHAR) LIKE ?', [$like]);
            });
        }

        return $q;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('vacantHouseMonitoringTable')
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
            Column::make('house_code')->title('House Code'),
            Column::make('house_name')->title('House Name'),
            Column::make('meter_number')->title('Meter Number')->orderable(false)->searchable(false),
            Column::make('last_meter_reading_before_vacancy')->title('Last Meter Reading before Vacancy')->orderable(false)->searchable(false),
            Column::make('last_allottee_employee_name')->title('Last Allottee Employee Name'),
            Column::make('vacancy_date')->title('Vacancy Date'),
        ];
    }

    protected function filename(): string
    {
        return 'VacantHouseMonitoring_' . date('YmdHis');
    }
}
