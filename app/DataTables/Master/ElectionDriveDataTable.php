<?php

namespace App\DataTables\Master;

use App\Http\Controllers\Admin\Master\ElectionDriveController;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

/**
 * One row per election drive. Election Publish and Result are independent
 * switches, each with its own action, and the result cannot be published before
 * the election is live (enforced server-side too).
 */
class ElectionDriveDataTable extends DataTable
{
    public function dataTable($query): QueryDataTable
    {
        return (new QueryDataTable($query))
            ->addIndexColumn()
            ->editColumn('election_drive_name', function ($row) {
                // The name links through to this drive's nominations.
                $url = route('master.election.drive.nominations', ['id' => encrypt($row->pk)]);

                return '<a href="' . $url . '" class="ed-drive-link">' . e($row->election_drive_name) . '</a>';
            })
            ->editColumn('nomination_drive_name', fn ($row) => e($row->nomination_drive_name ?? '-'))
            ->editColumn('course_name', fn ($row) => e($row->course_name ?? '-'))
            ->addColumn('publish_status', fn ($row) => (int) $row->election_publish_status === 1
                ? '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Live</span>'
                : '<span class="badge rounded-1 programme-status-badge ed-status-pending">Pending</span>')
            ->addColumn('result_status_label', fn ($row) => (int) $row->result_status === 1
                ? '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Published</span>'
                : '<span class="badge rounded-1 programme-status-badge ed-status-pending">Pending</span>')
            ->addColumn('action', function ($row) {
                $id        = encrypt($row->pk);
                $label     = e($row->election_drive_name);
                $isLive    = (int) $row->election_publish_status === 1;
                $hasResult = (int) $row->result_status === 1;

                $viewBtn = '<a href="' . route('master.election.drive.nominations', ['id' => $id]) . '"'
                    . ' class="cs-action-btn" aria-label="View nominations">'
                    . '<i class="bi bi-eye" aria-hidden="true"></i><span>View</span></a>';

                $editBtn = '<button type="button" class="cs-action-btn ed-edit-btn" aria-label="Edit election drive"'
                    . ' data-id="' . $id . '" data-label="' . $label . '">'
                    . '<i class="bi bi-pencil" aria-hidden="true"></i><span>Edit</span></button>';

                $publishResultBtn = '<button type="button" class="cs-action-btn ed-publish-btn ed-action-green" aria-label="Publish result"'
                    . ' data-id="' . $id . '" data-field="result_status" data-next="' . ($hasResult ? 0 : 1) . '"'
                    . ' data-label="' . $label . '">'
                    . '<i class="bi bi-upload" aria-hidden="true"></i>'
                    . '<span>' . ($hasResult ? 'Unpublish' : 'Publish') . ' Result</span></button>';

                $publishElectionBtn = '<button type="button" class="cs-action-btn ed-publish-btn ed-action-green" aria-label="Publish election"'
                    . ' data-id="' . $id . '" data-field="election_publish_status" data-next="' . ($isLive ? 0 : 1) . '"'
                    . ' data-label="' . $label . '">'
                    . '<i class="bi bi-play-fill" aria-hidden="true"></i>'
                    . '<span>' . ($isLive ? 'Unpublish' : 'Publish') . ' Election</span></button>';

                // Results only exist once published — keep the action inert until then.
                $viewResultBtn = $hasResult
                    ? '<a href="' . route('master.election.drive.result', ['id' => $id]) . '" class="cs-action-btn" aria-label="View result">'
                        . '<i class="bi bi-file-earmark-text" aria-hidden="true"></i><span>View Result</span></a>'
                    : '<span class="cs-action-btn ed-action-disabled" aria-disabled="true" title="Publish the result first">'
                        . '<i class="bi bi-file-earmark-text" aria-hidden="true"></i><span>View Result</span></span>';

                $deleteBtn = '<button type="button" class="cs-action-btn cs-action-btn--danger ed-delete-btn" aria-label="Delete election drive"'
                    . ' data-id="' . $id . '" data-label="' . $label . '">'
                    . '<i class="bi bi-trash3" aria-hidden="true"></i><span>Delete</span></button>';

                return '<div class="d-inline-flex align-items-start justify-content-center cs-action-group ed-action-group" role="group" aria-label="Row actions">'
                    . $viewBtn . $editBtn . $publishResultBtn . $publishElectionBtn . $viewResultBtn . $deleteBtn
                    . '</div>';
            })
            ->filterColumn('election_drive_name', function ($query, $keyword) {
                $query->where('e.election_drive_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('nomination_drive_name', function ($query, $keyword) {
                $query->where('nd.drive_name', 'like', "%{$keyword}%");
            })
            // The grid displays the short code, so search both it and the full name.
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('c.course_name', 'like', "%{$keyword}%")
                      ->orWhere('c.couse_short_name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['election_drive_name', 'publish_status', 'result_status_label', 'action']);
    }

    /**
     * @return QueryBuilder
     */
    public function query()
    {
        return ElectionDriveController::baseDriveQuery(
            request('status_filter', 'active'),
            request('course_master_pk')
        );
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('electiondrive-table')
            ->columns($this->getColumns())
            // Raw JS MUST go through minifiedAjax()'s $script argument — the
            // $data array quotes its values, which would send the literal source
            // text as the filter and silently match nothing.
            ->minifiedAjax('', <<<'JS'
            data.status_filter = window.edStatusFilter ? window.edStatusFilter() : 'active';
            data.course_master_pk = window.edCourseFilter ? window.edCourseFilter() : '';
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
            Column::make('election_drive_name')->title('Election Drive')->orderable(false)->addClass('ed-name-cell'),
            Column::make('nomination_drive_name')->title('Nomination Drive')->orderable(false)->addClass('ed-name-cell'),
            Column::make('course_name')->title('Course Name')->orderable(false),
            Column::computed('publish_status')->title('Election Publish Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::computed('result_status_label')->title('Result Status')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'ElectionDrive_' . date('YmdHis');
    }
}
