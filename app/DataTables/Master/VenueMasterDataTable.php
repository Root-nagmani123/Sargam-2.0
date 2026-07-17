<?php

namespace App\DataTables\Master;

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
            ->addColumn('venue_name', fn($row) => $row->venue_name ?? '-')
            ->addColumn('venue_short_name', fn($row) => $row->venue_short_name ?? '-')
            ->addColumn('description', fn($row) => $row->description ?? '-')
            ->addColumn('status', function ($row) {
                $checked = (int) $row->active_inactive === 1 ? 'checked' : '';
                return '<div class="form-check form-switch">
                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                        data-table="venue_master" data-column="active_inactive"
                        data-id="' . $row->venue_id . '" data-id_column="venue_id" ' . $checked . '>
                </div>';
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('Venue-Master.edit', $row->venue_id);
                $isActive = (int) $row->active_inactive === 1;
                $csrf = csrf_token();

                $editBtn = '<a href="' . $editUrl . '" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" aria-label="Edit venue">'
                        . '<span class="material-symbols-rounded fs-6" aria-hidden="true">edit</span>'
                        . '<span class="d-none d-md-inline">Edit</span>'
                        . '</a>';

                if ($isActive) {
                    $deleteBtn = '<button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"'
                            . ' disabled aria-disabled="true" title="Cannot delete active venue">'
                            . '<span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>'
                            . '<span class="d-none d-md-inline">Delete</span>'
                            . '</button>';
                } else {
                    $deleteUrl = route('Venue-Master.destroy', $row->venue_id);
                    $deleteBtn = '<form action="' . $deleteUrl . '" method="POST" class="d-inline delete-form"'
                            . ' onsubmit="return confirm(\'Are you sure you want to delete this venue?\');">'
                            . '<input type="hidden" name="_token" value="' . $csrf . '">'
                            . '<input type="hidden" name="_method" value="DELETE">'
                            . '<button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" aria-label="Delete venue">'
                            . '<span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>'
                            . '<span class="d-none d-md-inline">Delete</span>'
                            . '</button>'
                            . '</form>';
                }

                return '<div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Venue actions">'
                        . $editBtn . $deleteBtn . '</div>';
            })
            ->orderColumn('venue_name', function ($query, $order) {
                $query->orderBy('venue_name', $order);
            })
            ->orderColumn('venue_short_name', function ($query, $order) {
                $query->orderBy('venue_short_name', $order);
            })
            ->orderColumn('description', function ($query, $order) {
                $query->orderBy('description', $order);
            })
            ->filterColumn('venue_name', function ($query, $keyword) {
                $query->where('venue_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('venue_short_name', function ($query, $keyword) {
                $query->where('venue_short_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('description', function ($query, $keyword) {
                $query->where('description', 'like', "%{$keyword}%");
            })
            ->setRowId('venue_id')
            ->rawColumns(['status', 'action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\VenueMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(VenueMaster $model): QueryBuilder
    {
        $query = $model->newQuery();

        // Default newest-first, but ONLY when the user hasn't clicked a column
        // to sort — otherwise this base order would dominate (venue_id is
        // unique, so a requested secondary sort would never take effect).
        if (empty(request('order'))) {
            $query->orderBy('venue_id', 'desc');
        }

        return $query;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('venuemaster-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->selectStyleSingle()
                    ->responsive(true)
                    ->parameters([
                        'responsive'   => true,
                        'scrollX'      => false,
                        'autoWidth'    => false,
                        'ordering'     => true,
                        // Keep DataTables' native server-side ordering (see
                        // datatable-global-ui.js): clicking a header re-queries and
                        // sorts the FULL dataset, not just the visible page.
                        'sargamServerOrder' => true,
                        'searching'    => true,
                        'lengthChange' => true,
                        'pageLength'   => 10,
                        'lengthMenu'   => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                        'order'        => [],
                        'language'     => [
                            'search'            => '',
                            'searchPlaceholder' => 'Search',
                            'paginate'          => [
                                'previous' => '‹',
                                'next'     => '›',
                            ],
                            'lengthMenu'   => 'Showing _MENU_',
                            'info'         => 'of _TOTAL_ items',
                            'infoEmpty'    => 'of 0 items',
                            'infoFiltered' => 'of _MAX_ items',
                        ],
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
            Column::make('venue_name')->title('Venue Name')->orderable(true),
            Column::make('venue_short_name')->title('Short Name')->orderable(true),
            Column::make('description')->title('Description')->orderable(true),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center'),
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
