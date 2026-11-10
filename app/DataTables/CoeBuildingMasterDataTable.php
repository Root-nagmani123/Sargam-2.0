<?php

namespace App\DataTables;

use App\DataTables\Concerns\CoeMasterCells;
use App\Models\BuildingMaster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * COE → Master Data → Building Master, server-side via Yajra.
 *
 * The shared building_master, Classroom buildings only (hostels and the rest are
 * not examination venues). Total Floors / Total Rooms are the building's own
 * no_of_floors / no_of_rooms; mapped_rooms (rooms in building_floor_room_mapping)
 * only decides whether it may be deleted.
 */
class CoeBuildingMasterDataTable extends DataTable
{
    use CoeMasterCells;

    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('building_name', fn ($row) => e(trim((string) $row->building_name)))
            ->editColumn('no_of_floors', fn ($row) => $row->no_of_floors === null ? '' : (int) $row->no_of_floors)
            ->editColumn('no_of_rooms', fn ($row) => $row->no_of_rooms === null ? '' : (int) $row->no_of_rooms)
            ->addColumn('status', fn ($row) => $this->statusCell((int) $row->active_inactive === 1))
            ->addColumn('actions', fn ($row) => $this->actionsCell((int) $row->active_inactive === 1,
                (int) $row->mapped_rooms > 0 ? 'Rooms are mapped to this building; remove them in Room Master first' : ''))
            ->setRowAttr([
                'data-coe-id' => fn ($row) => encrypt($row->pk),
                'data-coe-active' => fn ($row) => (int) $row->active_inactive === 1 ? '1' : '0',
            ])
            ->filterColumn('building_name', fn ($q, $kw) => $q->where('building_name', 'like', '%'.$kw.'%'))
            ->rawColumns(['status', 'actions']);
    }

    public function query(BuildingMaster $model): Builder
    {
        return $this->applyStatusFilter(self::withMappedRooms($model->newQuery()->classroom()))->orderBy('building_name');
    }

    /** Adds mapped_rooms: rows of building_floor_room_mapping on the building. */
    public static function withMappedRooms(Builder $query): Builder
    {
        return $query->select('building_master.*')->selectSub(
            DB::table('building_floor_room_mapping')->selectRaw('COUNT(*)')
                ->whereColumn('building_floor_room_mapping.building_master_pk', 'building_master.pk'),
            'mapped_rooms'
        );
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('coe-col-sno'),
            Column::make('building_name')->title('Building Name'),
            Column::make('no_of_floors')->title('Total Floors')->searchable(false),
            Column::make('no_of_rooms')->title('Total Rooms')->searchable(false),
            Column::computed('status')->title('Status')->addClass('coe-col-center coe-cell-status'),
            Column::computed('actions')->title('Action')->addClass('coe-cell-actions'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('ebmTable')
            ->columns($this->getColumns());
    }

    protected function filename(): string
    {
        return 'BuildingMaster_' . date('YmdHis');
    }
}
