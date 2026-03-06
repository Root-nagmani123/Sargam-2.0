<?php

namespace App\DataTables;

use App\Models\EstatePossessionOther;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EstateReturnHouseDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('name', fn($row) => $row->estateOtherRequest->emp_name ?? 'N/A')
            ->editColumn('employee_type', fn() => 'Other Employee')
            ->editColumn('section_name', fn($row) => $row->estateOtherRequest->section ?? 'N/A')
            ->editColumn('estate_name', fn($row) => $row->campus_name ?? '—')
            ->editColumn('unit_name', fn($row) => $row->unit_type_name ?? '—')
            ->editColumn('building_name', fn($row) => $row->block_name ?? '—')
            ->editColumn('house_no', fn($row) => $row->house_no ?? $row->house_no_display ?? '—')
            ->editColumn('unit_sub_type', fn($row) => $row->unit_sub_type_name ?? '—')
            ->editColumn('allotment_date', fn($row) => $row->allotment_date ? $row->allotment_date->format('d-m-Y') : '—')
            ->editColumn('possession_date_oth', fn($row) => $row->possession_date_oth ? $row->possession_date_oth->format('d-m-Y') : '—')
            ->editColumn('returning_date', fn($row) => $row->current_meter_reading_date ? $row->current_meter_reading_date->format('d-m-Y') : '—')
            ->editColumn('upload_document', function ($row) {
                $path = $row->upload_document ?? $row->noc_document ?? null;
                if (!$path) {
                    $path = $this->returnHouseMetaByPk()[(string) $row->pk]['upload_document'] ?? null;
                }
                if (!$path) {
                    return '—';
                }
                $url = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
                return '<a href="' . e($url) . '" target="_blank" rel="noopener">View</a>';
            })
            ->editColumn('remarks', function ($row) {
                $remarks = $row->remarks;
                if (!$remarks) {
                    $remarks = $this->returnHouseMetaByPk()[(string) $row->pk]['remarks'] ?? null;
                }
                return $remarks ?: '—';
            })
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');
                if (!empty($searchValue)) {
                    $query->where(function ($q) use ($searchValue) {
                        $q->whereHas('estateOtherRequest', function ($sq) use ($searchValue) {
                            $sq->where('emp_name', 'like', "%{$searchValue}%")
                                ->orWhere('request_no_oth', 'like', "%{$searchValue}%")
                                ->orWhere('section', 'like', "%{$searchValue}%");
                        })
                            ->orWhere('ehm.house_no', 'like', "%{$searchValue}%")
                            ->orWhere('estate_possession_other.house_no', 'like', "%{$searchValue}%")
                            ->orWhere('ec.campus_name', 'like', "%{$searchValue}%")
                            ->orWhere('eb.block_name', 'like', "%{$searchValue}%")
                            ->orWhere('eut.unit_type', 'like', "%{$searchValue}%")
                            ->orWhere('eust.unit_sub_type', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->rawColumns(['upload_document'])
            ->setRowId('pk');
    }

    public function query(EstatePossessionOther $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['estateOtherRequest'])
            ->select([
                'estate_possession_other.*',
                'ec.campus_name',
                'eb.block_name',
                'eut.unit_type as unit_type_name',
                'eust.unit_sub_type as unit_sub_type_name',
                'ehm.house_no as house_no_display',
            ])
            ->leftJoin('estate_campus_master as ec', 'estate_possession_other.estate_campus_master_pk', '=', 'ec.pk')
            ->leftJoin('estate_block_master as eb', 'estate_possession_other.estate_block_master_pk', '=', 'eb.pk')
            ->leftJoin('estate_unit_type_master as eut', 'estate_possession_other.estate_unit_type_master_pk', '=', 'eut.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'estate_possession_other.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->leftJoin('estate_house_master as ehm', 'estate_possession_other.estate_house_master_pk', '=', 'ehm.pk')
            ->where('estate_possession_other.return_home_status', 1)
            ->orderBy('estate_possession_other.pk', 'desc');
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
                'order' => [[0, 'asc']],
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
            Column::make('name')->title('Name')->orderable(false)->searchable(false),
            Column::make('employee_type')->title('Employee Type')->orderable(false)->searchable(false),
            Column::make('section_name')->title('Section')->orderable(false)->searchable(false),
            Column::make('estate_name')->title('Estate Name')->orderable(false)->searchable(false),
            Column::make('house_no')->title('House No.')->orderable(false)->searchable(false),
            Column::make('unit_name')->title('Unit Name')->orderable(false)->searchable(false),
            Column::make('building_name')->title('Building Name')->orderable(false)->searchable(false),
            Column::make('unit_sub_type')->title('Unit Subtype')->orderable(false)->searchable(false),
            Column::make('allotment_date')->title('Date of Allotment')->orderable(false)->searchable(false),
            Column::make('possession_date_oth')->title('Date of Possession')->orderable(false)->searchable(false),
            Column::make('returning_date')->title('Returning Date')->orderable(false)->searchable(false),
            Column::make('upload_document')->title('Upload Document')->orderable(false)->searchable(false),
            Column::make('remarks')->title('Remarks')->orderable(false)->searchable(false),
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
