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

        ->addColumn('actions', function ($row) {
                $deleteUrl = route('master.memo.conclusion.master.delete', $row->pk);
                $isActive  = ($row->active_inactive == 1);

                $editBtn = '<a href="javascript:void(0)" class="mc-action-btn text-primary editshowConclusionAlert"'
                    . ' data-pk="' . $row->pk . '"'
                    . ' data-discussion_name="' . htmlspecialchars($row->discussion_name, ENT_QUOTES) . '"'
                    . ' data-pt_discusion="' . htmlspecialchars($row->pt_discusion ?? '', ENT_QUOTES) . '"'
                    . ' data-active_inactive="' . $row->active_inactive . '"'
                    . ' title="Edit"><span class="material-symbols-rounded">edit</span></a>';

                $toggleBtn = '<div class="form-check form-switch d-inline-block mb-0" style="min-height:0;">'
                    . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    . ' data-table="memo_conclusion_master" data-column="active_inactive"'
                    . ' data-id="' . $row->pk . '" ' . ($isActive ? 'checked' : '') . '>'
                    . '</div>';

                $deleteBtn = $isActive
                    ? '<button type="button" class="mc-action-btn text-muted" disabled style="opacity:0.35;cursor:not-allowed;" title="Cannot delete active record"><span class="material-symbols-rounded">delete</span></button>'
                    : '<button type="button" class="mc-action-btn text-danger deleteBtn"'
                        . ' data-url="' . $deleteUrl . '"'
                        . ' data-id="' . $row->pk . '"'
                        . ' title="Delete"><span class="material-symbols-rounded">delete</span></button>';

                return '<div class="d-inline-flex align-items-center gap-1">'
                    . $editBtn . $toggleBtn . $deleteBtn
                    . '</div>';
        })

        ->addColumn('status', function ($row) {
                return $row->active_inactive == 1
                    ? '<span class="mc-badge-active">Active</span>'
                    : '<span class="mc-badge-inactive">Inactive</span>';
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

