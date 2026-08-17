<?php

namespace App\DataTables;

use App\Models\MemoTypeMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Storage;


class MemoTypeMasterDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('memo_type_name', fn($row) => $row->memo_type_name ?? 'N/A')
            ->editColumn('document', function ($row) {
                if (! $row->memo_doc_upload) {
                    return '<span class="mtm-doc-empty">—</span>';
                }

                return '<a class="mtm-doc-link" target="_blank" rel="noopener"'
                    . ' href="' . e(asset('storage/' . $row->memo_doc_upload)) . '">'
                    . '<i class="bi bi-file-earmark-text" aria-hidden="true"></i>'
                    . '<span>View</span></a>';
            })
            ->filterColumn('memo_type_name', function ($query, $keyword) {
                $query->where('memo_type_name', 'like', "%{$keyword}%");
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (!empty($searchValue)) {
                    $query->where(function ($subQuery) use ($searchValue) {
                        $subQuery->where('memo_type_name', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            // Action: Edit · the status switch · Delete, three equal stacks of an
            // icon over a caption (docs/new-design-index-page.md §3b).
            ->addColumn('actions', function ($row) {
                $isActive = (int) $row->active_inactive === 1;
                // The caption names the ACTION, not the state: the state is
                // already shown by the badge one column over.
                $toggleLabel = $isActive ? 'Deactivate' : 'Activate';
                $deleteUrl = route('master.memo.type.master.delete', ['id' => encrypt($row->pk)]);

                $edit = '<button type="button" class="mtm-act mtm-act--edit editMemo"'
                    . ' data-pk="' . (int) $row->pk . '"'
                    . ' data-name="' . e($row->memo_type_name) . '"'
                    . ' data-status="' . (int) $row->active_inactive . '"'
                    . ' data-file="' . ($row->memo_doc_upload ? e(asset('storage/' . $row->memo_doc_upload)) : '') . '"'
                    . ' title="Edit" aria-label="Edit memo type">'
                    . '<span class="mtm-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
                    . '<span class="mtm-act__label">Edit</span></button>';

                // No .form-check/.form-switch wrapper (§3b trap 1): custom.css
                // pulls a .form-check-input inside one left by -2.375rem, which
                // is right for switch-beside-label and wrong for this layout.
                // custom.js binds .status-toggle globally off these data-* attrs.
                $toggle = '<label class="mtm-act mtm-act--toggle" title="' . $toggleLabel . '">'
                    . '<span class="mtm-act__icon">'
                    . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    . ' data-table="memo_type_master" data-column="active_inactive"'
                    . ' data-id="' . (int) $row->pk . '" ' . ($isActive ? 'checked' : '')
                    . ' aria-label="' . $toggleLabel . ' memo type">'
                    . '</span>'
                    . '<span class="mtm-act__label">' . $toggleLabel . '</span></label>';

                // Mirror the rule this page already enforced: an active memo type
                // cannot be deleted, so the control is muted and inert.
                $delete = $isActive
                    ? '<span class="mtm-act mtm-act--del is-disabled" aria-disabled="true"'
                        . ' title="Deactivate this memo type before deleting">'
                        . '<span class="mtm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="mtm-act__label">Delete</span></span>'
                    : '<button type="button" class="mtm-act mtm-act--del deleteBtn"'
                        . ' data-pk="' . (int) $row->pk . '" data-url="' . e($deleteUrl) . '"'
                        . ' title="Delete" aria-label="Delete memo type">'
                        . '<span class="mtm-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                        . '<span class="mtm-act__label">Delete</span></button>';

                return '<div class="mtm-act-group" role="group" aria-label="Row actions">'
                    . $edit . $toggle . $delete
                    . '</div>';
            })

            // Status: soft badge, display only (§3b). data-order lets a
            // client-side sort order by state.
            ->addColumn('status', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return '<span class="status-pill badge rounded-1 '
                    . ($isActive ? 'bg-success-subtle' : 'bg-danger-subtle') . '"'
                    . ' data-order="' . (int) $isActive . '">'
                    . ($isActive ? 'Active' : 'Inactive')
                    . '</span>';
            })
            ->rawColumns(['memo_type_name', 'document', 'actions', 'status']);
    }

    public function query(MemoTypeMaster $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('pk', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('memotypemaster-table')
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
            Column::make('memo_type_name')->title('Memo Type Name')->addClass('text-center')->orderable(false)->searchable(true),
            Column::make('document')->title('Document')->addClass('text-center')->orderable(false)->searchable(false),
            Column::computed('status')->title('Status')->addClass('text-center')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Action')->addClass('text-center')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'MemoTypeMaster_' . date('YmdHis');
    }
}

