<?php

namespace App\DataTables;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class UserCredentialsDataTable extends DataTable
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
            ->editColumn('user_name', fn($row) => '<label class="text-dark">' . $row->user_name . '</label>')
            ->editColumn('first_name', fn($row) => '<label class="text-dark">' . $row->first_name . '</label>')
            ->editColumn('last_name', fn($row) => '<label class="text-dark">' . $row->last_name . '</label>')
            ->editColumn('email', fn($row) => '<label class="text-dark">' . $row->email_id . '</label>')
            ->editColumn('mobile_no', fn($row) => '<label class="text-dark">' . $row->mobile_no . '</label>')
            ->editColumn('role', function($row) {

                $roleName = $row->getRoleNames()->all();
                $array = [];
                foreach ($roleName as $role) {
                    $array[] = '<span class="badge bg-primary">' . $role . '</span>';
                }
                return implode('', $array);
            })

            // Filtering columns
            ->filterColumn('user_name', function ($query, $keyword) {
                $query->where('user_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('first_name', function ($query, $keyword) {
                $query->where('first_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('last_name', function ($query, $keyword) {
                $query->where('last_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('email', function ($query, $keyword) {
                $query->where('email_id', 'like', "%{$keyword}%");
            })
            ->filterColumn('mobile_no', function ($query, $keyword) {
                $query->where('mobile_no', 'like', "%{$keyword}%");
            })
            ->addColumn('action', function ($row) {
                return '<a href="' . route('admin.users.show', $row->pk) . '" class="btn btn-sm btn-primary">View</a>
                <a href="' . route('admin.users.edit', $row->pk) . '" class="btn btn-sm btn-primary">Edit</a>';
            })
            ->rawColumns(['user_name', 'first_name', 'last_name', 'email', 'mobile_no', 'action', 'role']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\UserCredential $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(User $model): QueryBuilder
    {
        return $model->with('roles')->orderBy('pk')->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('usercredentials-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->orderBy(0)
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
                Button::make('reload'),
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

            Column::make('user_name')
                ->title('Username')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(true),

            Column::make('first_name')
                ->title('First Name')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(true),

            Column::make('last_name')
                ->title('Last Name')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(true),

            Column::make('email')
                ->title('Email')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(true),

            Column::make('mobile_no')
                ->title('Mobile No')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(true),
            Column::make('role')
                ->title('Role')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(false),
            Column::make('action')
                ->title('Action')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(true),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'UserCredentials_' . date('YmdHis');
    }
}
