<?php

namespace App\DataTables\Master;

use App\Models\DesignationMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DesignationMasterDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param  QueryBuilder  $query  Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('designation_name', fn ($row) => $row->designation_name ?? '-')
            // Status: soft badge, display only. data-order lets a client-side sort
            // order by state (docs/new-design-index-page.md §3b).
            ->addColumn('status', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return '<span class="status-pill badge rounded-1 ' . ($isActive ? 'bg-success-subtle' : 'bg-danger-subtle') . '"'
                    . ' data-order="' . (int) $isActive . '">'
                    . ($isActive ? 'Active' : 'Inactive')
                    . '</span>';
            })
            ->addColumn('action', function ($row) {
                $isActive = (int) $row->active_inactive === 1;
                $checked = $isActive ? 'checked' : '';
                // The caption names the ACTION, not the state — the badge one column
                // over already shows the state (§3b).
                $toggleLabel = $isActive ? 'Deactivate' : 'Activate';

                // Edit opens the page's modal rather than navigating: the row carries
                // its own values, so no second request is needed to populate the form.
                // The switch is the shared .status-toggle: public/admin_assets/js/custom.js
                // confirms, POSTs to routes.toggleStatus and reloads the grid, so this
                // page writes no toggle JS of its own. NOTE the deliberate absence of a
                // .form-check.form-switch wrapper — it would yank the input -2.375rem
                // left of its caption (§3b, trap 1).
                return '
                <div class="mst-act-group" role="group" aria-label="Row actions">
                    <button type="button" class="mst-act mst-act--edit dsg-edit-btn" title="Edit"
                        data-id="' . e(encrypt($row->pk)) . '"
                        data-name="' . e((string) $row->designation_name) . '">
                        <span class="mst-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                        <span class="mst-act__label">Edit</span>
                    </button>
                    <label class="mst-act mst-act--toggle" title="' . $toggleLabel . ' designation">
                        <span class="mst-act__icon">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                data-table="designation_master" data-column="active_inactive"
                                data-id="' . (int) $row->pk . '" ' . $checked . '>
                        </span>
                        <span class="mst-act__label">' . $toggleLabel . '</span>
                    </label>
                </div>';
            })
            ->setRowId('pk')
            ->filterColumn('designation_name', function ($query, $keyword) {
                $query->where('designation_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action', 'status']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(DesignationMaster $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            // Was 'departmentmaster-table' — a copy-paste from the Department grid.
            // Two pages sharing one table id makes every id-keyed hook ambiguous:
            // the search/footer slots, the colvis storage key and any init.dt
            // filter all match the wrong grid.
            ->setTableId('designationmaster-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            // No `dom` here on purpose: the page uses the shared programme-dt
            // chrome, and public/js/datatable-global-ui.js relocates the search
            // box / pagination / count into the #dsgDtSearch and #dsgDtFooter
            // slots declared in admin/master/designation/index.blade.php.
            ->selectStyleSingle()
            // Responsive would collapse the Actions column into a "+" detail row.
            ->responsive(false)
            // NOTE: no ->buttons() here. Button::make('reset') / ('reload') are not
            // real button types; they throw during init, and jQuery then skips every
            // later init.dt handler — which silently kills the global enhancer, so
            // the search box, pagination and item count never get relocated.
            ->parameters([
                'responsive' => false,
                'scrollX' => false,
                'autoWidth' => false,
                'order' => [],
                'paging' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'pagingType' => 'full_numbers',
                'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                'language' => [
                    'search' => '',
                    'searchPlaceholder' => 'Search',
                    'emptyTable' => 'No designations found.',
                    'zeroRecords' => 'No matching designations found.',
                    'lengthMenu' => 'Showing _MENU_',
                    'info' => 'of _TOTAL_ items',
                    'infoEmpty' => 'of 0 items',
                    'infoFiltered' => 'of _MAX_ items',
                    'paginate' => [
                        'previous' => '&lsaquo;',
                        'next' => '&rsaquo;',
                    ],
                ],
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        // Status before Action: the badge is the display, the switch beside Edit
        // is the control (docs/new-design-index-page.md §3b).
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('designation_name')->title('Designation Name')->orderable(false)->addClass('text-start'),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center')
                ->exportable(false)->printable(false),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center')
                ->exportable(false)->printable(false),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'DesignationMaster_' . date('YmdHis');
    }
}
