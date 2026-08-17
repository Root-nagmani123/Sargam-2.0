<?php

namespace App\DataTables;

use App\Models\MemoConclusionMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class MemoConclusionMasterDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
{
    return (new EloquentDataTable($query))
        ->addIndexColumn()

        ->editColumn('discussion_name', fn ($row) => $row->discussion_name ?? 'N/A')
        ->editColumn('pt_discusion', fn ($row) => $row->pt_discusion ?? 'N/A')

        ->filterColumn('discussion_name', function ($query, $keyword) {
            $query->where('discussion_name', 'like', "%{$keyword}%");
        })

        ->filterColumn('pt_discusion', function ($query, $keyword) {
            $query->where('pt_discusion', 'like', "%{$keyword}%");
        })

        ->filter(function ($query) {
            $searchValue = request()->input('search.value');
            if (!empty($searchValue)) {
                $query->where(function ($subQuery) use ($searchValue) {
                    $subQuery->where('discussion_name', 'like', "%{$searchValue}%")
                             ->orWhere('pt_discusion', 'like', "%{$searchValue}%");
                });
            }
        }, true)

        // Action: Edit · the status switch · Delete, three equal stacks of an
        // icon over a caption (docs/new-design-index-page.md §3b).
        ->addColumn('actions', function ($row) {
            $isActive  = (int) $row->active_inactive === 1;
            $deleteUrl = route('master.memo.conclusion.master.delete', $row->pk);
            // The caption names the ACTION, not the state: the state is already
            // shown by the badge one column over.
            $toggleLabel = $isActive ? 'Deactivate' : 'Activate';

            $edit = '<button type="button" class="mtm-act mtm-act--edit editshowConclusionAlert"'
                . ' data-pk="' . (int) $row->pk . '"'
                . ' data-discussion_name="' . e($row->discussion_name) . '"'
                . ' data-pt_discusion="' . e($row->pt_discusion) . '"'
                . ' data-active_inactive="' . (int) $row->active_inactive . '"'
                . ' title="Edit" aria-label="Edit memo conclusion">'
                . '<span class="mtm-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
                . '<span class="mtm-act__label">Edit</span></button>';

            // No .form-check/.form-switch wrapper (§3b trap 1): custom.css pulls
            // a .form-check-input inside one left by -2.375rem, which is right
            // for switch-beside-label and wrong for this layout. custom.js binds
            // .status-toggle globally off these data-* attributes.
            $toggle = '<label class="mtm-act mtm-act--toggle" title="' . $toggleLabel . '">'
                . '<span class="mtm-act__icon">'
                . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                . ' data-table="memo_conclusion_master" data-column="active_inactive"'
                . ' data-id="' . (int) $row->pk . '" ' . ($isActive ? 'checked' : '')
                . ' aria-label="' . $toggleLabel . ' memo conclusion">'
                . '</span>'
                . '<span class="mtm-act__label">' . $toggleLabel . '</span></label>';

            // Mirror destroy()'s own refusal: an active conclusion cannot be
            // deleted, so the control is muted and inert rather than
            // red-and-always-failing.
            $delete = $isActive
                ? '<span class="mtm-act mtm-act--del is-disabled" aria-disabled="true"'
                    . ' title="Deactivate this memo conclusion before deleting">'
                    . '<span class="mtm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                    . '<span class="mtm-act__label">Delete</span></span>'
                : '<button type="button" class="mtm-act mtm-act--del deleteBtn"'
                    . ' data-url="' . e($deleteUrl) . '" data-id="' . (int) $row->pk . '"'
                    . ' title="Delete" aria-label="Delete memo conclusion">'
                    . '<span class="mtm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                    . '<span class="mtm-act__label">Delete</span></button>';

            return '<div class="mtm-act-group" role="group" aria-label="Row actions">'
                . $edit . $toggle . $delete
                . '</div>';
        })

        // Status: soft badge, display only (§3b). data-order lets a client-side
        // sort order by state.
        ->addColumn('status', function ($row) {
            $isActive = (int) $row->active_inactive === 1;

            return '<span class="status-pill badge rounded-1 '
                . ($isActive ? 'bg-success-subtle' : 'bg-danger-subtle') . '"'
                . ' data-order="' . (int) $isActive . '">'
                . ($isActive ? 'Active' : 'Inactive')
                . '</span>';
        })
        ->rawColumns(['discussion_name', 'pt_discusion', 'actions', 'status']);
}


    public function query(MemoConclusionMaster $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('memoconclusionmaster-table')
            ->addTableClass('table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => false,
                'autoWidth' => false,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                // ‹ 1 2 3 › — First/Last are not part of the shared footer (§4).
                'pagingType' => 'simple_numbers',
                'language' => [
                    // The same glyphs vendor/pagination/custom.blade.php uses,
                    // which is what makes the two footer variants match (§4).
                    'paginate' => [
                        'previous' => '&lsaquo;',
                        'next' => '&rsaquo;',
                    ],
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('discussion_name')->title('Conclusion name')->addClass('text-center')->orderable(false)->searchable(true),
            Column::make('pt_discusion')->title('PT Discussion')->addClass('text-center')->orderable(false)->searchable(true),
            Column::computed('status')->title('Status')->addClass('text-center')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'MemoConclusionMaster_' . date('YmdHis');
    }
}

