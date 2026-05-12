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

            ->addColumn('status', function ($row) {
                return $row->active_inactive == 1
                    ? '<span class="dis-badge-active">Active</span>'
                    : '<span class="dis-badge-inactive">Inactive</span>';
            })

            ->addColumn('actions', function ($row) {
                $deleteUrl = route('master.discipline.delete', encrypt($row->pk));
                $isActive  = ($row->active_inactive == 1);

                $editBtn = '<a href="javascript:void(0);" class="dis-action-btn text-primary dis-edit-btn"'
                    . ' data-pk="' . encrypt($row->pk) . '"'
                    . ' data-course="' . $row->course_master_pk . '"'
                    . ' data-name="' . htmlspecialchars($row->discipline_name, ENT_QUOTES) . '"'
                    . ' data-deduction="' . $row->mark_deduction . '"'
                    . ' data-status="' . $row->active_inactive . '"'
                    . ' title="Edit"><span class="material-symbols-rounded">edit</span></a>';

                $toggleBtn = '<div class="form-check form-switch d-inline-block mb-0" style="min-height:0;">'
                    . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    . ' data-table="discipline_master" data-column="active_inactive"'
                    . ' data-id="' . $row->pk . '" ' . ($isActive ? 'checked' : '') . '>'
                    . '</div>';

                $deleteBtn = $isActive
                    ? '<button type="button" class="dis-action-btn text-muted" disabled style="opacity:0.35;cursor:not-allowed;" title="Cannot delete active record"><span class="material-symbols-rounded">delete</span></button>'
                    : '<button type="button" class="dis-action-btn text-danger dis-delete-btn"'
                        . ' data-url="' . $deleteUrl . '"'
                        . ' title="Delete"><span class="material-symbols-rounded">delete</span></button>';

                return '<div class="d-inline-flex align-items-center gap-1">'
                    . $editBtn . $toggleBtn . $deleteBtn . '</div>';
            })

            ->rawColumns(['status','actions']);
    }

    public function query(DisciplineMaster $model): Builder
    {
        $data_course_id =  get_Role_by_course();
        $query = $model->newQuery()
            ->leftJoin('course_master as cm', 'cm.pk', '=', 'discipline_master.course_master_pk')
            ->select('discipline_master.*', 'cm.course_name')
            ->orderBy('discipline_master.pk', 'desc');

        if (!empty($data_course_id)) {
            $query->whereIn('discipline_master.course_master_pk', $data_course_id);
        }

        return $query;
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No'),
            Column::make('course_name')->title('Course'),
            Column::make('discipline_name')->title('Discipline'),
            Column::make('mark_deduction')->title('Mark Deduction'),
            Column::computed('status')->title('Status'),
            Column::computed('actions')->title('Actions'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('discipline-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive'  => false,
                'autoWidth'   => false,
                'pagingType'  => 'full_numbers',
                'ordering'    => false,
                'searching'   => true,
                'dom'         => 'rtp',
                'info'        => false,
                'pageLength'  => 10,
                'language'    => [
                    'paginate' => ['first' => '«', 'last' => '»', 'next' => '›', 'previous' => '‹']
                ],
            ]);
    }
}
