<?php

namespace App\DataTables;

use App\DataTables\Concerns\CoeMasterCells;
use App\Http\Controllers\Admin\COE\RoomController;
use App\Models\BuildingFloorRoomMapping;
use App\Models\BuildingMaster;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * COE → Master Data → Room Master, server-side via Yajra.
 *
 * The shared building_floor_room_mapping, rooms of Classroom buildings only
 * (building_master.building_type). Building / Floor are selects: their pks ride
 * on the row as data-coe-val-building / -floor for the Edit dialog. Faulty /
 * Available laptops only once RoomController::faultyEnabled().
 */
class CoeRoomMasterDataTable extends DataTable
{
    use CoeMasterCells;

    public function dataTable(Builder $query): EloquentDataTable
    {
        $table = new EloquentDataTable($query);
        if (RoomController::faultyEnabled()) {
            $table->editColumn('faulty_laptop_count', fn ($row) => $row->faulty_laptop_count === null ? '' : (int) $row->faulty_laptop_count)
                ->addColumn('available_laptops', fn ($row) => RoomController::availableLaptops($row) ?? '');
        }

        return $table
            ->addIndexColumn()
            ->editColumn('room_name', fn ($row) => e(trim((string) $row->room_name)))
            // Selects: the cell shows the name, the pk rides on data-coe-val-*.
            ->editColumn('building_master_pk', fn ($row) => e(trim((string) $row->building_name)))
            ->editColumn('floor_master_pk', fn ($row) => e(trim((string) $row->floor_name)))
            ->editColumn('room_type', fn ($row) => e((string) $row->room_type))
            ->editColumn('capacity', fn ($row) => $row->capacity === null ? '' : (int) $row->capacity)
            ->editColumn('laptop_count', fn ($row) => $row->laptop_count === null ? '' : (int) $row->laptop_count)
            ->editColumn('scriber_count', fn ($row) => $row->scriber_count === null ? '' : (int) $row->scriber_count)
            ->addColumn('status', fn ($row) => $this->statusCell((int) $row->active_inactive === 1))
            ->addColumn('actions', fn ($row) => $this->actionsCell((int) $row->active_inactive === 1))
            ->setRowAttr([
                'data-coe-id' => fn ($row) => encrypt($row->pk),
                'data-coe-active' => fn ($row) => (int) $row->active_inactive === 1 ? '1' : '0',
                // Room Name is a Venue Master select keyed by short name: the stored room_name.
                'data-coe-val-name' => fn ($row) => trim((string) $row->room_name),
                'data-coe-val-building' => fn ($row) => (string) $row->building_master_pk,
                'data-coe-val-floor' => fn ($row) => (string) $row->floor_master_pk,
                'data-coe-val-type' => fn ($row) => (string) $row->room_type,
            ])
            ->filterColumn('room_name', fn ($q, $kw) => $q->where('building_floor_room_mapping.room_name', 'like', '%'.$kw.'%'))
            ->filterColumn('building_master_pk', fn ($q, $kw) => $q->where('b.building_name', 'like', '%'.$kw.'%'))
            ->filterColumn('floor_master_pk', fn ($q, $kw) => $q->where('f.floor_name', 'like', '%'.$kw.'%'))
            ->filterColumn('room_type', fn ($q, $kw) => $q->where('building_floor_room_mapping.room_type', 'like', '%'.$kw.'%'))
            ->orderColumn('building_master_pk', 'b.building_name $1')
            ->orderColumn('floor_master_pk', 'f.floor_name $1')
            ->rawColumns(['status', 'actions']);
    }

    public function query(BuildingFloorRoomMapping $model): Builder
    {
        return $this->applyStatusFilter(self::classroomRooms($model->newQuery()), 'building_floor_room_mapping.active_inactive')
            ->orderBy('b.building_name')->orderBy('building_floor_room_mapping.floor_master_pk')->orderBy('building_floor_room_mapping.room_name');
    }

    /** Rooms of Classroom buildings, with building_name / floor_name. */
    public static function classroomRooms(Builder $query): Builder
    {
        return $query
            ->join('building_master as b', 'b.pk', '=', 'building_floor_room_mapping.building_master_pk')
            ->leftJoin('floor_master as f', 'f.pk', '=', 'building_floor_room_mapping.floor_master_pk')
            ->where('b.building_type', BuildingMaster::TYPE_CLASSROOM)
            ->select('building_floor_room_mapping.*', 'b.building_name', 'f.floor_name');
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('coe-col-sno'),
            Column::make('room_name')->title('Room Name'),
            Column::make('building_master_pk')->title('Building'),
            Column::make('floor_master_pk')->title('Floor'),
            Column::make('room_type')->title('Room Type'),
            Column::make('capacity')->title('Total Capacity')->searchable(false),
            Column::make('laptop_count')->title('Laptop Count')->searchable(false),
            Column::make('scriber_count')->title('Scriber Count')->searchable(false),
            Column::computed('status')->title('Status')->addClass('coe-col-center coe-cell-status'),
            Column::computed('actions')->title('Action')->addClass('coe-cell-actions'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('exrTable')
            ->columns($this->getColumns());
    }

    protected function filename(): string
    {
        return 'RoomMaster_' . date('YmdHis');
    }
}
