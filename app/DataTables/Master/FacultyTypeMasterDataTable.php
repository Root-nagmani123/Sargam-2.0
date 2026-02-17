<?php

namespace App\DataTables\Master;

use App\Models\FacultyTypeMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class FacultyTypeMasterDataTable extends DataTable
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
            ->addColumn('faculty_type_name', fn($row) => $row->faculty_type_name ?? '-')
            ->addColumn('shot_faculty_type_name', fn($row) => $row->shot_faculty_type_name ?? '-')
            ->addColumn('action', function ($row) {
                $editUrl = route('master.faculty.type.master.edit', ['id' => encrypt($row->pk)]);
                $deleteUrl = route('master.faculty.type.master.delete', ['id' => encrypt($row->pk)]);
                $facultyTypeName = htmlspecialchars($row->faculty_type_name ?? 'N/A', ENT_QUOTES, 'UTF-8');
                $isActive = $row->active_inactive == 1;
                
                $deleteButton = $isActive
                    ? '<a href="javascript:void(0)" class="text-primary d-flex align-items-center gap-1" disabled aria-disabled="true" title="Cannot delete active Faculty Type">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                    </a>'
                    : '<form action="' . $deleteUrl . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this record?\');">
                        <input type="hidden" name="_token" value="' . csrf_token() . '">
                        <input type="hidden" name="_method" value="DELETE">
                        <a href="javascript:void(0)" class="text-primary d-flex align-items-center gap-1" aria-label="Delete faculty type" title="Delete Faculty Type">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                        </a>
                    </form>';

                return '<div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Faculty type actions">
                    <a href="' . $editUrl . '" class="text-primary d-flex align-items-center gap-1" aria-label="Edit faculty type">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">edit</i>
                    </a>
                    ' . $deleteButton . '
                </div>';
            })
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block ms-2">
                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                    data-table="faculty_type_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
            </div>';
            })

            ->setRowId('pk')
            ->setRowClass('text-center')
            ->filterColumn('faculty_type_name', function ($query, $keyword) {
                $query->where('faculty_type_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('shot_faculty_type_name', function ($query, $keyword) {
                $query->where('shot_faculty_type_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['faculty_type_name', 'shot_faculty_type_name', 'action', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\FacultyTypeMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(FacultyTypeMaster $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('facultytypemaster-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->selectStyleSingle()
            ->parameters([
                'responsive' => false,
                'scrollX' => true,
                'autoWidth' => false,
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
            Column::computed('DT_RowIndex')->title('S.No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('faculty_type_name')->title('Faculty Type')->orderable(false)->addClass('text-center'),
            Column::make('shot_faculty_type_name')->title('Short Name')->orderable(false)->addClass('text-center'),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center')
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'FacultyTypeMaster_' . date('YmdHis');
    }
}
