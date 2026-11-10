<?php

namespace App\DataTables;

use App\DataTables\Concerns\CoeMasterCells;
use App\Models\FloorMaster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * COE → Master Data → Floor Master, server-side via Yajra.
 *
 * The shared floor_master, every floor: floors are universal, not per building.
 * Total Rooms is left blank until floors are mapped for the COE; mapped_rooms
 * (rooms on the floor, any building) only decides whether it may be deleted.
 */
class CoeFloorMasterDataTable extends DataTable
{
    use CoeMasterCells;

    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('floor_name', fn ($row) => e(trim((string) $row->floor_name)))
            ->addColumn('total_rooms', fn () => '')
            ->addColumn('status', fn ($row) => $this->statusCell((int) $row->active_inactive === 1))
            ->addColumn('actions', fn ($row) => $this->actionsCell((int) $row->active_inactive === 1,
                (int) $row->mapped_rooms > 0 ? 'Rooms are mapped to this floor, so it cannot be deleted' : ''))
            ->setRowAttr([
                'data-coe-id' => fn ($row) => encrypt($row->pk),
                'data-coe-active' => fn ($row) => (int) $row->active_inactive === 1 ? '1' : '0',
            ])
            ->filterColumn('floor_name', fn ($q, $kw) => $q->where('floor_name', 'like', '%'.$kw.'%'))
            ->rawColumns(['status', 'actions']);
    }

    public function query(FloorMaster $model): Builder
    {
        return $this->applyStatusFilter(self::withMappedRooms($model->newQuery()))->orderBy('pk');
    }

    /** Adds mapped_rooms: rows of building_floor_room_mapping on the floor, any building. */
    public static function withMappedRooms(Builder $query): Builder
    {
        return $query->select('floor_master.*')->selectSub(
            DB::table('building_floor_room_mapping')->selectRaw('COUNT(*)')
                ->whereColumn('building_floor_room_mapping.floor_master_pk', 'floor_master.pk'),
            'mapped_rooms'
        );
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->addClass('coe-col-sno'),
            Column::make('floor_name')->title('Floor Name'),
            Column::computed('total_rooms')->title('Total Rooms'),
            Column::computed('status')->title('Status')->addClass('coe-col-center coe-cell-status'),
            Column::computed('actions')->title('Action')->addClass('coe-cell-actions'),
        ];
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('efmTable')
            ->columns($this->getColumns());
    }

    protected function filename(): string
    {
        return 'FloorMaster_' . date('YmdHis');
    }
}
