<?php

namespace App\DataTables;

use App\Models\Country;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CountryMasterDataTable extends DataTable
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
            ->addColumn('country_name', fn($row) => $row->country_name ?? 'N/A')
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '
                <div class="form-check form-switch d-inline-block">
                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                        data-table="country_master" data-column="active_inactive" data-id="'.$row->pk.'" '.$checked.'>
                </div>';
            })
            ->addColumn('action', function ($row) {
                $updateUrl = route('master.country.update', $row->pk);
                $deleteUrl = route('master.country.delete', $row->pk);
                $isActive = $row->active_inactive == 1;
                $csrf = csrf_token();
                $countryName = e($row->country_name ?? '');
                $status = (string) ($row->active_inactive ?? 1);

                $html = '<div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Country actions">';

                $html .= '<button type="button" class="btn btn-link p-0 d-flex align-items-center gap-1 text-primary text-decoration-none" aria-label="Edit country"
                    data-bs-toggle="modal" data-bs-target="#editCountryModal"
                    data-id="'.$row->pk.'" data-name="'.$countryName.'" data-status="'.$status.'" data-update-url="'.$updateUrl.'">
                    <span class="material-symbols-rounded fs-6" aria-hidden="true">edit</span>
                </button>';

                if ($isActive) {
                    $html .= '<a href="javascript:void(0);" class="d-flex align-items-center gap-1 text-primary" aria-label="Delete country" disabled aria-disabled="true" title="Cannot delete active country">
                        <span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>
                    </a>';
                } else {
                    $html .= '<form action="'.$deleteUrl.'" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this?\');">
                        <input type="hidden" name="_token" value="'.$csrf.'">
                        <input type="hidden" name="_method" value="DELETE">
                        <a href="javascript:void(0);" class="d-flex align-items-center gap-1 text-primary" aria-label="Delete country">
                            <span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>
                        </a>
                    </form>';
                }

                $html .= '</div>';
                return $html;
            })
            ->filterColumn('country_name', function ($query, $keyword) {
                $query->where('country_name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action', 'status'])
            ->setRowId('pk');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Country $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Country $model): QueryBuilder
    {
        return $model->orderBy('pk', 'desc')->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('country-table')
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
            Column::computed('DT_RowIndex')->title('#')->addClass('text-center')->searchable(false)->orderable(false),
            Column::make('country_name')->title('Country Name')->addClass('text-center')->orderable(true)->searchable(true),
            Column::computed('status')->title('Status')->addClass('text-center')->orderable(false)->searchable(false),
            Column::computed('action')->title('Actions')->addClass('text-center')->orderable(false)->searchable(false),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'CountryMaster_' . date('YmdHis');
    }
}
