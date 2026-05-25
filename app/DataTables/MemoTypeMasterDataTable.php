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
                return $row->memo_doc_upload ? 'Available' : 'NA';
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
                $deleteUrl = route('master.memo.type.master.delete', ['id' => encrypt($row->pk)]);
                $isActive  = ($row->active_inactive == 1);
                $docUrl    = $row->memo_doc_upload ? asset('storage/' . $row->memo_doc_upload) : '';

                $eyeBtn = $docUrl
                    ? '<a href="' . $docUrl . '" target="_blank" class="mt-action-btn text-secondary" title="View Document"><span class="material-symbols-rounded">visibility</span></a>'
                    : '<span class="mt-action-btn text-muted" style="opacity:0.35;cursor:default;" title="No Document"><span class="material-symbols-rounded">visibility</span></span>';

                $editBtn = '<a href="javascript:void(0);" class="mt-action-btn text-primary editMemo"'
                    . ' data-pk="' . $row->pk . '"'
                    . ' data-name="' . htmlspecialchars($row->memo_type_name, ENT_QUOTES) . '"'
                    . ' data-status="' . $row->active_inactive . '"'
                    . ' data-file="' . $docUrl . '"'
                    . ' title="Edit"><span class="material-symbols-rounded">edit</span></a>';

                $toggleBtn = '<div class="form-check form-switch d-inline-block mb-0" style="min-height:0;">'
                    . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    . ' data-table="memo_type_master" data-column="active_inactive"'
                    . ' data-id="' . $row->pk . '" ' . ($isActive ? 'checked' : '') . '>'
                    . '</div>';

                $deleteBtn = $isActive
                    ? '<button type="button" class="mt-action-btn text-muted" disabled style="opacity:0.35;cursor:not-allowed;" title="Cannot delete active record"><span class="material-symbols-rounded">delete</span></button>'
                    : '<button type="button" class="mt-action-btn text-danger deleteBtn"'
                        . ' data-pk="' . $row->pk . '"'
                        . ' data-url="' . $deleteUrl . '"'
                        . ' title="Delete"><span class="material-symbols-rounded">delete</span></button>';

                return '<div class="d-inline-flex align-items-center gap-1">'
                    . $eyeBtn . $editBtn . $toggleBtn . $deleteBtn
                    . '</div>';
            })

            ->addColumn('status', function ($row) {
                return $row->active_inactive == 1
                    ? '<span class="mt-badge-active">Active</span>'
                    : '<span class="mt-badge-inactive">Inactive</span>';
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
                'pagingType' => 'full_numbers',
                'ordering' => false,
                'searching' => true,
                'dom' => 'rtp',
                'info' => false,
                'pageLength' => 10,
                'language' => [
                    'paginate' => ['first' => '«', 'last' => '»', 'next' => '›', 'previous' => '‹']
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

