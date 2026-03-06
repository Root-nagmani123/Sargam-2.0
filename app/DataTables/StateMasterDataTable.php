<?php

namespace App\DataTables;

use App\Models\State;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class StateMasterDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     * @return \Yajra\DataTables\EloquentDataTable
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('state_name', fn($row) => $row->state_name ?? 'N/A')
            ->addColumn('country_name', function ($row) {
                return $row->country->country_name ?? 'N/A';
            })
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '
                <div class="form-check form-switch d-inline-block">
                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                        data-table="state_master" data-column="active_inactive" data-id="'.$row->pk.'" '.$checked.'>
                </div>';
            })
            ->addColumn('action', function ($row) {
                $updateUrl = route('master.state.update', $row->pk);
                $deleteUrl = route('master.state.delete', $row->pk);
                $isActive = $row->active_inactive == 1;
                $csrf = csrf_token();
                $stateName = e($row->state_name ?? '');
                $status = (string) ($row->active_inactive ?? 1);
                $countryPk = (string) ($row->country_master_pk ?? '');

                $html = '<div class="d-inline-flex align-items-center gap-2" role="group" aria-label="State actions">';

                $html .= '<button type="button" class="btn btn-link p-0 d-flex align-items-center gap-1 text-primary text-decoration-none open-state-edit-modal" aria-label="Edit state"
                    data-bs-toggle="modal" data-bs-target="#stateFormModal"
                    data-mode="edit"
                    data-pk="'.$row->pk.'" 
                    data-state-name="'.$stateName.'" 
                    data-country-pk="'.$countryPk.'" 
                    data-active-inactive="'.$status.'" 
                    data-update-url="'.$updateUrl.'">
                    <span class="material-symbols-rounded fs-6" aria-hidden="true">edit</span>
                </button>';

                if ($isActive) {
                    $html .= '<a href="javascript:void(0);" class="d-flex align-items-center gap-1 text-primary" aria-label="Delete state" disabled aria-disabled="true" title="Cannot delete active state">
                        <span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>
                    </a>';
                } else {
                    $html .= '<form action="'.$deleteUrl.'" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this?\');">
                        <input type="hidden" name="_token" value="'.$csrf.'">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-link p-0 d-flex align-items-center gap-1 text-primary text-decoration-none" aria-label="Delete state">
                            <span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>
                        </button>
                    </form>';
                }

                $html .= '</div>';
                return $html;
            })
            ->filterColumn('state_name', function ($query, $keyword) {
                $query->where('state_master.state_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('country_name', function ($query, $keyword) {
                $query->whereHas('country', function($countryQuery) use ($keyword) {
                    $countryQuery->where('country_name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['action', 'status'])
            ->setRowId('pk');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\State $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(State $model): QueryBuilder
    {
        return $model->with('country')->orderBy('pk', 'desc')->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('state-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => true,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [[1, 'asc']],
            ]);
    }

    /**
     * Get the dataTable columns definition.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-left')->searchable(false)->orderable(false),
            Column::make('state_name')->title('State Name')->addClass('text-left')->orderable(true)->searchable(true),
            Column::computed('country_name')->title('Country Name')->addClass('text-left')->orderable(false)->searchable(true),
            Column::computed('status')->title('Status')->addClass('text-left')->orderable(false)->searchable(false),
            Column::computed('action')->title('Action')->addClass('text-left')->orderable(false)->searchable(false),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'StateMaster_' . date('YmdHis');
    }
}
