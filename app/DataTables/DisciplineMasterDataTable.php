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

            // Status: soft badge, display only (docs/new-design-index-page.md
            // §3b). data-order lets a client-side sort order by state.
            ->addColumn('status', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return '<span class="status-pill badge rounded-1 '
                    . ($isActive ? 'bg-success-subtle' : 'bg-danger-subtle') . '"'
                    . ' data-order="' . (int) $isActive . '">'
                    . ($isActive ? 'Active' : 'Inactive')
                    . '</span>';
            })

            // Action: Edit · the status switch · Delete, three equal stacks of an
            // icon over a caption (§3b). Edit opens the listing's modal rather
            // than navigating to a separate page, so the row carries its values.
            ->addColumn('actions', function ($row) {
                $isActive = (int) $row->active_inactive === 1;
                $deleteUrl = route('master.discipline.delete', encrypt($row->pk));
                // The caption names the ACTION, not the state: the state is
                // already shown by the badge one column over.
                $toggleLabel = $isActive ? 'Deactivate' : 'Activate';

                $edit = '<button type="button" class="mtm-act mtm-act--edit dm-edit-btn"'
                    . ' data-id="' . e(encrypt($row->pk)) . '"'
                    . ' data-course="' . e($row->course_master_pk) . '"'
                    . ' data-name="' . e($row->discipline_name) . '"'
                    . ' data-mark="' . e($row->mark_deduction) . '"'
                    . ' data-status="' . (int) $row->active_inactive . '"'
                    . ' title="Edit" aria-label="Edit discipline">'
                    . '<span class="mtm-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
                    . '<span class="mtm-act__label">Edit</span></button>';

                // No .form-check/.form-switch wrapper (§3b trap 1): custom.css
                // pulls a .form-check-input inside one left by -2.375rem, which is
                // right for switch-beside-label and wrong for this layout.
                // custom.js binds .status-toggle globally off these data-* attrs.
                $toggle = '<label class="mtm-act mtm-act--toggle" title="' . $toggleLabel . '">'
                    . '<span class="mtm-act__icon">'
                    . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    . ' data-table="discipline_master" data-column="active_inactive"'
                    . ' data-id="' . (int) $row->pk . '" ' . ($isActive ? 'checked' : '')
                    . ' aria-label="' . $toggleLabel . ' discipline">'
                    . '</span>'
                    . '<span class="mtm-act__label">' . $toggleLabel . '</span></label>';

                // Mirror the rule this grid already enforced: an active discipline
                // cannot be deleted, so the control is muted and inert rather than
                // red-and-always-failing.
                $delete = $isActive
                    ? '<span class="mtm-act mtm-act--del is-disabled" aria-disabled="true"'
                        . ' title="Deactivate this discipline before deleting">'
                        . '<span class="mtm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="mtm-act__label">Delete</span></span>'
                    : '<form action="' . e($deleteUrl) . '" method="POST" class="mtm-act mtm-act--del">'
                        . csrf_field() . method_field('DELETE')
                        . '<button type="submit" class="mtm-act__btn" title="Delete"'
                        . ' aria-label="Delete discipline"'
                        . ' onclick="return confirm(\'Are you sure you want to delete this discipline?\');">'
                        . '<span class="mtm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="mtm-act__label">Delete</span>'
                        . '</button></form>';

                return '<div class="mtm-act-group" role="group" aria-label="Row actions">'
                    . $edit . $toggle . $delete
                    . '</div>';
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
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('course_name')->title('Course'),
            Column::make('discipline_name')->title('Discipline'),
            Column::make('mark_deduction')->title('Mark Deduction'),
            Column::computed('status')->title('Status')->addClass('text-center')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Action')->addClass('text-center')->orderable(false)->searchable(false),
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
                'responsive' => false,
                'autoWidth' => false,
                // ‹ 1 2 3 › — First/Last are not part of the shared footer (§4).
                'pagingType' => 'simple_numbers',
                'language' => [
                    'paginate' => [
                        'previous' => '&lsaquo;',
                        'next' => '&rsaquo;',
                    ],
                ],
            ]);
    }
}
