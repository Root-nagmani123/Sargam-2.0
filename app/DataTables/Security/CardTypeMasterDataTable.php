<?php

namespace App\DataTables\Security;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CardTypeMasterDataTable extends DataTable
{
    private const TABLE = 'sec_id_cardno_master';

    /** Whether the status column exists (older DBs may not have run the migration). */
    private function hasStatus(): bool
    {
        return Schema::hasColumn(self::TABLE, 'active_inactive');
    }

    /**
     * Build DataTable class.
     */
    public function dataTable($query)
    {
        $hasStatus = $this->hasStatus();

        return datatables()
            ->query($query)
            ->addIndexColumn()
            ->addColumn('sec_card_name', fn ($row) => e($row->sec_card_name ?? '-'))
            ->addColumn('status', function ($row) use ($hasStatus) {
                if (! $hasStatus) {
                    return '<span class="badge rounded-1 bg-secondary">N/A</span>';
                }

                return (int) ($row->active_inactive ?? 1) === 1
                    ? '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>'
                    : '<span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>';
            })
            ->addColumn('action', function ($row) use ($hasStatus) {
                $editUrl = route('admin.security.idcard_card_type.edit', encrypt($row->pk));
                $deleteUrl = route('admin.security.idcard_card_type.delete', encrypt($row->pk));

                // Active card types can't be deleted (must be set Inactive first).
                $isActive = $hasStatus ? ((int) ($row->active_inactive ?? 1) === 1) : false;
                $canDelete = ! $hasStatus || ! $isActive;

                $editBtn = '<a href="' . $editUrl . '" class="programme-action-btn openEditCardType" title="Edit" aria-label="Edit card type">'
                    . '<i class="bi bi-pencil" aria-hidden="true"></i></a>';

                $toggle = '';
                if ($hasStatus) {
                    $checked = $isActive ? 'checked' : '';
                    $toggle = '<div class="form-check form-switch programme-action-switch mb-0">'
                        . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                        . ' data-table="' . self::TABLE . '" data-column="active_inactive"'
                        . ' data-id="' . $row->pk . '" data-id_column="pk" ' . $checked . '></div>';
                }

                if ($canDelete) {
                    $deleteBtn = '<form action="' . $deleteUrl . '" method="POST" class="d-inline cardtype-delete-form" '
                        . 'onsubmit="return confirm(\'Delete this Card Type?\');">'
                        . csrf_field() . method_field('DELETE')
                        . '<button type="submit" class="programme-action-btn programme-action-btn--danger" title="Delete" aria-label="Delete card type">'
                        . '<i class="bi bi-trash3" aria-hidden="true"></i></button></form>';
                } else {
                    $deleteBtn = '<button type="button" class="programme-action-btn programme-action-btn--danger is-disabled" disabled '
                        . 'title="Set status to Inactive before delete" aria-label="Delete disabled">'
                        . '<i class="bi bi-trash3" aria-hidden="true"></i></button>';
                }

                return '<div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">'
                    . $editBtn . $toggle . $deleteBtn . '</div>';
            })
            ->filterColumn('sec_card_name', function ($query, $keyword) {
                $query->where('sec_card_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['sec_card_name', 'status', 'action']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query()
    {
        $columns = ['pk', 'sec_card_name'];
        if ($this->hasStatus()) {
            $columns[] = 'active_inactive';
        }

        return DB::table(self::TABLE)
            ->select($columns)
            ->orderBy('sec_card_name');
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('cardtypemaster-table')
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
            Column::make('sec_card_name')->title('Card Type Name')->orderable(false),
            Column::computed('status')->title('Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center')->width(120),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'CardTypeMaster_' . date('YmdHis');
    }
}
