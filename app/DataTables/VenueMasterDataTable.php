<?php

namespace App\DataTables;

use App\Models\VenueMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class VenueMasterDataTable extends DataTable
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
            ->setRowId('venue_id')
            ->editColumn('venue_name', fn($row) => '<label class="text-dark">' . e($row->venue_name) . '</label>')
            ->editColumn('venue_short_name', fn($row) => e($row->venue_short_name))
            ->editColumn('description', fn($row) => e($row->description ?? '—'))
            ->filterColumn('venue_name', function ($query, $keyword) {
                $query->where('venue_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('venue_short_name', function ($query, $keyword) {
                $query->where('venue_short_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('description', function ($query, $keyword) {
                $query->where('description', 'like', "%{$keyword}%");
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');
                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->where('venue_name', 'like', "%{$searchValue}%")
                            ->orWhere('venue_short_name', 'like', "%{$searchValue}%")
                            ->orWhere('description', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block">
                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                        data-table="venue_master"
                        data-column="active_inactive"
                        data-id="' . (int) $row->venue_id . '"
                        data-id_column="venue_id"
                        ' . $checked . '>
                </div>';
            })
            ->addColumn('actions', function ($row) {
                $editUrl = route('Venue-Master.edit', $row->venue_id);
                $deleteUrl = route('Venue-Master.destroy', $row->venue_id);
                $csrf = csrf_token();

                $html = '<div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Venue actions">';
                $html .= '<a href="' . $editUrl . '" class="d-flex align-items-center gap-1 text-primary" aria-label="Edit venue">';
                $html .= '<span class="material-symbols-rounded fs-6" aria-hidden="true">edit</span></a>';

                if ($row->active_inactive == 1) {
                    $html .= '<a href="javascript:void(0);" class="d-flex align-items-center gap-1 text-primary" disabled aria-disabled="true" title="Cannot delete active venue">';
                    $html .= '<span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span></a>';
                } else {
                    $html .= '<form action="' . $deleteUrl . '" method="POST" class="d-inline delete-form" onsubmit="return confirm(\'Are you sure you want to delete this venue?\');">';
                    $html .= '<input type="hidden" name="_token" value="' . $csrf . '">';
                    $html .= '<input type="hidden" name="_method" value="DELETE">';
                    $html .= '<button type="submit" class="btn btn-link p-0 border-0 d-flex align-items-center gap-1 text-primary" aria-label="Delete venue">';
                    $html .= '<span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span></button>';
                    $html .= '</form>';
                }
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['venue_name', 'venue_short_name', 'description', 'status', 'actions']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\VenueMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(VenueMaster $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('venue_id', 'desc');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('venue-master-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'order' => [],
                'responsive' => true,
                'autoWidth' => false,
                'scrollX' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'lengthMenu' => [[10, 25, 50, 100], [10, 25, 50, 100]],
                'buttons' => ['excel', 'csv', 'pdf', 'print', 'reset', 'reload'],
                'columnDefs' => [
                    ['orderable' => false, 'targets' => [0, 3, 4, 5]],
                ],
                'language' => [
                    'paginate' => [
                        'previous' => ' <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">chevron_left</i>',
                        'next' => '<i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">chevron_right</i>'
                    ]
                ],
            ])
            ->selectStyleSingle()
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
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('venue_name')->title('Venue Name')->addClass('text-center')->orderable(true)->searchable(true),
            Column::make('venue_short_name')->title('Short Name')->addClass('text-center')->orderable(true)->searchable(true),
            Column::make('description')->title('Description')->addClass('text-center')->orderable(false)->searchable(true),
            Column::computed('actions')->title('Action')->addClass('text-center')->orderable(false)->searchable(false),
            Column::computed('status')->title('Status')->addClass('text-center')->orderable(false)->searchable(false),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'VenueMaster_' . date('YmdHis');
    }
}
