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

            // Action: Edit · status switch · Delete (docs/new-design-index-page.md §3b).
            ->addColumn('actions', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return view('admin.master.partials.grid-actions', [
                    'name'   => $row->discipline_name ?? '',
                    'edit'   => ['href' => route('master.discipline.edit', encrypt($row->pk))],
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
            ->orderBy('discipline_master.pk', 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('text-nowrap')->width('5.5rem'),
            Column::make('course_name')->title('Course'),
            Column::make('discipline_name')->title('Discipline'),
            Column::make('mark_deduction')->title('Mark Deduction')->addClass('text-nowrap'),
            Column::computed('status')->title('Status')->addClass('text-nowrap')->width('8rem'),
            Column::computed('actions')->title('Action')->addClass('text-nowrap')->width('13rem'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('discipline-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->pageLength(10);
    }
}
