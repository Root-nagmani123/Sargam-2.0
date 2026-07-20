<?php

namespace App\DataTables\Security;

use App\Models\EmployeeMaster;
use App\Models\SecVisitorCardGenerated;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class VisitorPassDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('pass_number', fn ($row) => e($row->pass_number ?? '--'))
            ->addColumn('visitors', function ($row) {
                $names = $row->visitorNames;
                if ($names->isEmpty()) {
                    return '<span class="text-muted">--</span>';
                }

                $shown = $names->take(2)->pluck('visitor_name')->map(fn ($n) => e($n))->implode(', ');
                $extra = $names->count() - 2;
                if ($extra > 0) {
                    $shown .= ' <small class="text-muted">(+' . $extra . ' more)</small>';
                }

                return $shown;
            })
            ->addColumn('company', fn ($row) => e($row->company ?: '--'))
            ->addColumn('purpose', fn ($row) => e(Str::limit((string) $row->purpose, 30)))
            ->addColumn('host_employee', function ($row) {
                if (! $row->employee) {
                    return '--';
                }

                $name = trim(($row->employee->first_name ?? '') . ' ' . ($row->employee->last_name ?? ''));

                return e($name !== '' ? $name : '--');
            })
            ->addColumn('in_time', fn ($row) => $row->in_time ? $row->in_time->format('d-m-Y H:i') : '--')
            ->addColumn('out_time', function ($row) {
                if ($row->out_time) {
                    return e($row->out_time->format('d-m-Y H:i'));
                }

                return '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>';
            })
            ->addColumn('action', function ($row) {
                $id = encrypt($row->pk);
                $viewUrl = route('admin.security.visitor_pass.show', $id);
                $deleteUrl = route('admin.security.visitor_pass.delete', $id);

                $html = '<div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">'
                    . '<a href="' . $viewUrl . '" class="programme-action-btn" title="View" aria-label="View visitor pass">'
                    . '<i class="bi bi-eye" aria-hidden="true"></i></a>';

                // Check-out + Edit only while the visitor is still on-site (no out_time yet).
                if (! $row->out_time) {
                    $checkoutUrl = route('admin.security.visitor_pass.checkout', $id);
                    $editUrl = route('admin.security.visitor_pass.edit', $id);

                    $html .= '<form action="' . $checkoutUrl . '" method="POST" class="d-inline" '
                        . 'onsubmit="return confirm(\'Mark visitor as checked out?\');">'
                        . csrf_field()
                        . '<button type="submit" class="programme-action-btn" style="color:#d97706;" title="Check Out" aria-label="Check out visitor">'
                        . '<i class="bi bi-box-arrow-right" aria-hidden="true"></i></button></form>'
                        . '<a href="' . $editUrl . '" class="programme-action-btn" title="Edit" aria-label="Edit visitor pass">'
                        . '<i class="bi bi-pencil" aria-hidden="true"></i></a>';
                }

                $html .= '<form action="' . $deleteUrl . '" method="POST" class="d-inline" '
                    . 'onsubmit="return confirm(\'Delete this visitor pass?\');">'
                    . csrf_field() . method_field('DELETE')
                    . '<button type="submit" class="programme-action-btn programme-action-btn--danger" title="Delete" aria-label="Delete visitor pass">'
                    . '<i class="bi bi-trash3" aria-hidden="true"></i></button></form>';

                return $html . '</div>';
            })
            ->filterColumn('pass_number', function ($query, $keyword) {
                $query->where('pass_number', 'like', "%{$keyword}%");
            })
            ->filterColumn('company', function ($query, $keyword) {
                $query->where('company', 'like', "%{$keyword}%");
            })
            ->filterColumn('purpose', function ($query, $keyword) {
                $query->where('purpose', 'like', "%{$keyword}%");
            })
            ->filterColumn('visitors', function ($query, $keyword) {
                $query->whereHas('visitorNames', function ($v) use ($keyword) {
                    $v->where('visitor_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('host_employee', function ($query, $keyword) {
                // Deliberately NOT whereHas(): the employee relation joins the
                // unindexed employee_master.pk_old, so a correlated subquery means a
                // nested-loop over ~193k x ~1.8k rows (it ran for minutes). Resolving
                // the ids up-front against the small employee table is one cheap scan.
                $ids = EmployeeMaster::query()
                    ->where(function ($e) use ($keyword) {
                        $e->where('first_name', 'like', "%{$keyword}%")
                            ->orWhere('last_name', 'like', "%{$keyword}%");
                    })
                    ->whereNotNull('pk_old')
                    ->pluck('pk_old');

                $query->whereIn('employee_master_pk', $ids->all() ?: [null]);
            })
            // Sorting: these are addColumn() values, so each needs a real column to
            // ORDER BY. Only plain columns are offered — this table has ~193k rows and
            // no index beyond the PK, so every sort is a filesort; ordering by the
            // employee/visitorNames relations would mean a correlated subquery per row.
            ->orderColumn('pass_number', 'sec_visitor_card_generated.pass_number $1')
            ->orderColumn('company', 'sec_visitor_card_generated.company $1')
            ->orderColumn('purpose', 'sec_visitor_card_generated.purpose $1')
            ->orderColumn('in_time', 'sec_visitor_card_generated.in_time $1')
            ->orderColumn('out_time', 'sec_visitor_card_generated.out_time $1')
            ->rawColumns(['visitors', 'out_time', 'action']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(SecVisitorCardGenerated $model): QueryBuilder
    {
        $query = $model->newQuery()->with(['employee', 'visitorNames', 'createdBy']);

        // Newest-first by default, but ONLY when the user hasn't picked a sort:
        // DataTables appends its ORDER BY after ours, so an unconditional base
        // ordering would always win and header sorting would look dead. There is
        // no visible created_date column, so this can't live in html()'s `order`.
        if (! request()->filled('order')) {
            $query->orderBy('created_date', 'desc');
        }

        return $query;
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('visitorPass-table')
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
            Column::make('pass_number')->title('Pass #')->orderable(true),
            // visitors / host_employee come from relations: sorting them on ~193k
            // unindexed rows would be a correlated subquery per row, so they stay
            // searchable but unsortable.
            Column::make('visitors')->title('Visitor(s)')->orderable(false),
            Column::make('company')->title('Company')->orderable(true),
            Column::make('purpose')->title('Purpose')->orderable(true),
            Column::make('host_employee')->title('Host Employee')->orderable(false),
            Column::make('in_time')->title('In Time')->searchable(false)->orderable(true)->addClass('text-center'),
            Column::make('out_time')->title('Out Time')->searchable(false)->orderable(true)->addClass('text-center'),
            Column::computed('action')->title('Action')->searchable(false)->orderable(false)->addClass('text-center')->width(150),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'VisitorPass_' . date('YmdHis');
    }
}
