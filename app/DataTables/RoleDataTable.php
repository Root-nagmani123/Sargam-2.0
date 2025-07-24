<?php

namespace App\DataTables;

use App\Models\UserRoleMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class RoleDataTable extends DataTable
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
            ->editColumn('USER_ROLE_NAME', fn($row) => '<label class="text-dark">' . $row->USER_ROLE_NAME . '</label>')
            ->editColumn('USER_ROLE_DISPLAY_NAME', fn($row) => '<label class="text-dark">' . $row->USER_ROLE_DISPLAY_NAME . '</label>')

            // Filtering columns
            ->filterColumn('USER_ROLE_NAME', function ($query, $keyword) {
                $query->where('USER_ROLE_NAME', 'like', "%{$keyword}%");
            })
            ->filterColumn('USER_ROLE_DISPLAY_NAME', function ($query, $keyword) {
                $query->where('USER_ROLE_DISPLAY_NAME', 'like', "%{$keyword}%");
            })

            ->rawColumns(['USER_ROLE_NAME', 'USER_ROLE_DISPLAY_NAME']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Role $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(UserRoleMaster $model): QueryBuilder
    {
        return $model->orderBy('PK')->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('role-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    // ->orderBy(1)
                    ->selectStyleSingle()
                    ->parameters([
                        'order' => [],
                    ])
                    ->buttons([
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')
                ->title('#')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(false),

            Column::make('USER_ROLE_NAME')
                ->title('User Role Name')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(true),

            Column::make('USER_ROLE_DISPLAY_NAME')
                ->title('User Role Display Name')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(true)
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'Role_' . date('YmdHis');
    }
}
