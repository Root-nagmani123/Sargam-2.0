<?php

namespace App\DataTables;

use App\Models\MDODutyTypeMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class MDODutyTypeMasterDataTable extends DataTable
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
            ->setRowId('pk')
            // Escaped here: the column stays in rawColumns below.
            ->editColumn('mdo_duty_type_name', fn($row) => e($row->mdo_duty_type_name ?? ''))
            ->filterColumn('mdo_duty_type_name', function ($query, $keyword) {
                $query->where('mdo_duty_type_name', 'like', "%{$keyword}%");
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->where('mdo_duty_type_name', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            // Status: display-only soft badge. The switch lives in the Action stack.
            ->addColumn('status', fn ($row) => view('admin.master.partials.grid-status', [
                'active' => (int) $row->active_inactive === 1,
            ])->render())

            // Action: Edit · status switch · Delete (docs/new-design-index-page.md §3b).
            // .edit-btn / .plain-status-toggle / .delete-btn and their data-* are
            // the hooks the index page's own handlers read — keep them.
            ->addColumn('actions', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                $html = view('admin.master.partials.grid-actions', [
                    'name'   => $row->mdo_duty_type_name ?? '',
                    'edit'   => [
                        'class' => 'edit-btn',
                        'attrs' => [
                            'data-id'                 => $row->pk,
                            'data-mdo_duty_type_name' => $row->mdo_duty_type_name,
                            'data-active_inactive'    => $row->active_inactive,
                        ],
                    ],
                    'toggle' => [
                        'active' => $isActive,
                        'table'  => 'mdo_duty_type_master',
                        'column' => 'active_inactive',
                        'id'     => $row->pk,
                        'class'  => 'plain-status-toggle',
                        // Own route via the page's .plain-status-toggle handler — no
                        // global .status-toggle, or custom.js fires a second request.
                        'global' => false,
                    ],
                    // The grid never offered Delete on an active row — keep that rule.
                    'delete' => $isActive
                        ? ['disabled' => true, 'reason' => 'Active duty types cannot be deleted. Deactivate it first.']
                        : ['class' => 'delete-btn', 'attrs' => ['data-id' => $row->pk]],
                ])->render();

                return $html;
            })
            ->rawColumns(['mdo_duty_type_name', 'status', 'actions']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\MDODutyTypeMaster $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(MDODutyTypeMaster $model): QueryBuilder
    {
        // Show all records (both active and inactive)
        return $model->newQuery()->orderBy('pk', 'desc');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('mdodutytypemaster-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            // ->orderBy(1)
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
                    ['orderable' => false, 'targets' => 0],
                    ['orderable' => false, 'targets' => 1],
                    ['orderable' => false, 'targets' => 2],
                    ['orderable' => false, 'targets' => 3],
                ],
                'language' => [
                    'paginate' => [
                        'previous' => ' <i class="material-icons menu-icon material-symbols-rounded"
                                                    style="font-size: 24px;">chevron_left</i>',
                        'next' => '<i class="material-icons menu-icon material-symbols-rounded"
                                                    style="font-size: 24px;">chevron_right</i>'
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
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('text-nowrap')->width('5.5rem')->orderable(false)->searchable(false),
            Column::make('mdo_duty_type_name')->title('Duty Type Name')->orderable(false)->searchable(true),
            Column::computed('status')->title('Status')->addClass('text-nowrap')->width('8rem')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Action')->addClass('text-nowrap')->width('13rem')->orderable(false)->searchable(false),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'MDODutyTypeMaster_' . date('YmdHis');
    }
}
