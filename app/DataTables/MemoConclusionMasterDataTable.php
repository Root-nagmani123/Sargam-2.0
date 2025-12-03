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
            ->editColumn('discussion_name', fn($row) => $row->discussion_name ?? 'N/A')
            ->editColumn('pt_discusion', fn($row) => $row->pt_discusion ?? 'N/A')
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
            ->addColumn('actions', function ($row) {
                $editUrl = route('master.memo.conclusion.master.edit', encrypt($row->pk));
                $deleteUrl = route('master.memo.conclusion.master.delete', encrypt($row->pk));
                $isActive = $row->active_inactive == 1;
                
                $html = '
                    <div class="d-flex justify-content-center align-items-center gap-2">
                        <a href="' . $editUrl . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">edit</i>
                        </a>
                        <div class="delete-icon-container" data-item-id="' . $row->pk . '" data-delete-url="' . $deleteUrl . '">';
                
                if ($isActive) {
                    $html .= '
                            <span class="delete-icon-disabled" title="Cannot delete active memo conclusion">
                                <i class="material-icons menu-icon material-symbols-rounded"
                                    style="font-size: 24px; color: #ccc; cursor: not-allowed;">delete</i>
                            </span>';
                } else {
                    $csrf = csrf_token();
                    $html .= '
                            <form action="' . $deleteUrl . '" method="POST" class="m-0 delete-form" data-status="0">
                                <input type="hidden" name="_token" value="' . $csrf . '">
                                <input type="hidden" name="_method" value="DELETE">
                                <a href="javascript:void(0)" onclick="event.preventDefault();
                                    if(confirm(\'Are you sure you want to delete this memo conclusion?\')) {
                                        this.closest(\'form\').submit();
                                    }" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                    <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">delete</i>
                                </a>
                            </form>';
                }
                
                $html .= '
                        </div>
                    </div>';
                
                return $html;
            })
            ->addColumn('status', function ($row) {
                return '<div class="form-check form-switch d-inline-block">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                data-table="memo_conclusion_master" data-column="active_inactive" data-id="' . $row->pk . '" ' . ($row->active_inactive == 1 ? 'checked' : '') . '>
                        </div>';
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
            Column::make('discussion_name')->title('Discussion Name')->addClass('text-center')->orderable(false)->searchable(true),
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

