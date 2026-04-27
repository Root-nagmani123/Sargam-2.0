<?php

namespace App\DataTables\FC;

use App\Models\FC\StudentTravelPlanMaster;
use App\Services\FC\FcTravelPlanReportService;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class FcTravelPlanReportDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->query($query)
            ->addIndexColumn()
            ->editColumn('full_name', function ($row) {
                $name = trim((string) ($row->full_name ?? ''));
                if ($name !== '') {
                    return e($name);
                }
                $smName = trim((string) ($row->sm_full_name ?? ''));
                if ($smName !== '') {
                    return e($smName);
                }

                return '<span class="text-muted" title="No student_master_firsts row">' . e($row->username ?? '—') . '</span>';
            })
            ->editColumn('mobile_no', fn ($row) => ($row->mobile_no !== null && (string) $row->mobile_no !== '') ? e($row->mobile_no) : '—')
            ->editColumn('roll_no', fn ($row) => ($row->roll_no !== null && (string) $row->roll_no !== '') ? $row->roll_no : '—')
            ->editColumn('joining_date', fn ($row) => $row->joining_date ? \Carbon\Carbon::parse($row->joining_date)->format('d-m-Y') : '—')
            ->editColumn('academy_arrival_date', fn ($row) => $row->academy_arrival_date ? \Carbon\Carbon::parse($row->academy_arrival_date)->format('d-m-Y') : '—')
            ->editColumn('mode_of_journey', fn ($row) => $row->mode_of_journey ? $row->mode_of_journey : '—')
            ->editColumn('journey_vehicle_no', fn ($row) => $row->journey_vehicle_no ? $row->journey_vehicle_no : '—')
            ->editColumn('arrival_time_dehradun', fn ($row) => $row->arrival_time_dehradun ? $row->arrival_time_dehradun : '—')
            ->editColumn('service_code', fn ($row) => $row->service_code ? $row->service_code : '—')
            ->editColumn('slot_display', function ($row) {
                $label = $row->slot_label ?? '—';
                if (! empty($row->time_start) && ! empty($row->time_end)) {
                    $label .= ' <small class="text-muted">(' . substr((string) $row->time_start, 0, 5) . '–' . substr((string) $row->time_end, 0, 5) . ')</small>';
                }

                return $label;
            })
            ->editColumn('require_academy_vehicle', function ($row) {
                if ($row->require_academy_vehicle === null || $row->require_academy_vehicle === '') {
                    return '—';
                }

                return StudentTravelPlanMaster::interpretRequiresAcademyVehicle($row->require_academy_vehicle)
                    ? '<span class="badge bg-success">Yes</span>'
                    : '<span class="badge bg-secondary">No</span>';
            })
            ->editColumn('is_submitted', function ($row) {
                return StudentTravelPlanMaster::interpretIsSubmitted($row->is_submitted ?? null)
                    ? '<span class="badge bg-success">Yes</span>'
                    : '<span class="badge bg-warning text-dark">Draft</span>';
            })
            ->addColumn('action', function ($row) {
                $url = route('admin.travel.show', $row->username);

                return '<a href="'.e($url).'" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:11px"><i class="bi bi-eye"></i></a>';
            })
            ->rawColumns(['full_name', 'slot_display', 'require_academy_vehicle', 'is_submitted', 'action'])
            ->orderColumn('full_name', 'COALESCE(NULLIF(TRIM(s1.full_name), \'\'), NULLIF(TRIM(sm.full_name), \'\'), tp.username) $1')
            ->orderColumn('roll_no', 'COALESCE(NULLIF(TRIM(s1.roll_no), \'\'), sm.roll_no, s1.roll_no) $1')
            ->orderColumn('mobile_no', 'COALESCE(s1.mobile_no, \'\') $1')
            ->orderColumn('joining_date', 'tp.joining_date $1')
            ->orderColumn('mode_of_journey', 'tp.mode_of_journey $1')
            ->orderColumn('journey_vehicle_no', 'tp.journey_vehicle_no $1')
            ->orderColumn('academy_arrival_date', 'tp.academy_arrival_date $1')
            ->orderColumn('arrival_time_dehradun', 'tp.arrival_time_dehradun $1')
            ->orderColumn('service_code', 'COALESCE(svc.service_code, sm.service_code) $1')
            ->orderColumn('is_submitted', 'tp.is_submitted $1')
            ->orderColumn('require_academy_vehicle', 'tp.require_academy_vehicle $1')
            ->filter(function ($q) {
                FcTravelPlanReportService::applyFilters($q, request());
            }, false);
    }

    public function query()
    {
        return FcTravelPlanReportService::baseQuery();
    }

    public function html(): HtmlBuilder
    {
        $ajaxData = "function (d) {
            d.filter_session_id = document.getElementById('f_session_id') ? document.getElementById('f_session_id').value : '';
            d.filter_slot_id = document.getElementById('f_slot_id') ? document.getElementById('f_slot_id').value : '';
            d.filter_submitted = document.getElementById('f_submitted') ? document.getElementById('f_submitted').value : '';
            d.filter_mode = document.getElementById('f_mode') ? document.getElementById('f_mode').value : '';
            d.filter_vehicle = document.getElementById('f_vehicle') ? document.getElementById('f_vehicle').value : '';
            d.date_from = document.getElementById('f_date_from') ? document.getElementById('f_date_from').value : '';
            d.date_to = document.getElementById('f_date_to') ? document.getElementById('f_date_to').value : '';
        }";

        return $this->builder()
            ->setTableId('fcTravelPlanReportTable')
            ->addTableClass('table table-hover table-sm text-nowrap align-middle mb-0')
            ->columns($this->getColumns())
            ->ajax([
                'url'  => route('admin.travel.index'),
                'type' => 'GET',
                'data' => $ajaxData,
            ])
            ->parameters([
                'responsive'  => true,
                'autoWidth'   => false,
                'scrollX'     => true,
                'ordering'    => true,
                'searching'   => true,
                'lengthChange'=> true,
                'pageLength'  => 25,
                'order'       => [[1, 'asc']],
                'lengthMenu'  => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'processing'  => true,
                'serverSide'  => true,
                'dom'         => '<"row mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false)->width('48px'),
            Column::make('full_name')->title('Name'),
            Column::make('roll_no')->title('Code'),
            Column::make('mobile_no')->title('Mobile'),
            Column::make('joining_date')->title('Arrival date'),
            Column::computed('slot_display')->title('Slot & time')->orderable(false)->searchable(false),
            Column::make('mode_of_journey')->title('Mode of journey'),
            Column::make('journey_vehicle_no')->title('Flight/Train/Vehicle no.'),
            Column::make('academy_arrival_date')->title('Date of arrival at Academy'),
            Column::make('arrival_time_dehradun')->title('Arrival time at Dehradun (Airport)'),
            Column::make('require_academy_vehicle')->title('Require academy vehicle?')->searchable(false),
            Column::make('service_code')->title('Service'),
            Column::make('is_submitted')->title('Submitted')->searchable(false),
            Column::computed('action')->title('')->orderable(false)->searchable(false)->width('50px'),
        ];
    }
}
