<?php

namespace App\DataTables;

use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use App\Models\EmployeeMaster;

class MemberDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     * @return \Yajra\DataTables\EloquentDataTable
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('employee_name', 
            function($row) {
                return '<label class="text-dark">' . $row->first_name . ' ' . $row->middle_name . ' ' . $row->last_name . '</label>';
            })
            ->addColumn('employee_id', fn($row) => '<label class="text-dark">' . $row->emp_id . '</label>')
            ->addColumn('actions', function($row) {
                return '<a href="' . route('member.edit', $row->pk) . '" class="btn btn-sm btn-primary">Edit</a>
                    <a href="' . route('member.show', encrypt($row->pk)) . '" class="btn btn-sm btn-secondary">View</a>
                ';

            })
            ->rawColumns(['employee_name', 'employee_id', 'actions']);
    }

    
    public function query(EmployeeMaster $model): QueryBuilder
    {
        return $model->newQuery();
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('member-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    ->orderBy(1)
                    ->selectStyleSingle()
                    ->buttons([
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    ]);
    }

    
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('employee_name')->title('Employee Name')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('employee_id')->title('Employee ID')->addClass('text-center')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false)->searchable(false),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'Member_' . date('YmdHis');
    }
}
