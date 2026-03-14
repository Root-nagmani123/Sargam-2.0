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
            // Use a no-op filter callback to disable Yajra's default
            // global search. Actual search is handled inside query().
            ->filter(function ($query) {
                // Intentionally left blank.
            })
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
        $request = $this->request();
        $searchValue = '';
        if ($request && is_array($request->get('search'))) {
            $searchValue = strtolower((string) ($request->get('search')['value'] ?? ''));
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        $isPrivileged = hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST');
        $employeeIds = [];
        if (! $isPrivileged && $user) {
            $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null) ?: [];
        }

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
            ->when(Schema::hasColumn('estate_possession_details', 'return_home_status'), function ($q) {
                $q->where('epd.return_home_status', 1);
            })
            // RBAC: non-privileged users see only their own LBSNAA records.
            // Use BOTH request.employee_pk and possession.emploee_master_pk to handle legacy/migrated rows
            // where employee_pk on request may be null/0 but possession is linked correctly.
            ->when(!$isPrivileged && !empty($employeeIds), function ($q) use ($employeeIds) {
                $q->where(function ($sub) use ($employeeIds) {
                    $sub->whereIn('ehrd.employee_pk', $employeeIds)
                        ->orWhereIn('epd.emploee_master_pk', $employeeIds);
                });
            })
            // Manual global search on underlying columns (cannot use aliases in WHERE).
            ->when($searchValue !== '', function ($q) use ($searchValue) {
                $like = '%' . $searchValue . '%';
                $q->where(function ($sub) use ($like) {
                    $sub
                        // name (ehrd.emp_name)
                        ->orWhereRaw('LOWER(ehrd.emp_name) LIKE ?', [$like])
                        // estate_name (ec.campus_name)
                        ->orWhereRaw('LOWER(ec.campus_name) LIKE ?', [$like])
                        // unit_name (eut.unit_type)
                        ->orWhereRaw('LOWER(eut.unit_type) LIKE ?', [$like])
                        // building_name (eb.block_name)
                        ->orWhereRaw('LOWER(eb.block_name) LIKE ?', [$like])
                        // house_no (ehm.house_no)
                        ->orWhereRaw('LOWER(ehm.house_no) LIKE ?', [$like])
                        // unit_sub_type (eust.unit_sub_type)
                        ->orWhereRaw('LOWER(eust.unit_sub_type) LIKE ?', [$like])
                        // allotment_date (epd.allotment_date)
                        ->orWhereRaw('LOWER(CAST(epd.allotment_date AS CHAR)) LIKE ?', [$like])
                        // possession_date_oth (epd.possession_date)
                        ->orWhereRaw('LOWER(CAST(epd.possession_date AS CHAR)) LIKE ?', [$like])
                        // returning_date (epd.current_meter_reading_date)
                        ->orWhereRaw('LOWER(CAST(epd.current_meter_reading_date AS CHAR)) LIKE ?', [$like])
                        // remarks (epd.remarks) if exists
                        ->when(Schema::hasColumn('estate_possession_details', 'remarks'), function ($inner) use ($like) {
                            $inner->orWhereRaw('LOWER(epd.remarks) LIKE ?', [$like]);
                        });
                });
            })
            ->selectRaw("
                epd.pk as pk,
                CONVERT(CONCAT('L-', epd.pk) USING utf8mb4) COLLATE utf8mb4_unicode_ci as row_id,
                CONVERT('L' USING utf8mb4) COLLATE utf8mb4_unicode_ci as scope,
                CONVERT('LBSNAA' USING utf8mb4) COLLATE utf8mb4_unicode_ci as employee_type,
                CONVERT(ehrd.emp_name USING utf8mb4) COLLATE utf8mb4_unicode_ci as name,
                CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as section_name,
                CONVERT(ec.campus_name USING utf8mb4) COLLATE utf8mb4_unicode_ci as estate_name,
                CONVERT(eut.unit_type USING utf8mb4) COLLATE utf8mb4_unicode_ci as unit_name,
                CONVERT(eb.block_name USING utf8mb4) COLLATE utf8mb4_unicode_ci as building_name,
                CONVERT(ehm.house_no USING utf8mb4) COLLATE utf8mb4_unicode_ci as house_no,
                CONVERT(eust.unit_sub_type USING utf8mb4) COLLATE utf8mb4_unicode_ci as unit_sub_type,
                epd.allotment_date as allotment_date,
                epd.possession_date as possession_date_oth,
                epd.current_meter_reading_date as returning_date,
                CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as upload_document,
                " . (Schema::hasColumn('estate_possession_details', 'remarks')
                    ? "CONVERT(epd.remarks USING utf8mb4) COLLATE utf8mb4_unicode_ci"
                    : "CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci") . " as remarks
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
            // RBAC: non-privileged users should not see "Other Employee" section at all
            ->when(!$isPrivileged, function ($q) {
                $q->whereRaw('1 = 0');
            })
            // Manual global search for "Other Employee" branch.
            ->when($searchValue !== '', function ($q) use ($searchValue) {
                $like = '%' . $searchValue . '%';
                $q->where(function ($sub) use ($like) {
                    $sub
                        // name (eor.emp_name)
                        ->orWhereRaw('LOWER(eor.emp_name) LIKE ?', [$like])
                        // section_name (eor.section)
                        ->orWhereRaw('LOWER(eor.section) LIKE ?', [$like])
                        // estate_name (ec.campus_name)
                        ->orWhereRaw('LOWER(ec.campus_name) LIKE ?', [$like])
                        // unit_name (eut.unit_type)
                        ->orWhereRaw('LOWER(eut.unit_type) LIKE ?', [$like])
                        // building_name (eb.block_name)
                        ->orWhereRaw('LOWER(eb.block_name) LIKE ?', [$like])
                        // house_no (COALESCE(NULLIF(TRIM(epo.house_no),''), ehm.house_no))
                        ->orWhereRaw("LOWER(COALESCE(NULLIF(TRIM(epo.house_no), ''), ehm.house_no)) LIKE ?", [$like])
                        // unit_sub_type (eust.unit_sub_type)
                        ->orWhereRaw('LOWER(eust.unit_sub_type) LIKE ?', [$like])
                        // allotment_date (epo.allotment_date)
                        ->orWhereRaw('LOWER(CAST(epo.allotment_date AS CHAR)) LIKE ?', [$like])
                        // possession_date_oth (epo.possession_date_oth)
                        ->orWhereRaw('LOWER(CAST(epo.possession_date_oth AS CHAR)) LIKE ?', [$like])
                        // returning_date (epo.current_meter_reading_date)
                        ->orWhereRaw('LOWER(CAST(epo.current_meter_reading_date AS CHAR)) LIKE ?', [$like])
                        // upload_document (various columns)
                        ->when(Schema::hasColumn('estate_possession_other', 'upload_document') || Schema::hasColumn('estate_possession_other', 'noc_document'), function ($inner) use ($like) {
                            if (Schema::hasColumn('estate_possession_other', 'upload_document') && Schema::hasColumn('estate_possession_other', 'noc_document')) {
                                $inner->orWhereRaw('LOWER(COALESCE(epo.upload_document, epo.noc_document)) LIKE ?', [$like]);
                            } elseif (Schema::hasColumn('estate_possession_other', 'upload_document')) {
                                $inner->orWhereRaw('LOWER(epo.upload_document) LIKE ?', [$like]);
                            } elseif (Schema::hasColumn('estate_possession_other', 'noc_document')) {
                                $inner->orWhereRaw('LOWER(epo.noc_document) LIKE ?', [$like]);
                            }
                        })
                        // remarks (epo.remarks) if exists
                        ->when(Schema::hasColumn('estate_possession_other', 'remarks'), function ($inner) use ($like) {
                            $inner->orWhereRaw('LOWER(epo.remarks) LIKE ?', [$like]);
                        });
                });
            })
            ->selectRaw("
                epo.pk as pk,
                CONVERT(CONCAT('O-', epo.pk) USING utf8mb4) COLLATE utf8mb4_unicode_ci as row_id,
                CONVERT('O' USING utf8mb4) COLLATE utf8mb4_unicode_ci as scope,
                CONVERT('Other Employee' USING utf8mb4) COLLATE utf8mb4_unicode_ci as employee_type,
                CONVERT(eor.emp_name USING utf8mb4) COLLATE utf8mb4_unicode_ci as name,
                CONVERT(eor.section USING utf8mb4) COLLATE utf8mb4_unicode_ci as section_name,
                CONVERT(ec.campus_name USING utf8mb4) COLLATE utf8mb4_unicode_ci as estate_name,
                CONVERT(eut.unit_type USING utf8mb4) COLLATE utf8mb4_unicode_ci as unit_name,
                CONVERT(eb.block_name USING utf8mb4) COLLATE utf8mb4_unicode_ci as building_name,
                CONVERT(COALESCE(NULLIF(TRIM(epo.house_no),''), ehm.house_no) USING utf8mb4) COLLATE utf8mb4_unicode_ci as house_no,
                CONVERT(eust.unit_sub_type USING utf8mb4) COLLATE utf8mb4_unicode_ci as unit_sub_type,
                epo.allotment_date as allotment_date,
                epo.possession_date_oth as possession_date_oth,
                epo.current_meter_reading_date as returning_date,
                " . (Schema::hasColumn('estate_possession_other', 'upload_document')
                    ? (Schema::hasColumn('estate_possession_other', 'noc_document')
                        ? 'CONVERT(COALESCE(epo.upload_document, epo.noc_document) USING utf8mb4) COLLATE utf8mb4_unicode_ci'
                        : 'CONVERT(epo.upload_document USING utf8mb4) COLLATE utf8mb4_unicode_ci')
                    : (Schema::hasColumn('estate_possession_other', 'noc_document')
                        ? 'CONVERT(epo.noc_document USING utf8mb4) COLLATE utf8mb4_unicode_ci'
                        : 'CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci')) . " as upload_document,
                " . (Schema::hasColumn('estate_possession_other', 'remarks')
                    ? 'CONVERT(epo.remarks USING utf8mb4) COLLATE utf8mb4_unicode_ci'
                    : 'CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci') . " as remarks
            ");

        // Default ordering: latest returned should appear first.
        // We must wrap UNION in a subquery to apply ORDER BY reliably.
        $union = $lbsnaa->unionAll($other);
        return DB::query()
            ->fromSub($union, 'rh')
            ->orderByDesc('returning_date')
            ->orderByDesc('pk');
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
                // Default sort by Returning Date (desc). Column index is 0-based in DataTables config.
                'order' => [[11, 'desc']],
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
