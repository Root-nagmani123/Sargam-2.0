<?php
namespace App\DataTables;

use App\Models\DisciplineMaster;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;

class DisciplineMasterDataTable extends DataTable
{
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->editColumn('discipline_name', fn($row) => $row->discipline_name ?? 'N/A')
            ->editColumn('mark_deduction', fn($row) => $row->mark_deduction ?? '0')

            // Status: display-only soft badge. The switch lives in the Action stack.
            ->addColumn('status', fn ($row) => view('admin.master.partials.grid-status', [
                'active' => (int) $row->active_inactive === 1,
            ])->render())
            ->orderColumn('status', 'discipline_master.active_inactive $1')

            // Action: Edit · status switch · Delete (docs/new-design-index-page.md §3b).
            ->addColumn('actions', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return view('admin.master.partials.grid-actions', [
                    'name'   => $row->discipline_name ?? '',
                    'edit'   => [
                        'href'  => route('master.discipline.edit', encrypt($row->pk)),
                        // Opens the edit form in the shared modal (master-admin.js).
                        'attrs' => ['data-mst-modal-form' => true],
                    ],
                    'toggle' => [
                        'active' => $isActive,
                        'table'  => 'discipline_master',
                        'column' => 'active_inactive',
                        'id'     => $row->pk,
                    ],
                    // Active records were never deletable from this grid — keep that rule.
                    'delete' => $isActive
                        ? ['disabled' => true, 'reason' => 'Active records cannot be deleted. Deactivate it first.']
                        : ['action' => route('master.discipline.delete', encrypt($row->pk))],
                ])->render();
            })

            ->rawColumns(['status','actions']);
    }

    public function query(DisciplineMaster $model): Builder
    {
        return $model->newQuery()
            ->Join('course_master as cm', 'cm.pk', '=', 'discipline_master.course_master_pk')
            ->select('discipline_master.*', 'cm.course_name')
            // Default order only on first load; a header click sends `order`.
            ->when(empty(request('order')), fn ($q) => $q->orderBy('discipline_master.pk', 'desc'));
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('text-nowrap')->width('5.5rem'),
            // course_name comes from the course_master join; without the qualified
            // name every grid search failed with "Unknown column discipline_master.course_name".
            Column::make('course_name')->name('cm.course_name')->title('Course'),
            Column::make('discipline_name')->name('discipline_master.discipline_name')->title('Discipline'),
            Column::make('mark_deduction')->name('discipline_master.mark_deduction')->title('Mark Deduction')->addClass('text-nowrap'),
            Column::computed('status')->title('Status')->orderable(true)->addClass('text-nowrap')->width('8rem'),
            Column::computed('actions')->title('Action')->addClass('text-nowrap')->width('13rem'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('discipline-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->pageLength(10)
            ->parameters([
                // Real server-side ordering over the whole table (datatable-global-ui.js
                // opt-in); no initial sort so the first load keeps the query order.
                'sargamServerOrder' => true,
                'order'             => [],
            ]);
    }
}
