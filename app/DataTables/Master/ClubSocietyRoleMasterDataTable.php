<?php

namespace App\DataTables\Master;

use App\Models\ClubSocietyRoleMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ClubSocietyRoleMasterDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('club_society_role_name', fn ($row) => e($row->club_society_role_name ?? '-'))
            ->addColumn('action', function ($row) {
                $encryptedPk = encrypt($row->pk);

                $editBtn = '<button type="button" class="cs-action-btn csr-edit-btn" aria-label="Edit club/society role"'
                    . ' data-id="' . $encryptedPk . '"'
                    . ' data-name="' . e($row->club_society_role_name) . '"'
                    . ' data-status="' . (int) $row->active_inactive . '">'
                    . '<i class="bi bi-pencil" aria-hidden="true"></i>'
                    . '<span>Edit</span>'
                    . '</button>';

                $deleteBtn = '<button type="button" class="cs-action-btn cs-action-btn--danger csr-delete-btn" aria-label="Delete club/society role"'
                    . ' data-id="' . $encryptedPk . '"'
                    . ' data-name="' . e($row->club_society_role_name) . '">'
                    . '<i class="bi bi-trash3" aria-hidden="true"></i>'
                    . '<span>Delete</span>'
                    . '</button>';

                return '<div class="d-inline-flex align-items-start justify-content-center cs-action-group" role="group" aria-label="Row actions">'
                    . $editBtn . $deleteBtn
                    . '</div>';
            })
            ->setRowId('pk')
            ->filterColumn('club_society_role_name', function ($query, $keyword) {
                $query->where('club_society_role_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['club_society_role_name', 'action']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(ClubSocietyRoleMaster $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('pk');
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('clubsocietyrolemaster-table')
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
            ]);
        // NOTE: no ->buttons([...]) on purpose. Button::make('reset')/('reload')
        // are not real button types; they throw during init and jQuery then skips
        // every later init.dt handler, so public/js/datatable-global-ui.js never
        // relocates the search box / pagination into the slots below.
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false),
            Column::make('club_society_role_name')->title('Club/Society/Association Role')->orderable(false),
            Column::make('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'ClubSocietyRoleMaster_' . date('YmdHis');
    }
}
