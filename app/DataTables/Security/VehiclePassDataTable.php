<?php

namespace App\DataTables\Security;

use App\Models\EmployeeMaster;
use App\Models\VehiclePassTWApply;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class VehiclePassDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('employee_name', fn ($row) => e($row->display_name ?? '--'))
            ->addColumn('vehicle_pass_no', fn ($row) => e($row->vehicle_req_id ?? '--'))
            ->addColumn('vehicle_type_name', fn ($row) => e($row->vehicleType->vehicle_type ?? '--'))
            ->addColumn('vehicle_no', fn ($row) => e($row->vehicle_no ?? '--'))
            ->addColumn('document', function ($row) {
                $docPath = $row->doc_upload;

                if ($docPath && Storage::disk('public')->exists($docPath)) {
                    return '<a href="' . asset('storage/' . $docPath) . '" target="_blank" '
                        . 'class="text-primary d-inline-flex align-items-center gap-1" title="View document">'
                        . '<i class="bi bi-file-earmark-text" aria-hidden="true"></i>'
                        . '<span class="d-none d-sm-inline">Download</span></a>';
                }

                if ($docPath) {
                    return '<span class="text-warning small">No file available</span>';
                }

                return '<span class="text-muted">--</span>';
            })
            ->addColumn('request_date', fn ($row) => $row->created_date ? $row->created_date->format('d-m-Y H:i') : '--')
            ->addColumn('status', function ($row) {
                $badge = match ((int) $row->vech_card_status) {
                    2       => 'bg-success',
                    3       => 'bg-danger',
                    default => 'bg-warning text-dark',
                };

                return '<span class="badge rounded-1 ' . $badge . '">' . e($row->status_text) . '</span>';
            })
            ->addColumn('action', function ($row) {
                $id = encrypt($row->vehicle_tw_pk);
                $viewUrl = route('admin.security.vehicle_pass.show', $id);

                $html = '<div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">'
                    . '<a href="' . $viewUrl . '" class="programme-action-btn" title="View" aria-label="View request">'
                    . '<i class="bi bi-eye" aria-hidden="true"></i></a>';

                // Editable/deletable only while Pending and before any approval activity.
                if ((int) $row->vech_card_status === 1 && ! $row->approvals_exists) {
                    $editUrl = route('admin.security.vehicle_pass.edit', $id);
                    $deleteUrl = route('admin.security.vehicle_pass.delete', $id);

                    $html .= '<a href="' . $editUrl . '" class="programme-action-btn" title="Edit" aria-label="Edit request">'
                        . '<i class="bi bi-pencil" aria-hidden="true"></i></a>'
                        . '<form action="' . $deleteUrl . '" method="POST" class="d-inline vp-delete-form" '
                        . 'onsubmit="return confirm(\'Delete this application?\');">'
                        . csrf_field() . method_field('DELETE')
                        . '<button type="submit" class="programme-action-btn programme-action-btn--danger" title="Delete" aria-label="Delete request">'
                        . '<i class="bi bi-trash3" aria-hidden="true"></i></button></form>';
                }

                return $html . '</div>';
            })
            ->filterColumn('employee_name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    // NB: no applicant_name clause — vehicle_pass_tw_apply has no such
                    // column (searching it raised SQLSTATE 42S22). The display_name
                    // accessor reads it too, but that just resolves to null.
                    $q->whereHas('employee', function ($e) use ($keyword) {
                        $e->where('first_name', 'like', "%{$keyword}%")
                            ->orWhere('last_name', 'like', "%{$keyword}%");
                    })
                    ->orWhere('employee_id_card', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('vehicle_pass_no', function ($query, $keyword) {
                $query->where('vehicle_req_id', 'like', "%{$keyword}%");
            })
            ->filterColumn('vehicle_type_name', function ($query, $keyword) {
                $query->whereHas('vehicleType', function ($v) use ($keyword) {
                    $v->where('vehicle_type', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('vehicle_no', function ($query, $keyword) {
                $query->where('vehicle_no', 'like', "%{$keyword}%");
            })
            // Sorting: every visible column is an addColumn() value, so each needs an
            // explicit SQL expression. employee_name mirrors the display_name accessor
            // (employee relation -> id card); vehicle_type_name comes from a relation,
            // hence the correlated subqueries.
            ->orderColumn('employee_name', 'coalesce((select nullif(trim(concat(coalesce(e.first_name, \'\'), \' \', coalesce(e.last_name, \'\'))), \'\') '
                . 'from employee_master e where e.pk = vehicle_pass_tw_apply.emp_master_pk), '
                . 'vehicle_pass_tw_apply.employee_id_card) $1')
            ->orderColumn('vehicle_pass_no', 'vehicle_pass_tw_apply.vehicle_req_id $1')
            ->orderColumn('vehicle_type_name', '(select vt.vehicle_type from sec_vehicle_type vt '
                . 'where vt.pk = vehicle_pass_tw_apply.vehicle_type) $1')
            ->orderColumn('vehicle_no', 'vehicle_pass_tw_apply.vehicle_no $1')
            ->orderColumn('request_date', 'vehicle_pass_tw_apply.created_date $1')
            ->orderColumn('status', 'vehicle_pass_tw_apply.vech_card_status $1')
            ->rawColumns(['document', 'status', 'action']);
    }

    /**
     * Get query source of dataTable. Scoped to the current user's passes and split
     * by the Active / Archive pill via the `status_filter` request param.
     */
    public function query(VehiclePassTWApply $model): QueryBuilder
    {
        $user = Auth::user();
        $employeePk = $user->user_id ?? null;
        $pkOld = EmployeeMaster::where('pk', $user->user_id)->value('pk_old');

        $statusFilter = request('status_filter', 'active');
        $statuses = $statusFilter === 'archive' ? [2, 3] : [1];

        return $model->newQuery()
            ->with(['vehicleType', 'employee'])
            ->withExists(['approvals' => function ($q) {
                $q->where(function ($w) {
                    $w->whereIn('status', [1, 2, 3])
                        ->orWhereIn('veh_recommend_status', [1, 2, 3]);
                });
            }])
            ->where(function ($q) use ($employeePk, $pkOld) {
                $q->where('veh_created_by', $employeePk);
                if ($pkOld !== null) {
                    $q->orWhere('veh_created_by', $pkOld);
                }
            })
            // No orderBy here on purpose: DataTables appends the user's ORDER BY after
            // this one, so a base ordering would always win and sorting would look dead.
            // The default sort (requested date desc) lives in html() as `order`.
            ->whereIn('vech_card_status', $statuses);
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('vehiclePass-table')
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
                // Default sort: newest request first (Requested Date, column index 6).
                'order'        => [[6, 'desc']],
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
            Column::make('employee_name')->title('Employee Name')->orderable(true),
            Column::make('vehicle_pass_no')->title('Vehicle Pass No')->orderable(true),
            Column::make('vehicle_type_name')->title('Vehicle Type')->orderable(true),
            Column::make('vehicle_no')->title('Vehicle Number')->orderable(true),
            Column::computed('document')->title('Uploaded Document')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('request_date')->title('Requested Date')->searchable(false)->orderable(true)->addClass('text-center'),
            Column::computed('status')->title('Status')->exportable(false)->printable(false)->searchable(false)->orderable(true)->addClass('text-center'),
            Column::computed('action')->title('Action')->exportable(false)->printable(false)->searchable(false)->orderable(false)->addClass('text-center')->width(130),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'VehiclePass_' . date('YmdHis');
    }
}
