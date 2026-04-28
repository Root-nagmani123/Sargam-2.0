<?php

namespace App\DataTables;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use App\Models\FacultyMaster;

class FacultyDataTable extends DataTable
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
            ->addColumn('faculty_code', function($row) {
                return $row->faculty_code ?? '';
            })
            ->addColumn('full_name', function($row) {
                return $row->full_name ?? '';
            })
            ->addColumn('faculty_email', function($row) {
                return $row->email_id ?? '';
            })
            ->addColumn('mobile_number', function($row) {
                return $row->mobile_no ?? '';
            })
            ->addColumn('action', function ($row) {
                $id = encrypt($row->pk);
                $csrf = csrf_token();

                $editUrl = route('faculty.edit', ['id' => $id]);
                $viewUrl = route('faculty.show', ['id' => $id]);
                $deleteUrl = route('faculty.destroy', ['id' => $id]);
                $isActive = $row->active_inactive == 1;
                $disabledAttr = $isActive ? 'disabled' : '';
                $deleteTitle = $isActive ? 'Deactivate faculty first to enable deletion' : 'Delete';
                $deleteStyle = $isActive ? 'opacity:0.5;cursor:not-allowed;' : 'cursor:pointer;';

                return '
                    <div class="d-flex align-items-center gap-2" style="white-space:nowrap;">
                        <a href="'.$editUrl.'" class="btn bg-transparent border-0 p-0 text-primary" title="Edit">
                            <i class="material-icons" style="font-size:20px;">edit</i>
                        </a>
                        <a href="'.$viewUrl.'" class="btn bg-transparent border-0 p-0 text-info" title="View">
                            <i class="material-icons" style="font-size:20px;">visibility</i>
                        </a>
                        <button type="button" class="btn bg-transparent border-0 p-0 text-danger delete-faculty-btn"
                            data-url="'.$deleteUrl.'"
                            data-name="'.htmlspecialchars($row->full_name, ENT_QUOTES).'"
                            data-token="'.$csrf.'"
                            title="'.$deleteTitle.'" '.$disabledAttr.' style="'.$deleteStyle.'">
                            <i class="material-icons" style="font-size:20px;">delete</i>
                        </button>
                    </div>
                ';
            })
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return "
                <div class='form-check form-switch d-inline-block'>
                    <input class='form-check-input status-toggle' type='checkbox' role='switch'
                        data-table='faculty_master'
                        data-column='active_inactive'
                        data-id='{$row->pk}' {$checked}>
                </div>
                ";
            })
            ->filterColumn('full_name', function ($query, $keyword) {
                $query->where('full_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('faculty_code', function ($query, $keyword) {
                $query->where('faculty_code', 'like', "%{$keyword}%");
            })
            ->filterColumn('faculty_email', function ($query, $keyword) {
                $query->where('email_id', 'like', "%{$keyword}%");
            })
            ->filterColumn('mobile_number', function ($query, $keyword) {
                $query->where('mobile_no', 'like', "%{$keyword}%");
            })

        ->addColumn('last_update', function($row) {
                return $row->last_update ? \Carbon\Carbon::parse($row->last_update)->format('d-m-Y H:i') : 'N/A';
            })
            ->addColumn('created_by', function($row) {
                return $row->createdByUser?->name ?? 'N/A';
            })        

            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->where('full_name', 'like', "%{$searchValue}%")
                            ->orWhere('mobile_no', 'like', "%{$searchValue}%")
                            ->orWhere('faculty_code', 'like', "%{$searchValue}%")
                            ->orWhere('email_id', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->rawColumns(['action', 'status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Faculty $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(FacultyMaster $model): QueryBuilder
    {
        // return $model->newQuery();
        return $model->orderBy('pk', 'desc')->newQuery();
      
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('faculty-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    ->orderBy(1)
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
            Column::computed('DT_RowIndex')->title('S.No.'),
            Column::make('faculty_code')
                ->title('Faculty Code')
                ->searchable(true)
                ->orderable(false),
            Column::make('full_name')
                ->title('Faculty Name')
                ->searchable(true)
                ->orderable(false),
            Column::make('faculty_email')
                ->title('Faculty Email')
                ->searchable(true)
                ->orderable(false),
            Column::make('mobile_number')
                ->title('Mobile Number')
                ->searchable(true)
                ->orderable(false),

            Column::make('last_update')
                ->title('Last Updated')
                ->addClass('text-center')
                ->searchable(false)
                ->orderable(false),
            Column::make('created_by')
                ->title('Updated By')
                ->addClass('text-center')
                ->searchable(false)
                ->orderable(false),    
            Column::computed('status')
                ->exportable(false)
                ->printable(false)
                ->addClass('text-center'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->addClass('text-center')
                ->width(120)
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'Faculty_' . date('YmdHis');
    }
}
