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
                $label = $row->salaryGrade?->display_label_text;

                return $label ? '<span class="em-pay-scale">' . e($label) . '</span>' : '<span class="em-muted">—</span>';
            })
            ->addColumn('unit_type', function ($row) {
                $label = $row->unitType?->name;

                return $label ? '<span class="em-chip em-chip--type">' . e($label) . '</span>' : '<span class="em-muted">—</span>';
            })
            ->addColumn('unit_sub_type', function ($row) {
                $label = $row->unitSubType?->name;

                return $label ? '<span class="em-chip em-chip--subtype">' . e($label) . '</span>' : '<span class="em-muted">—</span>';
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
                // Edit opens the Add / Edit modal on the index with these values;
                // Delete opens the shared confirm dialog (eligibility_criteria/index).
                $values = [
                    'salary_grade_master_pk' => (string) $row->salary_grade_master_pk,
                    'estate_unit_type_master_pk' => (string) $row->estate_unit_type_master_pk,
                    'estate_unit_sub_type_master_pk' => (string) $row->estate_unit_sub_type_master_pk,
                ];
                $name = trim(($row->salaryGrade?->display_label_text ?? '') . ' → ' . ($row->unitSubType?->name ?? ''), ' →');
                $deleteUrl = route('admin.estate.eligibility-criteria.destroy', $row->pk);

                return '<div class="em-act-group" role="group" aria-label="Actions for ' . e($name) . '">'
                    . '<button type="button" class="em-act em-act--edit js-em-edit" data-pk="' . (int) $row->pk . '"'
                    . ' data-values="' . e(json_encode($values)) . '" aria-label="Edit ' . e($name) . '">'
                    . '<span class="em-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
                    . '<span class="em-act__label">Edit</span></button>'
                    . '<button type="button" class="em-act em-act--delete js-em-delete" data-url="' . e($deleteUrl) . '"'
                    . ' data-name="' . e($name) . '" aria-label="Delete ' . e($name) . '">'
                    . '<span class="em-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>'
                    . '<span class="em-act__label">Delete</span></button>'
                    . '</div>';
            })
            ->rawColumns(['pay_scale', 'unit_type', 'unit_sub_type', 'actions'])
            ->setRowId('pk');
    }

    public function query(EligibilityCriterion $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['salaryGrade', 'unitType', 'unitSubType'])
            ->orderByDesc('pk');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('eligibilityCriteriaTable')
            // programme-dt chrome (docs/new-design-index-page.md) — no `dom` and no
            // `language` on purpose: datatable-global-ui.js owns both, and a page-level
            // override would break the "Showing N of M items" footer.
            ->addTableClass('table table-hover align-middle mb-0 w-100 programme-dt-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
                // Re-sort the whole list on the server, not just the loaded page.
                'sargamServerOrder' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [],
                'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('em-col-sno no-sort')->orderable(false)->searchable(false),
            Column::computed('pay_scale')->title('Pay Scale')->orderable(true)->searchable(true),
            Column::computed('unit_type')->title('Unit Type')->orderable(true)->searchable(true),
            Column::computed('unit_sub_type')->title('Unit Sub Type')->name('unit_sub_type')->orderable(true)->searchable(true),
            Column::computed('actions')->title('Action')->addClass('em-col-action no-sort')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'EligibilityCriteria_' . date('YmdHis');
    }
}