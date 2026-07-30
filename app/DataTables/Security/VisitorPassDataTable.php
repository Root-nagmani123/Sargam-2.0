<?php

namespace App\DataTables\Security;

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
                $query->whereHas('employee', function ($e) use ($keyword) {
                    $e->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['visitors', 'out_time', 'action']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(SecVisitorCardGenerated $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['employee', 'visitorNames', 'createdBy'])
            ->orderBy('created_date', 'desc');
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
            Column::make('pass_number')->title('Pass #')->orderable(false),
            Column::make('visitors')->title('Visitor(s)')->orderable(false),
            Column::make('company')->title('Company')->orderable(false),
            Column::make('purpose')->title('Purpose')->orderable(false),
            Column::make('host_employee')->title('Host Employee')->orderable(false),
            Column::make('in_time')->title('In Time')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('out_time')->title('Out Time')->searchable(false)->orderable(false)->addClass('text-center'),
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
