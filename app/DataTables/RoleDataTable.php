<?php

namespace App\DataTables;

use Spatie\Permission\Models\Role;
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
            ->editColumn('name', fn($row) => '<label class="text-dark">' . $row->name . '</label>')
            
            ->editColumn('permissions', function ($row) {
                $badges = [];

                foreach ($row->permissions as $permission) {
                    $group = e($permission->permission_group);
                    $subGroup = e($permission->permission_sub_group);
                    $name = e($permission->display_name);

                    $badges[] = <<<HTML
                        <div class="mb-1">
                            <span class="text-muted fw-semibold">{$group}</span>
                            <span class="text-muted"> &raquo; </span>
                            <span class="text-muted fw-semibold">{$subGroup}</span>
                            <span class="text-muted"> &raquo; </span>
                            <span class="badge bg-primary">{$name}</span>
                        </div>
                    HTML;
                }

                return implode('', $badges);
            })


            ->editColumn('action', function($row){
                if(auth()->user()->can('admin.roles.edit')) {
                    return '<a href="' . route('admin.roles.edit', $row->id) . '" class="btn btn-primary btn-sm">Edit</a>';
                }
                return '';
            })
            ->filterColumn('name', function ($query, $keyword) {
                $query->where('name', 'like', "%{$keyword}%");
            })
            ->filterColumn('permissions', function ($query, $keyword) {
                $query->whereHas('permissions', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            

            ->rawColumns(['name', 'permissions', 'action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Role $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Role $model): QueryBuilder
    {
        return $model->with('permissions')->orderBy('id', 'desc')->newQuery();
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

            Column::make('name')
                ->title('Name')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(true),


            Column::make('permissions')
                ->title('Permissions')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(true),
            Column::computed('action')
                ->addClass('text-center')
                ->exportable(false)
                ->printable(false),
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
