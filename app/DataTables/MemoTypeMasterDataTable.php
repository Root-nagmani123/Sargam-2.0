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
            // Escaped here: memo_type_name stays in rawColumns below.
            ->editColumn('memo_type_name', fn($row) => e($row->memo_type_name ?? 'N/A'))
            ->editColumn('document', function ($row) {
                if ($row->memo_doc_upload) {
                    return '<a href="' . e(asset('storage/' . $row->memo_doc_upload)) . '" target="_blank" rel="noopener"'
                        . ' class="d-inline-flex align-items-center gap-1 text-primary fw-medium"'
                        . ' title="Open the memo document in a new tab">'
                        . '<i class="bi bi-file-earmark-text" aria-hidden="true"></i><span>View</span></a>';
                }
                return '<span class="text-muted">N/A</span>';
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
            // Action: Edit · status switch · Delete (docs/new-design-index-page.md §3b).
            // .editMemo / .deleteBtn and their data-* are the hooks the index
            // page's SweetAlert handlers read — keep them.
            ->addColumn('actions', function ($row) {
                $isActive = (int) $row->active_inactive === 1;

                return view('admin.master.partials.grid-actions', [
                    'name'   => $row->memo_type_name ?? '',
                    'edit'   => [
                        'class' => 'editMemo',
                        'attrs' => [
                            'data-pk'     => $row->pk,
                            'data-name'   => $row->memo_type_name,
                            'data-status' => $row->active_inactive,
                            'data-file'   => $row->memo_doc_upload ? asset('storage/' . $row->memo_doc_upload) : '',
                        ],
                    ],
                    'toggle' => [
                        'active' => $isActive,
                        'table'  => 'memo_type_master',
                        'column' => 'active_inactive',
                        'id'     => $row->pk,
                    ],
                    // DELETE() refuses an active memo type (403) — mirror it.
                    'delete' => $isActive
                        ? ['disabled' => true, 'reason' => 'Active memo types cannot be deleted. Deactivate it first.']
                        : [
                            'class' => 'deleteBtn',
                            'attrs' => [
                                'data-pk'  => $row->pk,
                                'data-url' => route('master.memo.type.master.delete', ['id' => encrypt($row->pk)]),
                            ],
                        ],
                ])->render();
            })

            // Status: display-only soft badge. The switch lives in the Action stack.
            ->addColumn('status', fn ($row) => view('admin.master.partials.grid-status', [
                'active' => (int) $row->active_inactive === 1,
            ])->render())
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
                'language' => [
                    'paginate' => [
                        'previous' => ' <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 24px;">chevron_left</i>',
                        'next' => '<i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 24px;">chevron_right</i>'
                    ]
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('text-nowrap')->width('5.5rem')->orderable(false)->searchable(false),
            Column::make('memo_type_name')->title('Memo Type Name')->orderable(false)->searchable(true),
            Column::make('document')->title('Document')->addClass('text-nowrap')->orderable(false)->searchable(false),
            Column::computed('status')->title('Status')->addClass('text-nowrap')->width('8rem')->orderable(false)->searchable(false),
            Column::computed('actions')->title('Action')->addClass('text-nowrap')->width('13rem')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'MemoTypeMaster_' . date('YmdHis');
    }
}

