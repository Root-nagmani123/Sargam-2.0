<?php

namespace App\DataTables;

use App\Models\City;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CityMasterDataTable extends DataTable
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
            ->addColumn('city_name', fn($row) => $row->city_name ?? 'N/A')
            ->addColumn('district_name', function ($row) {
                return $row->district->district_name ?? 'N/A';
            })
            ->addColumn('state_name', function ($row) {
                return $row->state->state_name ?? 'N/A';
            })
            ->addColumn('country_name', function ($row) {
                return $row->country->country_name ?? 'N/A';
            })
            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '
                <div class="form-check form-switch d-inline-block">
                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                        data-table="city_master" data-column="active_inactive" data-id="'.$row->pk.'" '.$checked.'>
                </div>';
            })
            ->addColumn('action', function ($row) {
                $updateUrl = route('master.city.update', $row->pk);
                $deleteUrl = route('master.city.delete', $row->pk);
                $isActive = $row->active_inactive == 1;
                $csrf = csrf_token();
                $cityName = e($row->city_name ?? '');
                $status = (string) ($row->active_inactive ?? 1);
                $countryPk = (string) ($row->country_master_pk ?? '');
                $statePk = (string) ($row->state_master_pk ?? '');
                $districtPk = (string) ($row->district_master_pk ?? '');

                $html = '<div class="d-inline-flex align-items-center gap-2" role="group" aria-label="City actions">';

                $html .= '<a href="javascript:void(0);" class="d-inline-flex align-items-center gap-1 text-primary editCityBtn" aria-label="Edit city"
                    data-bs-toggle="modal" 
                    data-bs-target="#editCityModal"
                    data-id="'.$row->pk.'" 
                    data-city-name="'.$cityName.'" 
                    data-country-pk="'.$countryPk.'" 
                    data-state-pk="'.$statePk.'" 
                    data-district-pk="'.$districtPk.'" 
                    data-status="'.$status.'" 
                    data-update-url="'.$updateUrl.'">
                    <span class="material-symbols-rounded fs-6" aria-hidden="true">edit</span>
                </a>';

                if ($isActive) {
                    $html .= '<a href="javascript:void(0);" class="d-inline-flex align-items-center gap-1 text-primary" disabled aria-label="Delete city" title="Cannot delete active city">
                        <span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>
                    </a>';
                } else {
                    $html .= '<form action="'.$deleteUrl.'" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this?\');">
                        <input type="hidden" name="_token" value="'.$csrf.'">
                        <input type="hidden" name="_method" value="DELETE">
                        <a href="javascript:void(0);" class="d-inline-flex align-items-center gap-1 text-primary" aria-label="Delete city">
                            <span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>
                        </a>
                    </form>';
                }

                $html .= '</div>';
                return $html;
            })
            ->filterColumn('city_name', function ($query, $keyword) {
                $query->where('city_master.city_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('district_name', function ($query, $keyword) {
                $query->whereHas('district', function($districtQuery) use ($keyword) {
                    $districtQuery->where('district_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('state_name', function ($query, $keyword) {
                $query->whereHas('state', function($stateQuery) use ($keyword) {
                    $stateQuery->where('state_name', 'like', "%{$keyword}%");
                });
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
     * @param \App\Models\City $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(City $model): QueryBuilder
    {
        return $model->with(['country', 'state', 'district'])->orderBy('pk', 'desc')->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('city-table')
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
            Column::make('city_name')->title('City Name')->addClass('text-left')->orderable(true)->searchable(true),
            Column::computed('district_name')->title('District')->addClass('text-left')->orderable(false)->searchable(true),
            Column::computed('state_name')->title('State')->addClass('text-left')->orderable(false)->searchable(true),
            Column::computed('country_name')->title('Country')->addClass('text-left')->orderable(false)->searchable(true),
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
        return 'CityMaster_' . date('YmdHis');
    }
}
