<?php

namespace App\DataTables;

use App\Models\UserCredential;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;
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
    $dataTable = new EloquentDataTable($query);

    // ✅ Add custom search for joined/aliased columns
    $dataTable->filterColumn('email_id', function($query, $keyword) {
        $query->whereRaw("LOWER(user_credentials.email_id) like ?", ["%".strtolower($keyword)."%"]);
    });

    $dataTable->filterColumn('user_name', function($query, $keyword) {
        $query->whereRaw("LOWER(user_credentials.user_name) like ?", ["%".strtolower($keyword)."%"]);
    });

    $dataTable->filterColumn('first_name', function($query, $keyword) {
        $query->whereRaw("LOWER(user_credentials.first_name) like ?", ["%".strtolower($keyword)."%"]);
    });

    $dataTable->filterColumn('last_name', function($query, $keyword) {
        $query->whereRaw("LOWER(user_credentials.last_name) like ?", ["%".strtolower($keyword)."%"]);
    });

    // Now continue with your normal columns and rawColumns
    return $dataTable
        ->addIndexColumn()
        ->editColumn('user_name', fn($row) => '<label>'.$row->user_name.'</label>')
        ->editColumn('first_name', fn($row) => '<label>'.$row->first_name.'</label>')
        ->editColumn('last_name', fn($row) => '<label>'.$row->last_name.'</label>')
        ->editColumn('email_id', fn($row) => '<label>'.$row->email_id.'</label>')
        ->editColumn('mobile_no', fn($row) => '<label>'.$row->mobile_no.'</label>')
        ->editColumn('roles', function ($row) {
    $roles = $row->roles ? $row->roles : 'No Roles';
    return '<div class="roles-cell badge bg-success">'.$roles.'</div>';
})
        ->filter(function ($query) {
            $searchValue = request()->input('search.value');

            if (!empty($searchValue)) {
                $query->where(function ($subQuery) use ($searchValue) {
                    $subQuery->whereRaw("LOWER(user_credentials.user_name) like ?", ["%".strtolower($searchValue)."%"])
                        ->orWhereRaw("LOWER(user_credentials.first_name) like ?", ["%".strtolower($searchValue)."%"])
                        ->orWhereRaw("LOWER(user_credentials.last_name) like ?", ["%".strtolower($searchValue)."%"])
                        ->orWhereRaw("LOWER(user_credentials.email_id) like ?", ["%".strtolower($searchValue)."%"]);
                });
            }
        }, true)
        ->addColumn('action', function ($row) {
            $url = route('admin.users.assignRole', encrypt($row->pk));
            return '<a href="'.$url.'" class="btn btn-sm btn-primary">Assign Role</a>';
        })
        ->rawColumns(['user_name','first_name','last_name','email_id','mobile_no','roles','action']);
}





    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\UserCredential $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
 public function query(UserCredential $model): QueryBuilder
{
    return $model->newQuery()
        ->leftJoin('employee_role_mapping as erm', 'erm.user_credentials_pk', '=', 'user_credentials.pk')
        ->leftJoin('user_role_master as urm', 'urm.pk', '=', 'erm.user_role_master_pk')
        ->select(
            'user_credentials.pk',
            DB::raw('MIN(user_credentials.user_name) as user_name'),
            DB::raw('MIN(user_credentials.first_name) as first_name'),
            DB::raw('MIN(user_credentials.last_name) as last_name'),
            DB::raw('MIN(user_credentials.email_id) as email_id'),
            DB::raw('MIN(user_credentials.mobile_no) as mobile_no'),
            DB::raw("GROUP_CONCAT(urm.user_role_display_name ORDER BY urm.pk SEPARATOR ', ') as roles")
        )
        ->groupBy('user_credentials.pk');
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
        Column::computed('DT_RowIndex')->title('#')->addClass('text-center')->orderable(false)->searchable(false),

        Column::make('user_name')->title('Username')->addClass('text-center')->orderable(false)->searchable(true),
        Column::make('first_name')->title('First Name')->addClass('text-center')->orderable(false)->searchable(true),
        Column::make('last_name')->title('Last Name')->addClass('text-center')->orderable(false)->searchable(true),
        Column::make('email_id')->title('Email')->addClass('text-center')->orderable(false)->searchable(true),

        Column::make('mobile_no')->title('Mobile No')->addClass('text-center')->orderable(false)->searchable(false),

        Column::make('roles')
            ->title('Assigned Roles')
            ->addClass('text-center')
            ->orderable(false)
            ->searchable(false),

        Column::make('action')
            ->title('Action')
            ->addClass('text-center')
            ->orderable(false)
            ->searchable(false),
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
