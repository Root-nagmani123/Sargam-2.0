<?php

namespace App\DataTables;

use App\Models\BuildingFloorRoomMapping;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class BuildingFloorRoomMappingDataTable extends DataTable
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
            ->addColumn('building_master_pk', function ($row) {

                return '<label class="text-dark">' . optional($row->building)->building_name . '</label>';
            })
            ->addColumn('floor_master_pk', function ($row) {
                return '<label class="text-dark">' . optional($row->floor)->floor_name ?? '—' . '</label>';
            })
            ->addColumn('room_name', function ($row) {
                return '<label class="text-dark">' . $row->room_name . '</label>';
            })
            ->addColumn('capacity', function ($row) {
                return '<label class="text-dark">' . $row->capacity . '</label>';
            })
            ->addColumn('actions', function ($row) {
                return '<a href="' . route('hostel.building.floor.room.map.edit', encrypt($row->pk)) . '" class="btn btn-sm btn-primary">Edit</a>
                ';
            })
            ->addColumn('comment', function ($row) {
                return '<input type="text" class="form-control form-control-sm comment-input" 
                            data-id="' . $row->pk . '" 
                            value="' . htmlspecialchars($row->comment) . '">';
            })

            ->addColumn('status', function ($row) {
                $checked = $row->active_inactive == 1 ? 'checked' : '';
                return '<div class="form-check form-switch d-inline-block ms-2">
                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                    data-table="building_floor_room_mapping" data-column="active_inactive" data-id="' . $row->pk . '" ' . $checked . '>
            </div>';
            })
            ->filterColumn('comment', function ($query, $keyword) {
                $query->whereRaw("IFNULL(comment,'') like ?", ["%{$keyword}%"]);
            })
            ->filterColumn('capacity', function ($query, $keyword) {
                $query->where('capacity', 'like', "%{$keyword}%");
            })
            ->rawColumns(['building_master_pk', 'floor_master_pk', 'room_name', 'capacity', 'actions', 'status', 'comment']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\BuildingFloorRoomMapping $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(BuildingFloorRoomMapping $model): QueryBuilder
    {
        $query = $model->newQuery()->latest('pk');
        if (request()->filled('building_id')) {
            $query->where('building_master_pk', request('building_id'));
        }
        if (request()->filled('room_type')) {
            $query->where('room_type', request('room_type'));
        }
        if (request()->filled('status')) {
            $query->where('active_inactive', request('status'));
        }

        return $query;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html(): HtmlBuilder
    {
        // Convert PHP array to JSON for JavaScript
        $roomTypes = json_encode(BuildingFloorRoomMapping::$roomTypes);

        return $this->builder()
            ->setTableId('hostelbuildingfloormapping-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'order' => [],
                'initComplete' => 'function(settings, json) {
                    var api = this.api();

                    // Room types array from PHP
                    var roomTypes = ' . $roomTypes . ';

                    // Create filter container above DataTable
                    var filterHtml = `
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="buildingFilter" class="form-label fw-bold">Filter by Building</label>
                                <select id="buildingFilter" class="form-select form-select-sm">
                                    <option value="">All Buildings</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="roomTypeFilter" class="form-label fw-bold">Filter by Room Type</label>
                                <select id="roomTypeFilter" class="form-select form-select-sm">
                                    <option value="">All Room Types</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="statusFilter" class="form-label fw-bold">Filter by Status</label>
                                <select id="statusFilter" class="form-select form-select-sm">
                                    <option value="">Select</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-end h-100">
                                    <button class="btn btn-secondary btn-sm mt-4" id="resetFilters">Reset Filters</button>
                                    <a href="' . route('hostel.building.floor.room.map.export') . '" 
                                    id="filterExportBtn"
                                    class="btn btn-secondary btn-sm mt-4 ms-2">Filter Export</a>
                                </div>
                            </div>
                        </div>
                    `;

                    // Insert filters above DataTable
                    $("#hostelbuildingfloormapping-table_wrapper").prepend(filterHtml);

                    // Populate buildings dynamically
                    $.ajax({
                        url: "' . route('api.get.buildings') . '",
                        type: "GET",
                        success: function(data) {
                            $.each(data, function(i, item) {
                                $("#buildingFilter").append(
                                    "<option value=\'" + item.pk + "\'>" + item.building_name + "</option>"
                                );
                            });
                        }
                    });

                    // Populate room type dropdown
                    $.each(roomTypes, function(key, val) {
                        $("#roomTypeFilter").append("<option value=\'" + key + "\'>" + val + "</option>");
                    });

                    // Reset filters
                    $(document).on("click", "#resetFilters", function() {
                        $("#buildingFilter").val("");
                        $("#roomTypeFilter").val("");
                        $("#statusFilter").val("");
                        var url = "' . route('hostel.building.floor.room.map.index') . '";
                        api.ajax.url(url).load();
                    });

                    // Handle both filters
                    $(document).on("change", "#buildingFilter, #roomTypeFilter, #statusFilter", function() {
                        var buildingId = $("#buildingFilter").val();
                        var roomType = $("#roomTypeFilter").val();
                        var status = $("#statusFilter").val();

                        var url = "' . route('hostel.building.floor.room.map.index') . '?building_id=" + buildingId + "&room_type=" + roomType + "&status=" + status;
                        api.ajax.url(url).load();
                    });

                    $(document).on("click", "#filterExportBtn", function(e) {
                        e.preventDefault();
                        var buildingId = $("#buildingFilter").val();
                        var roomType = $("#roomTypeFilter").val();
                        var status = $("#statusFilter").val();

                        var baseUrl = "' . route('hostel.building.floor.room.map.export') . '";
                        var params = "?building_id=" + buildingId + "&room_type=" + roomType + "&status=" + status;

                        // Redirect to filtered export URL
                        window.location.href = baseUrl + params;
                    });
                }',
            ])
            ->selectStyleSingle()
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload'),
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
            Column::computed('DT_RowIndex')->title('#')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('building_master_pk')->title('Building Name')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('floor_master_pk')->title('Floor')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('room_name')->title('Room Name')->addClass('text-center')->orderable(false)->searchable(false),
            Column::make('capacity')->title('Capacity')->addClass('text-center')->orderable(false),
            Column::computed('comment')->title('Comment')->addClass('text-center')->orderable(false)->searchable(true),
            Column::computed('actions')->title('Actions')->addClass('text-center')->orderable(false)->searchable(false),
            Column::computed('status')->title('Status')->addClass('text-center')->orderable(false)->searchable(false),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'BuildingFloorRoomMapping_' . date('YmdHis');
    }
}
