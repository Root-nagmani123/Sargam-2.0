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
<div class="d-inline-flex align-items-center gap-2"
     role="group"
     aria-label="Memo type actions">

    <!-- Edit -->
    <a href="{$editUrl}"
       class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
       aria-label="Edit memo type">
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
                title="Cannot delete active memo type">
            <span class="material-icons material-symbols-rounded"
                  style="font-size:18px;"
                  aria-hidden="true">delete</span>
            <span class="d-none">Delete</span>
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
                    aria-label="Delete memo type"
                    onclick="return confirm('Are you sure you want to delete this memo type?');">
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

