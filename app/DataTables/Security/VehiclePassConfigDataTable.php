<?php

namespace App\DataTables\Security;

use App\Models\SecVehiclePassConfig;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class VehiclePassConfigDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $datePrefix = now()->format('Ymd');

        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('vehicle_type', fn ($row) => e($row->vehicleType->vehicle_type ?? '--'))
            ->addColumn('charges', fn ($row) => number_format((float) $row->charges, 2))
            ->addColumn('start_counter', fn ($row) => e($row->start_counter))
            ->addColumn('preview', function ($row) use ($datePrefix) {
                $id = 'VP' . $datePrefix . str_pad((string) $row->start_counter, 4, '0', STR_PAD_LEFT);

                return '<span class="badge rounded-1 bg-info-subtle text-info-emphasis border border-info-subtle">' . e($id) . '</span>';
            })
            ->addColumn('status', function ($row) {
                // Soft status badge — canonical country/index pattern (new-design-index-page.md §3b).
                return (int) $row->active_inactive === 1
                    ? '<span class="status-pill badge bg-success-subtle">Active</span>'
                    : '<span class="status-pill badge bg-danger-subtle">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                // Icon-over-label Edit (opens the AJAX modal via .openEditConfig) · toggle · Delete (§3b).
                $editUrl = route('admin.security.vehicle_pass_config.edit', encrypt($row->pk));
                $deleteUrl = route('admin.security.vehicle_pass_config.delete', encrypt($row->pk));
                $toggleUrl = route('admin.security.vehicle_pass_config.toggle.status', encrypt($row->pk));

                $checked = (int) $row->active_inactive === 1 ? 'checked' : '';

                $editBtn = '<a href="' . $editUrl . '" class="vpc-act vpc-act--edit openEditConfig" title="Edit" aria-label="Edit configuration">'
                    . '<i class="bi bi-pencil-square" aria-hidden="true"></i><span>Edit</span></a>';

                $toggle = '<div class="form-check form-switch m-0">'
                    . '<input class="form-check-input config-status-toggle" type="checkbox" role="switch"'
                    . ' data-url="' . $toggleUrl . '" ' . $checked . '></div>';

                $deleteBtn = '<form action="' . $deleteUrl . '" method="POST" class="d-inline m-0 config-delete-form" '
                    . 'onsubmit="return confirm(\'Delete this configuration?\');">'
                    . csrf_field() . method_field('DELETE')
                    . '<button type="submit" class="vpc-act vpc-act--del" title="Delete" aria-label="Delete configuration">'
                    . '<i class="bi bi-trash3" aria-hidden="true"></i><span>Delete</span></button></form>';

                return '<div class="d-inline-flex align-items-center justify-content-center gap-3" role="group" aria-label="Row actions">'
                    . $editBtn . $toggle . $deleteBtn . '</div>';
            })
            ->filterColumn('vehicle_type', function ($query, $keyword) {
                $query->whereHas('vehicleType', function ($v) use ($keyword) {
                    $v->where('vehicle_type', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('charges', function ($query, $keyword) {
                $query->where('charges', 'like', "%{$keyword}%");
            })
            ->filterColumn('start_counter', function ($query, $keyword) {
                $query->where('start_counter', 'like', "%{$keyword}%");
            })
            ->rawColumns(['preview', 'status', 'action']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(SecVehiclePassConfig $model): QueryBuilder
    {
        return $model->newQuery()->with('vehicleType')->orderBy('pk', 'desc');
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('vehiclePassConfig-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->selectStyleSingle()
            ->responsive(true)
            ->parameters([
                'responsive'   => true,
                'scrollX'      => false,
                'autoWidth'    => false,
                'ordering'     => false,
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
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('vehicle_type')->title('Vehicle Type')->orderable(false),
            Column::make('charges')->title('Charges (₹)')->orderable(false)->addClass('text-center'),
            Column::make('start_counter')->title('Start Counter')->orderable(false)->addClass('text-center'),
            Column::computed('preview')->title('Preview')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center')->width(130),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'VehiclePassConfig_' . date('YmdHis');
    }
}
