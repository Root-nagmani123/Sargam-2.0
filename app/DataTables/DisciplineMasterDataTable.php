<?php
namespace App\DataTables;

use App\Models\DisciplineMaster;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;

class DisciplineMasterDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->editColumn('discipline_name', fn($row) => $row->discipline_name ?? 'N/A')
            ->editColumn('mark_deduction', fn($row) => $row->mark_deduction ?? '0')

            ->addColumn('status', function ($row) {
                 return '<div class="form-check form-switch d-inline-block">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                data-table="discipline_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . ($row->active_inactive == 1 ? 'checked' : '') . '>
                        </div>';
            })

            ->addColumn('actions', function ($row) {
                $edit = route('master.discipline.edit', encrypt($row->pk));
                $delete = route('master.discipline.delete', encrypt($row->pk));
if($row->active_inactive == 1){
    return '
                <a href="'.$edit.'" title="Edit">
                    <i class="material-icons">edit</i>
                </a>
              
                    <button style="border:none;background:none " disabled title="Delete">
                        <i class="material-icons text-danger">delete</i>
                    </button>';
}else{
return '
                <a href="'.$edit.'" title="Edit">
                    <i class="material-icons">edit</i>
                </a>

                <form action="'.$delete.'" method="POST" style="display:inline">
                    '.csrf_field().method_field('DELETE').'
                    <button onclick="return confirm(\'Delete?\')" style="border:none;background:none">
                        <i class="material-icons text-danger">delete</i>
                    </button>
                </form>';
}
                
            })

            ->rawColumns(['status','actions']);
    }

    public function query(DisciplineMaster $model): Builder
    {
        $data_course_id =  get_Role_by_course();
        $query = $model->newQuery()
            ->leftJoin('course_master as cm', 'cm.pk', '=', 'discipline_master.course_master_pk')
            ->select('discipline_master.*', 'cm.course_name')
            ->orderBy('discipline_master.pk', 'desc');

        if (!empty($data_course_id)) {
            $query->whereIn('discipline_master.course_master_pk', $data_course_id);
        }

        return $query;
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No'),
            Column::make('course_name')->title('Course'),
            Column::make('discipline_name')->title('Discipline'),
            Column::make('mark_deduction')->title('Mark Deduction'),
            Column::computed('status')->title('Status'),
            Column::computed('actions')->title('Actions'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('discipline-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->pageLength(10);
    }
}
