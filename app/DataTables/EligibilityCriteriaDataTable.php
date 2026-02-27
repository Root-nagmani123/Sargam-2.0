<?php

namespace App\DataTables;

use App\Models\EligibilityCriterion;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EligibilityCriteriaDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('pay_scale', function ($row) {
                return $row->salaryGrade?->display_label_text ?? '-';
            })
            ->addColumn('unit_type', function ($row) {
                return $row->unitType?->name ?? '-';
            })
            ->addColumn('unit_sub_type', function ($row) {
                return $row->unitSubType?->name ?? '-';
            })
            ->orderColumn('pay_scale', 'salary_grade_master_pk $1')
            ->filterColumn('pay_scale', function ($query, $keyword) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $keyword) . '%';
                $query->whereHas('salaryGrade', function ($q) use ($like) {
                    $q->where('salary_grade', 'like', $like);
                });
            })
            ->orderColumn('unit_type', 'estate_unit_type_master_pk $1')
            ->filterColumn('unit_type', function ($query, $keyword) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $keyword) . '%';
                $query->whereHas('unitType', function ($q) use ($like) {
                    $q->where('unit_type', 'like', $like);
                });
            })
            ->orderColumn('unit_sub_type', 'estate_unit_sub_type_master_pk $1')
            ->filterColumn('unit_sub_type', function ($query, $keyword) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $keyword) . '%';
                $query->whereHas('unitSubType', function ($q) use ($like) {
                    $q->where('unit_sub_type', 'like', $like);
                });
            })
            ->addColumn('actions', function ($row) {
                $editUrl = route('admin.estate.eligibility-criteria.edit', $row->pk);
                $deleteUrl = route('admin.estate.eligibility-criteria.destroy', $row->pk);
                $token = csrf_token();

                return '<div class="d-flex gap-1 flex-wrap">
                    <a href="' . e($editUrl) . '" class="text-primary" title="Edit">
                        <i class="material-icons material-symbols-rounded">edit</i>
                    </a>
                    <form action="' . e($deleteUrl) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this eligibility mapping?\');">
                        <input type="hidden" name="_token" value="' . e($token) . '">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-link p-0 text-primary border-0" title="Delete">
                            <i class="material-icons material-symbols-rounded">delete</i>
                        </button>
                    </form>
                </div>';
            })
            ->rawColumns(['actions'])
            ->setRowId('pk');
    }

    public function query(EligibilityCriterion $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['salaryGrade', 'unitType', 'unitSubType'])
            ->orderBy('pk');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('eligibilityCriteriaTable')
            ->addTableClass('table text-nowrap w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [[1, 'asc']],
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'search' => 'Search within table:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'infoEmpty' => 'Showing 0 to 0 of 0 entries',
                    'infoFiltered' => '(filtered from _MAX_ total entries)',
                    'paginate' => [
                        'first' => 'First',
                        'last' => 'Last',
                        'next' => 'Next',
                        'previous' => 'Previous',
                    ],
                ],
                'dom' => '<"row align-items-center mb-3"<"col-12 col-md-4"l><"col-12 col-md-8"f>>rt<"row align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false)->width('80px'),
            Column::computed('pay_scale')->title('PAY SCALE')->orderable(true)->searchable(true),
            Column::computed('unit_type')->title('UNIT TYPE')->orderable(true)->searchable(true),
            Column::computed('unit_sub_type')->title('UNIT SUB TYPE')->name('unit_sub_type')->orderable(true)->searchable(true),
            Column::computed('actions')->title('Actions')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'EligibilityCriteria_' . date('YmdHis');
    }
}
