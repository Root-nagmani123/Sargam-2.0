<?php

namespace App\DataTables\Security;

use App\Models\SecVehicleType;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class VehicleTypeDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('vehicle_type', fn ($row) => e($row->vehicle_type ?? '--'))
            ->addColumn('description', fn ($row) => e($row->description ?: '--'))
            ->addColumn('status', function ($row) {
                return (int) $row->active_inactive === 1
                    ? '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>'
                    : '<span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('admin.security.vehicle_type.edit', encrypt($row->pk));
                $deleteUrl = route('admin.security.vehicle_type.delete', encrypt($row->pk));
                $toggleUrl = route('admin.security.vehicle_type.toggle.status', encrypt($row->pk));

                $checked = (int) $row->active_inactive === 1 ? 'checked' : '';

                $editBtn = '<a href="' . $editUrl . '" class="programme-action-btn openEditVehicleType" title="Edit" aria-label="Edit vehicle type">'
                    . '<i class="bi bi-pencil" aria-hidden="true"></i></a>';

                $toggle = '<div class="form-check form-switch programme-action-switch mb-0">'
                    . '<input class="form-check-input vehicle-type-status-toggle" type="checkbox" role="switch"'
                    . ' data-url="' . $toggleUrl . '" ' . $checked . '></div>';

                $deleteBtn = '<form action="' . $deleteUrl . '" method="POST" class="d-inline vehicle-type-delete-form" '
                    . 'onsubmit="return confirm(\'Delete this Vehicle Type?\');">'
                    . csrf_field() . method_field('DELETE')
                    . '<button type="submit" class="programme-action-btn programme-action-btn--danger" title="Delete" aria-label="Delete vehicle type">'
                    . '<i class="bi bi-trash3" aria-hidden="true"></i></button></form>';

                return '<div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">'
                    . $editBtn . $toggle . $deleteBtn . '</div>';
            })
            ->filterColumn('vehicle_type', function ($query, $keyword) {
                $query->where('vehicle_type', 'like', "%{$keyword}%");
            })
            // NB: no filterColumn for `description` — sec_vehicle_type has no such
            // column in this database (searching it raised SQLSTATE 42S22). It always
            // renders as '--'; see the note on getColumns().
            // Sorting: these are addColumn() values, so each needs a real column to
            // ORDER BY. Only vehicle_type and active_inactive actually exist.
            ->orderColumn('vehicle_type', 'sec_vehicle_type.vehicle_type $1')
            ->orderColumn('status', 'sec_vehicle_type.active_inactive $1')
            ->rawColumns(['status', 'action']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(SecVehicleType $model): QueryBuilder
    {
        // No orderBy here on purpose: DataTables appends the user's ORDER BY after
        // this one, so a base ordering would always win and sorting would look dead.
        // The default sort lives in html() as the `order` parameter instead.
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('vehicleType-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->selectStyleSingle()
            ->responsive(true)
            ->parameters([
                'responsive'   => true,
                'scrollX'      => false,
                'autoWidth'    => false,
                'ordering'     => true,
                // Required opt-in: datatable-global-ui.js force-disables ordering on
                // every serverSide table unless this is set, falling back to sorting
                // only the visible page. We sort in SQL, so keep native ordering.
                'sargamServerOrder' => true,
                'searching'    => true,
                'lengthChange' => true,
                'pageLength'   => 10,
                'lengthMenu'   => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                // Default sort: Vehicle Type A-Z (column index 1). The old page used
                // pk desc, but pk is not a visible column so DataTables can't express it.
                'order'        => [[1, 'asc']],
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
            Column::make('vehicle_type')->title('Vehicle Type')->orderable(true),
            // Description is not backed by this database: sec_vehicle_type has no
            // `description` column, so it always renders '--'. Kept visible for parity
            // with the previous page, but NOT searchable or sortable — touching it in
            // SQL raises 42S22. (The table does have `charges`, which nothing shows.)
            Column::make('description')->title('Description')->searchable(false)->orderable(false),
            Column::computed('status')->title('Status')->searchable(false)->orderable(true)->addClass('text-center'),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center')->width(130),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'VehicleType_' . date('YmdHis');
    }
}
