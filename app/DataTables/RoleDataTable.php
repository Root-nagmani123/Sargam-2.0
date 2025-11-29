<?php

namespace App\DataTables;

use App\Models\UserRoleMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Crypt;

class RoleDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->editColumn('user_role_name', fn($row) =>
                '<label class="text-dark">'.$row->user_role_name.'</label>'
            )

            ->editColumn('user_role_display_name', fn($row) =>
                '<label class="text-dark">'.$row->user_role_display_name.'</label>'
            )

            // ✅ STATUS TOGGLE
            ->editColumn('STATUS', function ($row) {

                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '
                    
                     <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="user_role_master" data-column="active_inactive"
                                                data-id="'. $row->pk .'"
                                                '. ($row->active_inactive == 1 ? 'checked' : '') .'>
                                        </div>
                ';
            })

            // ✅ ACTION ICONS (Edit + Delete)
            ->addColumn('action', function ($row) {

                $id = Crypt::encrypt($row->pk);

                $editUrl = route('admin.roles.edit', $id);
                $deleteUrl = route('admin.roles.destroy', $id);

                return '
                    <a href="'.$editUrl.'" class="text-primary me-2">
                        <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">edit</i>
                    </a>

                    
                    <form class="delete-role-form"
      action="'.$deleteUrl.'" method="POST" style="display:inline">
    '.csrf_field().method_field("DELETE").'
    <button type="submit" class="btn btn-sm btn-danger">
        <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">delete</i>
    </button>
</form>

                ';
            })

            ->rawColumns(['user_role_name', 'user_role_display_name', 'STATUS', 'action']);
    }

    public function query(UserRoleMaster $model): QueryBuilder
    {
        return $model->orderBy('pk')->newQuery();
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('role-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters(['order' => []])
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
            Column::computed('DT_RowIndex')
                ->title('#')
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center'),

            Column::make('user_role_name')
                ->title('Role Name')
                ->addClass('text-center'),

            Column::make('user_role_display_name')
                ->title('Display Name')
                ->addClass('text-center'),

            // STATUS Column
            Column::make('STATUS')
                ->title('Status')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(false),

            // ACTION ICONS
            Column::computed('action')
                ->title('Action')
                ->addClass('text-center')
                ->exportable(false)
                ->printable(false)
        ];
    }

    protected function filename(): string
    {
        return 'Role_' . date('YmdHis');
    }
}
