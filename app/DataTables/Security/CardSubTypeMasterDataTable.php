<?php

namespace App\DataTables\Security;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CardSubTypeMasterDataTable extends DataTable
{
    private const TABLE = 'sec_id_cardno_config_map';

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
            ->addColumn('card_type', fn ($row) => e($row->sec_card_name ?? '-'))
            ->addColumn('employee_category', function ($row) {
                return match ($row->card_name) {
                    'p'     => '<span class="badge rounded-1 bg-primary">Permanent</span>',
                    'c'     => '<span class="badge rounded-1 bg-info">Contractual</span>',
                    default => '<span class="badge rounded-1 bg-secondary">' . e($row->card_name) . '</span>',
                };
            })
            ->addColumn('sub_type', fn ($row) => e($row->config_name ?? '-'))
            ->addColumn('status', function ($row) use ($hasStatus) {
                if (! $hasStatus) {
                    return '<span class="badge rounded-1 bg-secondary">N/A</span>';
                }

                return (int) ($row->active_inactive ?? 1) === 1
                    ? '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>'
                    : '<span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>';
            })
            ->addColumn('action', function ($row) use ($hasStatus) {
                $editUrl = route('admin.security.idcard_sub_type.edit', encrypt($row->pk));
                $deleteUrl = route('admin.security.idcard_sub_type.delete', encrypt($row->pk));

                // Active sub types can't be deleted (must be set Inactive first).
                $isActive = $hasStatus ? ((int) ($row->active_inactive ?? 1) === 1) : false;
                $canDelete = ! $hasStatus || ! $isActive;

                $editBtn = '<a href="' . $editUrl . '" class="programme-action-btn openEditSubType" title="Edit" aria-label="Edit sub type">'
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
                    $deleteBtn = '<form action="' . $deleteUrl . '" method="POST" class="d-inline subtype-delete-form" '
                        . 'onsubmit="return confirm(\'Delete this Sub Type mapping?\');">'
                        . csrf_field() . method_field('DELETE')
                        . '<button type="submit" class="programme-action-btn programme-action-btn--danger" title="Delete" aria-label="Delete sub type">'
                        . '<i class="bi bi-trash3" aria-hidden="true"></i></button></form>';
                } else {
                    $deleteBtn = '<button type="button" class="programme-action-btn programme-action-btn--danger is-disabled" disabled '
                        . 'title="Set status to Inactive before delete" aria-label="Delete disabled">'
                        . '<i class="bi bi-trash3" aria-hidden="true"></i></button>';
                }

                return '<div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">'
                    . $editBtn . $toggle . $deleteBtn . '</div>';
            })
            ->filterColumn('card_type', function ($query, $keyword) {
                $query->where('t.sec_card_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('sub_type', function ($query, $keyword) {
                $query->where('m.config_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('employee_category', function ($query, $keyword) {
                // Let users search by the readable label as well as the stored p/c code.
                $kw = strtolower($keyword);
                if (str_contains($kw, 'perm')) {
                    $query->where('m.card_name', 'p');
                } elseif (str_contains($kw, 'contr')) {
                    $query->where('m.card_name', 'c');
                } else {
                    $query->where('m.card_name', 'like', "%{$keyword}%");
                }
            })
            // Sorting: these are addColumn() values over a join, so each needs an
            // explicit alias-qualified column to ORDER BY.
            ->orderColumn('card_type', 't.sec_card_name $1')
            ->orderColumn('employee_category', 'm.card_name $1')
            ->orderColumn('sub_type', 'm.config_name $1')
            ->orderColumn('status', 'm.active_inactive $1')
            ->rawColumns(['employee_category', 'status', 'action']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query()
    {
        // No orderBy here on purpose: DataTables appends the user's ORDER BY after
        // this one, so a base ordering would always win and sorting would look dead.
        // The default sort lives in html() as the `order` parameter instead.
        return DB::table('sec_id_cardno_config_map as m')
            ->join('sec_id_cardno_master as t', 't.pk', '=', 'm.sec_id_cardno_master')
            ->select('m.*', 't.sec_card_name');
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('cardsubtypemaster-table')
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
                // Default sort: Card Type A-Z (column index 1), as before.
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
            Column::make('card_type')->title('Card Type')->orderable(true),
            Column::computed('employee_category')->title('Employee Category')->searchable(true)->orderable(true),
            Column::make('sub_type')->title('Sub Type')->orderable(true),
            // Only sortable when the status column actually exists in this DB.
            Column::computed('status')->title('Status')->searchable(false)->orderable($this->hasStatus())->addClass('text-center'),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center')->width(120),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'CardSubTypeMaster_' . date('YmdHis');
    }
}
