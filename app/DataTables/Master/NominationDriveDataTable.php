<?php

namespace App\DataTables\Master;

use App\Http\Controllers\Admin\Master\NominationDriveController;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

/**
 * One row per nomination drive. The two status columns are independent switches
 * (accepting nominations vs allowing withdrawals), each with its own toggle in
 * the Action group, mirroring the design.
 */
class NominationDriveDataTable extends DataTable
{
    public function dataTable($query): QueryDataTable
    {
        return (new QueryDataTable($query))
            ->addIndexColumn()
            ->editColumn('drive_name', function ($row) {
                // The drive name is the link through to its nominee list.
                $url = route('master.nomination.drive.show', ['id' => encrypt($row->pk)]);

                return '<a href="' . $url . '" class="nd-drive-link">' . e($row->drive_name) . '</a>';
            })
            ->editColumn('course_name', fn ($row) => e($row->course_name ?? '-'))
            ->editColumn('start_date', fn ($row) => $row->start_date ? date('d-m-Y', strtotime($row->start_date)) : '-')
            ->editColumn('end_date', fn ($row) => $row->end_date ? date('d-m-Y', strtotime($row->end_date)) : '-')
            ->addColumn('accept_status', fn ($row) => self::statusBadge((int) $row->nomination_accept_status))
            ->addColumn('withdraw_status', fn ($row) => self::statusBadge((int) $row->nomination_withdraw_status))
            ->addColumn('action', function ($row) {
                $id       = encrypt($row->pk);
                $viewUrl  = route('master.nomination.drive.show', ['id' => $id]);
                $accept   = (int) $row->nomination_accept_status === 1;
                $withdraw = (int) $row->nomination_withdraw_status === 1;
                $label    = e($row->drive_name);

                $viewBtn = '<a href="' . $viewUrl . '" class="cs-action-btn" aria-label="View nominations">'
                    . '<i class="bi bi-eye" aria-hidden="true"></i><span>View</span></a>';

                $editBtn = '<button type="button" class="cs-action-btn nd-edit-btn" aria-label="Edit drive"'
                    . ' data-id="' . $id . '" data-label="' . $label . '">'
                    . '<i class="bi bi-pencil" aria-hidden="true"></i><span>Edit</span></button>';

                // Each toggle is labelled with the action it performs, as in the
                // design ("Disable Nomination Withdraw" when it is currently on).
                $withdrawBtn = '<button type="button" class="cs-action-btn nd-toggle-btn" aria-label="Toggle nomination withdraw"'
                    . ' data-id="' . $id . '" data-field="nomination_withdraw_status" data-next="' . ($withdraw ? 0 : 1) . '"'
                    . ' data-label="' . $label . '">'
                    . '<i class="bi ' . ($withdraw ? 'bi-toggle-on nd-toggle-on' : 'bi-toggle-off nd-toggle-off') . '" aria-hidden="true"></i>'
                    . '<span>' . ($withdraw ? 'Disable' : 'Enable') . ' Nomination Withdraw</span></button>';

                $acceptBtn = '<button type="button" class="cs-action-btn nd-toggle-btn" aria-label="Toggle nomination accept"'
                    . ' data-id="' . $id . '" data-field="nomination_accept_status" data-next="' . ($accept ? 0 : 1) . '"'
                    . ' data-label="' . $label . '">'
                    . '<i class="bi ' . ($accept ? 'bi-toggle-on nd-toggle-on' : 'bi-toggle-off nd-toggle-off') . '" aria-hidden="true"></i>'
                    . '<span>' . ($accept ? 'Disable' : 'Enable') . ' Nomination Accept</span></button>';

                $deleteBtn = '<button type="button" class="cs-action-btn cs-action-btn--danger nd-delete-btn" aria-label="Delete drive"'
                    . ' data-id="' . $id . '" data-label="' . $label . '">'
                    . '<i class="bi bi-trash3" aria-hidden="true"></i><span>Delete</span></button>';

                return '<div class="d-inline-flex align-items-start justify-content-center cs-action-group nd-action-group" role="group" aria-label="Row actions">'
                    . $viewBtn . $editBtn . $withdrawBtn . $acceptBtn . $deleteBtn
                    . '</div>';
            })
            ->filterColumn('drive_name', function ($query, $keyword) {
                $query->where('d.drive_name', 'like', "%{$keyword}%");
            })
            // The grid displays the short code, so search both it and the full name.
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('c.course_name', 'like', "%{$keyword}%")
                      ->orWhere('c.couse_short_name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['drive_name', 'accept_status', 'withdraw_status', 'action']);
    }

    private static function statusBadge(int $on): string
    {
        return $on
            ? '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Enable</span>'
            : '<span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Disable</span>';
    }

    /**
     * @return QueryBuilder
     */
    public function query()
    {
        return NominationDriveController::baseDriveQuery(
            request('status_filter', 'active'),
            request('course_master_pk')
        );
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('nominationdrive-table')
            ->columns($this->getColumns())
            // Raw JS MUST go through minifiedAjax()'s $script argument — the
            // $data array quotes its values, which would send the literal source
            // text as the filter and silently match nothing.
            ->minifiedAjax('', <<<'JS'
            data.status_filter = window.ndStatusFilter ? window.ndStatusFilter() : 'active';
            data.course_master_pk = window.ndCourseFilter ? window.ndCourseFilter() : '';
JS)
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
                    'paginate'          => ['previous' => '‹', 'next' => '›'],
                    'lengthMenu'        => 'Showing _MENU_',
                    'info'              => 'of _TOTAL_ items',
                    'infoEmpty'         => 'of 0 items',
                    'infoFiltered'      => 'of _MAX_ items',
                ],
            ]);
        // No ->buttons([...]) — Button::make('reset')/('reload') are not real
        // button types and break the global enhancer's init.dt hook.
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false),
            Column::make('drive_name')->title('Nomination Drive')->orderable(false)->addClass('nd-name-cell'),
            Column::make('course_name')->title('Course Name')->orderable(false),
            Column::make('start_date')->title('Start Date')->searchable(false)->orderable(false),
            Column::make('end_date')->title('End Date')->searchable(false)->orderable(false),
            Column::computed('accept_status')->title('Nomination Accept Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('withdraw_status')->title('Nomination Withdraw Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'NominationDrive_' . date('YmdHis');
    }
}
