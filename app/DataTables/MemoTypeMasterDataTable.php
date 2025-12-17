<?php

namespace App\DataTables;

use App\Models\MemoTypeMaster;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class MemoTypeMasterDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('memo_type_name', fn($row) => $row->memo_type_name ?? 'N/A')
            ->editColumn('document', function ($row) {
                if ($row->memo_doc_upload) {
                    return '<a href="' . asset('storage/' . $row->memo_doc_upload) . '" target="_blank">View</a>';
                }
                return 'N/A';
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
            ->addColumn('actions', function ($row) {
                $editUrl = route('master.memo.type.master.edit', ['id' => encrypt($row->pk)]);
                $deleteUrl = route('master.memo.type.master.delete', ['id' => encrypt($row->pk)]);
                $isActive = $row->active_inactive == 1;
                $csrf = csrf_token();
                $formId = 'delete-form-' . $row->pk;

                $html = <<<HTML
<div class="dropdown text-center">
    <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions">
        <span class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">more_horiz</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        <li>
            <a class="dropdown-item d-flex align-items-center" href="{$editUrl}">
                <span class="material-icons menu-icon material-symbols-rounded me-2" style="font-size: 20px;">edit</span>
                Edit
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
HTML;

                if ($isActive) {
                    $html .= <<<HTML
        <li>
            <span class="dropdown-item d-flex align-items-center disabled" title="Cannot delete active memo type" aria-disabled="true">
                <span class="material-icons menu-icon material-symbols-rounded me-2" style="font-size: 20px;">delete</span>
                Delete
            </span>
        </li>
HTML;
                } else {
                    $html .= <<<HTML
        <li>
            <form id="{$formId}" action="{$deleteUrl}" method="POST" class="d-inline">
                <input type="hidden" name="_token" value="{$csrf}">
                <input type="hidden" name="_method" value="DELETE">
                <a href="#" class="dropdown-item d-flex align-items-center text-danger" onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this memo type?')) document.getElementById('{$formId}').submit();">
                    <span class="material-icons menu-icon material-symbols-rounded me-2" style="font-size: 20px;">delete</span>
                    Delete
                </a>
            </form>
        </li>
HTML;
                }

                $html .= <<<HTML
    </ul>
</div>
HTML;

                return $html;
            })
            ->addColumn('status', function ($row) {
                return '<div class="form-check form-switch d-inline-block">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                data-table="memo_type_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . ($row->active_inactive == 1 ? 'checked' : '') . '>
                        </div>';
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
                'ordering' => false,
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

