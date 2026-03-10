<?php

namespace App\DataTables;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Facades\DataTables;

class EstateReturnHouseDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query)
    {
        $meta = $this->returnHouseMetaByPk();

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('upload_document', function ($row) use ($meta) {
                $scope = (string) ($row->scope ?? '');
                $pk = (string) ($row->pk ?? '');
                $key = $scope !== '' ? ($scope . ':' . $pk) : $pk;

                $path = $row->upload_document ?? null;
                if (!$path) {
                    $path = $meta[$key]['upload_document'] ?? ($meta[$pk]['upload_document'] ?? null);
                }
                if (!$path) return '—';

                $url = Storage::disk('public')->url($path);
                return '<a href="' . e($url) . '" target="_blank" rel="noopener">View</a>';
            })
            ->editColumn('remarks', function ($row) use ($meta) {
                $scope = (string) ($row->scope ?? '');
                $pk = (string) ($row->pk ?? '');
                $key = $scope !== '' ? ($scope . ':' . $pk) : $pk;

                $remarks = $row->remarks ?? null;
                if (!$remarks) {
                    $remarks = $meta[$key]['remarks'] ?? ($meta[$pk]['remarks'] ?? null);
                }
                return $remarks ?: '—';
            })
            ->rawColumns(['upload_document'])
            ->setRowId('row_id');
    }

    public function query(): QueryBuilder
    {
        $hasUnitTypeOnSubType = Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');

        // LBSNAA returned houses (estate_possession_details)
        $lbsnaa = DB::table('estate_possession_details as epd')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->leftJoin('estate_campus_master as ec', 'ehm.estate_campus_master_pk', '=', 'ec.pk')
            ->leftJoin('estate_block_master as eb', 'ehm.estate_block_master_pk', '=', 'eb.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->leftJoin('estate_unit_type_master as eut', function ($join) use ($hasUnitTypeOnSubType) {
                if ($hasUnitTypeOnSubType) {
                    $join->on('eust.estate_unit_type_master_pk', '=', 'eut.pk');
                } else {
                    $join->on('ehm.estate_unit_master_pk', '=', 'eut.pk');
                }
            })
            ->whereNotNull('epd.estate_house_master_pk')
            ->where('epd.estate_change_id', -1)
            ->when(Schema::hasColumn('estate_possession_details', 'return_home_status'), function ($q) {
                $q->where('epd.return_home_status', 1);
            })
            ->selectRaw("
                epd.pk as pk,
                CONCAT('L-', epd.pk) as row_id,
                'L' as scope,
                'LBSNAA' as employee_type,
                ehrd.emp_name as name,
                NULL as section_name,
                ec.campus_name as estate_name,
                eut.unit_type as unit_name,
                eb.block_name as building_name,
                ehm.house_no as house_no,
                eust.unit_sub_type as unit_sub_type,
                epd.allotment_date as allotment_date,
                epd.possession_date as possession_date_oth,
                epd.current_meter_reading_date as returning_date,
                NULL as upload_document,
                " . (Schema::hasColumn('estate_possession_details', 'remarks') ? 'epd.remarks' : 'NULL') . " as remarks
            ");

        // Other Employee returned houses (estate_possession_other)
        $other = DB::table('estate_possession_other as epo')
            ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
            ->leftJoin('estate_campus_master as ec', 'epo.estate_campus_master_pk', '=', 'ec.pk')
            ->leftJoin('estate_block_master as eb', 'epo.estate_block_master_pk', '=', 'eb.pk')
            ->leftJoin('estate_unit_type_master as eut', 'epo.estate_unit_type_master_pk', '=', 'eut.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'epo.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->leftJoin('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
            ->where('epo.return_home_status', 1)
            ->selectRaw("
                epo.pk as pk,
                CONCAT('O-', epo.pk) as row_id,
                'O' as scope,
                'Other Employee' as employee_type,
                eor.emp_name as name,
                eor.section as section_name,
                ec.campus_name as estate_name,
                eut.unit_type as unit_name,
                eb.block_name as building_name,
                COALESCE(NULLIF(TRIM(epo.house_no),''), ehm.house_no) as house_no,
                eust.unit_sub_type as unit_sub_type,
                epo.allotment_date as allotment_date,
                epo.possession_date_oth as possession_date_oth,
                epo.current_meter_reading_date as returning_date,
                " . (Schema::hasColumn('estate_possession_other', 'upload_document')
                    ? (Schema::hasColumn('estate_possession_other', 'noc_document')
                        ? 'COALESCE(epo.upload_document, epo.noc_document)'
                        : 'epo.upload_document')
                    : (Schema::hasColumn('estate_possession_other', 'noc_document')
                        ? 'epo.noc_document'
                        : 'NULL')) . " as upload_document,
                " . (Schema::hasColumn('estate_possession_other', 'remarks') ? 'epo.remarks' : 'NULL') . " as remarks
            ");

        return $lbsnaa->unionAll($other);
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('returnHouseTable')
            ->addTableClass('table table-striped table-hover align-middle mb-0 text-nowrap w-100')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'ordering' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'order' => [[0, 'desc']],
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'infoEmpty' => 'Showing 0 to 0 of 0 entries',
                    'infoFiltered' => '(filtered from _MAX_ total entries)',
                    'paginate' => [
                        'first' => 'First',
                        'last' => 'Last',
                        'next' => 'Next',
                        'previous' => 'Previous',
                    ],
                ],
                'dom' => '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                'scrollX' => true,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            Column::make('name')->title('Name'),
            Column::make('employee_type')->title('Employee Type'),
            Column::make('section_name')->title('Section'),
            Column::make('estate_name')->title('Estate Name'),
            Column::make('house_no')->title('House No.'),
            Column::make('unit_name')->title('Unit Name'),
            Column::make('building_name')->title('Building Name'),
            Column::make('unit_sub_type')->title('Unit Subtype'),
            Column::make('allotment_date')->title('Date of Allotment'),
            Column::make('possession_date_oth')->title('Date of Possession'),
            Column::make('returning_date')->title('Returning Date'),
            Column::make('upload_document')->title('Upload Document'),
            Column::make('remarks')->title('Remarks'),
        ];
    }

    protected function filename(): string
    {
        return 'EstateReturnHouse_' . date('YmdHis');
    }

    private function returnHouseMetaByPk(): array
    {
        static $meta = null;
        if ($meta !== null) {
            return $meta;
        }

        $file = 'estate/return-house-meta.json';
        if (!Storage::disk('local')->exists($file)) {
            $meta = [];
            return $meta;
        }

        $decoded = json_decode((string) Storage::disk('local')->get($file), true);
        $meta = is_array($decoded) ? $decoded : [];
        return $meta;
    }
}
