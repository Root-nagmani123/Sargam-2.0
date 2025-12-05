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
            ->addColumn('mobile_no', fn($row) => '<label class="text-dark">' . $row->mobile . '</label>')
            ->addColumn('email', fn($row) => '<label class="text-dark">' . $row->email . '</label>')
            ->addColumn('actions', function($row) {
                $deleteUrl = route('member.destroy', encrypt($row->pk));
                return '<div class="d-flex justify-content-center gap-2">
                    <a href="' . route('member.edit', $row->pk) . '" class="btn btn-sm btn-primary">Edit</a>
                    <a href="' . route('member.show', encrypt($row->pk)) . '" class="btn btn-sm btn-success">View</a>
                    <form action="' . $deleteUrl . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this member?\')">
                        ' . csrf_field() . '
                        ' . method_field('DELETE') . '
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </div>';

            })
            ->filterColumn('employee_name', function ($query, $keyword) {
                $query->where('first_name', 'like', "%{$keyword}%")
                      ->orWhere('middle_name', 'like', "%{$keyword}%")
                      ->orWhere('last_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('mobile_no', function ($query, $keyword) {
                $query->where('mobile', 'like', "%{$keyword}%");
            })
            ->filterColumn('email', function ($query, $keyword) {
                $query->where('email', 'like', "%{$keyword}%");
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->where('first_name', 'like', "%{$searchValue}%")
                            ->orWhere('middle_name', 'like', "%{$searchValue}%")
                            ->orWhere('last_name', 'like', "%{$searchValue}%")
                            ->orWhere('mobile', 'like', "%{$searchValue}%")
                            ->orWhere('email', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->rawColumns(['employee_name', 'employee_id', 'actions', 'mobile_no', 'email']);
    }

    
    public function query(EmployeeMaster $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('member-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    // ->dom('Bfrtip')
                    // ->orderBy(1)
                    ->selectStyleSingle()
                    ->parameters([
                        'order' => [],
                        'ordering' => false,
                        'searching' => true,
                        'lengthChange' => true,
                        'pageLength' => 10,
                        'language' => [
                            'paginate' => [
                                'previous' => ' <i class="material-icons menu-icon material-symbols-rounded"
                                                    style="font-size: 24px;">chevron_left</i>',
                                'next' => '<i class="material-icons menu-icon material-symbols-rounded"
                                                    style="font-size: 24px;">chevron_right</i>'
                            ]
                        ],
                    ])
                    ->buttons([
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        [
                            'text' => 'Reload',
                            'action' => 'function ( e, dt, node, config ) {
                                dt.ajax.reload();
                            }'
                        ]
                    ]);
    }

    
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('#')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('employee_name')->title('Employee Name')->addClass('text-center')->orderable(false)->searchable(true),
            Column::make('employee_id')->title('Employee ID')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('mobile_no')->title('Mobile No')->addClass('text-center')->orderable(false)->searchable(true),
            Column::make('email')->title('Email')->addClass('text-center')->orderable(false)->searchable(true),
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
