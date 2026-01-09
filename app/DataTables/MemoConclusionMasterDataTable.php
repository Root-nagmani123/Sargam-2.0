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
                $csrf = csrf_token();
                $formId = 'delete-form-' . $row->pk;

                $html = <<<HTML
<div class="d-inline-flex align-items-center gap-2"
     role="group"
     aria-label="Memo conclusion actions">

    <!-- Edit -->
    <a href="{$editUrl}"
       class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
       aria-label="Edit memo conclusion">
        <span class="material-icons material-symbols-rounded"
              style="font-size:18px;"
              aria-hidden="true">edit</span>
        <span class="d-none d-md-inline">Edit</span>
    </a>

    <!-- Delete -->
    <?php if ($isActive): ?>
        <button type="button"
                class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 d-none"
                disabled
                aria-disabled="true"
                title="Cannot delete active memo conclusion">
            <span class="material-icons material-symbols-rounded"
                  style="font-size:18px;"
                  aria-hidden="true">delete</span>
            <span class="d-none d-md-inline">Delete</span>
        </button>
    <?php else: ?>
        <form id="<?= $formId ?>"
              action="<?= $deleteUrl ?>"
              method="POST"
              class="d-inline">
            <input type="hidden" name="_token" value="<?= $csrf ?>">
            <input type="hidden" name="_method" value="DELETE">

            <button type="submit"
                    class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"
                    aria-label="Delete memo conclusion"
                    onclick="return confirm('Are you sure you want to delete this memo conclusion?');">
                <span class="material-icons material-symbols-rounded"
                      style="font-size:18px;"
                      aria-hidden="true">delete</span>
                <span class="d-none d-md-inline">Delete</span>
            </button>
        </form>
    <?php endif; ?>

</div>

HTML;

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

