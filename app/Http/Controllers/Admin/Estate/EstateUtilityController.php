<?php

namespace App\Http\Controllers\Admin\Estate;

use App\DataTables\EstateContractualEmployeeUtilityDataTable;
use App\DataTables\EstateVacantHouseMonitoringDataTable;
use App\Http\Controllers\Controller;
use App\Services\Estate\ContractualEmployeeUtilityService;
use App\Services\Estate\EstateVacantHouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EstateUtilityController extends Controller
{
    public function __construct(
        private readonly EstateVacantHouseService $vacantHouseService,
        private readonly ContractualEmployeeUtilityService $contractualUtilityService
    ) {}

    public function vacantHouseMonitoring(EstateVacantHouseMonitoringDataTable $dataTable)
    {
        $this->authorizeEstateReports();

        if (Schema::hasTable('estate_vacant_house_monitoring')) {
            $user = Auth::user();
            $actorPk = $user ? (int) ($user->user_id ?? $user->pk ?? 0) : null;
            $this->vacantHouseService->syncActiveVacantRecords($actorPk ?: null);
        }

        return $dataTable->render('admin.estate.vacant_house_monitoring');
    }

    public function exportVacantHouseMonitoring(Request $request): StreamedResponse
    {
        $this->authorizeEstateReports();

        if (! Schema::hasTable('estate_vacant_house_monitoring')) {
            abort(404, 'Table not found. Please run the estate utility SQL script.');
        }

        $this->vacantHouseService->syncActiveVacantRecords($this->actorPk());

        $rows = DB::table('estate_vacant_house_monitoring')
            ->where('is_active', 1)
            ->orderBy('house_code')
            ->get();

        $filename = 'vacant_house_monitoring_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['House Code', 'House Name', 'Meter Number', 'Last Meter Reading before Vacancy', 'Last Allottee Employee Name', 'Vacancy Date']);
            foreach ($rows as $row) {
                $meter = (int) ($row->meter_number_two ?? 0) > 0
                    ? $row->meter_number . ' / ' . $row->meter_number_two
                    : (string) ($row->meter_number ?? '');
                $reading = (int) ($row->last_meter_reading_two_before_vacancy ?? 0) > 0
                    ? $row->last_meter_reading_before_vacancy . ' / ' . $row->last_meter_reading_two_before_vacancy
                    : (string) ($row->last_meter_reading_before_vacancy ?? '');
                fputcsv($out, [
                    $row->house_code,
                    $row->house_name,
                    $meter,
                    $reading,
                    $row->last_allottee_employee_name,
                    $row->vacancy_date,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function contractualEmployeeUtility(EstateContractualEmployeeUtilityDataTable $dataTable)
    {
        $this->authorizeEstateReports();

        return $dataTable->render(
            'admin.estate.contractual_employee_utility',
            $this->contractualUtilityService->filterOptions()
        );
    }

    public function updateContractualEmployeeUtilityInline(Request $request, int $id)
    {
        $this->authorizeEstateReports();

        if (! Schema::hasTable('estate_month_reading_details_other')) {
            return response()->json(['success' => false, 'message' => 'Table not found.'], 404);
        }

        $validated = $request->validate([
            'field' => 'required|in:bill_number',
            'value' => 'nullable|string|max:100',
        ]);

        $reading = $this->contractualUtilityService->findReadingRow($id);
        if (! $reading) {
            return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
        }

        $value = \Illuminate\Support\Str::limit((string) ($validated['value'] ?? ''), 100, '');
        $value = trim($value);
        $billNo = $value !== '' && is_numeric($value) ? (int) $value : 0;
        DB::table('estate_month_reading_details_other')->where('pk', $id)->update(['bill_no' => $billNo]);

        return response()->json(['success' => true, 'message' => 'Saved.']);
    }

    public function exportContractualEmployeeUtility(Request $request): StreamedResponse
    {
        $this->authorizeEstateReports();

        if (! Schema::hasTable('estate_month_reading_details_other')) {
            abort(404, 'Table not found.');
        }

        $q = $this->contractualUtilityService->listQuery()
            ->orderByDesc('emro.bill_year')
            ->orderBy('emro.bill_month')
            ->orderBy('employee_name');

        $this->contractualUtilityService->applyFilters($q, $request);

        $rows = $q->get();
        $filename = 'contractual_employee_utility_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Employee Name', 'Section', 'House Code', 'Month', 'Year',
                'Electricity Charges', 'Water Charges', 'Licence Fee', 'Total Amount',
                'Bill Number',
            ]);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->employee_name,
                    $row->department,
                    $row->house_code,
                    $row->bill_month,
                    $row->bill_year,
                    $row->electricity_charges,
                    $row->water_charges,
                    $row->licence_fee,
                    $row->total_amount,
                    $this->contractualUtilityService->resolveBillNumber($row->bill_no ?? null, $row->pk ?? null),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function authorizeEstateReports(): void
    {
        abort_unless(hasRole('Estate') || hasRole('Super Admin'), 403, 'You do not have permission to access this estate section.');
    }

    private function actorPk(): ?int
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }
        $pk = (int) ($user->user_id ?? $user->pk ?? 0);

        return $pk > 0 ? $pk : null;
    }
}
