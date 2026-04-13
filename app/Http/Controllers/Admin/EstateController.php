<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\EstateApprovalSettingDataTable;
use App\DataTables\EstateMigrationReportDataTable;
use App\DataTables\EstateOtherRequestDataTable;
use App\DataTables\EstatePossessionDetailsDataTable;
use App\DataTables\EstatePossessionOtherDataTable;
use App\DataTables\EstateRequestForEstateDataTable;
use App\DataTables\EstateReturnHouseDataTable;
use App\DataTables\EstateRequestPutInHacDataTable;
use App\DataTables\EstateHacApprovedDataTable;
use App\Http\Controllers\Controller;
use App\Models\EstateChangeHomeReqDetails;
use App\Models\EstateMonthReadingDetailsOther;
use App\Models\EstateHomeRequestDetails;
use App\Models\EstateHomeReqApprovalMgmt;
use App\Models\EstateMigrationReport;
use App\Models\EstateOtherRequest;
use App\Models\EstateElectricSlab;
use Illuminate\Support\Facades\Schema;
use App\Models\EstatePossessionOther;
use App\Models\EmployeeMaster;
use App\Models\EstateHouse;
use App\Models\EstateMonthReadingDetails;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Services\NotificationReceiverService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class EstateController extends Controller
{
    /**
     * Column on employee_master that payroll_salary_master.employee_master_pk joins to (often pk_old when that column exists).
     * estate_possession_details.emploee_master_pk is canonical employee_master.pk — use resolveEmployeeMasterCanonicalPk() when saving.
     */
    private function estateEmployeePkColumn(): string
    {
        return \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'pk_old') ? 'pk_old' : 'pk';
    }

    /**
     * Meter number is missing or zero (used when falling back to estate_update_reading.old_meter_no_*).
     */
    private function estateMeterNoIsEmpty($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return (int) $value === 0;
    }

    /**
     * Map one Update Meter No. row (union of permanent + other) to DataTable payload fields.
     */
    private function mapUpdateMeterNoListRow(object $r): array
    {
        $oldM1Source = $r->prev_meter_one;
        if ($this->estateMeterNoIsEmpty($oldM1Source) && isset($r->eur_old_meter_one) && $r->eur_old_meter_one !== null && ! $this->estateMeterNoIsEmpty($r->eur_old_meter_one)) {
            $oldM1Source = $r->eur_old_meter_one;
        }
        $oldM2Source = $r->prev_meter_two;
        if ($this->estateMeterNoIsEmpty($oldM2Source) && isset($r->eur_old_meter_two) && $r->eur_old_meter_two !== null && ! $this->estateMeterNoIsEmpty($r->eur_old_meter_two)) {
            $oldM2Source = $r->eur_old_meter_two;
        }

        $oldM1 = ! $this->estateMeterNoIsEmpty($oldM1Source) ? (string) $oldM1Source : '—';
        $newM1 = $r->emrd_meter_one !== null && $r->emrd_meter_one !== '' ? (string) $r->emrd_meter_one : '—';
        $oldM2 = ! $this->estateMeterNoIsEmpty($oldM2Source) ? (string) $oldM2Source : '—';
        $newM2 = $r->emrd_meter_two !== null && $r->emrd_meter_two !== '' ? (string) $r->emrd_meter_two : '—';
        if ($newM2 === '0') {
            $newM2 = '—';
        }

        $oldR1Source = $r->last_month_elec_red;
        $oldR2Source = $r->last_month_elec_red2;
        $hasSecondaryMeter = ($r->emrd_meter_two !== null && (int) $r->emrd_meter_two > 0)
            || ($r->prev_meter_two !== null && (int) $r->prev_meter_two > 0)
            || (isset($r->eur_old_meter_two) && ! $this->estateMeterNoIsEmpty($r->eur_old_meter_two));

        $possR1 = $r->poss_reading1 ?? null;
        $possR2 = $r->poss_reading2 ?? null;

        $oldM1Reading = '—';
        $oldM1ReadingVal = $oldR1Source;
        if ($oldM1ReadingVal !== null && $oldM1ReadingVal !== '' && (int) $oldM1ReadingVal > 0) {
            $oldM1Reading = (string) $oldM1ReadingVal;
        } elseif ($possR1 !== null && (int) $possR1 > 0) {
            $oldM1Reading = (string) $possR1;
        } elseif (isset($r->eur_old_reading_one) && $r->eur_old_reading_one !== null && (int) $r->eur_old_reading_one > 0) {
            $oldM1Reading = (string) $r->eur_old_reading_one;
        }

        $oldM2Reading = '—';
        if ($hasSecondaryMeter) {
            if ($oldR2Source !== null && $oldR2Source !== '' && (int) $oldR2Source > 0) {
                $oldM2Reading = (string) $oldR2Source;
            } elseif ($possR2 !== null && (int) $possR2 > 0) {
                $oldM2Reading = (string) $possR2;
            } elseif (isset($r->eur_old_reading_two) && $r->eur_old_reading_two !== null && (int) $r->eur_old_reading_two > 0) {
                $oldM2Reading = (string) $r->eur_old_reading_two;
            }
        }

        return [
            'name' => $r->emp_name ?? 'N/A',
            'employee_type' => $r->employee_type ?? '—',
            'unit_type' => $r->unit_type ?? '—',
            'unit_sub_type' => $r->unit_sub_type ?? '—',
            'building_name' => $r->building_name ?? '—',
            'house_no' => $r->house_no ?? '—',
            'old_meter1_no' => $oldM1,
            'new_meter1_no' => $newM1,
            'old_meter2_no' => $oldM2,
            'new_meter2_no' => $newM2,
            'old_meter1_reading' => $oldM1Reading,
            'new_meter1_reading' => $r->curr_month_elec_red !== null && $r->curr_month_elec_red !== '' ? (string) $r->curr_month_elec_red : '—',
            'old_meter2_reading' => $oldM2Reading,
            'new_meter2_reading' => $hasSecondaryMeter
                ? (($r->curr_month_elec_red2 !== null && $r->curr_month_elec_red2 !== '') ? (string) $r->curr_month_elec_red2 : '—')
                : '—',
        ];
    }

    /**
     * PK for estate_update_reading.created_by (nullable).
     */
    private function estateAuditCreatedByPk(): ?int
    {
        $u = Auth::user();
        if (! $u) {
            return null;
        }
        if (isset($u->pk) && $u->pk !== null && $u->pk !== '') {
            return (int) $u->pk;
        }
        if (method_exists($u, 'getAuthIdentifier') && $u->getAuthIdentifier() !== null) {
            return (int) $u->getAuthIdentifier();
        }

        return null;
    }

    /**
     * estate_home_request_details.employee_pk may store employee_master.pk or pk_old; estate_update_reading.employee_master_pk must always be employee_master.pk.
     */
    private function resolveEmployeeMasterCanonicalPk(?int $employeeId): ?int
    {
        if ($employeeId === null || $employeeId <= 0) {
            return null;
        }
        $canonical = DB::table('employee_master')
            ->where(function ($q) use ($employeeId) {
                $q->where('pk', $employeeId);
                if (Schema::hasColumn('employee_master', 'pk_old')) {
                    $q->orWhere('pk_old', $employeeId);
                }
            })
            ->value('pk');

        return $canonical !== null ? (int) $canonical : null;
    }

    /**
     * Persist meter number change audit (estate_update_reading). type: l = regular possession, o = other.
     * FK column estate_possession_details_pk holds epd.pk (l) or epo.pk (o).
     */
    private function logEstateMeterNumberChange(
        string $type,
        int $possessionPk,
        ?int $estateHousePk,
        ?int $campusPk,
        ?int $unitMasterPk,
        ?int $blockPk,
        ?int $unitSubTypePk,
        string $houseNo,
        string $meterChangeMonth,
        int $oldM1,
        int $oldM2,
        int $newM1,
        int $newM2,
        ?int $oldReading1,
        ?int $oldReading2,
        ?int $newReading1,
        ?int $newReading2,
        ?int $employeeMasterPk
    ): void {
        if (! Schema::hasTable('estate_update_reading')) {
            return;
        }
        $t = strtolower(trim($type));
        if ($t !== 'l' && $t !== 'o') {
            return;
        }
        if (($oldM1 === $newM1 && $oldM2 === $newM2) || $possessionPk <= 0) {
            return;
        }
        $trimHouse = trim($houseNo);
        $trimMonth = trim($meterChangeMonth);
        if ($trimHouse === '' || $trimMonth === '') {
            return;
        }
        $c = $campusPk !== null ? (int) $campusPk : 0;
        $u = $unitMasterPk !== null ? (int) $unitMasterPk : 0;
        $b = $blockPk !== null ? (int) $blockPk : 0;
        $s = $unitSubTypePk !== null ? (int) $unitSubTypePk : 0;
        if ($c <= 0 || $u <= 0 || $b <= 0 || $s <= 0) {
            return;
        }

        $mNum = static function (?int $v): ?int {
            if ($v === null || $v <= 0) {
                return null;
            }

            return $v;
        };
        $mRead = static function (?int $v): ?int {
            return $v;
        };

        $now = now();
        $by = $this->estateAuditCreatedByPk();
        $resolvedEmployeePk = $this->resolveEmployeeMasterCanonicalPk($employeeMasterPk);

        DB::table('estate_update_reading')->insert([
            'estate_campus_master_pk' => $c,
            'estate_unit_master_pk' => $u,
            'estate_block_master_pk' => $b,
            'estate_unit_sub_type_master_pk' => $s,
            'house_no' => mb_substr($trimHouse, 0, 20),
            'old_meter_no_one' => $mNum($oldM1),
            'new_meter_no_one' => $mNum($newM1),
            'old_meter_no_two' => $mNum($oldM2),
            'new_meter_no_two' => $mNum($newM2),
            'old_meter_reading_one' => $mRead($oldReading1),
            'new_meter_reading_one' => $mRead($newReading1),
            'old_meter_reading_two' => $mRead($oldReading2),
            'new_meter_reading_two' => $mRead($newReading2),
            'estate_possession_details_pk' => $possessionPk,
            'estate_house_pk' => ($estateHousePk !== null && $estateHousePk > 0) ? $estateHousePk : null,
            'type' => $t,
            'meter_update_date' => $now,
            'meter_change_month' => mb_substr($trimMonth, 0, 255),
            'created_date' => $now,
            'created_by' => $by,
            'employee_master_pk' => ($resolvedEmployeePk !== null && $resolvedEmployeePk > 0) ? $resolvedEmployeePk : null,
        ]);
    }

    /**
     * HAC Person role must not access Request Details / Change Request Details. Abort 403 if HAC Person only.
     * @deprecated No longer used for Change Request Details; HAC Person can access their own via ensureChangeRequestOwnership.
     */
    private function denyIfHacPersonOnly(): void
    {
        if (hasRole('HAC Person')
            && ! hasRole('Estate')
            && ! hasRole('Admin')
            && ! hasRole('Training-Induction')
            && ! hasRole('Training-MCTP')
            && ! hasRole('IST')
            && ! hasRole('Staff')
            && ! hasRole('Student-OT')
            && ! hasRole('Doctor')
            && ! hasRole('Guest Faculty')
            && ! hasRole('Internal Faculty')) {
            abort(403, 'You do not have access to this page.');
        }
    }

    /**
     * Ensure the change request belongs to the current user (self-service). Estate/Admin/IST etc. skip. Otherwise 403.
     */
    private function ensureChangeRequestOwnership(int $changeRequestPk): void
    {
        if (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin')) {
            return;
        }
        $user = Auth::user();
        $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
        if (empty($employeeIds)) {
            abort(403, 'You do not have access to this record.');
        }
        $empPk = DB::table('estate_change_home_req_details as ec')
            ->join('estate_home_request_details as eh', 'ec.estate_home_req_details_pk', '=', 'eh.pk')
            ->where('ec.pk', $changeRequestPk)
            ->value('eh.employee_pk');
        if ($empPk === null || ! in_array((string) $empPk, array_map('strval', $employeeIds), true)) {
            abort(403, 'You can only view or edit your own change request.');
        }
    }

    /**
     * Estate Request for Others - Listing (dynamic from DB).
     */
    public function requestForOthers(EstateOtherRequestDataTable $dataTable)
    {
        return $dataTable->render('admin.estate.estate_request_for_others');
    }

    /**
     * Request For Estate - Listing from estate_home_request_details with possession details.
     * Eligibility Type dropdown shows only unit sub types present in estate_eligibility_mapping.
     */
    public function requestForEstate(EstateRequestForEstateDataTable $dataTable)
    {
        $user = Auth::user();
        $selfEmployeePk = null;

        // Only unit sub types that exist in estate_eligibility_mapping (mapped data)
        $eligibilityTypes = DB::table('estate_eligibility_mapping as eem')
            ->join('estate_unit_sub_type_master as ust', 'eem.estate_unit_sub_type_master_pk', '=', 'ust.pk')
            ->whereNotNull('eem.estate_unit_sub_type_master_pk')
            ->select('ust.pk', 'ust.unit_sub_type')
            ->distinct()
            ->orderBy('ust.unit_sub_type')
            ->pluck('ust.unit_sub_type', 'ust.pk');

        // Self-service + Home sidebar (?scope=self): resolve employee_master.pk for prefilled add form.
        $needsSelfEmployeePk = $user && (
            $this->isEstateAuthorityPersonalScope(request())
            || ! (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin') || hasRole('HAC Person'))
        );
        if ($needsSelfEmployeePk) {
            $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
            if (! empty($employeeIds)) {
                $selfEmployeePk = DB::table('employee_master')
                    ->whereIn('pk', $employeeIds)
                    ->orWhere(function ($q) use ($employeeIds) {
                        if (Schema::hasColumn('employee_master', 'pk_old')) {
                            $q->whereIn('pk_old', $employeeIds);
                        }
                    })
                    ->value('pk');
            }
        }

        View::share('eligibilityTypes', $eligibilityTypes);

        return $dataTable->render('admin.estate.request_for_estate', [
            'eligibilityTypes' => $eligibilityTypes,
            'selfEmployeePk' => $selfEmployeePk,
        ]);
    }

    /**
     * Put In HAC - Authority view: List estate requests not yet in HAC. Authority selects and puts in HAC.
     */
    public function putInHac(EstateRequestPutInHacDataTable $dataTable)
    {
        return $dataTable->render('admin.estate.put_in_hac');
    }

    /**
     * Put selected estate requests in HAC.
     * HAC Forward stage removed, so requests become HAC-approved candidates directly.
     */
    public function putInHacAction(Request $request)
    {
        if (! (hasRole('HAC Person') || hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'))) {
            abort(403, 'You do not have permission to put requests in HAC.');
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:estate_home_request_details,pk',
        ]);

        $updated = EstateHomeRequestDetails::whereIn('pk', $request->ids)
            ->where('hac_status', 0)
            ->update(['hac_status' => 1, 'f_status' => 1]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $updated . ' request(s) put in HAC successfully.',
            ]);
        }
        return redirect()->route('admin.estate.put-in-hac')
            ->with('success', $updated . ' request(s) put in HAC successfully.');
    }

    /**
     * Get next Request ID for Add Estate Request (e.g. home-req-01, home-req-02).
     */
    public function getNextRequestForEstateId()
    {
        $nextId = $this->getNextEstateRequestId();
        return response()->json(['next_req_id' => $nextId]);
    }

    /**
     * Compute next req_id from DB (home-req-01, home-req-02, ...).
     */
    private function getNextEstateRequestId(): string
    {
        $latestReqId = EstateHomeRequestDetails::whereNotNull('req_id')
            ->where('req_id', 'like', 'home-req-%')
            ->orderBy('pk', 'desc')
            ->value('req_id');

        $nextNumber = 1;
        if ($latestReqId && preg_match('/home-req-(\d+)/', $latestReqId, $m)) {
            $nextNumber = ((int) $m[1]) + 1;
        }
        return 'home-req-' . sprintf('%02d', $nextNumber);
    }

    /**
     * Primary key column name for employee_master (pk or pk_old if used).
     */
    // private function estateEmployeePkColumn(): string
    // {
    //     return Schema::hasColumn('employee_master', 'pk') ? 'pk' : 'pk_old';
    // }

    public function estateApprovalSetting(EstateApprovalSettingDataTable $dataTable)
    {
        return $dataTable->render('admin.estate.estate_approval_setting');
    }

    /**
     * Add Approved Request House - Form to assign employees to an approver (dual list).
     */
    public function addApprovedRequestHouse(Request $request)
    {
        $empPkCol = $this->estateEmployeePkColumn();
        $approverPk = $request->query('approver');
        $approvers = EmployeeMaster::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn ($e) => [$e->{$empPkCol} => trim($e->first_name . ' ' . $e->last_name) ?: ('ID ' . $e->{$empPkCol})]);
        $allEmployees = EmployeeMaster::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $selectedPks = collect();
        $selectedApproverPk = null;
        if ($approverPk) {
            $selectedApproverPk = (int) $approverPk;
            $selectedPks = EstateHomeReqApprovalMgmt::where('employees_pk', $selectedApproverPk)
                ->pluck('employee_master_pk');
        }
        return view('admin.estate.add_approved_request_house', [
            'approvers' => $approvers,
            'allEmployees' => $allEmployees,
            'empPkCol' => $empPkCol,
            'selectedApproverPk' => $selectedApproverPk,
            'selectedPks' => $selectedPks,
        ]);
    }

    /**
     * Store Approved Request House - Save approver and assigned employees.
     */
    public function storeApprovedRequestHouse(Request $request)
    {
        $empPkCol = $this->estateEmployeePkColumn();
        $request->validate([
            'approver_pk' => ['required', 'integer', \Illuminate\Validation\Rule::exists('employee_master', $empPkCol)],
            'employee_pks' => 'nullable|array',
            'employee_pks.*' => ['integer', \Illuminate\Validation\Rule::exists('employee_master', $empPkCol)],
        ]);
        $approverPk = (int) $request->approver_pk;
        $employeePks = $request->filled('employee_pks') ? array_map('intval', (array) $request->employee_pks) : [];
        EstateHomeReqApprovalMgmt::where('employees_pk', $approverPk)->delete();
        foreach ($employeePks as $empPk) {
            EstateHomeReqApprovalMgmt::create([
                'employee_master_pk' => $empPk,
                'employees_pk' => $approverPk,
                'is_forword' => 0,
            ]);
        }
        return redirect()
            ->route('admin.estate.estate-approval-setting')
            ->with('success', 'Approved request house settings saved successfully.');
    }

    /**
     * Delete a single estate approval setting record.
     */
    public function destroyEstateApprovalSetting(Request $request, $id)
    {
        $record = EstateHomeReqApprovalMgmt::find($id);
        if (!$record) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
            }
            return redirect()->route('admin.estate.estate-approval-setting')->with('error', 'Record not found.');
        }
        $record->delete();
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Approval setting deleted successfully.']);
        }
        return redirect()->route('admin.estate.estate-approval-setting')->with('success', 'Approval setting deleted successfully.');
    }

    /**
     * Resolve eligibility_type_pk (unit sub type) from employee payroll → estate_eligibility_mapping,
     * with the same fallback as getRequestForEstateEmployeeDetails.
     */
    private function resolveEstateEligibilityTypePkForEmployeeMasterPk(int $pk): int
    {
        $pk = (int) $pk;
        if ($pk <= 0) {
            return 0;
        }

        $hasPkOld = Schema::hasColumn('employee_master', 'pk_old');
        $salaryGradeCol = Schema::hasColumn('payroll_salary_master', 'salary_grade_pk')
            ? 'salary_grade_pk'
            : 'salary_grade_master_pk';

        $employee = DB::table('employee_master as e')
            ->where(function ($q) use ($pk, $hasPkOld) {
                $q->where('e.pk', $pk);
                if ($hasPkOld) {
                    $q->orWhere('e.pk_old', $pk);
                }
            })
            ->first(['e.pk', 'e.pk_old']);

        if (! $employee) {
            return 0;
        }

        $empPkCandidates = [(int) ($employee->pk ?? 0)];
        if ($hasPkOld && ! empty($employee->pk_old)) {
            $empPkCandidates[] = (int) $employee->pk_old;
        }
        $empPkCandidates = array_values(array_unique(array_filter($empPkCandidates)));

        $salaryQuery = DB::table('payroll_salary_master as p')
            ->join('salary_grade_master as s', "p.$salaryGradeCol", '=', 's.pk')
            ->select("p.$salaryGradeCol as salary_grade_pk");

        if (count($empPkCandidates) === 1) {
            $salaryQuery->where('p.employee_master_pk', $empPkCandidates[0]);
        } elseif (! empty($empPkCandidates)) {
            $salaryQuery->whereIn('p.employee_master_pk', $empPkCandidates);
        }

        $salary = $salaryQuery->orderByDesc('p.pk')->first();

        $eligPk = $salary && ! empty($salary->salary_grade_pk)
            ? (int) DB::table('estate_eligibility_mapping')
                ->where('salary_grade_master_pk', (int) $salary->salary_grade_pk)
                ->orderBy('pk')
                ->value('estate_unit_sub_type_master_pk')
            : 0;

        if ($eligPk === 0 && Schema::hasColumn('estate_home_request_details', 'employee_pk')) {
            $existingReq = DB::table('estate_home_request_details')
                ->whereIn('employee_pk', $empPkCandidates)
                ->orderByDesc('pk')
                ->first();
            if ($existingReq && ! empty($existingReq->eligibility_type_pk)) {
                $eligPk = (int) $existingReq->eligibility_type_pk;
            }
        }

        return $eligPk > 0 ? $eligPk : 0;
    }

    /**
     * Store or update Request For Estate (estate_home_request_details).
     */
    public function storeRequestForEstate(Request $request)
    {
        $isEdit = $request->filled('id');
        $rules = [
            'req_id' => 'nullable|string|max:50',
            'req_date' => 'required|date',
            'emp_name' => 'required|string|max:50',
            'employee_id' => 'required|string|max:50',
            'emp_designation' => 'required|string|max:50',
            'pay_scale' => 'required|string|max:50',
            'doj_pay_scale' => 'required|date',
            'doj_academic' => 'required|date',
            'doj_service' => 'required|date',
            'eligibility_type_pk' => 'required|integer',
            'remarks' => 'nullable|string|max:500',
            'current_alot' => 'nullable|string|max:20',
        ];
        if ($isEdit) {
            $rules['status'] = 'required|integer|in:0,1,2';
        }
        // NOTE: This endpoint is used by "Request For Estate" add/edit modal which does not collect
        // any meter reading. Keep it optional, but validate format if provided by other flows.
        $messages = [
            'meter_reading_oth.regex' => 'Electric Meter Reading must be numbers only (max 10 digits).',
            'meter_reading_oth.max' => 'Electric Meter Reading must be at most 10 digits.',
        ];
        $rules['meter_reading_oth'] = 'nullable|regex:/^[0-9]{1,10}$/|max:10';

        $attributes = [
            'emp_name' => 'Employee Name',
            'employee_id' => 'Employee ID',
            'emp_designation' => 'Designation',
            'pay_scale' => 'Pay Scale',
            'doj_pay_scale' => 'DOJ (Pay Scale)',
            'doj_academic' => 'DOJ (Academy)',
            'doj_service' => 'DOJ (Service)',
            'eligibility_type_pk' => 'Eligibility Type',
            'req_date' => 'Request Date',
            'remarks' => 'Remarks',
        ];

        $validated = $request->validate($rules, $messages, $attributes);

        // DB column (wherever stored) is INT; hard guard against overflow if provided.
        if (!empty($validated['meter_reading_oth']) && (int) $validated['meter_reading_oth'] > 2147483647) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['meter_reading_oth' => 'Electric Meter Reading value is too large. Please enter up to 10 digits.'])
                ->with('error', 'Please correct the errors and try again.');
        }

        $user = Auth::user();
        // Training roles should behave like normal staff in estate request flow.
        $isEstateAuthority = $user && (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'));
        $selfEmployeeIds = [];
        if ($user && ! $isEstateAuthority) {
            $selfEmployeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
            // Normalize possible pk_old values to canonical employee_master.pk
            if (!empty($selfEmployeeIds)) {
                $selfEmployeeIds = DB::table('employee_master')
                    ->whereIn('pk', $selfEmployeeIds)
                    ->orWhere(function ($q) use ($selfEmployeeIds) {
                        if (Schema::hasColumn('employee_master', 'pk_old')) {
                            $q->whereIn('pk_old', $selfEmployeeIds);
                        }
                    })
                    ->pluck('pk')
                    ->filter()
                    ->map(fn ($v) => (string) $v)
                    ->unique()
                    ->values()
                    ->all();
            }
            if ($isEdit) {
                $existingReq = EstateHomeRequestDetails::findOrFail($request->id);
                // Existing rows may store employee_pk as pk_old; normalize before compare
                $existingEmpPkRaw = (int) ($existingReq->employee_pk ?? 0);
                $existingEmpPk = $existingEmpPkRaw;
                if ($existingEmpPkRaw > 0) {
                    $existingEmpPk = (int) DB::table('employee_master')
                        ->where('pk', $existingEmpPkRaw)
                        ->orWhere(function ($q) use ($existingEmpPkRaw) {
                            if (Schema::hasColumn('employee_master', 'pk_old')) {
                                $q->where('pk_old', $existingEmpPkRaw);
                            }
                        })
                        ->value('pk') ?: $existingEmpPkRaw;
                }
                if (! in_array((string) $existingEmpPk, array_map('strval', $selfEmployeeIds), true)) {
                    abort(403, 'You cannot modify estate requests of other employees.');
                }
            }
        }

        // Generate / resolve Request ID
        if ($isEdit) {
            // Editing: keep existing ID if none provided, otherwise use given one
            $reqId = $validated['req_id'] ?? null;
            if ($reqId === null || $reqId === '') {
                $existing = EstateHomeRequestDetails::findOrFail($request->id);
                $reqId = $existing->req_id;
            }
        } else {
            // Creating: always auto-generate (home-req-01, home-req-02, ...)
            $reqId = $this->getNextEstateRequestId();
        }

        $existingCurrentAlot = null;
        if ($isEdit) {
            $existingCurrentAlot = EstateHomeRequestDetails::where('pk', $request->id)->value('current_alot');
        }

        $data = [
            'req_id' => $reqId,
            'req_date' => $validated['req_date'],
            'emp_name' => $validated['emp_name'],
            'employee_id' => $validated['employee_id'],
            'emp_designation' => $validated['emp_designation'],
            'pay_scale' => $validated['pay_scale'],
            'doj_pay_scale' => $validated['doj_pay_scale'],
            'doj_academic' => $validated['doj_academic'],
            'doj_service' => $validated['doj_service'],
            'eligibility_type_pk' => (int) $validated['eligibility_type_pk'],
            'status' => $isEdit ? (int) $validated['status'] : 0,
            'remarks' => $validated['remarks'] ?? null,
            'current_alot' => array_key_exists('current_alot', $validated)
                ? \Illuminate\Support\Str::limit((string) ($validated['current_alot'] ?? ''), 20)
                : ($isEdit ? $existingCurrentAlot : null),
            'app_status' => (int) ($request->input('app_status', 0)),
            'hac_status' => (int) ($request->input('hac_status', 0)),
            'f_status' => (int) ($request->input('f_status', 0)),
            'change_status' => (int) ($request->input('change_status', 0)),
        ];

        if ($user && ! $isEstateAuthority) {
            $data['employee_pk'] = !empty($selfEmployeeIds) ? (int) reset($selfEmployeeIds) : 0;
        } else {
            $data['employee_pk'] = (int) ($request->input('employee_pk', 0));
        }

        // Non–Estate/Admin/Super Admin users cannot choose eligibility; force payroll-derived value when available.
        if ($user && ! $isEstateAuthority && (int) ($data['employee_pk'] ?? 0) > 0) {
            $resolvedElig = $this->resolveEstateEligibilityTypePkForEmployeeMasterPk((int) $data['employee_pk']);
            if ($resolvedElig > 0) {
                $data['eligibility_type_pk'] = $resolvedElig;
            }
        }

        // Prevent duplicate active occupation per employee.
        // Rule: block only if the employee currently occupies a house
        // (i.e. there is an active, not-yet-returned possession linked to any of their requests).
        if (! $isEdit) {
            $employeePkForCheck = (int) ($data['employee_pk'] ?? 0);
            $idsToCheck = [$employeePkForCheck];
            if ($user && ! $isEstateAuthority && !empty($selfEmployeeIds)) {
                $idsToCheck = array_map('intval', $selfEmployeeIds);
                $idsToCheck = array_filter($idsToCheck, fn ($id) => $id > 0);
            }
            if (!empty($idsToCheck)) {
                $hasPossessionTable = \Illuminate\Support\Facades\Schema::hasTable('estate_possession_details');

                $activeQuery = EstateHomeRequestDetails::whereIn('employee_pk', $idsToCheck)
                    // Only requests that are Pending or Allotted can have an active occupation.
                    ->whereIn('status', [0, 1]);

                if ($hasPossessionTable) {
                    // If possession table exists, require an active (not returned) possession row.
                    $activeQuery->whereExists(function ($sub) {
                        $sub->from('estate_possession_details as epd')
                            ->whereColumn('epd.estate_home_request_details', 'estate_home_request_details.pk')
                            ->whereNotNull('epd.estate_house_master_pk')
                            ->where('epd.estate_change_id', -1);
                        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
                            $sub->where(function ($q) {
                                $q->whereNull('epd.return_home_status')
                                    ->orWhere('epd.return_home_status', 0);
                            });
                        }
                    });
                }

                $hasActiveRequest = $activeQuery->exists();

                if ($hasActiveRequest) {
                    $errorMessage = 'You already have an active estate request. You cannot submit another until the current one is closed or you return the house.';
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $errorMessage,
                            'errors' => ['employee_pk' => [$errorMessage]],
                        ], 422);
                    }
                    return redirect()
                        ->back()
                        ->withInput()
                        ->withErrors(['employee_pk' => $errorMessage]);
                }
            }
            // Self-service user must have a valid employee mapping; do not create with employee_pk 0
            if ($user && ! $isEstateAuthority && (empty($data['employee_pk']) || $data['employee_pk'] === 0)) {
                $errorMessage = 'Employee mapping not found. You cannot submit an estate request.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => ['employee_pk' => [$errorMessage]],
                    ], 422);
                }
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['employee_pk' => $errorMessage]);
            }
        }

        if ($request->filled('id')) {
            $record = EstateHomeRequestDetails::findOrFail($request->id);
            $record->update($data);
            $message = 'Estate request updated successfully.';
        } else {
            $data['employee_pk'] = $data['employee_pk'] ?: 0;
            $record = EstateHomeRequestDetails::create($data);
            $message = 'Estate request created successfully.';

            if ($user && ! $isEstateAuthority) {
                try {
                    $notificationService = app(NotificationService::class);
                    $receiverService = app(NotificationReceiverService::class);
                    $approverUserIds = $receiverService->getEstateRequestApproverUserIds();
                    $hacUserIds = $receiverService->getEstateHacPersonUserIds();
                    $empLabel = trim((string) ($validated['emp_name'] ?? ''));
                    $idLabel = trim((string) ($validated['employee_id'] ?? ''));
                    $who = $empLabel !== '' || $idLabel !== ''
                        ? trim($empLabel . ($idLabel !== '' ? ' (' . $idLabel . ')' : ''))
                        : 'An employee';
                    $senderId = (int) ($user->user_id ?? 0);

                    $hacSet = [];
                    foreach ($hacUserIds as $rid) {
                        $hacSet[(int) $rid] = true;
                    }

                    $titleApprove = 'New estate request';
                    $bodyApprove = "{$who} has submitted an estate request. Please review and approve.";
                    $titleHac = 'Estate request — Put in HAC';
                    $bodyHac = "{$who} has submitted an estate request. Please put it in HAC and complete HAC approval.";
                    $bodyBoth = "{$who} has submitted an estate request. Please review and approve, put it in HAC, and complete HAC approval.";

                    $sent = [];
                    foreach ($approverUserIds as $rid) {
                        $rid = (int) $rid;
                        if ($senderId > 0 && $rid === $senderId) {
                            continue;
                        }
                        $inHac = isset($hacSet[$rid]);
                        $body = $inHac ? $bodyBoth : $bodyApprove;
                        $title = $inHac ? 'New estate request (approve / HAC)' : $titleApprove;
                        $notificationService->create(
                            $rid,
                            'estate_request',
                            'Estate',
                            (int) $record->pk,
                            $title,
                            $body
                        );
                        $sent[$rid] = true;
                    }

                    foreach ($hacUserIds as $rid) {
                        $rid = (int) $rid;
                        if ($senderId > 0 && $rid === $senderId) {
                            continue;
                        }
                        if (isset($sent[$rid])) {
                            continue;
                        }
                        $notificationService->create(
                            $rid,
                            'estate_request',
                            'EstateHac',
                            (int) $record->pk,
                            $titleHac,
                            $bodyHac
                        );
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to send estate request approver notifications: ' . $e->getMessage());
                }
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return redirect()->route('admin.estate.request-for-estate')->with('success', $message);
    }

    /**
     * Get employees list for Request For Estate dropdown from employee_master.
     */
    public function getRequestForEstateEmployees(Request $request)
    {
        // IMPORTANT: UI + estate_home_request_details.employee_pk should use employee_master.pk (canonical).
        // payroll_salary_master may still reference employee_master.pk_old, so we keep a separate column for joins.
        $empPkColForPayrollJoin = $this->estateEmployeePkColumn();
        $hasEmpId = \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'emp_id');
        $hasEmployeeId = \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'employee_id');
        $salaryGradeCol = \Illuminate\Support\Facades\Schema::hasColumn('payroll_salary_master', 'salary_grade_pk')
            ? 'salary_grade_pk'
            : 'salary_grade_master_pk';
        $hasEligibilityUnitType = \Illuminate\Support\Facades\Schema::hasColumn('estate_eligibility_mapping', 'estate_unit_type_master_pk');
        $employeeIdSelect = $hasEmpId
            ? ($hasEmployeeId
                ? "COALESCE(NULLIF(TRIM(em.emp_id), ''), NULLIF(TRIM(em.employee_id), ''), '')"
                : "COALESCE(NULLIF(TRIM(em.emp_id), ''), '')")
            : ($hasEmployeeId ? "COALESCE(NULLIF(TRIM(em.employee_id), ''), '')" : "''");

        // Employees ko dropdown se block sirf tab karna hai jab unke paas
        // kisi bhi request ke against ek "active possession" ho
        // (estate_possession_details.return_home_status = 0 ya NULL).
        // Jinke sab houses return ho chuke hain (sirf return_home_status = 1 rows),
        // unhe dubara request karne dena hai.
        $activePossessionEmployeePks = [];
        $allPossessionEmployeePks = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('estate_possession_details')) {
            $hasReturnStatus = \Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status');

            // Sabhi possessions ke employee_pk (returned + active) capture karo
            $allPossessionEmployeePks = DB::table('estate_possession_details as epd')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->where('ehrd.employee_pk', '>', 0)
                ->whereNotNull('epd.estate_house_master_pk')
                ->pluck('ehrd.employee_pk')
                ->filter()
                ->map(fn ($v) => (int) $v)
                ->all();

            // Sirf active possessions ke employee_pk (return_home_status = 0 / NULL)
            $epdQuery = DB::table('estate_possession_details as epd')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->where('ehrd.employee_pk', '>', 0)
                ->whereNotNull('epd.estate_house_master_pk');
            if ($hasReturnStatus) {
                $epdQuery->where(function ($q) {
                    $q->whereNull('epd.return_home_status')
                        ->orWhere('epd.return_home_status', 0);
                });
            }
            $activePossessionEmployeePks = $epdQuery
                ->pluck('ehrd.employee_pk')
                ->filter()
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        // Final active set = employees jinke paas kam se kam ek active possession hai.
        // Baaki sab (including returned) dropdown me allowed hain.
        $activeEmployeePks = array_values(array_unique($activePossessionEmployeePks));

        // Employees jinke paas *pending* estate request (status = 0) hai,
        // unhe bhi dropdown se block karna hai — chahe abhi allotment/possession na hua ho.
        // IMPORTANT: legacy data me kuch requests "Returned" effectively ho sakte hain (possession returned),
        // but status still 0. Aise cases ko pending block me include nahi karna chahiye.
        $pendingRequestEmployeePks = [];
        if (Schema::hasTable('estate_possession_details')) {
            $hasReturnStatus = Schema::hasColumn('estate_possession_details', 'return_home_status');
            $pendingQuery = DB::table('estate_home_request_details as ehrd')
                ->where('ehrd.employee_pk', '>', 0)
                ->where('ehrd.status', 0);

            // "Effectively returned" requests ko exclude karo:
            // request ke against at least one possession row jiska house set ho aur return_home_status=1 (ya column absent ho to can't infer).
            if ($hasReturnStatus) {
                $pendingQuery->whereNotExists(function ($sub) {
                    $sub->from('estate_possession_details as epd')
                        ->whereColumn('epd.estate_home_request_details', 'ehrd.pk')
                        ->whereNotNull('epd.estate_house_master_pk')
                        ->where('epd.return_home_status', 1);
                });
            }

            $pendingRequestEmployeePks = $pendingQuery
                ->pluck('ehrd.employee_pk')
                ->filter()
                ->map(fn ($v) => (int) $v)
                ->all();
        } else {
            $pendingRequestEmployeePks = EstateHomeRequestDetails::where('employee_pk', '>', 0)
                ->where('status', 0)
                ->pluck('employee_pk')
                ->filter()
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        // Blocked set (raw ids from existing data) = active possession wale + pending request wale.
        // These values may be mixed (employee_master.pk OR employee_master.pk_old) depending on legacy/migration.
        $blockedEmployeePksRaw = array_values(array_unique(array_merge($activeEmployeePks, $pendingRequestEmployeePks)));

        // Normalize any legacy pk_old values to canonical employee_master.pk for dropdown filtering.
        $blockedEmployeePks = [];
        if (!empty($blockedEmployeePksRaw)) {
            $blockedEmployeePks = DB::table('employee_master')
                ->whereIn('pk', $blockedEmployeePksRaw)
                ->orWhere(function ($q) use ($blockedEmployeePksRaw) {
                    if (Schema::hasColumn('employee_master', 'pk_old')) {
                        $q->whereIn('pk_old', $blockedEmployeePksRaw);
                    }
                })
                ->pluck('pk')
                ->filter()
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->all();
        }

        $query = DB::table('estate_house_master as h')
            ->join('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->join('estate_campus_master as a', 'h.estate_campus_master_pk', '=', 'a.pk')
            ->join('estate_eligibility_mapping as e', 'h.estate_unit_sub_type_master_pk', '=', 'e.estate_unit_sub_type_master_pk')
            ->join('salary_grade_master as sg', 'e.salary_grade_master_pk', '=', 'sg.pk')
            ->join('payroll_salary_master as ps', "sg.pk", '=', "ps.$salaryGradeCol")
            // payroll_salary_master.employee_master_pk may point to employee_master.pk_old in this system
            ->join('employee_master as em', 'ps.employee_master_pk', '=', 'em.' . $empPkColForPayrollJoin)
            ->leftJoin('designation_master as d', 'em.designation_master_pk', '=', 'd.pk')
            ->select(
                'em.pk as pk',
                DB::raw("TRIM(CONCAT(COALESCE(em.first_name, ''), ' ', COALESCE(em.middle_name, ''), ' ', COALESCE(em.last_name, ''))) as emp_name"),
                DB::raw($employeeIdSelect . ' as employee_id'),
                DB::raw("COALESCE(d.designation_name, '') as emp_designation")
            )
            ->where('em.status', 1)
            ->where('em.payroll', 0)
            ->distinct()
            ->orderByRaw("TRIM(CONCAT(COALESCE(em.first_name, ''), ' ', COALESCE(em.middle_name, ''), ' ', COALESCE(em.last_name, ''))) asc")
            ->orderBy('em.pk');

        // Self-service: staff / HAC / etc. see only themselves. Admin / Estate / Super Admin see full list
        // on the setup screen, but on ?scope=self (Home sidebar personal tab) they must see only themselves.
        $user = Auth::user();
        $isEstateAuthority = hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin');
        $restrictToSelf = $user && (! $isEstateAuthority || $this->isEstateAuthorityPersonalScope($request));
        $employeeIds = [];
        if ($restrictToSelf) {
            $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
            // Normalize possible pk_old values to canonical employee_master.pk
            if (!empty($employeeIds)) {
                $employeeIds = DB::table('employee_master')
                    ->whereIn('pk', $employeeIds)
                    ->orWhere(function ($q) use ($employeeIds) {
                        if (Schema::hasColumn('employee_master', 'pk_old')) {
                            $q->whereIn('pk_old', $employeeIds);
                        }
                    })
                    ->pluck('pk')
                    ->filter()
                    ->map(fn ($v) => (string) $v)
                    ->unique()
                    ->values()
                    ->all();
            }
            if (empty($employeeIds) && ($user->user_id || $user->pk)) {
                $tryIds = array_filter([$user->user_id, $user->pk], fn ($v) => $v !== null && $v !== '');
                if (!empty($tryIds)) {
                    $foundRows = DB::table('employee_master')
                        ->whereIn('pk', $tryIds)
                        ->orWhere(function ($q) use ($tryIds) {
                            if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'pk_old')) {
                                $q->whereIn('pk_old', $tryIds);
                            }
                        })
                        ->get(['pk']);
                    $found = $foundRows->pluck('pk')->filter()->map(fn ($v) => (string) $v)->unique()->values()->toArray();
                    if (!empty($found)) {
                        $employeeIds = $found;
                    }
                }
            }
            if (!empty($employeeIds)) {
                $query->whereIn('em.pk', $employeeIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (!empty($blockedEmployeePks)) {
            $query->whereNotIn('em.pk', $blockedEmployeePks);
        }

        $rows = $query->get();

        // Fallback: if no employees from estate-eligibility chain (e.g. no houses/payroll/eligibility setup), load from employee_master so dropdown shows names
        if ($rows->isEmpty()) {
            $fallbackQuery = DB::table('employee_master as em')
                ->leftJoin('designation_master as d', 'em.designation_master_pk', '=', 'd.pk')
                ->select(
                    'em.pk as pk',
                    DB::raw("TRIM(CONCAT(COALESCE(em.first_name, ''), ' ', COALESCE(em.middle_name, ''), ' ', COALESCE(em.last_name, ''))) as emp_name"),
                    DB::raw($employeeIdSelect . ' as employee_id'),
                    DB::raw("COALESCE(d.designation_name, '') as emp_designation")
                )
                ->where('em.status', 1)
                ->orderByRaw("TRIM(CONCAT(COALESCE(em.first_name, ''), ' ', COALESCE(em.middle_name, ''), ' ', COALESCE(em.last_name, ''))) asc")
                ->orderBy('em.pk');
            if (!empty($blockedEmployeePks)) {
                $fallbackQuery->whereNotIn('em.pk', $blockedEmployeePks);
            }
            if ($restrictToSelf) {
                $fallbackEmployeeIds = $employeeIds;
                if (empty($fallbackEmployeeIds)) {
                    $fallbackEmployeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
                }
                if (empty($fallbackEmployeeIds) && ($user->user_id || $user->pk)) {
                    $tryIds = array_filter([$user->user_id, $user->pk], fn ($v) => $v !== null && $v !== '');
                    if (!empty($tryIds)) {
                        $foundRows = DB::table('employee_master')
                            ->whereIn('pk', $tryIds)
                            ->orWhere(function ($q) use ($tryIds) {
                                if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'pk_old')) {
                                    $q->whereIn('pk_old', $tryIds);
                                }
                            })
                            ->get(['pk']);
                        $found = $foundRows->pluck('pk')->filter()->map(fn ($v) => (string) $v)->unique()->values()->toArray();
                        if (!empty($found)) {
                            $fallbackEmployeeIds = $found;
                        }
                    }
                }
                if (!empty($fallbackEmployeeIds)) {
                    $fallbackQuery->whereIn('em.pk', $fallbackEmployeeIds);
                } else {
                    $fallbackQuery->whereRaw('1 = 0');
                }
            }
            $rows = $fallbackQuery->get();
        }

        // Additional mapping: ensure jo employees pehle kabhi estate possession me the
        // aur ab unke saare houses return ho chuke hain, unka naam bhi dropdown me aaye,
        // chahe wo current estate_eligibility_mapping chain se na aa rahe hon.
        if (!empty($allPossessionEmployeePks)) {
            $allPossessionEmployeePks = array_values(array_unique($allPossessionEmployeePks));
            // Returned-only = jinke paas koi active possession nahi hai
            $returnedOnlyEmployeePks = array_diff($allPossessionEmployeePks, $activeEmployeePks);

            if (!empty($returnedOnlyEmployeePks)) {
                // Normalize returnedOnly list to canonical employee_master.pk
                $returnedOnlyCanonical = DB::table('employee_master')
                    ->whereIn('pk', $returnedOnlyEmployeePks)
                    ->orWhere(function ($q) use ($returnedOnlyEmployeePks) {
                        if (Schema::hasColumn('employee_master', 'pk_old')) {
                            $q->whereIn('pk_old', $returnedOnlyEmployeePks);
                        }
                    })
                    ->pluck('pk')
                    ->filter()
                    ->map(fn ($v) => (int) $v)
                    ->unique()
                    ->values()
                    ->all();

                $existingPks = $rows->pluck('pk')->map(fn ($v) => (int) $v)->all();
                // Dropdown me sirf un returned employees ko add karein
                // jo abhi kisi active possession ya pending request ki wajah se blocked nahi hain.
                $missingReturnedPks = array_diff($returnedOnlyCanonical, $existingPks, $blockedEmployeePks);

                if (!empty($missingReturnedPks)) {
                    $extraReturnedQuery = DB::table('employee_master as em')
                        ->leftJoin('designation_master as d', 'em.designation_master_pk', '=', 'd.pk')
                        ->select(
                            'em.pk as pk',
                            DB::raw("TRIM(CONCAT(COALESCE(em.first_name, ''), ' ', COALESCE(em.middle_name, ''), ' ', COALESCE(em.last_name, ''))) as emp_name"),
                            DB::raw($employeeIdSelect . ' as employee_id'),
                            DB::raw("COALESCE(d.designation_name, '') as emp_designation")
                        )
                        ->whereIn('em.pk', $missingReturnedPks);

                    // Only show active employees in dropdown (even for returned backfill)
                    $extraReturnedQuery->where('em.status', 1)->where('em.payroll', 0);

                    // Self-service / Home ?scope=self: only own row in returned-employee backfill
                    if ($restrictToSelf) {
                        if (!empty($employeeIds)) {
                            $extraReturnedQuery->whereIn('em.pk', $employeeIds);
                        } else {
                            $extraReturnedQuery->whereRaw('1 = 0');
                        }
                    }

                    $extraReturned = $extraReturnedQuery->get();
                    if ($extraReturned->isNotEmpty()) {
                        $rows = $rows->merge($extraReturned)->unique('pk')->values();
                    }
                }
            }
        }

        $includePk = (int) $request->query('include_pk', 0);
        if ($includePk > 0) {
            $currentReq = EstateHomeRequestDetails::find($includePk);
            if ($currentReq) {
                $currentEmployeePkRaw = (int) ($currentReq->employee_pk ?? 0);
                // Normalize stored employee_pk (may be pk_old) to canonical employee_master.pk
                $currentEmployeePk = 0;
                if ($currentEmployeePkRaw > 0) {
                    $currentEmployeePk = (int) DB::table('employee_master')
                        ->where('pk', $currentEmployeePkRaw)
                        ->orWhere(function ($q) use ($currentEmployeePkRaw) {
                            if (Schema::hasColumn('employee_master', 'pk_old')) {
                                $q->where('pk_old', $currentEmployeePkRaw);
                            }
                        })
                        ->value('pk');
                }

                if ($currentEmployeePk > 0 && ! $rows->contains(fn ($r) => (int) $r->pk === $currentEmployeePk)) {
                    $extra = DB::table('employee_master as em')
                        ->leftJoin('designation_master as d', 'em.designation_master_pk', '=', 'd.pk')
                        ->select(
                            'em.pk as pk',
                            DB::raw("TRIM(CONCAT(COALESCE(em.first_name, ''), ' ', COALESCE(em.middle_name, ''), ' ', COALESCE(em.last_name, ''))) as emp_name"),
                            DB::raw($employeeIdSelect . ' as employee_id'),
                            DB::raw("COALESCE(d.designation_name, '') as emp_designation")
                        )
                        ->where('em.pk', $currentEmployeePk)
                        ->where('em.status', 1)
                        ->where('em.payroll', 0)
                        ->first();
                    if ($extra) {
                        $rows->prepend($extra);
                    }
                }
            }
        }

        $list = $rows->map(function ($row) {
            $name = trim((string) ($row->emp_name ?? ''));
            $empId = trim((string) ($row->employee_id ?? ''));
            return [
                'pk' => (int) $row->pk,
                'emp_name' => $name,
                'employee_id' => $empId,
                'label' => trim($name . ($empId !== '' ? (' (' . $empId . ')') : '')),
            ];
        })->values()->all();

        // Prevent stale browser/proxy cache for dropdown list
        return response()
            ->json($list)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Get one employee's details for Request For Estate form (by employee_master pk).
     * Backward compatible: if pk is not employee_master, tries estate_home_request_details.
     */
    public function getRequestForEstateEmployeeDetails($pk)
    {
        $pk = (int) $pk;
        // Accept both employee_master.pk (canonical) and employee_master.pk_old (legacy)
        $hasPkOld = Schema::hasColumn('employee_master', 'pk_old');
        $hasEmpId = \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'emp_id');
        $hasEmployeeId = \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'employee_id');
        $salaryGradeCol = \Illuminate\Support\Facades\Schema::hasColumn('payroll_salary_master', 'salary_grade_pk')
            ? 'salary_grade_pk'
            : 'salary_grade_master_pk';

        $employeeIdSelect = $hasEmpId
            ? ($hasEmployeeId
                ? "COALESCE(NULLIF(TRIM(e.emp_id), ''), NULLIF(TRIM(e.employee_id), ''), '')"
                : "COALESCE(NULLIF(TRIM(e.emp_id), ''), '')")
            : ($hasEmployeeId ? "COALESCE(NULLIF(TRIM(e.employee_id), ''), '')" : "''");

        // Self-service: non-estate/admin/super-admin users must not fetch details for other employees
        $user = Auth::user();
        if ($user && ! (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'))) {
            $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
            // Normalize possible pk_old values to canonical employee_master.pk for correct comparison
            if (!empty($employeeIds)) {
                $employeeIds = DB::table('employee_master')
                    ->whereIn('pk', $employeeIds)
                    ->orWhere(function ($q) use ($employeeIds, $hasPkOld) {
                        if ($hasPkOld) {
                            $q->whereIn('pk_old', $employeeIds);
                        }
                    })
                    ->pluck('pk')
                    ->filter()
                    ->map(fn ($v) => (string) $v)
                    ->unique()
                    ->values()
                    ->all();
            }
            if (empty($employeeIds) || ! in_array((string) $pk, array_map('strval', $employeeIds), true)) {
                abort(403, 'You cannot view details of other employees.');
            }
        }

        $employeeSelect = [
            'e.pk',
            DB::raw("TRIM(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, ''))) as emp_name"),
            DB::raw($employeeIdSelect . ' as employee_id'),
            DB::raw("COALESCE(d.designation_name, '') as emp_designation"),
            'e.doj',
            'e.payroll_date',
        ];
        if ($hasPkOld) {
            $employeeSelect[] = 'e.pk_old';
        }

        $employee = DB::table('employee_master as e')
            ->leftJoin('designation_master as d', 'e.designation_master_pk', '=', 'd.pk')
            ->select($employeeSelect)
            ->where(function ($q) use ($pk, $hasPkOld) {
                $q->where('e.pk', $pk);
                if ($hasPkOld) {
                    $q->orWhere('e.pk_old', $pk);
                }
            })
            ->first();

        if ($employee) {
            $empPkCandidates = [(int) ($employee->pk ?? 0)];
            if ($hasPkOld && !empty($employee->pk_old)) {
                $empPkCandidates[] = (int) $employee->pk_old;
            }
            $empPkCandidates = array_values(array_unique(array_filter($empPkCandidates)));

            $salaryQuery = DB::table('payroll_salary_master as p')
                ->join('salary_grade_master as s', "p.$salaryGradeCol", '=', 's.pk')
                ->select("p.$salaryGradeCol as salary_grade_pk", 's.salary_grade', 'p.modified_date');

            if (count($empPkCandidates) === 1) {
                $salaryQuery->where('p.employee_master_pk', $empPkCandidates[0]);
            } elseif (!empty($empPkCandidates)) {
                $salaryQuery->whereIn('p.employee_master_pk', $empPkCandidates);
            }

            $salary = $salaryQuery
                ->orderByDesc('p.pk')
                ->first();

            $eligPk = $salary && !empty($salary->salary_grade_pk)
                ? (int) DB::table('estate_eligibility_mapping')
                    ->where('salary_grade_master_pk', (int) $salary->salary_grade_pk)
                    ->orderBy('pk')
                    ->value('estate_unit_sub_type_master_pk')
                : 0;

            $eligibilityTypeName = $eligPk
                ? DB::table('estate_unit_sub_type_master')->where('pk', $eligPk)->value('unit_sub_type')
                : null;

            $payScale = (string) ($salary?->salary_grade ?? '');
            $doj = !empty($employee->doj) ? \Carbon\Carbon::parse($employee->doj)->format('Y-m-d') : '';
            // DOJ (Pay Scale): prefer payroll_salary_master.modified_date, else employee_master.payroll_date, else employee_master.doj
            $payScaleDoj = '';
            if (!empty($salary?->modified_date)) {
                $payScaleDoj = \Carbon\Carbon::parse($salary->modified_date)->format('Y-m-d');
            } elseif (!empty($employee->payroll_date)) {
                $payScaleDoj = \Carbon\Carbon::parse($employee->payroll_date)->format('Y-m-d');
            } elseif (!empty($employee->doj)) {
                $payScaleDoj = \Carbon\Carbon::parse($employee->doj)->format('Y-m-d');
            }

            // Fallback: when payroll/salary data is missing on server, use latest estate_home_request_details for this employee
            if (($payScale === '' || $eligPk === 0) && Schema::hasColumn('estate_home_request_details', 'employee_pk')) {
                $existingReq = DB::table('estate_home_request_details')
                    ->whereIn('employee_pk', $empPkCandidates)
                    ->orderByDesc('pk')
                    ->first();
                if ($existingReq) {
                    if ($payScale === '' && !empty(trim((string) ($existingReq->pay_scale ?? '')))) {
                        $payScale = (string) $existingReq->pay_scale;
                    }
                    if ($eligPk === 0 && !empty($existingReq->eligibility_type_pk)) {
                        $eligPk = (int) $existingReq->eligibility_type_pk;
                        $eligibilityTypeName = DB::table('estate_unit_sub_type_master')->where('pk', $eligPk)->value('unit_sub_type');
                    }
                    if ($payScaleDoj === '' && !empty($existingReq->doj_pay_scale)) {
                        $payScaleDoj = \Carbon\Carbon::parse($existingReq->doj_pay_scale)->format('Y-m-d');
                    }
                }
            }

            return response()->json([
                'emp_name' => (string) ($employee->emp_name ?? ''),
                'employee_id' => (string) ($employee->employee_id ?? ''),
                'emp_designation' => (string) ($employee->emp_designation ?? ''),
                'pay_scale' => $payScale,
                'doj_pay_scale' => $payScaleDoj,
                'doj_academic' => $doj,
                'doj_service' => $doj,
                'eligibility_type_pk' => $eligPk ?: null,
                'eligibility_type_name' => $eligibilityTypeName,
            ]);
        }

        // Backward compatibility for old UI values that may send estate_home_request_details pk.
        $row = EstateHomeRequestDetails::find($pk);
        if (! $row) {
            return response()->json(['error' => 'Not found'], 404);
        }
        $eligPk = (int) ($row->eligibility_type_pk ?? 0);
        $eligibilityTypeName = $eligPk ? (DB::table('estate_unit_sub_type_master')->where('pk', $eligPk)->value('unit_sub_type')) : null;
        return response()->json([
            'emp_name' => $row->emp_name ?? '',
            'employee_id' => $row->employee_id ?? '',
            'emp_designation' => $row->emp_designation ?? '',
            'pay_scale' => $row->pay_scale ?? '',
            'doj_pay_scale' => $row->doj_pay_scale ? \Carbon\Carbon::parse($row->doj_pay_scale)->format('Y-m-d') : '',
            'doj_academic' => $row->doj_academic ? \Carbon\Carbon::parse($row->doj_academic)->format('Y-m-d') : '',
            'doj_service' => $row->doj_service ? \Carbon\Carbon::parse($row->doj_service)->format('Y-m-d') : '',
            'eligibility_type_pk' => $eligPk ?: null,
            'eligibility_type_name' => $eligibilityTypeName,
        ]);
    }

    /**
     * Get vacant houses for Request For Estate by eligibility type.
     * eligibility_type_pk is used as estate_unit_sub_type_master_pk (Type I=61, II=62, etc.).
     * Excludes houses already in estate_possession_details or estate_possession_other.
     */
    public function getVacantHousesForEstateRequest(Request $request)
    {
        $eligibilityTypePk = (int) $request->query('eligibility_type_pk', 0);
        if (! $eligibilityTypePk) {
            return response()->json(['data' => []]);
        }

        $occupiedHousePks = DB::table('estate_possession_details')
            ->whereNotNull('estate_house_master_pk')
            ->where('return_home_status', 0)
            ->pluck('estate_house_master_pk')
            ->merge(
                DB::table('estate_possession_other')
                    ->whereNotNull('estate_house_master_pk')
                    ->where('return_home_status', 0)
                    ->pluck('estate_house_master_pk')
            )
            ->unique()
            ->values();

        $query = DB::table('estate_house_master as h')
            ->join('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->where('h.estate_unit_sub_type_master_pk', $eligibilityTypePk)
            ->where('h.vacant_renovation_status', 1)
            ->select('h.pk', 'h.house_no', 'b.block_name')
            ->orderBy('b.block_name')
            ->orderBy('h.house_no');

        if ($occupiedHousePks->isNotEmpty()) {
            $query->whereNotIn('h.pk', $occupiedHousePks->toArray());
        }

        $houses = $query->get()->map(function ($row) {
            $houseNo = trim($row->house_no ?? '') ?: (string) $row->pk;
            return [
                'pk' => (int) $row->pk,
                'house_no' => $row->house_no ?? '',
                'block_name' => $row->block_name ?? '',
                'label' => $houseNo,
            ];
        });

        return response()->json(['data' => $houses]);
    }

    /**
     * Delete Request For Estate.
     */
    public function destroyRequestForEstate(Request $request, $id)
    {
        $record = EstateHomeRequestDetails::find($id);
        if (! $record) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
            }
            return redirect()->route('admin.estate.request-for-estate')->with('error', 'Record not found.');
        }

        $user = Auth::user();
        $isEstateAuthority = $user && (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'));
        if ($user && ! $isEstateAuthority) {
            $selfEmployeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
            if (! in_array((string) $record->employee_pk, array_map('strval', $selfEmployeeIds), true)) {
                $message = 'You cannot delete estate requests of other employees.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 403);
                }
                return redirect()->route('admin.estate.request-for-estate')->with('error', $message);
            }
        }

        $record->delete();
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Estate request deleted successfully.']);
        }
        return redirect()->route('admin.estate.request-for-estate')->with('success', 'Estate request deleted successfully.');
    }

    /**
     * Request for House & Change Request Details — Single page with two sections.
     * Section 1: Request for House (estate_home_request_details).
     * Section 2: Change Request Details (estate_change_home_req_details) when present.
     * Used from Request For Estate list and HAC Approved list.
     */
    public function requestAndChangeRequestDetails($id)
    {
        $id = (int) $id;
        $homeReq = DB::table('estate_home_request_details as ehrd')
            ->where('ehrd.pk', $id)
            ->select(
                'ehrd.pk',
                'ehrd.req_id',
                'ehrd.req_date',
                'ehrd.emp_name',
                'ehrd.employee_id',
                'ehrd.emp_designation',
                'ehrd.pay_scale',
                'ehrd.doj_pay_scale',
                'ehrd.doj_academic',
                'ehrd.doj_service',
                'ehrd.current_alot',
                'ehrd.status',
                'ehrd.app_status',
                'ehrd.hac_status',
                'ehrd.f_status',
                'ehrd.change_status',
                'ehrd.eligibility_type_pk',
                'ehrd.remarks',
                'ehrd.employee_pk'
            )
            ->first();

        if (! $homeReq) {
            return redirect()->route('admin.estate.request-for-estate')->with('error', 'Request not found.');
        }

        $eligibilityMap = [61 => 'Type-I', 62 => 'Type-II', 63 => 'Type-III', 64 => 'Type-IV', 65 => 'Type-V', 66 => 'Type-VI', 69 => 'Type-IX', 70 => 'Type-X', 71 => 'Type-XI', 73 => 'Type-XIII'];
        $statusMap = [0 => 'Pending', 1 => 'Approved/Allotted', 2 => 'Rejected'];
        $hacStatusMap = [0 => 'HAC not done', 1 => 'HAC approved'];
        $changeStatusMap = [0 => 'No change request', 1 => 'Change request raised'];

        $requestForHouse = (object) [
            'pk' => (int) $homeReq->pk,
            'req_id' => $homeReq->req_id ?? '—',
            'req_date' => $homeReq->req_date ? \Carbon\Carbon::parse($homeReq->req_date)->format('d-m-Y') : '—',
            'emp_name' => $homeReq->emp_name ?? '—',
            'employee_id' => $homeReq->employee_id ?? '—',
            'emp_designation' => $homeReq->emp_designation ?? '—',
            'pay_scale' => $homeReq->pay_scale ?? '—',
            'doj_pay_scale' => $homeReq->doj_pay_scale ? \Carbon\Carbon::parse($homeReq->doj_pay_scale)->format('d-m-Y') : '—',
            'doj_academic' => $homeReq->doj_academic ? \Carbon\Carbon::parse($homeReq->doj_academic)->format('d-m-Y') : '—',
            'doj_service' => $homeReq->doj_service ? \Carbon\Carbon::parse($homeReq->doj_service)->format('d-m-Y') : '—',
            'current_alot' => $homeReq->current_alot ?? '—',
            'status' => $statusMap[(int) ($homeReq->status ?? 0)] ?? '—',
            'app_status' => $statusMap[(int) ($homeReq->app_status ?? 0)] ?? '—',
            'hac_status' => $hacStatusMap[(int) ($homeReq->hac_status ?? 0)] ?? '—',
            'f_status' => (int) ($homeReq->f_status ?? 0) === 1 ? 'Forwarded' : 'Not forwarded',
            'change_status' => $changeStatusMap[(int) ($homeReq->change_status ?? 0)] ?? '—',
            'eligibility_label' => $eligibilityMap[(int) ($homeReq->eligibility_type_pk ?? 0)] ?? ('Type-' . ($homeReq->eligibility_type_pk ?? '')),
            'remarks' => $homeReq->remarks ?? '—',
        ];

        // Latest active house details (if possession exists) for this request.
        $houseDetails = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('estate_possession_details')) {
            $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
            $hasReturnStatus = \Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status');

            $rowQ = DB::table('estate_home_request_details as ehrd')
                ->join('estate_possession_details as epd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
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
                ->where('ehrd.pk', $id)
                ->whereNotNull('epd.estate_house_master_pk')
                ->when($hasReturnStatus, function ($q) {
                    $q->where(function ($inner) {
                        $inner->whereNull('epd.return_home_status')
                            ->orWhere('epd.return_home_status', 0);
                    });
                })
                ->orderByDesc('epd.pk')
                ->select(
                    'ec.campus_name',
                    'eb.block_name',
                    'eut.unit_type as unit_type_name',
                    'eust.unit_sub_type',
                    'ehm.house_no',
                    DB::raw("CASE WHEN epd.allotment_date <= '1900-01-01' THEN NULL ELSE DATE(epd.allotment_date) END as allotment_date"),
                    DB::raw("CASE WHEN epd.possession_date <= '1900-01-01' THEN NULL ELSE DATE(epd.possession_date) END as possession_date")
                );

            $row = $rowQ->first();
            if ($row) {
                $houseDetails = (object) [
                    'campus_name' => $row->campus_name ?? '—',
                    'block_name' => $row->block_name ?? '—',
                    'unit_type' => $row->unit_type_name ?? '—',
                    'unit_sub_type' => $row->unit_sub_type ?? '—',
                    'house_no' => $row->house_no ?? '—',
                    'allotment_date' => $row->allotment_date
                        ? (is_string($row->allotment_date)
                            ? (date('d-m-Y', strtotime($row->allotment_date)) ?: $row->allotment_date)
                            : \Carbon\Carbon::parse($row->allotment_date)->format('d-m-Y'))
                        : '—',
                    'possession_date' => $row->possession_date
                        ? (is_string($row->possession_date)
                            ? (date('d-m-Y', strtotime($row->possession_date)) ?: $row->possession_date)
                            : \Carbon\Carbon::parse($row->possession_date)->format('d-m-Y'))
                        : '—',
                ];
            }
        }

        $changeRows = DB::table('estate_change_home_req_details as ec')
            ->leftJoin('estate_campus_master as cm', 'cm.pk', '=', 'ec.estate_campus_master_pk')
            ->leftJoin('estate_block_master as bm', 'bm.pk', '=', 'ec.estate_block_master_pk')
            ->leftJoin('estate_unit_type_master as ut', 'ut.pk', '=', 'ec.estate_unit_type_master_pk')
            ->leftJoin('estate_unit_sub_type_master as ust', 'ust.pk', '=', 'ec.estate_unit_sub_type_master_pk')
            ->where('ec.estate_home_req_details_pk', $id)
            ->orderByDesc('ec.change_req_date')
            ->select(
                'ec.pk',
                'ec.estate_change_req_ID',
                'ec.change_house_no',
                'ec.change_req_date',
                'ec.remarks',
                'ec.f_status',
                'ec.change_ap_dis_status',
                'cm.campus_name',
                'bm.block_name',
                'ut.unit_type',
                'ust.unit_sub_type'
            )
            ->get();

        $changeApDisMap = [0 => 'Pending', 1 => 'Approved', 2 => 'Disapproved'];
        $changeRequestDetails = $changeRows->map(function ($row) use ($changeApDisMap) {
            $changeApDis = (int) ($row->change_ap_dis_status ?? 0);
            // Once Approved (1) or Disapproved (2), show "Decision made"; else use f_status for pending/forwarded
            $fStatusLabel = ($changeApDis === 1 || $changeApDis === 2)
                ? 'Decision made'
                : ((int) ($row->f_status ?? 0) === 1 ? 'Pending approval' : 'Decision made');
            return (object) [
                'pk' => (int) $row->pk,
                'estate_change_req_ID' => $row->estate_change_req_ID ?? ('Chg-' . $row->pk),
                'change_house_no' => $row->change_house_no ?? '—',
                'change_req_date' => $row->change_req_date ? \Carbon\Carbon::parse($row->change_req_date)->format('d-m-Y H:i') : '—',
                'remarks' => $row->remarks ?? '—',
                'f_status_label' => $fStatusLabel,
                'change_ap_dis_status' => $changeApDis,
                'change_ap_dis_status_label' => $changeApDisMap[$changeApDis] ?? '—',
                'campus_name' => $row->campus_name ?? '—',
                'block_name' => $row->block_name ?? '—',
                'unit_type' => $row->unit_type ?? '—',
                'unit_sub_type' => $row->unit_sub_type ?? '—',
                'edit_url' => route('admin.estate.change-request-details', ['id' => $row->pk]),
            ];
        });

        return view('admin.estate.request_and_change_request_details', [
            'requestForHouse' => $requestForHouse,
            'changeRequestDetails' => $changeRequestDetails,
            'houseDetails' => $houseDetails,
        ]);
    }

    /**
     * HAC Approved - Single table: Change requests + New requests.
     */
    public function changeRequestHacApproved(EstateHacApprovedDataTable $dataTable)
    {
        return $dataTable->render('admin.estate.change_request_hac_approved');
    }

    /**
     * Raise Change Request - Show form to create new change request.
     * Gate: estate_home_request_details.current_alot must NOT be null (employee must have house allotted).
     * Id = estate_home_request_details.pk.
     */
    public function raiseChangeRequest($id)
    {
        $id = (int) $id;
        $homeReq = DB::table('estate_home_request_details')->where('pk', $id)->first();
        if (! $homeReq) {
            return redirect()->route('admin.estate.request-for-estate')->with('error', 'Request not found.');
        }
        $currentAlot = trim((string) ($homeReq->current_alot ?? ''));
        if ($currentAlot === '') {
            return redirect()->route('admin.estate.request-details', ['id' => $id])
                ->with('error', 'Change request is only allowed when the employee already has a house allotted (Current Allotment must be set).');
        }

        // Prevent multiple *pending* change requests for the same house request.
        // As long as there is a change request with change_ap_dis_status = 0 (pending),
        // do not allow raising another one.
        $hasPendingChange = DB::table('estate_change_home_req_details')
            ->where('estate_home_req_details_pk', $id)
            ->where('change_ap_dis_status', 0)
            ->exists();
        if ($hasPendingChange) {
            return redirect()->route('admin.estate.request-details', ['id' => $id])
                ->with('error', 'A change request is already pending for this house request. You cannot raise another one until it is decided.');
        }

        $eligibilityMap = [61 => 'Type-I', 62 => 'Type-II', 63 => 'Type-III', 64 => 'Type-IV', 65 => 'Type-V', 66 => 'Type-VI', 69 => 'Type-IX', 70 => 'Type-X', 71 => 'Type-XI', 73 => 'Type-XIII'];
        $detail = (object) [
            'estate_home_req_details_pk' => $id,
            'request_id' => $homeReq->req_id ?? '—',
            'request_date' => $homeReq->req_date ? \Carbon\Carbon::parse($homeReq->req_date)->format('d-m-Y') : '—',
            'name' => $homeReq->emp_name ?? '—',
            'emp_id' => $homeReq->employee_id ?? '—',
            'designation' => $homeReq->emp_designation ?? '—',
            'pay_scale' => $homeReq->pay_scale ?? '—',
            'doj_pay_scale' => $homeReq->doj_pay_scale ? \Carbon\Carbon::parse($homeReq->doj_pay_scale)->format('d-m-Y') : '—',
            'doj_academy' => $homeReq->doj_academic ? \Carbon\Carbon::parse($homeReq->doj_academic)->format('d-m-Y') : '—',
            'doj_service' => $homeReq->doj_service ? \Carbon\Carbon::parse($homeReq->doj_service)->format('d-m-Y') : '—',
            'current_allotment' => $currentAlot,
            'requested_change_house' => '',
            'estate_campus_master_pk' => null,
            'estate_unit_type_master_pk' => null,
            'estate_block_master_pk' => null,
            'estate_unit_sub_type_master_pk' => null,
            'change_house_no' => null,
            'remarks' => null,
        ];
        $detail->eligibility_label = $eligibilityMap[(int) ($homeReq->eligibility_type_pk ?? 0)] ?? ('Type-' . ($homeReq->eligibility_type_pk ?? ''));

        $estateCampuses = DB::table('estate_campus_master')->orderBy('campus_name')->get(['pk', 'campus_name']);
        $unitTypes = DB::table('estate_unit_type_master')->orderBy('unit_type')->get(['pk', 'unit_type']);
        $buildings = DB::table('estate_block_master')->orderBy('block_name')->get(['pk', 'block_name']);
        $unitSubTypes = DB::table('estate_unit_sub_type_master')->orderBy('unit_sub_type')->get(['pk', 'unit_sub_type']);

        return view('admin.estate.raise_change_request', [
            'detail' => $detail,
            'estateCampuses' => $estateCampuses,
            'unitTypes' => $unitTypes,
            'buildings' => $buildings,
            'unitSubTypes' => $unitSubTypes,
            'houseOptions' => collect(),
            'formAction' => route('admin.estate.raise-change-request.store'),
        ]);
    }

    /**
     * Store new change request (Raise Change Request).
     * INSERT estate_change_home_req_details, UPDATE estate_home_request_details SET change_status = 1.
     */
    public function storeRaiseChangeRequest(Request $request)
    {
        $validated = $request->validate([
            'estate_home_req_details_pk' => 'required|integer|exists:estate_home_request_details,pk',
            'estate_name' => 'required|integer|exists:estate_campus_master,pk',
            'unit_type' => 'required|integer|exists:estate_unit_type_master,pk',
            'building_name' => 'required|integer|exists:estate_block_master,pk',
            'unit_sub_type' => 'required|integer|exists:estate_unit_sub_type_master,pk',
            'house_no' => 'required|string|max:50',
            'remarks' => 'nullable|string|max:500',
        ]);

        $homeReqPk = (int) $validated['estate_home_req_details_pk'];
        $homeReq = DB::table('estate_home_request_details')->where('pk', $homeReqPk)->first();
        if (! $homeReq) {
            return redirect()->route('admin.estate.request-for-estate')->with('error', 'Request not found.');
        }
        $currentAlot = trim((string) ($homeReq->current_alot ?? ''));
        if ($currentAlot === '') {
            return redirect()->route('admin.estate.request-details', ['id' => $homeReqPk])
                ->with('error', 'Change request is only allowed when the employee already has a house allotted.');
        }

        // Prevent duplicate *pending* change requests for the same house request.
        $hasPendingChange = DB::table('estate_change_home_req_details')
            ->where('estate_home_req_details_pk', $homeReqPk)
            ->where('change_ap_dis_status', 0)
            ->exists();
        if ($hasPendingChange) {
            return redirect()->route('admin.estate.request-details', ['id' => $homeReqPk])
                ->with('error', 'A change request is already pending for this house request. You cannot raise another one until it is decided.');
        }

        $changeReqId = $this->getNextChangeRequestId();

        $hasCol = fn ($col) => \Illuminate\Support\Facades\Schema::hasColumn('estate_change_home_req_details', $col);
        $insertData = [
            'estate_home_req_details_pk' => $homeReqPk,
            'estate_change_req_ID' => $changeReqId,
            'estate_campus_master_pk' => (int) $validated['estate_name'],
            'estate_unit_type_master_pk' => (int) $validated['unit_type'],
            'estate_block_master_pk' => (int) $validated['building_name'],
            'estate_unit_sub_type_master_pk' => (int) $validated['unit_sub_type'],
            'change_house_no' => $validated['house_no'],
            'remarks' => $validated['remarks'] ?? null,
            'change_req_date' => now()->toDateTimeString(),
            'f_status' => 1,
            'change_ap_dis_status' => 0,
        ];
        if ($hasCol('estate_change_hac_status')) {
            $insertData['estate_change_hac_status'] = 1;
        }

        DB::table('estate_change_home_req_details')->insert($insertData);
        DB::table('estate_home_request_details')->where('pk', $homeReqPk)->update(['change_status' => 1]);

        return redirect()
            ->route('admin.estate.request-details', ['id' => $homeReqPk])
            ->with('success', 'Change request raised successfully. It will appear in HAC Approved for approval.');
    }

    /**
     * Generate next change request ID (e.g. CHG-20240227-001).
     */
    private function getNextChangeRequestId(): string
    {
        $prefix = 'CHG-' . date('Ymd') . '-';
        $latest = DB::table('estate_change_home_req_details')
            ->whereNotNull('estate_change_req_ID')
            ->where('estate_change_req_ID', 'like', $prefix . '%')
            ->orderBy('pk', 'desc')
            ->value('estate_change_req_ID');
        $num = 1;
        if ($latest && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $latest, $m)) {
            $num = (int) $m[1] + 1;
        }
        return $prefix . sprintf('%03d', $num);
    }

    /**
     * Change Request Details - Form to add/edit change details (Bootstrap 5 layout).
     * Self-service (HAC Person etc.): only their own change requests; ownership enforced.
     */
    public function changeRequestDetails($id = null)
    {
        $optionsQuery = DB::table('estate_change_home_req_details as ec')
            ->join('estate_home_request_details as eh', 'ec.estate_home_req_details_pk', '=', 'eh.pk')
            ->orderByDesc('ec.pk')
            ->limit(300)
            ->select('ec.pk', 'ec.estate_change_req_ID');

        $user = Auth::user();
        if ($user && ! (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'))) {
            $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
            if (! empty($employeeIds)) {
                $optionsQuery->whereIn('eh.employee_pk', $employeeIds);
            } else {
                $optionsQuery->whereRaw('1 = 0');
            }
        }

        $changeRequestOptions = $optionsQuery->get();

        $selectedId = $id ? (int) $id : (int) ($changeRequestOptions->first()->pk ?? 0);
        if ($selectedId > 0) {
            $this->ensureChangeRequestOwnership($selectedId);
        }
        $mapped = $selectedId > 0 ? $this->mapChangeRequestDetail($selectedId) : null;

        // Home requests that have current allotment — for "Create new change request" (same rules as Raise Change Request).
        $homeRequestsQuery = DB::table('estate_home_request_details as eh')
            ->whereNotNull('eh.current_alot')
            ->whereRaw('TRIM(COALESCE(eh.current_alot, "")) != ""')
            ->whereIn('eh.status', [0, 1])
            ->select('eh.pk', 'eh.req_id', 'eh.emp_name', 'eh.employee_id', 'eh.current_alot')
            ->orderByDesc('eh.pk')
            ->limit(200);
        if ($user && ! (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'))) {
            $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
            if (! empty($employeeIds)) {
                $homeRequestsQuery->whereIn('eh.employee_pk', $employeeIds);
            } else {
                $homeRequestsQuery->whereRaw('1 = 0');
            }
        }
        $homeRequestsForNewChange = $homeRequestsQuery->get();

        return view('admin.estate.change_request_details', [
            'detail' => $mapped['detail'] ?? null,
            'selectedChangeRequestId' => $selectedId > 0 ? $selectedId : null,
            'changeRequestOptions' => $changeRequestOptions,
            'homeRequestsForNewChange' => $homeRequestsForNewChange,
            'estateCampuses' => $mapped['estateCampuses'] ?? collect(),
            'unitTypes' => $mapped['unitTypes'] ?? collect(),
            'buildings' => $mapped['buildings'] ?? collect(),
            'unitSubTypes' => $mapped['unitSubTypes'] ?? collect(),
            'houseOptions' => $mapped['houseOptions'] ?? collect(),
            'formAction' => ($selectedId > 0)
                ? route('admin.estate.change-request-details.update', ['id' => $selectedId])
                : '#',
        ]);
    }

    /**
     * Return Change Request Details form HTML for modal (AJAX).
     * Self-service: only own change request allowed (403 otherwise).
     */
    public function changeRequestDetailsModal($id)
    {
        $id = (int) $id;
        $this->ensureChangeRequestOwnership($id);

        $mapped = $this->mapChangeRequestDetail($id);
        $formAction = route('admin.estate.change-request-details.update', ['id' => $id]);
        return view('admin.estate._change_request_details_form', [
            'detail' => $mapped['detail'] ?? null,
            'estateCampuses' => $mapped['estateCampuses'] ?? collect(),
            'unitTypes' => $mapped['unitTypes'] ?? collect(),
            'buildings' => $mapped['buildings'] ?? collect(),
            'unitSubTypes' => $mapped['unitSubTypes'] ?? collect(),
            'houseOptions' => $mapped['houseOptions'] ?? collect(),
            'inModal' => true,
            'formAction' => $formAction,
        ]);
    }

    /**
     * Update change request details (house mapping + remarks).
     * Self-service: only own change request allowed (403 otherwise).
     */
    public function updateChangeRequestDetails(Request $request, $id)
    {
        $id = (int) $id;
        $this->ensureChangeRequestOwnership($id);

        $record = DB::table('estate_change_home_req_details')->where('pk', $id)->first();
        if (! $record) {
            return redirect()->back()->with('error', 'Change request record not found.');
        }
        $apDisStatus = (int) ($record->change_ap_dis_status ?? 0);
        if ($apDisStatus === 1 || $apDisStatus === 2) {
            $msg = $apDisStatus === 1
                ? 'This change request is already approved. You cannot edit it. Please create a new change request using the form below.'
                : 'This change request is already disapproved. You cannot edit it. Please create a new change request using the form below.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $validated = $request->validate([
            'estate_name' => 'required|integer|exists:estate_campus_master,pk',
            'unit_type' => 'required|integer|exists:estate_unit_type_master,pk',
            'building_name' => 'required|integer|exists:estate_block_master,pk',
            'unit_sub_type' => 'required|integer|exists:estate_unit_sub_type_master,pk',
            'house_no' => 'required|string|max:50',
            'remarks' => 'nullable|string|max:500',
        ]);

        DB::table('estate_change_home_req_details')
            ->where('pk', (int) $id)
            ->update([
                'estate_campus_master_pk' => (int) $validated['estate_name'],
                'estate_unit_type_master_pk' => (int) $validated['unit_type'],
                'estate_block_master_pk' => (int) $validated['building_name'],
                'estate_unit_sub_type_master_pk' => (int) $validated['unit_sub_type'],
                'change_house_no' => $validated['house_no'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Change request details updated successfully.',
            ]);
        }

        return redirect()
            ->route('admin.estate.change-request-details', ['id' => (int) $id])
            ->with('success', 'Change request details updated successfully.');
    }

    private function mapChangeRequestDetail(int $changeRequestPk): ?array
    {
        $latestPossessionSub = DB::table('estate_possession_details as ep')
            ->select('ep.estate_home_request_details', DB::raw('MAX(ep.pk) as latest_possession_pk'))
            ->whereNotNull('ep.estate_home_request_details')
            ->groupBy('ep.estate_home_request_details');

        $row = DB::table('estate_change_home_req_details as ec')
            ->leftJoin('estate_home_request_details as eh', 'ec.estate_home_req_details_pk', '=', 'eh.pk')
            ->leftJoinSub($latestPossessionSub, 'lp', function ($join) {
                $join->on('lp.estate_home_request_details', '=', 'eh.pk');
            })
            ->leftJoin('estate_possession_details as epd', 'epd.pk', '=', 'lp.latest_possession_pk')
            ->leftJoin('estate_house_master as hm', 'hm.pk', '=', 'epd.estate_house_master_pk')
            ->leftJoin('estate_campus_master as cm', 'cm.pk', '=', 'ec.estate_campus_master_pk')
            ->leftJoin('estate_unit_type_master as ut', 'ut.pk', '=', 'ec.estate_unit_type_master_pk')
            ->leftJoin('estate_block_master as bm', 'bm.pk', '=', 'ec.estate_block_master_pk')
            ->leftJoin('estate_unit_sub_type_master as ust', 'ust.pk', '=', 'ec.estate_unit_sub_type_master_pk')
            ->where('ec.pk', $changeRequestPk)
            ->select(
                'ec.pk',
                'ec.estate_change_req_ID',
                'ec.change_req_date',
                'ec.change_house_no',
                'ec.remarks',
                'ec.change_ap_dis_status',
                'ec.estate_campus_master_pk',
                'ec.estate_unit_type_master_pk',
                'ec.estate_block_master_pk',
                'ec.estate_unit_sub_type_master_pk',
                'eh.emp_name',
                'eh.employee_id',
                'eh.emp_designation',
                'eh.pay_scale',
                'eh.doj_pay_scale',
                'eh.doj_academic',
                'eh.doj_service',
                'eh.current_alot',
                'hm.house_no as possession_house_no'
            )
            ->first();

        if (! $row) {
            return null;
        }

        $detail = (object) [
            'pk' => (int) $row->pk,
            'request_id' => $row->estate_change_req_ID ?: ('Chg-Req-' . $row->pk),
            'request_date' => $row->change_req_date ? \Carbon\Carbon::parse($row->change_req_date)->format('d-m-Y') : '—',
            'name' => $row->emp_name ?: '—',
            'emp_id' => $row->employee_id ?: '—',
            'designation' => $row->emp_designation ?: '—',
            'pay_scale' => $row->pay_scale ?: '—',
            'doj_pay_scale' => $row->doj_pay_scale ? \Carbon\Carbon::parse($row->doj_pay_scale)->format('d-m-Y') : '—',
            'doj_academy' => $row->doj_academic ? \Carbon\Carbon::parse($row->doj_academic)->format('d-m-Y') : '—',
            'doj_service' => $row->doj_service ? \Carbon\Carbon::parse($row->doj_service)->format('d-m-Y') : '—',
            'current_allotment' => $row->current_alot ?: ($row->possession_house_no ?: '—'),
            'requested_change_house' => $row->change_house_no ?: '—',
            'estate_campus_master_pk' => $row->estate_campus_master_pk ? (int) $row->estate_campus_master_pk : null,
            'estate_unit_type_master_pk' => $row->estate_unit_type_master_pk ? (int) $row->estate_unit_type_master_pk : null,
            'estate_block_master_pk' => $row->estate_block_master_pk ? (int) $row->estate_block_master_pk : null,
            'estate_unit_sub_type_master_pk' => $row->estate_unit_sub_type_master_pk ? (int) $row->estate_unit_sub_type_master_pk : null,
            'change_house_no' => $row->change_house_no,
            'remarks' => $row->remarks,
            'change_ap_dis_status' => (int) ($row->change_ap_dis_status ?? 0),
        ];

        $estateCampuses = DB::table('estate_campus_master')
            ->orderBy('campus_name')
            ->get(['pk', 'campus_name']);

        $unitTypes = DB::table('estate_unit_type_master')
            ->orderBy('unit_type')
            ->get(['pk', 'unit_type']);

        // Buildings: filter by selected campus (and unit type) so dropdown matches estate
        $buildings = DB::table('estate_house_master as h')
            ->join('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->when($detail->estate_campus_master_pk, fn ($q) => $q->where('h.estate_campus_master_pk', $detail->estate_campus_master_pk))
            ->select('b.pk', 'b.block_name')
            ->distinct()
            ->orderBy('b.block_name')
            ->get();

        // Unit sub types: filter by selected campus + block (and unit type) so dropdown matches building
        $unitSubTypes = collect();
        if ($detail->estate_campus_master_pk && $detail->estate_block_master_pk) {
            $unitSubTypes = DB::table('estate_house_master as h')
                ->join('estate_unit_sub_type_master as u', 'h.estate_unit_sub_type_master_pk', '=', 'u.pk')
                ->where('h.estate_campus_master_pk', $detail->estate_campus_master_pk)
                ->where('h.estate_block_master_pk', $detail->estate_block_master_pk)
                ->select('u.pk', 'u.unit_sub_type')
                ->distinct()
                ->orderBy('u.unit_sub_type')
                ->get();
        }

        $occupiedHousePks = DB::table('estate_possession_details')
            ->where('return_home_status', 0)
            ->whereNotNull('estate_house_master_pk')
            ->pluck('estate_house_master_pk')
            ->merge(
                DB::table('estate_possession_other')
                    ->where('return_home_status', 0)
                    ->whereNotNull('estate_house_master_pk')
                    ->pluck('estate_house_master_pk')
            )
            ->unique()
            ->values();

        $houseQuery = DB::table('estate_house_master as h')
            ->leftJoin('estate_block_master as b', 'b.pk', '=', 'h.estate_block_master_pk')
            ->select('h.pk', 'h.house_no', 'h.estate_campus_master_pk', 'h.estate_block_master_pk', 'h.estate_unit_sub_type_master_pk', 'b.block_name')
            ->when($detail->estate_campus_master_pk, fn ($q) => $q->where('h.estate_campus_master_pk', $detail->estate_campus_master_pk))
            ->when($detail->estate_block_master_pk, fn ($q) => $q->where('h.estate_block_master_pk', $detail->estate_block_master_pk))
            ->when($detail->estate_unit_sub_type_master_pk, fn ($q) => $q->where('h.estate_unit_sub_type_master_pk', $detail->estate_unit_sub_type_master_pk))
            ->where(function ($q) use ($occupiedHousePks, $detail) {
                $q->where(function ($nested) use ($occupiedHousePks) {
                    $nested->where('h.used_home_status', 0)
                        ->where('h.vacant_renovation_status', 1);
                    if ($occupiedHousePks->isNotEmpty()) {
                        $nested->whereNotIn('h.pk', $occupiedHousePks->toArray());
                    }
                });
                if ($detail->change_house_no) {
                    $q->orWhere('h.house_no', $detail->change_house_no);
                }
            })
            ->orderBy('h.house_no');

        $houseOptions = $houseQuery->get()->map(function ($house) {
            $label = trim(($house->block_name ? $house->block_name . ' - ' : '') . ($house->house_no ?: 'N/A'));
            return (object) [
                'pk' => (int) $house->pk,
                'house_no' => $house->house_no,
                'label' => $label,
            ];
        });

        return [
            'detail' => $detail,
            'estateCampuses' => $estateCampuses,
            'unitTypes' => $unitTypes,
            'buildings' => $buildings,
            'unitSubTypes' => $unitSubTypes,
            'houseOptions' => $houseOptions,
        ];
    }

    /**
     * Request For House - List of house requests (Bootstrap 5 layout).
     * Self-service (HAC Person / Staff etc.): only their own change requests. Estate/Admin: full list.
     */
    public function requestForHouse()
    {
        $latestPossessionSub = DB::table('estate_possession_details as ep')
            ->select('ep.estate_home_request_details', DB::raw('MAX(ep.pk) as latest_possession_pk'))
            ->whereNotNull('ep.estate_home_request_details')
            ->groupBy('ep.estate_home_request_details');

        $query = DB::table('estate_change_home_req_details as ec')
            ->join('estate_home_request_details as eh', 'ec.estate_home_req_details_pk', '=', 'eh.pk')
            ->leftJoinSub($latestPossessionSub, 'lp', function ($join) {
                $join->on('lp.estate_home_request_details', '=', 'eh.pk');
            })
            ->leftJoin('estate_possession_details as epd', 'epd.pk', '=', 'lp.latest_possession_pk')
            ->select(
                'ec.pk',
                'ec.estate_change_req_ID',
                'ec.change_req_date',
                'ec.change_house_no',
                'ec.change_ap_dis_status',
                'eh.emp_name',
                'eh.employee_id',
                'eh.doj_academic',
                'eh.eligibility_type_pk',
                'eh.current_alot',
                'epd.possession_date',
                'epd.current_meter_reading_date',
                'epd.return_home_status'
            )
            ->orderByDesc('ec.pk');

        $user = Auth::user();
        if ($user && ! (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'))) {
            $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
            if (!empty($employeeIds)) {
                $query->whereIn('eh.employee_pk', $employeeIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $requests = $query->get()
            ->map(function ($row) {
                $status = (int) ($row->change_ap_dis_status ?? 0);
                $statusLabel = match ($status) {
                    1 => 'Approved',
                    2 => 'Disapproved',
                    default => 'Pending',
                };

                $eligibilityLabel = match ((int) ($row->eligibility_type_pk ?? 0)) {
                    61 => 'Type-I',
                    62 => 'Type-II',
                    63 => 'Type-III',
                    64 => 'Type-IV',
                    65 => 'Type-V',
                    66 => 'Type-VI',
                    69 => 'Type-IX',
                    70 => 'Type-X',
                    71 => 'Type-XI',
                    73 => 'Type-XIII',
                    default => '—',
                };

                $allottedHouse = $status === 1
                    ? ($row->change_house_no ?: ($row->current_alot ?: '—'))
                    : ($row->current_alot ?: ($row->change_house_no ?: '—'));

                // Possession To: if latest possession row is marked as returned (return_home_status = 1),
                // use its current_meter_reading_date as the "to" date; otherwise keep as em dash.
                $possessionTo = '—';
                if (isset($row->return_home_status) && (int) $row->return_home_status === 1 && ! empty($row->current_meter_reading_date)) {
                    $possessionTo = \Carbon\Carbon::parse($row->current_meter_reading_date)->format('d-m-Y');
                }

                return (object) [
                    'pk' => $row->pk,
                    'request_id' => $row->estate_change_req_ID ?: ('Chg-Req-' . $row->pk),
                    'request_date' => $row->change_req_date ? \Carbon\Carbon::parse($row->change_req_date)->format('d-m-Y') : '—',
                    'request_date_sort' => $row->change_req_date ? \Carbon\Carbon::parse($row->change_req_date)->format('Y-m-d') : '',
                    'change_status' => $status,
                    'name' => $row->emp_name ?: '—',
                    'emp_id' => $row->employee_id ?: '—',
                    'doj_academy' => $row->doj_academic ? \Carbon\Carbon::parse($row->doj_academic)->format('d-m-Y') : '—',
                    'status' => $statusLabel,
                    'alloted_house' => $allottedHouse,
                    'eligibility_type' => $eligibilityLabel,
                    'possession_from' => $row->possession_date ? \Carbon\Carbon::parse($row->possession_date)->format('d-m-Y') : '—',
                    'possession_to' => $possessionTo,
                    'change_approved' => $status === 1,
                ];
            });

        return view('admin.estate.request_for_house', ['requests' => $requests]);
    }

    /**
     * Get change request approve details - employee full details, paygrade/eligibility type, available vacant residences.
     */
    public function getChangeRequestApproveDetails($id)
    {
        $record = EstateChangeHomeReqDetails::with('estateHomeRequestDetails')
            ->where('estate_change_hac_status', 1)
            ->findOrFail($id);

        $homeReq = $record->estateHomeRequestDetails;
        if (! $homeReq) {
            return response()->json(['error' => 'Request details not found'], 404);
        }

        $eligibilityTypePk = (int) ($homeReq->eligibility_type_pk ?? 62);
        $eligibilityLabel = 'Type-' . ($eligibilityTypePk == 61 ? 'I' : ($eligibilityTypePk == 62 ? 'II' : ($eligibilityTypePk == 63 ? 'III' : 'IV')));

        // Fetch vacant houses by eligibility type (same logic as getChangeRequestVacantHouses)
        // Only houses with active possession (return_home_status = 0) are treated as occupied.
        $occupiedHousePks = DB::table('estate_possession_details')
            ->whereNotNull('estate_house_master_pk')
            ->where('return_home_status', 0)
            ->pluck('estate_house_master_pk')
            ->merge(
                DB::table('estate_possession_other')
                    ->whereNotNull('estate_house_master_pk')
                    ->where('return_home_status', 0)
                    ->pluck('estate_house_master_pk')
            )
            ->unique()
            ->values();

        $housesQuery = DB::table('estate_house_master as h')
            ->join('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->where('h.estate_unit_sub_type_master_pk', $eligibilityTypePk)
            ->where('h.used_home_status', 0)
            ->where('h.vacant_renovation_status', 1)
            ->whereNotNull('h.house_no')
            ->where('h.house_no', '!=', '')
            ->where('h.house_no', '!=', '0')
            ->select('h.pk', 'h.house_no', 'b.block_name')
            ->orderBy('b.block_name')
            ->orderBy('h.house_no');

        if ($occupiedHousePks->isNotEmpty()) {
            $housesQuery->whereNotIn('h.pk', $occupiedHousePks->toArray());
        }

        $vacantHouses = $housesQuery->get()
            ->filter(function ($row) {
                return trim($row->house_no ?? '') !== '' && trim($row->house_no) !== '0';
            })
            ->map(function ($row) {
                $houseNo = trim($row->house_no);
                return [
                    'pk' => (int) $row->pk,
                    'house_no' => $houseNo,
                    'block_name' => $row->block_name ?? '',
                    'label' => ($row->block_name ? $row->block_name . ' - ' : '') . $houseNo,
                ];
            });

        // Campuses and unit types per campus for dependent dropdowns
        $campuses = DB::table('estate_campus_master')
            ->orderBy('campus_name')
            ->get(['pk', 'campus_name']);

        $unitTypes = DB::table('estate_unit_type_master')
            ->orderBy('unit_type')
            ->get(['pk', 'unit_type']);

        $unitTypes = DB::table('estate_unit_type_master')
            ->orderBy('unit_type')
            ->get(['pk', 'unit_type']);

        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
        $unitTypesByCampusQ = DB::table('estate_campus_master as a')
            ->join('estate_house_master as b', 'a.pk', '=', 'b.estate_campus_master_pk')
            ->join('estate_unit_sub_type_master as eust', 'b.estate_unit_sub_type_master_pk', '=', 'eust.pk');

        if ($hasUnitTypeOnSubType) {
            $unitTypesByCampusQ->join('estate_unit_type_master as c', 'eust.estate_unit_type_master_pk', '=', 'c.pk');
        } else {
            $unitTypesByCampusQ->join('estate_unit_type_master as c', 'b.estate_unit_master_pk', '=', 'c.pk');
        }

        $unitTypesByCampus = $unitTypesByCampusQ
            ->select('a.pk as campus_pk', 'c.pk as unit_type_pk', 'c.unit_type')
            ->distinct()
            ->orderBy('a.pk')
            ->orderBy('c.unit_type')
            ->get()
            ->groupBy('campus_pk')
            ->map(fn ($rows) => $rows->map(fn ($r) => ['pk' => $r->unit_type_pk, 'unit_type' => $r->unit_type])->values()->all())
            ->all();

        $requestedHouseNo = trim((string) ($record->change_house_no ?? ''));
        $requestedHousePk = null;
        if ($requestedHouseNo !== '' && $record->estate_block_master_pk) {
            $houseRow = DB::table('estate_house_master')
                ->where('estate_block_master_pk', (int) $record->estate_block_master_pk)
                ->where('house_no', $requestedHouseNo)
                ->first();
            if ($houseRow) {
                $requestedHousePk = (int) $houseRow->pk;
            }
        }

        return response()->json([
            'employee' => [
                'emp_name' => $homeReq->emp_name ?? '',
                'employee_id' => $homeReq->employee_id ?? '',
                'emp_designation' => $homeReq->emp_designation ?? '',
                'pay_scale' => $homeReq->pay_scale ?? '',
                'doj_pay_scale' => $homeReq->doj_pay_scale ? \Carbon\Carbon::parse($homeReq->doj_pay_scale)->format('d-m-Y') : '',
                'doj_academic' => $homeReq->doj_academic ? \Carbon\Carbon::parse($homeReq->doj_academic)->format('d-m-Y') : '',
                'doj_service' => $homeReq->doj_service ? \Carbon\Carbon::parse($homeReq->doj_service)->format('d-m-Y') : '',
                'eligibility_type_pk' => $eligibilityTypePk,
                'eligibility_label' => $eligibilityLabel,
            ],
            'change_request' => [
                'pk' => $record->pk,
                'estate_change_req_ID' => $record->estate_change_req_ID ?? 'N/A',
                'change_house_no' => $requestedHouseNo ?: ($record->change_house_no ?? ''),
                'remarks' => $record->remarks ?? '',
                'requested_house_pk' => $requestedHousePk,
            ],
            'campuses' => $campuses,
            'unit_types_by_campus' => $unitTypesByCampus,
            'vacant_houses' => $vacantHouses,
        ]);
    }

    /**
     * Houses eligible for an employee by salary grade – DB admin query (house no laane ke liye).
     * Uses two sources:
     * 1) Requested salary-grade query via payroll_salary_master.salary_grade_pk
     * 2) Existing running query (dynamic salary-grade column fallback)
     * Distinct f.pk so saari eligible houses aayein (data mapping / duplicate rows issue fix).
     *
     * @return \Illuminate\Support\Collection<int, int> house PKs (f.pk)
     */
    private function getEligibleHousePksByEmployeePk(int $employeePk): Collection
    {
        $housePks = collect();
        $canonical = $this->resolveEmployeeMasterCanonicalPk($employeePk);
        if ($canonical === null || $canonical <= 0) {
            return $housePks;
        }
        $employeePk = $canonical;
        $empPkCol = $this->estateEmployeePkColumn();

        // Source 1 (requested): salary grades using payroll_salary_master.salary_grade_pk
        if (\Illuminate\Support\Facades\Schema::hasColumn('payroll_salary_master', 'salary_grade_pk')) {
            $gradeSql = "
                SELECT DISTINCT c.pk
                FROM employee_master a
                INNER JOIN payroll_salary_master b ON a.{$empPkCol} = b.employee_master_pk
                INNER JOIN salary_grade_master c ON b.salary_grade_pk = c.pk
                WHERE a.pk = ?
            ";

            $gradeRows = DB::select($gradeSql, [$employeePk]);
            $salaryGradePks = collect($gradeRows)->pluck('pk')->map(fn ($v) => (int) $v)->filter()->unique()->values();

            if ($salaryGradePks->isNotEmpty()) {
                $source1HousePks = DB::table('estate_eligibility_mapping as d')
                    ->join('estate_house_master as f', 'd.estate_unit_sub_type_master_pk', '=', 'f.estate_unit_sub_type_master_pk')
                    ->whereIn('d.salary_grade_master_pk', $salaryGradePks->all())
                    ->where('f.used_home_status', 0)
                    ->where('f.vacant_renovation_status', 1)
                    ->distinct()
                    ->pluck('f.pk')
                    ->map(fn ($v) => (int) $v)
                    ->values();

                $housePks = $housePks->merge($source1HousePks);
            }
        }

        // Source 2 (existing): current query (dynamic salary grade column fallback)
        $salaryGradeCol = \Illuminate\Support\Facades\Schema::hasColumn('payroll_salary_master', 'salary_grade_pk')
            ? 'salary_grade_pk'
            : 'salary_grade_master_pk';

        $sql = "
            SELECT DISTINCT f.pk
            FROM employee_master a
            INNER JOIN payroll_salary_master b ON a.{$empPkCol} = b.employee_master_pk
            INNER JOIN salary_grade_master c ON b.{$salaryGradeCol} = c.pk
            INNER JOIN estate_eligibility_mapping d ON c.pk = d.salary_grade_master_pk
            INNER JOIN estate_unit_sub_type_master e ON d.estate_unit_sub_type_master_pk = e.pk
            INNER JOIN estate_house_master f ON e.pk = f.estate_unit_sub_type_master_pk
            WHERE a.pk = ?
            AND f.used_home_status = 0
            AND f.vacant_renovation_status = 1
        ";

        $rows = DB::select($sql, [$employeePk]);
        $source2HousePks = collect($rows)->pluck('pk')->map(fn ($v) => (int) $v)->values();

        return $housePks
            ->merge($source2HousePks)
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Get new request details for allotment modal - employee info, campuses, unit types, vacant houses.
     * House list uses DB admin query: eligible by employee's salary grade (employee → payroll_salary → salary_grade → estate_eligibility_mapping → unit_sub_type → estate_house_master).
     */
    public function getNewRequestAllotDetails($id)
    {
        $homeReq = EstateHomeRequestDetails::where('hac_status', 1)
            ->where('change_status', 0)
            ->findOrFail($id);

        $existingPossession = DB::table('estate_possession_details')
            ->where('estate_home_request_details', $homeReq->pk)
            ->first();
        if ($existingPossession) {
            return response()->json(['error' => 'This request already has a house allotted.'], 404);
        }

        $employeePk = null;

        // Prefer resolving via employee_id (stable identifier); use canonical employee_master.pk.
        if ($homeReq->employee_id) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'emp_id')) {
                $employeePk = DB::table('employee_master')
                    ->where('emp_id', $homeReq->employee_id)
                    ->value('pk');
            }
            if (! $employeePk && \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'employee_id')) {
                $employeePk = DB::table('employee_master')
                    ->where('employee_id', $homeReq->employee_id)
                    ->value('pk');
            }
        }

        // Fallback: employee_pk column may store pk or pk_old — normalize to canonical pk.
        if (! $employeePk && $homeReq->employee_pk) {
            $employeePk = $this->resolveEmployeeMasterCanonicalPk((int) $homeReq->employee_pk);
        }

        $employeePk = $employeePk ? (int) $employeePk : null;

        $eligibilityTypePk = (int) ($homeReq->eligibility_type_pk ?? 62);
        $eligibilityLabel = 'Type-' . ($eligibilityTypePk == 61 ? 'I' : ($eligibilityTypePk == 62 ? 'II' : ($eligibilityTypePk == 63 ? 'III' : 'IV')));

        // Houses list ke liye aapki di hui SQL ka structure follow karte hue:
        //
        // SELECT ...
        // FROM estate_home_request_details AS eh
        // INNER JOIN estate_eligibility_mapping AS eem ON eh.eligibility_type_pk = eem.pk
        // LEFT JOIN estate_house_master AS hm
        //   ON eem.estate_unit_sub_type_master_pk = hm.estate_unit_sub_type_master_pk
        //   AND hm.used_home_status = 0
        // WHERE eh.change_status = 0 AND eh.pk = :id
        // ORDER BY eh.req_date DESC, hm.house_no ASC;
        //
        // Saath me block / campus / unit type joins add kiye gaye hain taa ki
        // UI ke labels (block_name, campus_name, unit_type) same format me rahein.

        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
        $houseRows = DB::table('estate_home_request_details as eh')
            ->join('estate_eligibility_mapping as eem', 'eh.eligibility_type_pk', '=', 'eem.pk')
            ->leftJoin('estate_house_master as h', function ($join) {
                $join->on('eem.estate_unit_sub_type_master_pk', '=', 'h.estate_unit_sub_type_master_pk')
                    ->where('h.used_home_status', 0)
                    ->where('h.vacant_renovation_status', 1);
            })
            ->leftJoin('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->leftJoin('estate_campus_master as a', 'h.estate_campus_master_pk', '=', 'a.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'h.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->leftJoin('estate_unit_type_master as c', function ($join) use ($hasUnitTypeOnSubType) {
                if ($hasUnitTypeOnSubType) {
                    $join->on('eust.estate_unit_type_master_pk', '=', 'c.pk');
                } else {
                    $join->on('h.estate_unit_master_pk', '=', 'c.pk');
                }
            })
            ->where('eh.change_status', 0)
            ->where('eh.pk', $homeReq->pk)
            ->select(
                'eh.pk as request_pk',
                'eh.req_id',
                'eh.req_date',
                'eh.emp_name',
                'eh.employee_id',
                'eh.emp_designation',
                'eh.pay_scale',
                'eh.doj_academic',
                'eh.doj_service',
                'eh.eligibility_type_pk',
                'eh.current_alot',
                'eh.status',
                'eh.app_status',
                'eh.hac_status',
                'eh.f_status',
                'eh.remarks',
                'h.pk as house_pk',
                'h.house_no',
                'h.estate_campus_master_pk',
                'h.estate_block_master_pk',
                'c.pk as estate_unit_master_pk',
                'h.estate_unit_sub_type_master_pk',
                'h.licence_fee',
                'h.water_charge',
                'h.electric_charge',
                'h.vacant_renovation_status',
                'h.used_home_status',
                'eem.salary_grade_master_pk',
                'eem.estate_unit_type_master_pk',
                // Extra for UI labels
                'b.block_name',
                'a.campus_name',
                'c.unit_type'
            )
            ->orderByDesc('eh.req_date')
            ->orderBy('h.house_no')
            ->get();

        $vacantHouses = collect($houseRows)
            ->filter(function ($row) {
                return trim($row->house_no ?? '') !== '';
            })
            ->map(function ($row) {
                $houseNo = trim($row->house_no);
                return [
                    'pk' => (int) ($row->house_pk ?? 0),
                    'house_no' => $houseNo,
                    'block_name' => $row->block_name ?? '',
                    'campus_pk' => (int) ($row->estate_campus_master_pk ?? 0),
                    'campus_name' => $row->campus_name ?? '',
                    'unit_type_pk' => (int) ($row->estate_unit_master_pk ?? 0),
                    'unit_type' => $row->unit_type ?? '',
                    'label' => ($row->block_name ? $row->block_name . ' - ' : '') . $houseNo,
                ];
            })
            ->values();

        // Fallback: include all vacant houses (same campus/unit-type mapping) even if not in salary-grade query
        $existingHousePks = $vacantHouses->pluck('pk')->filter()->unique()->values();
        $extraVacant = DB::table('estate_house_master as h')
            ->leftJoin('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->leftJoin('estate_campus_master as a', 'h.estate_campus_master_pk', '=', 'a.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'h.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->leftJoin('estate_unit_type_master as c', $hasUnitTypeOnSubType
                ? function ($join) {
                    $join->on('eust.estate_unit_type_master_pk', '=', 'c.pk');
                }
                : function ($join) {
                    $join->on('h.estate_unit_master_pk', '=', 'c.pk');
                }
            )
            ->where('h.used_home_status', 0)
            ->where('h.vacant_renovation_status', 1)
            ->when($existingHousePks->isNotEmpty(), function ($q) use ($existingHousePks) {
                $q->whereNotIn('h.pk', $existingHousePks->all());
            })
            ->select(
                'h.pk as house_pk',
                'h.house_no',
                'h.estate_campus_master_pk',
                'a.campus_name',
                'h.estate_block_master_pk',
                'b.block_name',
                'c.pk as estate_unit_master_pk',
                'c.unit_type'
            )
            ->orderBy('b.block_name')
            ->orderBy('h.house_no')
            ->get()
            ->filter(function ($row) {
                return trim($row->house_no ?? '') !== '';
            })
            ->map(function ($row) {
                $houseNo = trim($row->house_no);
                return [
                    'pk' => (int) ($row->house_pk ?? 0),
                    'house_no' => $houseNo,
                    'block_name' => $row->block_name ?? '',
                    'campus_pk' => (int) ($row->estate_campus_master_pk ?? 0),
                    'campus_name' => $row->campus_name ?? '',
                    'unit_type_pk' => (int) ($row->estate_unit_master_pk ?? 0),
                    'unit_type' => $row->unit_type ?? '',
                    'label' => ($row->block_name ? $row->block_name . ' - ' : '') . $houseNo,
                ];
            });

        if ($extraVacant->isNotEmpty()) {
            $vacantHouses = $vacantHouses
                ->merge($extraVacant)
                ->unique('pk')
                ->values();
        }

        $eligibleHousesCountFromQuery = $vacantHouses
            ->pluck('pk')
            ->filter()
            ->unique()
            ->count();

        // Keep Estate + Unit Type dropdowns aligned with eligible & vacant houses only.
        $campuses = $vacantHouses
            ->map(fn ($h) => ['pk' => (int) ($h['campus_pk'] ?? 0), 'campus_name' => (string) ($h['campus_name'] ?? '')])
            ->filter(fn ($c) => $c['pk'] > 0)
            ->unique('pk')
            ->sortBy('campus_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $unitTypesByCampus = $vacantHouses
            ->filter(fn ($h) => !empty($h['campus_pk']) && !empty($h['unit_type_pk']))
            ->groupBy('campus_pk')
            ->map(function ($rows) {
                return $rows
                    ->map(fn ($r) => ['pk' => (int) $r['unit_type_pk'], 'unit_type' => (string) ($r['unit_type'] ?? '')])
                    ->unique('pk')
                    ->sortBy('unit_type', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all();
            })
            ->all();

        return response()->json([
            'employee' => [
                'emp_name' => $homeReq->emp_name ?? '',
                'employee_id' => $homeReq->employee_id ?? '',
                'emp_designation' => $homeReq->emp_designation ?? '',
                'pay_scale' => $homeReq->pay_scale ?? '',
                'doj_pay_scale' => $homeReq->doj_pay_scale ? \Carbon\Carbon::parse($homeReq->doj_pay_scale)->format('d-m-Y') : '',
                'doj_academic' => $homeReq->doj_academic ? \Carbon\Carbon::parse($homeReq->doj_academic)->format('d-m-Y') : '',
                'doj_service' => $homeReq->doj_service ? \Carbon\Carbon::parse($homeReq->doj_service)->format('d-m-Y') : '',
                'eligibility_type_pk' => $eligibilityTypePk,
                'eligibility_label' => $eligibilityLabel,
                'employee_pk' => $employeePk,
            ],
            'request' => [
                'pk' => $homeReq->pk,
                'req_id' => $homeReq->req_id ?? 'N/A',
            ],
            'campuses' => $campuses,
            'unit_types_by_campus' => $unitTypesByCampus,
            'vacant_houses' => $vacantHouses,
            // Debug: query se kitni houses eligible thi (occupied exclude se pehle). 1 = is employee ke liye query me hi 1; zyada hone par occupied ki wajah se kam dikh rahi hain.
            'eligible_houses_count_from_query' => $eligibleHousesCountFromQuery,
            'employee_pk_used' => $employeePk,
        ]);
    }

    /**
     * Allot house to new request.
     *
     * IMPORTANT:
     * - Allotment should NOT auto-create a "completed possession" with today's dates.
     * - DB columns (allotment_date / possession_date) may be NOT NULL in some environments.
     *   So we store a sentinel pending date (1900-01-01) in possession_date.
     * - Possession record becomes "completed" only when user submits Add Possession form
     *   (with allotment_date + possession_date).
     */
    public function allotNewRequest(Request $request, $id)
    {
        if (! (hasRole('HAC Person') || hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'))) {
            abort(403, 'You do not have permission to allot estate houses.');
        }

        $homeReq = EstateHomeRequestDetails::where('hac_status', 1)
            ->where('change_status', 0)
            ->findOrFail($id);

        $request->validate([
            'estate_house_master_pk' => 'required|integer|exists:estate_house_master,pk',
        ]);

        $estateHouseMasterPk = (int) $request->estate_house_master_pk;
        $employeePk = $homeReq->employee_pk ? (int) $homeReq->employee_pk : null;

        // If employee_pk is missing, try to resolve from employee_id via employee_master (canonical pk)
        if (! $employeePk && $homeReq->employee_id) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'emp_id')) {
                $employeePk = DB::table('employee_master')->where('emp_id', $homeReq->employee_id)->value('pk');
            }
            if (! $employeePk && \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'employee_id')) {
                $employeePk = DB::table('employee_master')->where('employee_id', $homeReq->employee_id)->value('pk');
            }
            if ($employeePk) {
                $employeePk = (int) $employeePk;
                $homeReq->employee_pk = $employeePk;
                $homeReq->save();
            }
        }

        // estate_possession_details.emploee_master_pk must store employee_master.pk (not pk_old)
        if ($employeePk) {
            $resolved = $this->resolveEmployeeMasterCanonicalPk($employeePk);
            $employeePk = ($resolved !== null && $resolved > 0) ? $resolved : null;
        }

        // estate_possession_details.emploee_master_pk is NOT NULL; do not insert without a valid employee
        if (! $employeePk) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee could not be resolved for this request. Please ensure the request has employee_pk or a valid employee_id linked in employee_master.',
                ], 422);
            }
            return redirect()->route('admin.estate.change-request-hac-approved')
                ->with('error', 'Employee could not be resolved for this request. Please ensure the request has employee_pk or a valid employee_id linked in employee_master.');
        }

        // Align with Define House: only Vacant and not already used houses can be allotted
        $houseRow = DB::table('estate_house_master')->where('pk', $estateHouseMasterPk)->first();
        if ($houseRow) {
            if ((int) ($houseRow->vacant_renovation_status ?? 1) !== 1) {
                $msg = 'Selected house is Under Renovation in Define House. Only Vacant houses can be allotted.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->route('admin.estate.change-request-hac-approved')->with('error', $msg);
            }
            // Do not rely solely on used_home_status (can be stale). Consider occupied only if there is an active possession.
            $isOccupied = false;
            $activeDetails = DB::table('estate_possession_details')
                ->where('estate_house_master_pk', $estateHouseMasterPk)
                ->whereNotNull('estate_house_master_pk');
            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
                $activeDetails->where('return_home_status', 0);
            }
            $isOccupied = $activeDetails->exists();
            if (! $isOccupied) {
                $activeOther = DB::table('estate_possession_other')
                    ->where('estate_house_master_pk', $estateHouseMasterPk)
                    ->whereNotNull('estate_house_master_pk')
                    ->where('return_home_status', 0);
                $isOccupied = $activeOther->exists();
            }
            if ($isOccupied) {
                $msg = 'Selected house is already occupied. Please choose a vacant house from the list.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->route('admin.estate.change-request-hac-approved')->with('error', $msg);
            }

            // If it's not occupied but still marked used, sync the flag.
            if ((int) ($houseRow->used_home_status ?? 0) !== 0) {
                $this->refreshHouseUsedStatusFromPossession($estateHouseMasterPk);
            }
        }

        // Selected house must be eligible for this employee (salary grade → estate_eligibility_mapping → unit sub type).
        $eligibleHousePks = $this->getEligibleHousePksByEmployeePk($employeePk);
        if ($eligibleHousePks->isNotEmpty() && ! $eligibleHousePks->contains($estateHouseMasterPk)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected house is not eligible for this employee as per salary grade / estate eligibility. Please choose a house from the list (Estate, Building, Unit Sub Type) to see only eligible houses.',
                ], 422);
            }
            return redirect()->route('admin.estate.change-request-hac-approved')
                ->with('error', 'The selected house is not eligible for this employee as per salary grade / estate eligibility. Please choose a house from the list (Estate, Building, Unit Sub Type) to see only eligible houses.');
        }

        $existingPossession = DB::table('estate_possession_details')
            ->where('estate_home_request_details', $homeReq->pk)
            ->first();

        $pendingDate = '1900-01-01';

        if ($existingPossession) {
            $previousHousePk = (int) ($existingPossession->estate_house_master_pk ?? 0);
            DB::table('estate_possession_details')
                ->where('pk', $existingPossession->pk)
                ->update([
                    'estate_house_master_pk' => $estateHouseMasterPk,
                    'emploee_master_pk' => $employeePk,
                    'estate_change_id' => null,
                    // keep possession pending until Add Possession form is submitted
                    'allotment_date' => $pendingDate,
                    'possession_date' => $pendingDate,
                ]);
            $this->setHouseUsedStatus($estateHouseMasterPk, 1);
            if ($previousHousePk > 0 && $previousHousePk !== $estateHouseMasterPk) {
                $this->refreshHouseUsedStatusFromPossession($previousHousePk);
            }
        } else {
            // Create a *pending* possession row (dates will be filled later from Add Possession form).
            DB::table('estate_possession_details')->insert([
                'estate_home_request_details' => $homeReq->pk,
                'emploee_master_pk' => $employeePk,
                'allotment_date' => $pendingDate,
                'possession_date' => $pendingDate,
                'electric_meter_reading' => 0,
                'estate_house_master_pk' => $estateHouseMasterPk,
                'estate_change_id' => null,
            ]);
            $this->setHouseUsedStatus($estateHouseMasterPk, 1);
        }

        // Update request status to Allotted and set allotted house in request-for-estate list
        $house = EstateHouse::find($estateHouseMasterPk);
        $houseNo = $house ? ($house->house_no ?? '') : '';
        $homeReq->update([
            'status' => 1, // Allotted
            'current_alot' => $houseNo,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'House allotted successfully. Now add possession details (allotment date & possession date) to complete possession.',
            ]);
        }
        return redirect()->route('admin.estate.change-request-hac-approved')
            ->with('success', 'House allotted successfully. Now add possession details (allotment date & possession date) to complete possession.');
    }

    /**
     * Approve change request - set change_ap_dis_status = 1 and allot house to employee.
     */
    public function approveChangeRequest(Request $request, $id)
    {
        if (! (hasRole('HAC Person') || hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'))) {
            abort(403, 'You do not have permission to approve estate change requests.');
        }

        $record = EstateChangeHomeReqDetails::with('estateHomeRequestDetails')
            ->where('estate_change_hac_status', 1)
            ->findOrFail($id);

        $request->validate([
            'estate_house_master_pk' => 'required|integer|exists:estate_house_master,pk',
        ]);

        $estateHouseMasterPk = (int) $request->estate_house_master_pk;
        $homeReqPk = $record->estate_home_req_details_pk;
        $homeReq = $record->estateHomeRequestDetails;
        $employeePk = $homeReq->employee_pk ? (int) $homeReq->employee_pk : null;

        // If employee_pk is missing, try to resolve from employee_id via employee_master (canonical pk)
        if (! $employeePk && $homeReq->employee_id) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'emp_id')) {
                $employeePk = DB::table('employee_master')->where('emp_id', $homeReq->employee_id)->value('pk');
            }
            if (! $employeePk && \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'employee_id')) {
                $employeePk = DB::table('employee_master')->where('employee_id', $homeReq->employee_id)->value('pk');
            }
            if ($employeePk) {
                $employeePk = (int) $employeePk;
                $homeReq->employee_pk = $employeePk;
                $homeReq->save();
            }
        }

        // estate_possession_details.emploee_master_pk must store employee_master.pk (not pk_old)
        if ($employeePk) {
            $resolved = $this->resolveEmployeeMasterCanonicalPk($employeePk);
            $employeePk = ($resolved !== null && $resolved > 0) ? $resolved : null;
        }

        if (! $employeePk) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Employee could not be resolved for this request. Please ensure the request has employee_pk or a valid employee_id linked in employee_master.'], 422);
            }
            return redirect()->route('admin.estate.change-request-hac-approved')
                ->with('error', 'Employee could not be resolved for this request. Please ensure the request has employee_pk or a valid employee_id linked in employee_master.');
        }

        // Align with Define House: only Vacant and not already occupied houses can be allotted
        $houseRow = DB::table('estate_house_master')->where('pk', $estateHouseMasterPk)->first();
        if ($houseRow) {
            if ((int) ($houseRow->vacant_renovation_status ?? 1) !== 1) {
                $msg = 'Selected house is Under Renovation in Define House. Only Vacant houses can be allotted.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->route('admin.estate.change-request-hac-approved')->with('error', $msg);
            }
            $isOccupied = false;
            $activeDetails = DB::table('estate_possession_details')
                ->where('estate_house_master_pk', $estateHouseMasterPk)
                ->whereNotNull('estate_house_master_pk');
            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
                $activeDetails->where('return_home_status', 0);
            }
            $isOccupied = $activeDetails->exists();
            if (! $isOccupied) {
                $activeOther = DB::table('estate_possession_other')
                    ->where('estate_house_master_pk', $estateHouseMasterPk)
                    ->whereNotNull('estate_house_master_pk')
                    ->where('return_home_status', 0);
                $isOccupied = $activeOther->exists();
            }
            if ($isOccupied) {
                $msg = 'Selected house is already occupied. Please choose a vacant house from the list.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->route('admin.estate.change-request-hac-approved')->with('error', $msg);
            }

            if ((int) ($houseRow->used_home_status ?? 0) !== 0) {
                $this->refreshHouseUsedStatusFromPossession($estateHouseMasterPk);
            }
        }

        // Check if employee already has possession (estate_possession_details)
        $existingPossession = DB::table('estate_possession_details')
            ->where('estate_home_request_details', $homeReqPk)
            ->first();

        $pendingDate = '1900-01-01';

        if ($existingPossession) {
            $previousHousePk = (int) ($existingPossession->estate_house_master_pk ?? 0);
            DB::table('estate_possession_details')
                ->where('pk', $existingPossession->pk)
                ->update([
                    'estate_house_master_pk' => $estateHouseMasterPk,
                    'emploee_master_pk' => $employeePk,
                    'estate_change_id' => $record->estate_change_req_ID ?? null,
                    // keep possession pending until Add Possession form is submitted
                    'allotment_date' => $pendingDate,
                    'possession_date' => $pendingDate,
                ]);
            $this->setHouseUsedStatus($estateHouseMasterPk, 1);
            if ($previousHousePk > 0 && $previousHousePk !== $estateHouseMasterPk) {
                $this->refreshHouseUsedStatusFromPossession($previousHousePk);
            }
        } else {
            DB::table('estate_possession_details')->insert([
                'estate_home_request_details' => $homeReqPk,
                'emploee_master_pk' => $employeePk,
                'allotment_date' => $pendingDate,
                'possession_date' => $pendingDate,
                'electric_meter_reading' => 0,
                'estate_house_master_pk' => $estateHouseMasterPk,
                'estate_change_id' => $record->estate_change_req_ID ?? null,
            ]);
            $this->setHouseUsedStatus($estateHouseMasterPk, 1);
        }

        $record->change_ap_dis_status = 1;
        $record->f_status = 0; // Decision made — no longer "pending approval"
        $record->remarks = null;
        $record->save();

        $newHouseNo = DB::table('estate_house_master')->where('pk', $estateHouseMasterPk)->value('house_no');
        $homeReqUpdate = ['change_status' => 2];
        if ($newHouseNo !== null && $newHouseNo !== '') {
            $homeReqUpdate['current_alot'] = \Illuminate\Support\Str::limit((string) $newHouseNo, 20);
        }
        DB::table('estate_home_request_details')->where('pk', $homeReqPk)->update($homeReqUpdate);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Change request approved and house allotted successfully.']);
        }
        return redirect()->route('admin.estate.change-request-hac-approved')
            ->with('success', 'Change request approved and house allotted successfully.');
    }

    /**
     * Disapprove change request - open modal for reason; save reason in remarks and set change_ap_dis_status = 2.
     */
    public function disapproveChangeRequest(Request $request, $id)
    {
        if (! (hasRole('HAC Person') || hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'))) {
            abort(403, 'You do not have permission to disapprove estate change requests.');
        }

        $request->validate([
            'disapprove_reason' => 'required|string|max:500',
        ]);

        $record = EstateChangeHomeReqDetails::where('estate_change_hac_status', 1)->findOrFail($id);
        $record->change_ap_dis_status = 2;
        $record->f_status = 0; // Decision made — no longer "pending approval"
        $record->remarks = $request->disapprove_reason;
        $record->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Change request disapproved. Remark saved.']);
        }
        return redirect()->route('admin.estate.change-request-hac-approved')
            ->with('success', 'Change request disapproved. Remark saved.');
    }

    /**
     * Add Other Estate Request - Form page.
     */
    public function addOtherEstateRequest(Request $request)
    {
        $record = null;
        $prefill = [
            'employee_name' => $request->query('employee_name'),
            'father_name' => $request->query('father_name'),
            'section' => $request->query('section'),
            'doj_academy' => $request->query('doj_academy'),
        ];

        if ($request->filled('id')) {
            $record = EstateOtherRequest::find($request->query('id'));
            if ($record) {
                $prefill = [
                    'employee_name' => $record->emp_name,
                    'father_name' => $record->f_name,
                    'section' => $record->section,
                    'doj_academy' => $record->doj_acad?->format('Y-m-d'),
                ];
            }
        }

        return view('admin.estate.add_other_estate_request', compact('prefill', 'record'));
    }

    /**
     * Store Other Estate Request - saves to estate_other_req table (from SQL import).
     */
    public function storeOtherEstateRequest(Request $request)
    {
        $noSpecialChars = 'regex:/^[\pL\pN\s.\-\']+$/u';
        $validated = $request->validate([
            'employee_name' => ['required', 'string', 'max:500'],
            'father_name' => ['required', 'string', 'max:500', $noSpecialChars],
            'section' => ['required', 'string', 'max:500', $noSpecialChars],
            'doj_academy' => ['required', 'date', 'after_or_equal:1950-01-01', 'before_or_equal:today'],
            'designation' => ['nullable', 'string', 'max:500', $noSpecialChars],
        ], [
            'father_name.regex' => 'Father name may only contain letters, numbers, spaces, hyphen, apostrophe and dot.',
            'section.regex' => 'Section may only contain letters, numbers, spaces, hyphen, apostrophe and dot.',
            'designation.regex' => 'Designation may only contain letters, numbers, spaces, hyphen, apostrophe and dot.',
            'doj_academy.after_or_equal' => 'DOJ in Academy must be on or after 01-01-1950.',
            'doj_academy.before_or_equal' => 'DOJ in Academy cannot be a future date.',
        ]);

        $data = [
            'emp_name' => $validated['employee_name'],
            'f_name' => $validated['father_name'],
            'section' => $validated['section'],
            'doj_acad' => $validated['doj_academy'],
            'designation' => $validated['designation'] ?? null,
        ];

        if ($request->filled('id')) {
            $record = EstateOtherRequest::findOrFail($request->id);
            $record->update($data);
            $message = 'Estate request successfully updated.';
        } else {
            $data['status'] = 0;
            $data['request_no_oth'] = $this->generateRequestNo();
            EstateOtherRequest::create($data);
            $message = 'Estate request successfully saved.';
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return redirect()
            ->route('admin.estate.request-for-others')
            ->with('success', $message);
    }

    /**
     * Delete Other Estate Request.
     */
    public function destroyOtherEstateRequest(Request $request, $id)
    {
        $record = EstateOtherRequest::find($id);
        if (!$record) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
            }
            return redirect()->route('admin.estate.request-for-others')->with('error', 'Record not found.');
        }

        $record->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Estate request deleted successfully.']);
        }
        return redirect()->route('admin.estate.request-for-others')->with('success', 'Estate request deleted successfully.');
    }

    /**
     * Estate Possession View - Add possession form.
     * Unit types per campus from DB engineer query: campus + house_master + unit_type_master join.
     */
    public function possessionView(Request $request)
    {
        $record = null;
        if ($request->filled('id')) {
            $record = EstatePossessionOther::find($request->id);

            // When editing, normalise location + house fields from estate_house_master
            // so that the dependent dropdowns and meter display can be correctly
            // pre-populated even if legacy records have inconsistent columns.
            if ($record && $record->estate_house_master_pk) {
                $house = DB::table('estate_house_master')
                    ->where('pk', (int) $record->estate_house_master_pk)
                    ->select(
                        'pk',
                        'estate_campus_master_pk',
                        'estate_block_master_pk',
                        'estate_unit_sub_type_master_pk',
                        'estate_unit_master_pk',
                        'house_no',
                        'meter_one',
                        'meter_two'
                    )
                    ->first();
                if ($house) {
                    // Ensure record carries the latest house mapping so that Blade
                    // view + JS initial selections work reliably.
                    $record->estate_campus_master_pk = $house->estate_campus_master_pk;
                    $record->estate_block_master_pk = $house->estate_block_master_pk;
                    $record->estate_unit_sub_type_master_pk = $house->estate_unit_sub_type_master_pk;
                    // Some DBs keep unit type on sub type; we still store fallback for JS.
                    if (property_exists($house, 'estate_unit_master_pk')) {
                        $record->estate_unit_type_master_pk = $house->estate_unit_master_pk;
                    }
                    $record->house_no = $house->house_no ?? $record->house_no;
                }
            }
        }

        $includeRequesterPk = $record?->estate_other_req_pk ? (int) $record->estate_other_req_pk : null;

        $requesters = EstateOtherRequest::query()
            ->whereNotIn('pk', function ($q) {
                $q->select('estate_other_req_pk')
                    ->from('estate_possession_other')
                    ->whereNotNull('estate_other_req_pk');
            })
            ->when($includeRequesterPk, function ($q) use ($includeRequesterPk) {
                $q->orWhere('pk', $includeRequesterPk);
            })
            ->orderBy('emp_name')
            ->get(['pk', 'emp_name', 'request_no_oth', 'section', 'designation']);

        $campuses = DB::table('estate_campus_master')
            ->orderBy('campus_name')
            ->get(['pk', 'campus_name']);

        // Unit types per campus: via estate_house_master and estate_unit_sub_type_master (with schema fallback)
        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
        $unitTypesByCampusQ = DB::table('estate_campus_master as a')
            ->join('estate_house_master as b', 'a.pk', '=', 'b.estate_campus_master_pk')
            ->join('estate_unit_sub_type_master as eust', 'b.estate_unit_sub_type_master_pk', '=', 'eust.pk');

        if ($hasUnitTypeOnSubType) {
            $unitTypesByCampusQ->join('estate_unit_type_master as c', 'eust.estate_unit_type_master_pk', '=', 'c.pk');
        } else {
            $unitTypesByCampusQ->join('estate_unit_type_master as c', 'b.estate_unit_master_pk', '=', 'c.pk');
        }

        $unitTypesByCampus = $unitTypesByCampusQ
            ->select('a.pk as campus_pk', 'c.pk as unit_type_pk', 'c.unit_type')
            ->distinct()
            ->orderBy('a.pk')
            ->orderBy('c.unit_type')
            ->get()
            ->groupBy('campus_pk')
            ->map(fn ($rows) => $rows->map(fn ($r) => ['pk' => $r->unit_type_pk, 'unit_type' => $r->unit_type])->values()->all())
            ->all();

        $preselectedRequester = null;
        if ($request->filled('requester_id')) {
            $preselectedRequester = $request->requester_id;
        }

        return view('admin.estate.estate_possession_view', compact(
            'requesters', 'campuses', 'unitTypesByCampus', 'record', 'preselectedRequester'
        ));
    }

    /**
     * Store Estate Possession (estate_possession_other table).
     */
    public function storePossession(Request $request)
    {
        // Special-case: Return House for LBSNAA employees uses estate_possession_details, not estate_possession_other.
        if ($request->get('redirect_to') === 'return-house' && $request->input('employee_type') === 'LBSNAA') {
            $validated = $request->validate([
                'employee_select_id' => 'required|integer|exists:estate_home_request_details,pk',
                'returning_date' => 'nullable|date',
                'remarks' => 'nullable|string|max:1000',
                'noc_document' => 'nullable|file|max:5120',
            ]);

            $homeReqPk = (int) $validated['employee_select_id'];

            $epdQuery = DB::table('estate_possession_details as epd')
                ->where('epd.estate_home_request_details', $homeReqPk)
                ->whereNotNull('epd.estate_house_master_pk')
                ->where(function ($q) {
                    $q->whereNull('epd.estate_change_id')
                        ->orWhere('epd.estate_change_id', -1);
                });

            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
                $epdQuery->where(function ($q) {
                    $q->whereNull('epd.return_home_status')
                        ->orWhere('epd.return_home_status', 0);
                });
            }

            $epd = $epdQuery->orderByDesc('epd.pk')->first();
            if (! $epd) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Active possession record not found for selected employee.');
            }

            $epdUpdate = [];
            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
                $epdUpdate['return_home_status'] = 1;
            }
            if (!empty($validated['returning_date']) && \Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'current_meter_reading_date')) {
                $epdUpdate['current_meter_reading_date'] = $validated['returning_date'];
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'remarks')) {
                $epdUpdate['remarks'] = $validated['remarks'] ?? null;
            }

            $uploadedDocumentPath = null;
            if ($request->hasFile('noc_document')) {
                $uploadedDocumentPath = $request->file('noc_document')->store('estate/return-house-docs', 'public');
            }

            if (!empty($epdUpdate)) {
                DB::table('estate_possession_details')
                    ->where('pk', (int) $epd->pk)
                    ->update($epdUpdate);
            }

            // Persist remarks / document in meta store for compatibility (same as Other flow).
            $this->persistReturnHouseMeta((int) $epd->pk, $validated['remarks'] ?? null, $uploadedDocumentPath, 'L');

            $housePk = (int) ($epd->estate_house_master_pk ?? 0);
            if ($housePk > 0) {
                $this->refreshHouseUsedStatusFromPossession($housePk);
            }

            // Clear current allotment / mark request as Returned so that:
            // - Employee becomes available again in the Add Estate Request employee dropdown.
            // - Listing still shows the record with status = Returned for audit/history.
            $homeReqUpdate = ['current_alot' => null];
            if (Schema::hasColumn('estate_home_request_details', 'status')) {
                // 3 = Returned (new explicit status for "house returned").
                $homeReqUpdate['status'] = 3;
            }
            if (! empty($homeReqUpdate)) {
                EstateHomeRequestDetails::where('pk', $homeReqPk)->update($homeReqUpdate);
            }

            // Auto-cancel any pending Change Request when house is returned (keeps data consistent).
            $pendingChangeRows = DB::table('estate_change_home_req_details')
                ->where('estate_home_req_details_pk', $homeReqPk)
                ->where('change_ap_dis_status', 0)
                ->get();
            foreach ($pendingChangeRows as $chRow) {
                $existingRemarks = trim((string) ($chRow->remarks ?? ''));
                $suffix = ' [Cancelled – house returned by employee.]';
                $newRemarks = $existingRemarks === '' ? $suffix : $existingRemarks . $suffix;
                DB::table('estate_change_home_req_details')
                    ->where('pk', $chRow->pk)
                    ->update([
                        'change_ap_dis_status' => 2,
                        'remarks' => \Illuminate\Support\Str::limit($newRemarks, 500),
                    ]);
            }

            return redirect()->route('admin.estate.return-house')->with('success', 'House marked as returned successfully.');
        }

        $rules = [
            'estate_other_req_pk' => 'required|exists:estate_other_req,pk',
            'estate_campus_master_pk' => 'required|integer|exists:estate_campus_master,pk',
            'estate_block_master_pk' => 'required|integer|exists:estate_block_master,pk',
            'estate_unit_sub_type_master_pk' => 'required|integer|exists:estate_unit_sub_type_master,pk',
            'estate_house_master_pk' => 'required|integer|exists:estate_house_master,pk',
            'returning_date' => 'nullable|date',
            'house_no' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:1000',
            'noc_document' => 'nullable|file|max:5120',
        ];

        // Possession View (normal add/edit) requires these fields.
        // Return-house flow reuses this endpoint, so keep those fields optional there.
        if ($request->get('redirect_to') === 'return-house') {
            $rules['possession_date_oth'] = 'nullable|date';
            $rules['allotment_date'] = 'nullable|date';
            $rules['meter_reading_oth'] = 'nullable|regex:/^[0-9]{1,10}$/';
            $rules['meter_reading_oth1'] = 'nullable|regex:/^[0-9]{1,10}$/';
        } else {
            $rules['possession_date_oth'] = 'required|date';
            $rules['allotment_date'] = 'required|date';
            // Allow primary OR secondary; enforce "at least one" in after() hook.
            $rules['meter_reading_oth'] = 'nullable|regex:/^[0-9]{1,10}$/';
            $rules['meter_reading_oth1'] = 'nullable|regex:/^[0-9]{1,10}$/';
        }

        $messages = [
            'meter_reading_oth.regex' => 'Primary meter reading must be numbers only (max 10 digits).',
            'meter_reading_oth1.regex' => 'Secondary meter reading must be numbers only (max 10 digits).',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules, $messages);
        $validator->after(function ($v) use ($request) {
            if ($request->get('redirect_to') === 'return-house') {
                $returningDate = trim((string) $request->input('returning_date', ''));
                $allotmentDate = trim((string) $request->input('allotment_date', ''));
                if ($returningDate !== '' && $allotmentDate !== '') {
                    try {
                        $returning = \Carbon\Carbon::parse($returningDate)->startOfDay();
                        $allotment = \Carbon\Carbon::parse($allotmentDate)->startOfDay();
                        if ($returning->lt($allotment)) {
                            $v->errors()->add('returning_date', 'Returning Date cannot be before Date Of Allotment.');
                        }
                    } catch (\Throwable $e) {
                        // Date format errors are handled by base validation rules.
                    }
                }
                return;
            }
            $p = trim((string) $request->input('meter_reading_oth', ''));
            $s = trim((string) $request->input('meter_reading_oth1', ''));
            if ($p === '' && $s === '') {
                $v->errors()->add('meter_reading_oth', 'Electric Meter Reading is required (enter Primary or Secondary).');
            }
        });

        $validated = $validator->validate();

        // DB columns are INT; hard guard against overflow.
        foreach (['meter_reading_oth', 'meter_reading_oth1'] as $k) {
            if (!empty($validated[$k]) && (int) $validated[$k] > 2147483647) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors([$k => 'Electric Meter Reading is too large. Please enter up to 10 digits.'])
                    ->with('error', 'Please correct the errors and try again.');
            }
        }

        if ($request->get('redirect_to') !== 'return-house') {
            $duplicateQuery = EstatePossessionOther::where('estate_other_req_pk', $validated['estate_other_req_pk']);
            if ($request->filled('id')) {
                $duplicateQuery->where('pk', '!=', (int) $request->id);
            }
            if ($duplicateQuery->exists()) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Selected requester already has a possession entry.');
            }
        }

        $house = DB::table('estate_house_master')
            ->where('pk', $validated['estate_house_master_pk'])
            ->first();
        if (!$house) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Selected house not found. Please reselect the house and try again.');
        }

        // Always persist the actual house number from master, not the submitted hidden field.
        $resolvedHouseNo = trim((string) ($house->house_no ?? ''));
        if ($resolvedHouseNo === '') {
            $resolvedHouseNo = trim((string) ($validated['house_no'] ?? ''));
        }

        // When allotting (not return-house): align with Define House - only Vacant and not already used
        if ($request->get('redirect_to') !== 'return-house') {
            $isSameAsExisting = $request->filled('id') && (int) (EstatePossessionOther::find($request->id)?->estate_house_master_pk ?? 0) === (int) $house->pk;
            if (! $isSameAsExisting) {
                if ((int) ($house->vacant_renovation_status ?? 1) !== 1) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'Selected house is Under Renovation in Define House. Only Vacant houses can be allotted.');
                }
                if ((int) ($house->used_home_status ?? 0) !== 0) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'Selected house is already occupied. Please choose a vacant house from the list.');
                }
            }
        }

        // Derive unit type from selected house.
        // Some DBs store unit type on house (estate_unit_master_pk), others on sub type (estate_unit_type_master_pk).
        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
        $derivedUnitTypePk = null;
        if ($hasUnitTypeOnSubType && $house?->estate_unit_sub_type_master_pk) {
            $derivedUnitTypePk = DB::table('estate_unit_sub_type_master')
                ->where('pk', $house->estate_unit_sub_type_master_pk)
                ->value('estate_unit_type_master_pk');
        } else {
            $derivedUnitTypePk = $house?->estate_unit_master_pk;
        }

        $requestNo = $request->input('request_no_oth') ?? $this->generateRequestNo();

        $data = [
            'estate_other_req_pk' => $validated['estate_other_req_pk'],
            'estate_campus_master_pk' => $validated['estate_campus_master_pk'],
            // Always trust house → unit type mapping
            'estate_unit_type_master_pk' => $derivedUnitTypePk,
            'estate_block_master_pk' => $validated['estate_block_master_pk'],
            'estate_unit_sub_type_master_pk' => $validated['estate_unit_sub_type_master_pk'],
            'estate_house_master_pk' => $validated['estate_house_master_pk'],
            'possession_date_oth' => $validated['possession_date_oth'] ?? null,
            'allotment_date' => $validated['allotment_date'] ?? null,
            'meter_reading_oth' => $validated['meter_reading_oth'] ?? null,
            'meter_reading_oth1' => $validated['meter_reading_oth1'] ?? null,
            'house_no' => $resolvedHouseNo !== '' ? $resolvedHouseNo : null,
            'status' => 0,
            'create_date' => now(),
            'created_by' => Auth::id(),
        ];

        $hasRemarksCol = \Illuminate\Support\Facades\Schema::hasColumn('estate_possession_other', 'remarks');
        if ($hasRemarksCol) {
            $data['remarks'] = $validated['remarks'] ?? null;
        }

        $docColumn = null;
        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_other', 'upload_document')) {
            $docColumn = 'upload_document';
        } elseif (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_other', 'noc_document')) {
            $docColumn = 'noc_document';
        }

        $uploadedDocumentPath = null;
        if ($request->hasFile('noc_document')) {
            $uploadedDocumentPath = $request->file('noc_document')->store('estate/return-house-docs', 'public');
            if ($docColumn) {
                $data[$docColumn] = $uploadedDocumentPath;
            }
        }

        $targetPk = null;
        if ($request->get('redirect_to') === 'return-house') {
            $data['return_home_status'] = 1;
            $data['current_meter_reading_date'] = $validated['returning_date'] ?? now()->toDateString();
            unset($data['create_date'], $data['created_by']);
            $previousHousePk = 0;

            $target = null;
            if ($request->filled('id')) {
                $target = EstatePossessionOther::where('pk', $request->id)->first();
            }
            if (! $target) {
                $target = EstatePossessionOther::where('estate_other_req_pk', $validated['estate_other_req_pk'])
                    ->where(function ($q) {
                        $q->whereNull('return_home_status')
                            ->orWhere('return_home_status', 0);
                    })
                    ->orderByDesc('pk')
                    ->first();
            }

            if ($target) {
                $previousHousePk = (int) ($target->estate_house_master_pk ?? 0);
                EstatePossessionOther::where('pk', $target->pk)->update($data);
                $targetPk = (int) $target->pk;
                $message = 'Return house request updated successfully.';
            } else {
                $created = EstatePossessionOther::create($data);
                $targetPk = (int) $created->pk;
                $message = 'Return house request created successfully.';
            }

            // Fallback storage when DB has no columns for remarks/upload_document.
            $this->persistReturnHouseMeta(
                $targetPk,
                $validated['remarks'] ?? null,
                $uploadedDocumentPath,
                'O'
            );

            foreach (array_unique(array_filter([$previousHousePk, (int) $validated['estate_house_master_pk']])) as $housePk) {
                $this->refreshHouseUsedStatusFromPossession((int) $housePk);
            }
        } elseif ($request->filled('id')) {
            $existingRecord = EstatePossessionOther::find($request->id);
            $previousHousePk = (int) ($existingRecord?->estate_house_master_pk ?? 0);
            unset($data['create_date'], $data['created_by']);
            EstatePossessionOther::where('pk', $request->id)->update($data);
            $this->upsertMonthReadingOtherOnPossession(
                (int) $request->id,
                $validated['possession_date_oth'] ?? null,
                $validated['meter_reading_oth'] ?? null,
                $validated['meter_reading_oth1'] ?? null,
                $resolvedHouseNo !== '' ? $resolvedHouseNo : null,
                $house?->meter_one ?? null,
                $house?->meter_two ?? null,
                $house?->water_charge ?? null,
                $house?->licence_fee ?? null
            );
            $this->setHouseUsedStatus((int) $validated['estate_house_master_pk'], 1);
            if ($previousHousePk > 0 && $previousHousePk !== (int) $validated['estate_house_master_pk']) {
                $this->refreshHouseUsedStatusFromPossession($previousHousePk);
            }
            $message = 'Possession updated successfully.';
        } else {
            $createdPossession = EstatePossessionOther::create($data);
            $this->upsertMonthReadingOtherOnPossession(
                (int) $createdPossession->pk,
                $validated['possession_date_oth'] ?? null,
                $validated['meter_reading_oth'] ?? null,
                $validated['meter_reading_oth1'] ?? null,
                $resolvedHouseNo !== '' ? $resolvedHouseNo : null,
                $house?->meter_one ?? null,
                $house?->meter_two ?? null,
                $house?->water_charge ?? null,
                $house?->licence_fee ?? null
            );
            $this->setHouseUsedStatus((int) $validated['estate_house_master_pk'], 1);
            $message = 'Possession added successfully.';
        }

        if ($request->get('redirect_to') === 'return-house') {
            return redirect()->route('admin.estate.return-house')->with('success', $message);
        }
        return redirect()
            ->route('admin.estate.possession-for-others')
            ->with('success', $message);
    }

    private function persistReturnHouseMeta(?int $possessionPk, ?string $remarks, ?string $uploadedDocumentPath, string $scope = 'O'): void
    {
        if (!$possessionPk) {
            return;
        }

        $file = 'estate/return-house-meta.json';
        $raw = Storage::disk('local')->exists($file)
            ? Storage::disk('local')->get($file)
            : '{}';

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }

        $scope = strtoupper(trim($scope)) ?: 'O';
        $key = $scope . ':' . (string) $possessionPk;
        $meta = $decoded[$key] ?? [];

        $meta['remarks'] = $remarks !== null ? trim($remarks) : ($meta['remarks'] ?? null);
        if ($uploadedDocumentPath) {
            $meta['upload_document'] = $uploadedDocumentPath;
        } elseif (!array_key_exists('upload_document', $meta)) {
            $meta['upload_document'] = null;
        }

        $decoded[$key] = $meta;
        Storage::disk('local')->put($file, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function setHouseUsedStatus(int $housePk, int $status): void
    {
        if ($housePk <= 0) {
            return;
        }

        DB::table('estate_house_master')
            ->where('pk', $housePk)
            ->update(['used_home_status' => $status]);
    }

    private function refreshHouseUsedStatusFromPossession(int $housePk): void
    {
        if ($housePk <= 0) {
            return;
        }
        // Determine "used" based on the latest possession rows only (LBSNAA + Other),
        // so that legacy/older rows with return_home_status = 0 do not keep a house
        // marked as occupied after it has been properly returned.
        $detailsActive = false;
        if (\Illuminate\Support\Facades\Schema::hasTable('estate_possession_details')) {
            $latestDetails = DB::table('estate_possession_details')
                ->where('estate_house_master_pk', $housePk)
                ->whereNotNull('estate_house_master_pk')
                ->orderByDesc('pk')
                ->first();
            if ($latestDetails) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
                    $r = $latestDetails->return_home_status;
                    $detailsActive = ($r === null || (int) $r === 0);
                } else {
                    // If there is no return_home_status column, treat presence as active.
                    $detailsActive = true;
                }
            }
        }

        $otherActive = false;
        if (\Illuminate\Support\Facades\Schema::hasTable('estate_possession_other')) {
            $latestOther = DB::table('estate_possession_other')
                ->where('estate_house_master_pk', $housePk)
                ->whereNotNull('estate_house_master_pk')
                ->orderByDesc('pk')
                ->first();
            if ($latestOther) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_other', 'return_home_status')) {
                    $r = $latestOther->return_home_status;
                    $otherActive = ($r === null || (int) $r === 0);
                } else {
                    $otherActive = true;
                }
            }
        }

        $isUsed = $detailsActive || $otherActive;

        $this->setHouseUsedStatus($housePk, $isUsed ? 1 : 0);

        if (! $isUsed) {
            DB::table('estate_house_master')
                ->where('pk', $housePk)
                ->where('vacant_renovation_status', 2)
                ->update(['vacant_renovation_status' => 1]);
        }
    }

    /**
     * Initial month-reading row for "Other" possession.
     *
     * Mapping (same idea as LBSNAA possession):
     * - $meterReadingPrimary   (meter_reading_oth)  => last_month_elec_red / curr_month_elec_red
     * - $meterReadingSecondary (meter_reading_oth1) => last_month_elec_red2 / curr_month_elec_red2
     * - Meter numbers always from house master (meter_one / meter_two).
     */
    private function upsertMonthReadingOtherOnPossession(
        int $possessionPk,
        $possessionDate,
        $meterReadingPrimary,
        $meterReadingSecondary,
        ?string $houseNo,
        $meterOne,
        $meterTwo,
        $waterCharge = null,
        $licenceFee = null
    ): void {
        if ($possessionPk <= 0) {
            return;
        }

        $baseDate = $possessionDate ? \Carbon\Carbon::parse($possessionDate) : now();
        $billMonth = $baseDate->format('F');
        $billYear = $baseDate->format('Y');
        $fromDate = $baseDate->copy()->startOfMonth()->toDateString();
        $toDate = $baseDate->copy()->endOfMonth()->toDateString();

        // Always sync possession meter readings into the *latest* month-reading row only (highest pk for this possession).
        $reading = EstateMonthReadingDetailsOther::where('estate_possession_other_pk', $possessionPk)
            ->orderByDesc('pk')
            ->first();

        if ($reading) {
            // Existing row: keep any already captured readings as-is.
            // Only sync meter numbers / charges and backfill last-month readings when empty.
            $update = [
                'house_no' => $houseNo,
                'meter_one' => $meterOne,
                'meter_two' => $meterTwo,
            ];
            if ($reading->water_charges === null && $waterCharge !== null && $waterCharge !== '') {
                $update['water_charges'] = (float) $waterCharge;
            }
            if ($reading->licence_fees === null && $licenceFee !== null && $licenceFee !== '') {
                $update['licence_fees'] = (float) $licenceFee;
            }

            if ($reading->last_month_elec_red === null && $meterReadingPrimary !== null && $meterReadingPrimary !== '') {
                $update['last_month_elec_red'] = (int) $meterReadingPrimary;
            }
            if ($reading->last_month_elec_red2 === null && $meterReadingSecondary !== null && $meterReadingSecondary !== '') {
                $update['last_month_elec_red2'] = (int) $meterReadingSecondary;
            }

            // Possession edit updates epo.meter_reading_oth*; keep month-reading current columns in sync (same as LBSNAA).
            $update['curr_month_elec_red'] = ($meterReadingPrimary !== null && $meterReadingPrimary !== '')
                ? (int) $meterReadingPrimary
                : 0;
            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details_other', 'curr_month_elec_red2')) {
                $update['curr_month_elec_red2'] = ($meterReadingSecondary !== null && $meterReadingSecondary !== '')
                    ? (int) $meterReadingSecondary
                    : 0;
            }
            $this->applyElectricChargeToMonthReadingOther((int) $reading->pk, $update);
            DB::table('estate_month_reading_details_other')->where('pk', (int) $reading->pk)->update($update);

            return;
        }

        // Staging schema: last_month_elec_red is NOT NULL, so 0 is safe default.
        $primaryVal = ($meterReadingPrimary !== null && $meterReadingPrimary !== '') ? (int) $meterReadingPrimary : 0;
        $secondaryVal = ($meterReadingSecondary !== null && $meterReadingSecondary !== '') ? (int) $meterReadingSecondary : 0;

        EstateMonthReadingDetailsOther::create([
            'estate_possession_other_pk' => $possessionPk,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'last_month_elec_red' => $primaryVal,
            'curr_month_elec_red' => $primaryVal,
            'last_month_elec_red2' => $secondaryVal,
            'curr_month_elec_red2' => $secondaryVal,
            'bill_month' => $billMonth,
            'bill_year' => $billYear,
            'notify_employee_status' => 0,
            'process_status' => 0,
            'house_no' => $houseNo,
            'meter_one' => $meterOne,
            'meter_two' => $meterTwo,
            'water_charges' => ($waterCharge !== null && $waterCharge !== '') ? (float) $waterCharge : null,
            'licence_fees' => ($licenceFee !== null && $licenceFee !== '') ? (float) $licenceFee : null,
            'created_date' => now(),
        ]);
    }

    private function upsertMonthReadingOnPossession(
        int $possessionPk,
        $possessionDate,
        $meterReadingPrimary,
        $meterReadingSecondary,
        ?string $houseNo,
        $meterOne,
        $meterTwo
    ): void {
        if ($possessionPk <= 0) {
            return;
        }

        $baseDate = $possessionDate ? \Carbon\Carbon::parse($possessionDate) : now();
        $billMonth = $baseDate->format('F');
        $billYear = $baseDate->format('Y');
        $fromDate = $baseDate->copy()->startOfMonth()->toDateString();
        $toDate = $baseDate->copy()->endOfMonth()->toDateString();

        // Only sync into the *latest* month-reading row (highest pk) for this possession — same rule as Other.
        $reading = EstateMonthReadingDetails::where('estate_possession_details_pk', $possessionPk)
            ->orderByDesc('pk')
            ->first();

        if ($reading) {
            $update = [
                'house_no' => $houseNo,
                'meter_one' => $meterOne,
                'meter_two' => $meterTwo,
            ];
            if ($reading->last_month_elec_red === null && $meterReadingPrimary !== null && $meterReadingPrimary !== '') {
                $update['last_month_elec_red'] = (int) $meterReadingPrimary;
            }
            if ($reading->last_month_elec_red2 === null && $meterReadingSecondary !== null && $meterReadingSecondary !== '') {
                $update['last_month_elec_red2'] = (int) $meterReadingSecondary;
            }
            // Possession edit updates epd.electric_meter_reading*; keep month-reading "current" columns in sync for Update Meter Reading / bills.
            $update['curr_month_elec_red'] = ($meterReadingPrimary !== null && $meterReadingPrimary !== '')
                ? (int) $meterReadingPrimary
                : 0;
            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details', 'curr_month_elec_red2')) {
                $update['curr_month_elec_red2'] = ($meterReadingSecondary !== null && $meterReadingSecondary !== '')
                    ? (int) $meterReadingSecondary
                    : 0;
            }
            $this->applyElectricChargeToMonthReading($reading->pk, $update);
            DB::table('estate_month_reading_details')->where('pk', $reading->pk)->update($update);
            return;
        }

        $insertData = [
            'estate_possession_details_pk' => $possessionPk,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'last_month_elec_red' => ($meterReadingPrimary !== null && $meterReadingPrimary !== '') ? (int) $meterReadingPrimary : null,
            'curr_month_elec_red' => ($meterReadingPrimary !== null && $meterReadingPrimary !== '') ? (int) $meterReadingPrimary : 0,
            'last_month_elec_red2' => ($meterReadingSecondary !== null && $meterReadingSecondary !== '') ? (int) $meterReadingSecondary : null,
            'curr_month_elec_red2' => ($meterReadingSecondary !== null && $meterReadingSecondary !== '') ? (int) $meterReadingSecondary : 0,
            'bill_month' => $billMonth,
            'bill_year' => $billYear,
            'notify_employee_status' => 0,
            'process_status' => 0,
            'house_no' => $houseNo,
            'meter_one' => $meterOne,
            'meter_two' => $meterTwo,
            'created_date' => now(),
            'meter_one_consume_unit' => 0,
            'meter_two_consume_unit' => null,
            'meter_one_elec_charge' => 0,
            'meter_two_elec_charge' => 0,
            'electricty_charges' => 0,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details', 'per_unit')) {
            $insertData['per_unit'] = 0;
        }
        DB::table('estate_month_reading_details')->insert($insertData);
    }

    /**
     * After Update Meter Reading saves estate_month_reading_details, mirror latest current readings onto
     * estate_possession_details so possession-details listing stays aligned (meter I → electric_meter_reading, II → _2).
     */
    private function syncEstatePossessionElectricReadingsFromEmrdUpdate(int $estatePossessionDetailsPk, array $update): void
    {
        if ($estatePossessionDetailsPk <= 0 || $update === []) {
            return;
        }

        $epdPatch = [];
        if (array_key_exists('curr_month_elec_red', $update) && Schema::hasColumn('estate_possession_details', 'electric_meter_reading')) {
            $epdPatch['electric_meter_reading'] = (int) $update['curr_month_elec_red'];
        }
        if (array_key_exists('curr_month_elec_red2', $update) && Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2')) {
            $epdPatch['electric_meter_reading_2'] = (int) $update['curr_month_elec_red2'];
        }

        if ($epdPatch !== []) {
            DB::table('estate_possession_details')
                ->where('pk', $estatePossessionDetailsPk)
                ->update($epdPatch);
        }
    }

    /**
     * Compute and set meter_one_consume_unit, meter_two_consume_unit, meter_one_elec_charge, meter_two_elec_charge, electricty_charges,
     * and per_unit (total consumed units when column exists) for an existing estate_month_reading_details row (by pk).
     */
    private function applyElectricChargeToMonthReading(int $emrdPk, array &$update): void
    {
        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
        $row = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->where('emrd.pk', $emrdPk)
            ->select(
                'emrd.last_month_elec_red',
                'emrd.curr_month_elec_red',
                'emrd.last_month_elec_red2',
                'emrd.curr_month_elec_red2',
                DB::raw(($hasUnitTypeOnSubType ? 'eust.estate_unit_type_master_pk' : 'ehm.estate_unit_master_pk') . ' as unit_type_pk')
            )
            ->first();
        if (! $row) {
            return;
        }
        foreach (['last_month_elec_red', 'curr_month_elec_red', 'last_month_elec_red2', 'curr_month_elec_red2'] as $meterCol) {
            if (array_key_exists($meterCol, $update)) {
                $row->{$meterCol} = $update[$meterCol];
            }
        }
        $prev1 = (int) ($row->last_month_elec_red ?? 0);
        $prev2 = (int) ($row->last_month_elec_red2 ?? 0);
        $curr1 = (int) ($row->curr_month_elec_red ?? 0);
        $curr2 = (int) ($row->curr_month_elec_red2 ?? 0);
        $u1 = $curr1 >= $prev1 ? $curr1 - $prev1 : 0;
        $u2 = $curr2 >= $prev2 ? $curr2 - $prev2 : 0;
        $unitTypePk = isset($row->unit_type_pk) ? (int) $row->unit_type_pk : null;
        $m1 = $u1 > 0 ? $this->calculateElectricChargeForUnits($unitTypePk, $u1) : 0.0;
        $m2 = $u2 > 0 ? $this->calculateElectricChargeForUnits($unitTypePk, $u2) : 0.0;
        $update['meter_one_consume_unit'] = $u1;
        $update['meter_two_consume_unit'] = $u2;
        $update['meter_one_elec_charge'] = $m1;
        $update['meter_two_elec_charge'] = $m2;
        $update['electricty_charges'] = $m1 + $m2;
        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details', 'per_unit')) {
            $update['per_unit'] = $u1 + $u2;
        }
    }

    /**
     * Same as applyElectricChargeToMonthReading for estate_month_reading_details_other + estate_possession_other.
     */
    private function applyElectricChargeToMonthReadingOther(int $emroPk, array &$update): void
    {
        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
        $houseDerivedExpr = $hasUnitTypeOnSubType
            ? 'eust.estate_unit_type_master_pk'
            : 'ehm.estate_unit_master_pk';
        $q = DB::table('estate_month_reading_details_other as emro')
            ->join('estate_possession_other as epo', 'emro.estate_possession_other_pk', '=', 'epo.pk')
            ->leftJoin('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
            ->where('emro.pk', $emroPk);
        if ($hasUnitTypeOnSubType) {
            $q->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk');
        }
        $row = $q->select(
            'emro.last_month_elec_red',
            'emro.curr_month_elec_red',
            'emro.last_month_elec_red2',
            'emro.curr_month_elec_red2',
            DB::raw('COALESCE(epo.estate_unit_type_master_pk, ' . $houseDerivedExpr . ') as unit_type_pk')
        )->first();
        if (! $row) {
            return;
        }
        foreach (['last_month_elec_red', 'curr_month_elec_red', 'last_month_elec_red2', 'curr_month_elec_red2'] as $meterCol) {
            if (array_key_exists($meterCol, $update)) {
                $row->{$meterCol} = $update[$meterCol];
            }
        }
        $prev1 = (int) ($row->last_month_elec_red ?? 0);
        $prev2 = (int) ($row->last_month_elec_red2 ?? 0);
        $curr1 = (int) ($row->curr_month_elec_red ?? 0);
        $curr2 = (int) ($row->curr_month_elec_red2 ?? 0);
        $u1 = $curr1 >= $prev1 ? $curr1 - $prev1 : 0;
        $u2 = $curr2 >= $prev2 ? $curr2 - $prev2 : 0;
        $unitTypePk = isset($row->unit_type_pk) ? (int) $row->unit_type_pk : null;
        $unitTypePk = $unitTypePk > 0 ? $unitTypePk : null;
        $m1 = $u1 > 0 ? $this->calculateElectricChargeForUnits($unitTypePk, $u1) : 0.0;
        $m2 = $u2 > 0 ? $this->calculateElectricChargeForUnits($unitTypePk, $u2) : 0.0;
        $update['meter_one_elec_charge'] = $m1;
        $update['meter_two_elec_charge'] = $m2;
        $update['electricty_charges'] = $m1 + $m2;
        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details_other', 'meter_one_consume_unit')) {
            $update['meter_one_consume_unit'] = $u1;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details_other', 'meter_two_consume_unit')) {
            $update['meter_two_consume_unit'] = $u2;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details_other', 'per_unit')) {
            $update['per_unit'] = $u1 + $u2;
        }
    }

    /**
     * API: Get blocks for estate possession (by campus + optional unit type).
     */
    public function getPossessionBlocks(Request $request)
    {
        $campusId = $request->get('campus_id');
        $unitTypeId = $request->get('unit_type_id');
        if (!$campusId) {
            return response()->json(['status' => true, 'data' => []]);
        }

        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');

        $q = DB::table('estate_house_master as h')
            ->join('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->where('h.estate_campus_master_pk', $campusId);

        if ($unitTypeId) {
            // Some DBs don't have unit_type FK on sub_type; fall back to house column.
            if ($hasUnitTypeOnSubType) {
                $q->join('estate_unit_sub_type_master as eust', 'h.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                    ->where('eust.estate_unit_type_master_pk', $unitTypeId);
            } else {
                $q->where('h.estate_unit_master_pk', $unitTypeId);
            }
        }

        $blocks = $q->select('b.pk', 'b.block_name')
            ->distinct()
            ->orderBy('b.block_name')
            ->get();

        return response()->json(['status' => true, 'data' => $blocks]);
    }

    /**
     * Define House - index page with Add Estate House modal.
     * Tables: estate_house_master, estate_campus_master, estate_block_master,
     * estate_unit_type_master, estate_unit_sub_type_master.
     */
    public function defineHouse()
    {
        $campuses = DB::table('estate_campus_master')
            ->orderBy('campus_name')
            ->get(['pk', 'campus_name']);

        $unitTypes = DB::table('estate_unit_type_master')
            ->orderBy('unit_type')
            ->get(['pk', 'unit_type']);

        $unitSubTypes = DB::table('estate_unit_sub_type_master')
            ->orderBy('unit_sub_type')
            ->get(['pk', 'unit_sub_type']);

        return view('admin.estate.define_house', compact(
            'campuses', 'unitTypes', 'unitSubTypes'
        ));
    }

    /**
     * API: Get blocks for Define House form.
     *
     * For Define House, the user is defining houses and should be able to see
     * **all** blocks in the dropdown, not just those that already have houses
     * mapped for a particular campus. Therefore this endpoint always returns
     * the full list of blocks without restricting by campus or existing
     * mappings.
     */
    public function getDefineHouseBlocks(Request $request)
    {
        $blocks = DB::table('estate_block_master')
            ->orderBy('block_name')
            ->get(['pk', 'block_name']);

        return response()->json(['status' => true, 'data' => $blocks]);
    }

    /**
     * Store new estate house(s) (estate_house_master).
     * Accepts multiple house rows: house_no[], meter_one[], meter_two[], licence_fee[], vacant_renovation_status[].
     * Common fields: estate_campus_master_pk, estate_block_master_pk,
     * estate_unit_sub_type_master_pk, water_charge, electric_charge, remarks.
     */
    public function storeDefineHouse(Request $request)
    {
        $validated = $request->validate([
            'estate_campus_master_pk' => 'required|integer|exists:estate_campus_master,pk',
            'estate_unit_type_master_pk' => 'required|integer|exists:estate_unit_type_master,pk',
            'estate_block_master_pk' => 'required|integer|exists:estate_block_master,pk',
            'estate_unit_sub_type_master_pk' => 'required|integer|exists:estate_unit_sub_type_master,pk',
            'water_charge' => 'nullable|numeric|min:0',
            'electric_charge' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:200',
            'house_no' => 'required|array',
            'house_no.*' => 'required|string|max:20',
            // Meter No. 1 must be filled and numeric for every row.
            'meter_one' => 'required|array',
            'meter_one.*' => 'required|string|max:30|regex:/^[0-9]+$/',
            'meter_two' => 'nullable|array',
            'meter_two.*' => 'nullable|string|max:30',
            'licence_fee' => 'nullable|array',
            'licence_fee.*' => 'nullable|numeric|min:0',
            'vacant_renovation_status' => 'required|array',
            'vacant_renovation_status.*' => 'required|in:0,1,2',
        ]);

        $userId = Auth::id();
        $now = now();
        $houseNos = $validated['house_no'] ?? [];
        $count = count($houseNos);
        if ($count === 0) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'At least one house entry is required.'], 422);
            }
            return redirect()->back()->withInput()->withErrors(['house_no' => 'At least one house entry is required.']);
        }

        // Normalise house numbers for uniqueness:
        // - trim spaces
        // - collapse multiple hyphens ("HS--01" -> "HS-01")
        // - upper-case for case-insensitive compare
        $normalizeHouse = function ($v) {
            $v = trim((string) $v);
            if ($v === '') {
                return '';
            }
            $v = preg_replace('/-+/', '-', $v);
            return strtoupper($v);
        };

        // Prevent duplicate house definition in the same request (after normalisation).
        $houseNosNormalized = collect($houseNos)
            ->map($normalizeHouse)
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();

        $dupeInRequest = collect($houseNosNormalized)->duplicates()->values()->all();
        if (! empty($dupeInRequest)) {
            $msg = 'Duplicate House No. in the form: ' . implode(', ', array_unique($dupeInRequest));
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->withInput()->withErrors(['house_no' => $msg]);
        }

        // Prevent duplicate house definition against DB (same campus + block + normalised house_no).
        $existingHouseNos = DB::table('estate_house_master')
            ->where('estate_campus_master_pk', (int) $validated['estate_campus_master_pk'])
            ->where('estate_block_master_pk', (int) $validated['estate_block_master_pk'])
            ->pluck('house_no')
            ->map(fn ($v) => (string) $v)
            ->all();

        $existingNormalized = collect($existingHouseNos)
            ->mapWithKeys(function ($v) use ($normalizeHouse) {
                $n = $normalizeHouse($v);
                return $n !== '' ? [$n => $v] : [];
            })
            ->all();

        $conflictingHouseNos = [];
        foreach ($houseNos as $raw) {
            $n = $normalizeHouse($raw);
            if ($n !== '' && array_key_exists($n, $existingNormalized)) {
                $conflictingHouseNos[] = $raw;
            }
        }

        if (! empty($conflictingHouseNos)) {
            $msg = 'House already defined: ' . implode(', ', array_unique($conflictingHouseNos));
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->withInput()->withErrors(['house_no' => $msg]);
        }

        $meterOnes = array_pad($validated['meter_one'] ?? [], $count, '');
        $meterTwos = array_pad($validated['meter_two'] ?? [], $count, '');
        $licenceFees = array_pad($validated['licence_fee'] ?? [], $count, 0);
        $statuses = array_pad($validated['vacant_renovation_status'] ?? [], $count, 1);

        // Enforce Meter No. 1 uniqueness (after numeric normalisation).
        $normalizedMeterOnes = collect($meterOnes)
            ->map(fn ($v) => (int) preg_replace('/\D/', '', $v ?? ''))
            ->filter(fn ($v) => $v > 0)
            ->values()
            ->all();

        $dupeMetersInRequest = collect($normalizedMeterOnes)->duplicates()->values()->all();
        if (! empty($dupeMetersInRequest)) {
            $msg = 'Duplicate Meter No. 1 in the form: ' . implode(', ', array_unique($dupeMetersInRequest));
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->withInput()->withErrors(['meter_one' => $msg]);
        }

        if (! empty($normalizedMeterOnes)) {
            $existingMeters = DB::table('estate_house_master')
                ->whereIn('meter_one', $normalizedMeterOnes)
                ->where('meter_one', '>', 0)
                ->pluck('meter_one')
                ->map(fn ($v) => (int) $v)
                ->all();
            if (! empty($existingMeters)) {
                $msg = 'Meter No. 1 already used: ' . implode(', ', array_unique($existingMeters));
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->back()->withInput()->withErrors(['meter_one' => $msg]);
            }
        }

        $waterCharge = (float) ($validated['water_charge'] ?? 0);
        $electricCharge = (float) ($validated['electric_charge'] ?? 0);
        $remarks = $validated['remarks'] ?? '';
        $hasUnitTypeOnHouse = \Illuminate\Support\Facades\Schema::hasColumn('estate_house_master', 'estate_unit_master_pk');

        for ($i = 0; $i < $count; $i++) {
            $data = [
                'estate_campus_master_pk' => $validated['estate_campus_master_pk'],
                // DB schema requires this in many environments.
                'estate_unit_master_pk' => $hasUnitTypeOnHouse ? $validated['estate_unit_type_master_pk'] : null,
                'estate_block_master_pk' => $validated['estate_block_master_pk'],
                'estate_unit_sub_type_master_pk' => $validated['estate_unit_sub_type_master_pk'],
                'house_no' => $houseNos[$i],
                'water_charge' => $waterCharge,
                'electric_charge' => $electricCharge,
                'licence_fee' => (float) ($licenceFees[$i] ?? 0),
                'meter_one' => (int) preg_replace('/\D/', '', $meterOnes[$i] ?? '') ?: 0,
                'meter_two' => (int) preg_replace('/\D/', '', $meterTwos[$i] ?? '') ?: 0,
                'vacant_renovation_status' => (int) ($statuses[$i] ?? 1),
                'remarks' => $remarks,
                'used_home_status' => 0,
                'created_date' => $now,
                'created_by' => $userId,
            ];
            if (! $hasUnitTypeOnHouse) {
                unset($data['estate_unit_master_pk']);
            }
            EstateHouse::create($data);
        }

        $message = $count === 1 ? 'Estate house added successfully.' : $count . ' estate houses added successfully.';
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return redirect()->route('admin.estate.define-house')->with('success', $message);
    }

    /**
     * Define House list data for DataTable (server-side).
     */
    public function getDefineHouseData(Request $request)
    {
        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');

        $query = DB::table('estate_house_master as h')
            ->leftJoin('estate_campus_master as c', 'h.estate_campus_master_pk', '=', 'c.pk')
            ->leftJoin('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->leftJoin('estate_unit_sub_type_master as ust', 'h.estate_unit_sub_type_master_pk', '=', 'ust.pk')
            ->when($hasUnitTypeOnSubType, function ($q) {
                $q->leftJoin('estate_unit_type_master as ut', 'ust.estate_unit_type_master_pk', '=', 'ut.pk');
            }, function ($q) {
                $q->leftJoin('estate_unit_type_master as ut', 'h.estate_unit_master_pk', '=', 'ut.pk');
            })
            ->select(
                'h.pk',
                'c.campus_name as estate_name',
                'ut.unit_type',
                'b.block_name as building_name',
                'ust.unit_sub_type',
                'h.house_no',
                'h.meter_one',
                'h.water_charge',
                'h.electric_charge',
                'h.licence_fee',
                'h.used_home_status',
                'h.vacant_renovation_status',
                'h.remarks'
            )
            ->orderBy('h.pk', 'desc');

        $total = $query->count();

        if ($request->filled('search.value')) {
            // DataTables search: make it robust for multi-word terms and odd spacing
            // (e.g. double spaces / non-breaking spaces in DB or user input).
            $rawTerm = (string) data_get($request->get('search'), 'value', '');
            $rawTerm = str_replace("\xC2\xA0", ' ', $rawTerm); // NBSP -> normal space
            $rawTerm = trim(preg_replace('/\s+/u', ' ', $rawTerm));

            if ($rawTerm !== '') {
                $tokens = preg_split('/\s+/u', $rawTerm, -1, PREG_SPLIT_NO_EMPTY) ?: [];

                // AND across tokens; OR across searchable columns.
                foreach ($tokens as $token) {
                    $query->where(function ($q) use ($token) {
                        $like = '%' . $token . '%';
                        $q->where('c.campus_name', 'like', $like)
                            ->orWhere('b.block_name', 'like', $like)
                            ->orWhere('ut.unit_type', 'like', $like)
                            ->orWhere('ust.unit_sub_type', 'like', $like)
                            ->orWhere('h.house_no', 'like', $like)
                            ->orWhere('h.meter_one', 'like', $like);
                    });
                }
            }
        }

        $filtered = $query->count();

        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);
        if ($length > 0) {
            $query->offset($start)->limit($length);
        }

        // Use the status stored on Define House itself so that edits
        // (Vacant / Occupied / Under Renovation) are reflected directly
        // in the listing without being overridden by possession details.
        $rows = $query->get()->map(function ($row) {
            $row = (object) (array) $row;
            $row->vacant_renovation_status = (int) ($row->vacant_renovation_status ?? 1);
            return $row;
        });

        return response()->json([
            'draw' => (int) $request->get('draw', 1),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows,
        ]);
    }

    /**
     * Get single estate house for edit (JSON).
     */
    public function showDefineHouse($id)
    {
        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');

        $row = DB::table('estate_house_master as h')
            ->leftJoin('estate_campus_master as c', 'h.estate_campus_master_pk', '=', 'c.pk')
            ->leftJoin('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->leftJoin('estate_unit_sub_type_master as ust', 'h.estate_unit_sub_type_master_pk', '=', 'ust.pk')
            ->when($hasUnitTypeOnSubType, function ($q) {
                $q->leftJoin('estate_unit_type_master as ut', 'ust.estate_unit_type_master_pk', '=', 'ut.pk');
            }, function ($q) {
                $q->leftJoin('estate_unit_type_master as ut', 'h.estate_unit_master_pk', '=', 'ut.pk');
            })
            ->where('h.pk', $id)
            ->select(
                'h.pk',
                'h.estate_campus_master_pk',
                'ut.pk as estate_unit_master_pk',
                'h.estate_block_master_pk',
                'h.estate_unit_sub_type_master_pk',
                'c.campus_name as estate_name',
                'b.block_name as building_name',
                'h.house_no',
                'h.water_charge',
                'h.electric_charge',
                'h.licence_fee',
                'h.meter_one',
                'h.meter_two',
                'h.vacant_renovation_status',
                'h.used_home_status',
                'h.remarks'
            )
            ->first();

        if (!$row) {
            return response()->json(['message' => 'House not found.'], 404);
        }

        $row->vacant_renovation_status = (int) ($row->vacant_renovation_status ?? 1);
        $row->used_home_status = (int) ($row->used_home_status ?? 0);
        return response()->json($row);
    }

    /**
     * Update estate house (single record).
     */
    public function updateDefineHouse(Request $request, $id)
    {
        $house = EstateHouse::find($id);
        if (!$house) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'House not found.'], 404);
            }
            return redirect()->route('admin.estate.define-house')->with('error', 'House not found.');
        }

        $validated = $request->validate([
            'estate_campus_master_pk' => 'required|integer|exists:estate_campus_master,pk',
            'estate_unit_type_master_pk' => 'required|integer|exists:estate_unit_type_master,pk',
            'estate_block_master_pk' => 'required|integer|exists:estate_block_master,pk',
            'estate_unit_sub_type_master_pk' => 'required|integer|exists:estate_unit_sub_type_master,pk',
            'water_charge' => 'nullable|numeric|min:0',
            'electric_charge' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:200',
            'house_no' => 'required|array',
            'house_no.0' => 'required|string|max:20',
            // Meter No. 1 must be filled and numeric on edit as well.
            'meter_one' => 'required|array',
            'meter_one.0' => 'required|string|max:30|regex:/^[0-9]+$/',
            'meter_two' => 'nullable|array',
            'meter_two.0' => 'nullable|string|max:30',
            'licence_fee' => 'nullable|array',
            'licence_fee.0' => 'nullable|numeric|min:0',
            'vacant_renovation_status' => 'required|array',
            'vacant_renovation_status.0' => 'required|in:0,1,2',
        ]);

        // Normalise house no for duplicate detection (same rules as storeDefineHouse)
        $rawHouseNo = (string) ($validated['house_no'][0] ?? '');
        $normalizeHouse = function ($v) {
            $v = trim((string) $v);
            if ($v === '') {
                return '';
            }
            $v = preg_replace('/-+/', '-', $v);
            return strtoupper($v);
        };
        $houseNo = trim($rawHouseNo);
        // Prevent duplicate on update (same campus + block + normalised house_no, excluding current pk).
        $existsOther = DB::table('estate_house_master')
            ->where('estate_campus_master_pk', (int) $validated['estate_campus_master_pk'])
            ->where('estate_block_master_pk', (int) $validated['estate_block_master_pk'])
            ->whereRaw('UPPER(REPLACE(REGEXP_REPLACE(house_no, "-+", "-"), " ", "")) = ?', [strtoupper(preg_replace('/-+/', '-', $houseNo))])
            ->where('pk', '!=', (int) $house->pk)
            ->exists();
        if ($existsOther) {
            $msg = 'House already defined: ' . $houseNo;
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->withInput()->withErrors(['house_no.0' => $msg]);
        }

        // Enforce Meter No. 1 uniqueness on update only when meter is being changed (edit: same meter as current = allow).
        $newMeterOneRaw = ($validated['meter_one'] ?? [])[0] ?? '';
        $newMeterOne = (int) preg_replace('/\D/', '', $newMeterOneRaw);
        if ($newMeterOne <= 0) {
            $msg = 'Meter No. 1 is required and must be a positive number.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->withInput()->withErrors(['meter_one.0' => $msg]);
        }
        $currentMeterOne = (int) ($house->meter_one ?? 0);
        if ($newMeterOne !== $currentMeterOne) {
            $existsMeter = DB::table('estate_house_master')
                ->where('meter_one', $newMeterOne)
                ->where('pk', '!=', (int) $house->getKey())
                ->exists();
            if ($existsMeter) {
                $msg = 'Meter No. 1 already used: ' . $newMeterOne;
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->back()->withInput()->withErrors(['meter_one.0' => $msg]);
            }
        }

        $hasUnitTypeOnHouse = \Illuminate\Support\Facades\Schema::hasColumn('estate_house_master', 'estate_unit_master_pk');
        $house->estate_campus_master_pk = $validated['estate_campus_master_pk'];
        if ($hasUnitTypeOnHouse) {
            $house->estate_unit_master_pk = $validated['estate_unit_type_master_pk'];
        }
        $house->estate_block_master_pk = $validated['estate_block_master_pk'];
        $house->estate_unit_sub_type_master_pk = $validated['estate_unit_sub_type_master_pk'];
        $house->house_no = $houseNo;
        $house->water_charge = (float) ($validated['water_charge'] ?? 0);
        $house->electric_charge = (float) ($validated['electric_charge'] ?? 0);
        $house->licence_fee = (float) (($validated['licence_fee'] ?? [])[0] ?? 0);
        $house->meter_one = $newMeterOne;
        $house->meter_two = (int) preg_replace('/\D/', '', ($validated['meter_two'] ?? [])[0] ?? '') ?: 0;
        $house->vacant_renovation_status = (int) (($validated['vacant_renovation_status'] ?? [])[0] ?? 1);
        $house->remarks = $validated['remarks'] ?? '';
        $house->modify_date = now();
        $house->modify_by = Auth::id();
        $house->save();

        $message = 'Estate house updated successfully.';
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return redirect()->route('admin.estate.define-house')->with('success', $message);
    }

    /**
     * Delete estate house (Define House).
     * Only check: house allotted (occupied) or not. Occupied → cannot delete. Vacant → delete allowed.
     * Possession/billing history is ignored; only current allotment matters.
     */
    public function destroyDefineHouse(Request $request, $id)
    {
        $house = EstateHouse::find($id);
        if (! $house) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'House not found.'], 404);
            }
            return redirect()->route('admin.estate.define-house')->with('error', 'House not found.');
        }

        $housePk = (int) $house->pk;

        // Occupied = allotted: used_home_status = 1 or has active possession (return_home_status = 0)
        $isOccupied = (int) ($house->used_home_status ?? 0) === 1;
        if (! $isOccupied && \Illuminate\Support\Facades\Schema::hasTable('estate_possession_details')) {
            $activeDetails = DB::table('estate_possession_details')
                ->where('estate_house_master_pk', $housePk)
                ->whereNotNull('estate_house_master_pk');
            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
                $activeDetails->where(function ($q) {
                    $q->whereNull('return_home_status')->orWhere('return_home_status', 0);
                });
            }
            $isOccupied = $activeDetails->exists();
        }
        if (! $isOccupied && \Illuminate\Support\Facades\Schema::hasTable('estate_possession_other')) {
            $activeOther = DB::table('estate_possession_other')
                ->where('estate_house_master_pk', $housePk)
                ->whereNotNull('estate_house_master_pk');
            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_other', 'return_home_status')) {
                $activeOther->where(function ($q) {
                    $q->whereNull('return_home_status')->orWhere('return_home_status', 0);
                });
            }
            $isOccupied = $activeOther->exists();
        }

        if ($isOccupied) {
            $message = 'House is occupied. It cannot be deleted.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->route('admin.estate.define-house')->with('error', $message);
        }

        $house->delete();

        $message = 'Estate house deleted successfully.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return redirect()->route('admin.estate.define-house')->with('success', $message);
    }

    /**
     * API: Get unit sub types for estate possession (by campus + block).
     */
    public function getPossessionUnitSubTypes(Request $request)
    {
        $campusId = $request->get('campus_id');
        $blockId = $request->get('block_id');
        $unitTypeId = $request->get('unit_type_id');
        if (!$campusId || !$blockId) {
            return response()->json(['status' => true, 'data' => []]);
        }

        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');

        $items = DB::table('estate_house_master as h')
            ->join('estate_unit_sub_type_master as u', 'h.estate_unit_sub_type_master_pk', '=', 'u.pk')
            ->where('h.estate_campus_master_pk', $campusId)
            ->where('h.estate_block_master_pk', $blockId)
            ->when($unitTypeId, function ($q) use ($unitTypeId) {
                // Some DBs don't have unit_type FK on sub_type; fall back to house column.
                if (\Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk')) {
                    $q->where('u.estate_unit_type_master_pk', $unitTypeId);
                } else {
                    $q->where('h.estate_unit_master_pk', $unitTypeId);
                }
            })
            ->select('u.pk', 'u.unit_sub_type')
            ->distinct()
            ->orderBy('u.unit_sub_type')
            ->get();

        return response()->json(['status' => true, 'data' => $items]);
    }

    /**
     * API: Get houses for estate possession (by campus + block + unit_sub_type).
     * Returns ONLY vacant houses (used_home_status=0, vacant_renovation_status=1) and
     * excludes houses that are already in possession tables (estate_possession_details / estate_possession_other).
     *
     * Optional filters:
     * - unit_type_id
     * - employee_pk (to restrict to eligibility list, same as change request flow)
     * - include_house_pk (to force-include a specific house, e.g. when editing existing possession)
     */
    public function getPossessionHouses(Request $request)
    {
        $campusId = $request->get('campus_id');
        $blockId = $request->get('block_id');
        $unitSubTypeId = $request->get('unit_sub_type_id');
        $unitTypeId = $request->get('unit_type_id');
        $employeePk = $request->get('employee_pk') ? (int) $request->get('employee_pk') : null;
        $includeHousePk = $request->get('include_house_pk') ? (int) $request->get('include_house_pk') : null;

        if (! $blockId || ! $unitSubTypeId) {
            return response()->json(['status' => true, 'data' => []]);
        }

        // Houses currently occupied in either regular or "other" possession (and not yet returned)
        $occupiedDetails = DB::table('estate_possession_details')
            ->whereNotNull('estate_house_master_pk');
        if (Schema::hasColumn('estate_possession_details', 'return_home_status')) {
            $occupiedDetails->where('return_home_status', 0);
        }
        $occupiedHousePks = $occupiedDetails->pluck('estate_house_master_pk')
            ->merge(
                DB::table('estate_possession_other')
                    ->whereNotNull('estate_house_master_pk')
                    ->where('return_home_status', 0)
                    ->pluck('estate_house_master_pk')
            )
            ->unique()
            ->values();

        // If we are editing an existing possession, allow its house to appear even if currently occupied.
        if ($includeHousePk) {
            $occupiedHousePks = $occupiedHousePks
                ->reject(fn ($pk) => (int) $pk === $includeHousePk)
                ->values();
        }

        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');

        $query = DB::table('estate_house_master as h')
            ->when($unitTypeId, function ($q) use ($unitTypeId, $hasUnitTypeOnSubType) {
                if ($hasUnitTypeOnSubType) {
                    $q->join('estate_unit_sub_type_master as eust', 'h.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                        ->where('eust.estate_unit_type_master_pk', $unitTypeId);
                } else {
                    $q->where('h.estate_unit_master_pk', $unitTypeId);
                }
            })
            ->where('h.estate_block_master_pk', $blockId)
            ->where('h.estate_unit_sub_type_master_pk', $unitSubTypeId)
            ->where('h.vacant_renovation_status', 1)
            ->where(function ($q) {
                $q->whereNotNull('h.house_no')
                    ->where('h.house_no', '!=', '')
                    ->where('h.house_no', '!=', '0');
            })
            ->when($campusId, function ($q) use ($campusId) {
                $q->where('h.estate_campus_master_pk', $campusId);
            })
            ->whereNotIn('h.pk', $occupiedHousePks);

        // If employee-based eligibility is provided, reuse the same logic as change-request vacant houses.
        if ($employeePk) {
            $eligibleHousePks = $this->getEligibleHousePksByEmployeePk($employeePk);
            if ($eligibleHousePks->isNotEmpty()) {
                $query->whereIn('h.pk', $eligibleHousePks->toArray());
            } elseif ($includeHousePk) {
                // Fallback: show the specific included house even if not in eligibility list
                $includeHouseQuery = DB::table('estate_house_master as h')
                    ->when($unitTypeId, function ($q) use ($unitTypeId, $hasUnitTypeOnSubType) {
                        if ($hasUnitTypeOnSubType) {
                            $q->join('estate_unit_sub_type_master as eust', 'h.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                                ->where('eust.estate_unit_type_master_pk', $unitTypeId);
                        } else {
                            $q->where('h.estate_unit_master_pk', $unitTypeId);
                        }
                    })
                    ->where('h.pk', $includeHousePk)
                    ->where('h.estate_block_master_pk', $blockId)
                    ->where('h.estate_unit_sub_type_master_pk', $unitSubTypeId)
                    ->when($campusId, fn ($q) => $q->where('h.estate_campus_master_pk', $campusId));
                $includeHouse = $includeHouseQuery->select('h.pk', 'h.house_no', 'h.meter_one', 'h.meter_two')->first();
                if ($includeHouse) {
                    $houses = collect([(object) [
                        'pk' => (int) $includeHouse->pk,
                        'house_no' => $includeHouse->house_no ?? '',
                        'meter_one' => $includeHouse->meter_one ?? null,
                        'meter_two' => $includeHouse->meter_two ?? null,
                    ]]);

                    return response()->json(['status' => true, 'data' => $houses->values()->all()]);
                }
            }
        }

        $houses = $query
            ->select('h.pk', 'h.house_no', 'h.meter_one', 'h.meter_two')
            ->orderBy('h.house_no')
            ->get();

        // When include_house_pk is present (e.g. editing an existing possession for
        // "Other" employees) but no employee_pk filter is applied, the currently
        // allotted house might be occupied and therefore excluded by the vacancy
        // filters above. In that case we still need to return that specific house
        // so that the edit form can pre-select it and display its meter numbers.
        if ($includeHousePk) {
            $alreadyIncluded = $houses->contains(function ($h) use ($includeHousePk) {
                return (int) $h->pk === (int) $includeHousePk;
            });

            if (! $alreadyIncluded) {
                $includeHouseQuery = DB::table('estate_house_master as h')
                    ->when($unitTypeId, function ($q) use ($unitTypeId, $hasUnitTypeOnSubType) {
                        if ($hasUnitTypeOnSubType) {
                            $q->join('estate_unit_sub_type_master as eust', 'h.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                                ->where('eust.estate_unit_type_master_pk', $unitTypeId);
                        } else {
                            $q->where('h.estate_unit_master_pk', $unitTypeId);
                        }
                    })
                    ->where('h.pk', $includeHousePk)
                    ->where('h.estate_block_master_pk', $blockId)
                    ->where('h.estate_unit_sub_type_master_pk', $unitSubTypeId)
                    ->when($campusId, fn ($q) => $q->where('h.estate_campus_master_pk', $campusId));

                $includeHouse = $includeHouseQuery->select('h.pk', 'h.house_no', 'h.meter_one', 'h.meter_two')->first();
                if ($includeHouse) {
                    $houses->push((object) [
                        'pk' => (int) $includeHouse->pk,
                        'house_no' => $includeHouse->house_no ?? '',
                        'meter_one' => $includeHouse->meter_one ?? null,
                        'meter_two' => $includeHouse->meter_two ?? null,
                    ]);
                }
            }
        }

        return response()->json(['status' => true, 'data' => $houses]);
    }

    /**
     * API: Get vacant houses for change request / allot (block + unit_sub_type + used_home_status=0).
     * When employee_pk is passed (e.g. from Allot modal), only houses eligible for that employee by salary grade are returned (DB admin query).
     * Optional: campus_id, unit_type_id. Excludes houses in estate_possession_details and estate_possession_other.
     */
    public function getChangeRequestVacantHouses(Request $request)
    {
        $campusId = $request->get('campus_id');
        $blockId = $request->get('block_id');
        $unitSubTypeId = $request->get('unit_sub_type_id');
        $unitTypeId = $request->get('unit_type_id');
        $employeePk = $request->get('employee_pk') ? (int) $request->get('employee_pk') : null;
        $includeHousePk = $request->get('include_house_pk') ? (int) $request->get('include_house_pk') : null;
        if (! $blockId || ! $unitSubTypeId) {
            return response()->json(['status' => true, 'data' => []]);
        }

        $occupiedDetails = DB::table('estate_possession_details')
            ->whereNotNull('estate_house_master_pk');
        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
            $occupiedDetails->where('return_home_status', 0);
        }
        $occupiedHousePks = $occupiedDetails->pluck('estate_house_master_pk')
            ->merge(
                DB::table('estate_possession_other')
                    ->whereNotNull('estate_house_master_pk')
                    ->where('return_home_status', 0)
                    ->pluck('estate_house_master_pk')
            )
            ->unique()
            ->values();

        if ($includeHousePk) {
            $occupiedHousePks = $occupiedHousePks
                ->reject(fn ($pk) => (int) $pk === $includeHousePk)
                ->values();
        }

        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');

        $query = DB::table('estate_house_master as h')
            ->when($unitTypeId, function ($q) use ($unitTypeId, $hasUnitTypeOnSubType) {
                if ($hasUnitTypeOnSubType) {
                    $q->join('estate_unit_sub_type_master as eust', 'h.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                        ->where('eust.estate_unit_type_master_pk', $unitTypeId);
                } else {
                    $q->where('h.estate_unit_master_pk', $unitTypeId);
                }
            })
            ->where('h.estate_block_master_pk', $blockId)
            ->where('h.estate_unit_sub_type_master_pk', $unitSubTypeId)
            ->where('h.vacant_renovation_status', 1)
            ->where(function ($q) {
                $q->whereNotNull('h.house_no')
                    ->where('h.house_no', '!=', '')
                    ->where('h.house_no', '!=', '0');
            })
            ->when($campusId, function ($q) use ($campusId) {
                $q->where('h.estate_campus_master_pk', $campusId);
            });

        if ($employeePk) {
            $eligibleHousePks = $this->getEligibleHousePksByEmployeePk($employeePk);
            if ($eligibleHousePks->isNotEmpty()) {
                $query->whereIn('h.pk', $eligibleHousePks->toArray());
            } elseif ($includeHousePk) {
                // Add Possession edit: show requester's allotted house in dropdown even if not in eligibility list
                $includeHouseQuery = DB::table('estate_house_master as h')
                    ->when($unitTypeId, function ($q) use ($unitTypeId, $hasUnitTypeOnSubType) {
                        if ($hasUnitTypeOnSubType) {
                            $q->join('estate_unit_sub_type_master as eust', 'h.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                                ->where('eust.estate_unit_type_master_pk', $unitTypeId);
                        } else {
                            $q->where('h.estate_unit_master_pk', $unitTypeId);
                        }
                    })
                    ->where('h.pk', $includeHousePk)
                    ->where('h.estate_block_master_pk', $blockId)
                    ->where('h.estate_unit_sub_type_master_pk', $unitSubTypeId)
                    ->when($campusId, fn ($q) => $q->where('h.estate_campus_master_pk', $campusId));
                $includeHouse = $includeHouseQuery->select('h.pk', 'h.house_no')->first();
                if ($includeHouse) {
                    $includeHouseWithMeters = DB::table('estate_house_master')
                        ->where('pk', $includeHousePk)
                        ->select('pk', 'house_no', 'meter_one', 'meter_two')
                        ->first();
                    $obj = $includeHouseWithMeters ?? $includeHouse;
                    $houses = collect([(object) [
                        'pk' => (int) $obj->pk,
                        'house_no' => $obj->house_no ?? '',
                        'meter_one' => $obj->meter_one ?? null,
                        'meter_two' => $obj->meter_two ?? null,
                    ]]);
                    return response()->json(['status' => true, 'data' => $houses->values()->all()]);
                }
                return response()->json(['status' => true, 'data' => []]);
            }
            // Fallback: if eligibility query returns empty, don't hard-block allotment.
            // Show general vacant houses for selected filters.
        }

        if ($occupiedHousePks->isNotEmpty()) {
            $query->whereNotIn('h.pk', $occupiedHousePks->toArray());
        }

        $houses = $query->select('h.pk', 'h.house_no', 'h.meter_one', 'h.meter_two')
            ->orderBy('h.house_no')
            ->get();

        // Possession create pre-fill: include_house_pk (e.g. requester's allotted house) may be occupied;
        // include it in the list so House No. dropdown can pre-select it.
        if ($includeHousePk && $houses->where('pk', $includeHousePk)->isEmpty()) {
            $includeHouseQuery = DB::table('estate_house_master as h')
                ->when($unitTypeId, function ($q) use ($unitTypeId, $hasUnitTypeOnSubType) {
                    if ($hasUnitTypeOnSubType) {
                        $q->join('estate_unit_sub_type_master as eust', 'h.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                            ->where('eust.estate_unit_type_master_pk', $unitTypeId);
                    } else {
                        $q->where('h.estate_unit_master_pk', $unitTypeId);
                    }
                })
                ->where('h.pk', $includeHousePk)
                ->where('h.estate_block_master_pk', $blockId)
                ->where('h.estate_unit_sub_type_master_pk', $unitSubTypeId)
                ->when($campusId, fn ($q) => $q->where('h.estate_campus_master_pk', $campusId));
            $includeHouse = $includeHouseQuery->select('h.pk', 'h.house_no', 'h.meter_one', 'h.meter_two')->first();
            if ($includeHouse) {
                $houses = $houses->prepend((object) [
                    'pk' => (int) $includeHouse->pk,
                    'house_no' => $includeHouse->house_no ?? '',
                    'meter_one' => $includeHouse->meter_one ?? null,
                    'meter_two' => $includeHouse->meter_two ?? null,
                ])->values();
            }
        }

        return response()->json(['status' => true, 'data' => $houses]);
    }

    /**
     * Possession Details - Listing for LBSNAA employee possession (estate_possession_details).
     * Different from Estate Possession for Other (estate_possession_other).
     */
    public function possessionDetails(EstatePossessionDetailsDataTable $dataTable)
    {
        return $dataTable->render('admin.estate.possession_details');
    }

    /**
     * Possession Details (LBSNAA) - Add form.
     * Requester dropdown shows HAC-approved requests that are not already in possession_details.
     */
    public function possessionDetailsCreate(Request $request)
    {
        $baseRequestersQuery = DB::table('estate_home_request_details as ehrd')
            ->join('estate_possession_details as epd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            // Sirf HAC-approved requests
            ->where('ehrd.hac_status', 1)
            // Latest possession record per request.
            ->whereRaw('epd.pk = (SELECT MAX(epd2.pk) FROM estate_possession_details epd2 WHERE epd2.estate_home_request_details = ehrd.pk)')
            // "Allotted ho chuka hai" = request status marked allotted
            // (allotment_date/possession_date are filled later from Add Possession form)
            ->where('ehrd.status', 1)
            ->select(
                'ehrd.pk',
                'ehrd.req_id',
                'ehrd.emp_name',
                'ehrd.emp_designation',
                'ehrd.employee_pk',
                'ehrd.employee_id',
                DB::raw("CASE WHEN epd.allotment_date <= '1900-01-01' THEN NULL ELSE DATE(epd.allotment_date) END as allotment_date"),
                DB::raw("CASE WHEN epd.possession_date <= '1900-01-01' THEN NULL ELSE DATE(epd.possession_date) END as possession_date"),
                'epd.electric_meter_reading',
                'epd.estate_house_master_pk',
                'ehm.estate_campus_master_pk',
                'ehm.estate_block_master_pk',
                DB::raw((\Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk') ? 'eust.estate_unit_type_master_pk' : 'ehm.estate_unit_master_pk') . ' as estate_unit_type_master_pk'),
                'ehm.estate_unit_sub_type_master_pk'
            );
        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2')) {
            $baseRequestersQuery->addSelect('epd.electric_meter_reading_2');
        }
        $baseRequestersQuery->orderBy('ehrd.emp_name');

        // Self-service: non-estate/admin users should only see their own HAC-approved requester(s) in dropdown.
        $user = Auth::user();
        $isEstateAuthority = $user && (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'));
        if ($user && ! $isEstateAuthority) {
            $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
            if (!empty($employeeIds)) {
                $baseRequestersQuery->where(function ($q) use ($employeeIds) {
                    // Some legacy rows may have employee_pk = 0 but correct emploee_master_pk in possession table.
                    $q->whereIn('ehrd.employee_pk', $employeeIds)
                        ->orWhereIn('epd.emploee_master_pk', $employeeIds);
                });
            } else {
                // No mapped employee → keep query as-is so at least Estate/Admin can still use it if roles change.
            }
        }

        // Admin/Estate ke Add Possession form me sirf woh requester dikhne chahiye
        // jinke liye abhi possession form complete nahi hua (possession_date = sentinel 1900-01-01).
        // User ke end se agar possession ban chuka hai (possession_date set), to
        // unka naam yahan nahi aana chahiye; admin unko listing se edit karega.
        if ($isEstateAuthority) {
            $baseRequestersQuery->where('epd.possession_date', '<=', '1900-01-01');
        }

        $requesters = $baseRequestersQuery->get();

        // Edit mode: when requester_id is in URL, include that requester in dropdown (they have completed possession so not in "pending" list).
        $preselectedRequester = $request->query('requester_id');
        if ($preselectedRequester) {
            $editRequesterQuery = DB::table('estate_home_request_details as ehrd')
                ->join('estate_possession_details as epd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
                ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                ->where('ehrd.pk', $preselectedRequester)
                ->whereRaw('epd.pk = (SELECT MAX(epd2.pk) FROM estate_possession_details epd2 WHERE epd2.estate_home_request_details = ehrd.pk)')
                ->select(
                    'ehrd.pk',
                    'ehrd.req_id',
                    'ehrd.emp_name',
                    'ehrd.emp_designation',
                    'ehrd.employee_pk',
                    'ehrd.employee_id',
                    DB::raw("CASE WHEN epd.allotment_date <= '1900-01-01' THEN NULL ELSE DATE(epd.allotment_date) END as allotment_date"),
                    DB::raw("CASE WHEN epd.possession_date <= '1900-01-01' THEN NULL ELSE DATE(epd.possession_date) END as possession_date"),
                    'epd.electric_meter_reading',
                    'epd.estate_house_master_pk',
                    'ehm.estate_campus_master_pk',
                    'ehm.estate_block_master_pk',
                    DB::raw((\Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk') ? 'eust.estate_unit_type_master_pk' : 'ehm.estate_unit_master_pk') . ' as estate_unit_type_master_pk'),
                    'ehm.estate_unit_sub_type_master_pk'
                );
            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2')) {
                $editRequesterQuery->addSelect('epd.electric_meter_reading_2');
            }
            if ($user && ! (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'))) {
                $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
                if (! empty($employeeIds)) {
                    $editRequesterQuery->where(function ($q) use ($employeeIds) {
                        $q->whereIn('ehrd.employee_pk', $employeeIds)
                            ->orWhereIn('epd.emploee_master_pk', $employeeIds);
                    });
                }
            }
            $editRequester = $editRequesterQuery->first();
            if ($editRequester && $requesters->where('pk', $editRequester->pk)->isEmpty()) {
                $requesters = $requesters->push($editRequester)->sortBy('emp_name')->values();
            }
        }

        // If self-service user has exactly one requester, preselect it by default.
        if (! $preselectedRequester && $requesters->count() === 1) {
            $preselectedRequester = $requesters->first()->pk ?? null;
        }

        $campuses = DB::table('estate_campus_master')
            ->orderBy('campus_name')
            ->get(['pk', 'campus_name']);

        $unitTypes = DB::table('estate_unit_type_master')
            ->orderBy('unit_type')
            ->get(['pk', 'unit_type']);

        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
        $unitTypesByCampusQ = DB::table('estate_campus_master as a')
            ->join('estate_house_master as b', 'a.pk', '=', 'b.estate_campus_master_pk')
            ->join('estate_unit_sub_type_master as eust', 'b.estate_unit_sub_type_master_pk', '=', 'eust.pk');
        if ($hasUnitTypeOnSubType) {
            $unitTypesByCampusQ->join('estate_unit_type_master as c', 'eust.estate_unit_type_master_pk', '=', 'c.pk');
        } else {
            $unitTypesByCampusQ->join('estate_unit_type_master as c', 'b.estate_unit_master_pk', '=', 'c.pk');
        }
        $unitTypesByCampus = $unitTypesByCampusQ
            ->select('a.pk as campus_pk', 'c.pk as unit_type_pk', 'c.unit_type')
            ->distinct()
            ->orderBy('a.pk')
            ->orderBy('c.unit_type')
            ->get()
            ->groupBy('campus_pk')
            ->map(fn ($rows) => $rows->map(fn ($r) => ['pk' => $r->unit_type_pk, 'unit_type' => $r->unit_type])->values()->all())
            ->all();

        $isEdit = $request->filled('requester_id');

        return view('admin.estate.possession_details_form', compact(
            'requesters',
            'campuses',
            'unitTypesByCampus',
            'preselectedRequester',
            'isEdit'
        ));
    }


    /**
     * Possession Details (LBSNAA) - Store form into estate_possession_details.
     */
    public function storePossessionDetails(Request $request)
    {
        $messages = [
            'electric_meter_reading_primary.regex' => 'Primary meter reading must be numbers only (max 10 digits).',
            'electric_meter_reading_secondary.regex' => 'Secondary meter reading must be numbers only (max 10 digits).',
        ];

        // Human-friendly field names for validation errors (shown to user)
        $attributes = [
            'estate_home_request_details_pk' => 'Requester Name',
            'estate_campus_master_pk' => 'Estate Name',
            'estate_block_master_pk' => 'Building Name',
            'estate_unit_sub_type_master_pk' => 'Unit Sub Type',
            'estate_house_master_pk' => 'House No.',
            'allotment_date' => 'Allotment Date',
            'possession_date' => 'Possession Date',
            'electric_meter_reading_secondary' => 'Electric Meter Reading (Secondary)',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'estate_home_request_details_pk' => 'required|integer|exists:estate_home_request_details,pk',
            'estate_campus_master_pk' => 'required|integer',
            'estate_block_master_pk' => 'required|integer',
            'estate_unit_sub_type_master_pk' => 'required|integer',
            'estate_house_master_pk' => 'required|integer|exists:estate_house_master,pk',
            'allotment_date' => 'required|date',
            'possession_date' => 'required|date',
            'electric_meter_reading_primary' => 'nullable|regex:/^[0-9]{1,10}$/',
            'electric_meter_reading_secondary' => 'nullable|regex:/^[0-9]{1,10}$/',
            'electric_meter_reading' => 'nullable',
        ], $messages, $attributes);

        // For Estate/Admin roles, electric meter reading (primary/secondary) is required.
        // For normal users (self-service), meter reading is optional; admin will update later.
        $user = Auth::user();
        $isEstateAuthority = $user && (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'));

        if ($isEstateAuthority) {
            $validator->after(function ($v) use ($request) {
                $primary = trim((string) ($request->input('electric_meter_reading_primary', '')));
                $secondary = trim((string) ($request->input('electric_meter_reading_secondary', '')));
                if ($secondary === '' && $primary === '') {
                    $v->errors()->add('electric_meter_reading_secondary', 'Electric Meter Reading is required (enter Primary or Secondary).');
                }
            });
        }

        $validated = $validator->validate();

        $primary = trim((string) ($validated['electric_meter_reading_primary'] ?? ''));
        $secondary = trim((string) ($validated['electric_meter_reading_secondary'] ?? ''));
        // electric_meter_reading column = primary (main meter reading, shown in listing)
        $electricReading = $primary !== '' ? (int) $primary : 0;
        // electric_meter_reading_2 column = secondary (second meter, if any)
        $electricReading2 = $secondary !== '' ? (int) $secondary : null;

        $homeReq = EstateHomeRequestDetails::where('hac_status', 1)
            ->findOrFail((int) $validated['estate_home_request_details_pk']);

        // Use latest possession (same row as in listing: orderBy epd.pk desc)
        $existingPossession = DB::table('estate_possession_details')
            ->where('estate_home_request_details', (int) $homeReq->pk)
            ->orderByDesc('pk')
            ->first();

        if (! $existingPossession) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Requester is not allotted yet. Please allot first from HAC Approved.');
        }

        // Date editability:
        // UI keeps these readonly for non-authority users, but enforce server-side too.
        $user = Auth::user();
        $canEditDates = $user && (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'));
        if (! $canEditDates) {
            // When dates already exist on the latest possession record, keep them immutable.
            // If they are empty (legacy/incomplete), fall back to validated input so flow doesn't break.
            // BUT: pending allotment uses sentinel date 1900-01-01. Treat it as empty so user can fill real dates.
            $pendingSentinel = '1900-01-01';
            $existingAllotment = ! empty($existingPossession->allotment_date) ? (string) $existingPossession->allotment_date : '';
            $existingPossessionDate = ! empty($existingPossession->possession_date) ? (string) $existingPossession->possession_date : '';

            $isAllotmentPending = $existingAllotment !== '' && substr($existingAllotment, 0, 10) <= $pendingSentinel;
            $isPossessionPending = $existingPossessionDate !== '' && substr($existingPossessionDate, 0, 10) <= $pendingSentinel;

            if ($existingAllotment !== '' && ! $isAllotmentPending) {
                $validated['allotment_date'] = $existingPossession->allotment_date;
            }
            if ($existingPossessionDate !== '' && ! $isPossessionPending) {
                $validated['possession_date'] = $existingPossession->possession_date;
            }
        }

        // Self-service users (non-estate/admin) should not modify an already *completed*
        // LBSNAA possession record (jahan final meter reading aa chuka ho).
        // Lekin pehli baar possession details fill karne ke liye (electric_meter_reading 0/NULL)
        // unko allow karna hai – warna staff apna first possession complete hi nahi kar paayega.
        $user = Auth::user();
        $isEstateAuthority = $user && (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'));
        $hasFinalReading = $existingPossession && (int) ($existingPossession->electric_meter_reading ?? 0) > 0;
        if (! $isEstateAuthority && $hasFinalReading) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'You already have a possession record for this request. You can create a new possession only after returning the current house.');
        }

        $employeePk = $homeReq->employee_pk ? (int) $homeReq->employee_pk : null;
        if (! $employeePk && $homeReq->employee_id) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'emp_id')) {
                $employeePk = DB::table('employee_master')->where('emp_id', $homeReq->employee_id)->value('pk');
            }
            if (! $employeePk && \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'employee_id')) {
                $employeePk = DB::table('employee_master')->where('employee_id', $homeReq->employee_id)->value('pk');
            }
            if ($employeePk) {
                $employeePk = (int) $employeePk;
                $homeReq->employee_pk = $employeePk;
                $homeReq->save();
            }
        }

        // estate_possession_details.emploee_master_pk must store employee_master.pk (not pk_old)
        if ($employeePk) {
            $resolved = $this->resolveEmployeeMasterCanonicalPk($employeePk);
            $employeePk = ($resolved !== null && $resolved > 0) ? $resolved : null;
        }

        if (! $employeePk) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Employee could not be resolved for selected requester.');
        }

        $house = DB::table('estate_house_master')
            ->where('pk', (int) $validated['estate_house_master_pk'])
            ->where('estate_campus_master_pk', (int) $validated['estate_campus_master_pk'])
            ->where('estate_block_master_pk', (int) $validated['estate_block_master_pk'])
            ->where('estate_unit_sub_type_master_pk', (int) $validated['estate_unit_sub_type_master_pk'])
            ->first();

        if (! $house) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Selected house does not match selected Estate/Building/Unit Sub Type.');
        }

        // Skip eligibility check when selected house is the requester's existing allotted house
        // (e.g. possession form pre-filled with current house; eligibility list only has vacant houses).
        $isSameAsExistingPossession = $existingPossession && (int) $existingPossession->estate_house_master_pk === (int) $house->pk;
        if (! $isSameAsExistingPossession) {
            // Align with Define House: only Vacant and not already used houses can be allotted
            if ((int) ($house->vacant_renovation_status ?? 1) !== 1) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Selected house is Under Renovation in Define House. Only Vacant houses can be allotted.');
            }
            if ((int) ($house->used_home_status ?? 0) !== 0) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Selected house is already occupied. Please choose a vacant house from the list.');
            }
            $eligibleHousePks = $this->getEligibleHousePksByEmployeePk($employeePk);
            if ($eligibleHousePks->isNotEmpty() && ! $eligibleHousePks->contains((int) $house->pk)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Selected house is not eligible for this requester.');
            }
        }

        $occupiedDetails = DB::table('estate_possession_details')->whereNotNull('estate_house_master_pk');
        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
            $occupiedDetails->where('return_home_status', 0);
        }
        $occupiedHousePks = $occupiedDetails->pluck('estate_house_master_pk')
            ->merge(
                DB::table('estate_possession_other')
                    ->whereNotNull('estate_house_master_pk')
                    ->where('return_home_status', 0)
                    ->pluck('estate_house_master_pk')
            )
            ->unique()
            ->values();

        if ($occupiedHousePks->contains((int) $house->pk) && (! $existingPossession || (int) $existingPossession->estate_house_master_pk !== (int) $house->pk)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Selected house is already occupied.');
        }

        $payload = [
            'estate_home_request_details' => (int) $homeReq->pk,
            'emploee_master_pk' => $employeePk,
            'allotment_date' => $validated['allotment_date'],
            'possession_date' => $validated['possession_date'],
            'electric_meter_reading' => $electricReading,
            'estate_house_master_pk' => (int) $house->pk,
            'estate_change_id' => -1,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2')) {
            $payload['electric_meter_reading_2'] = $electricReading2;
        }

        if ($existingPossession) {
            $previousHousePk = (int) ($existingPossession->estate_house_master_pk ?? 0);
            DB::table('estate_possession_details')
                ->where('pk', (int) $existingPossession->pk)
                ->update($payload);
            $this->upsertMonthReadingOnPossession(
                (int) $existingPossession->pk,
                $validated['possession_date'] ?? null,
                $electricReading,
                $electricReading2,
                $house->house_no ?? null,
                $house->meter_one ?? null,
                $house->meter_two ?? null
            );
            $this->setHouseUsedStatus((int) $house->pk, 1);
            if ($previousHousePk > 0 && $previousHousePk !== (int) $house->pk) {
                $this->refreshHouseUsedStatusFromPossession($previousHousePk);
            }
            $message = 'Possession details updated successfully.';
        } else {
            $createdPossessionPk = (int) DB::table('estate_possession_details')->insertGetId($payload);
            $this->upsertMonthReadingOnPossession(
                $createdPossessionPk,
                $validated['possession_date'] ?? null,
                $electricReading,
                $electricReading2,
                $house->house_no ?? null,
                $house->meter_one ?? null,
                $house->meter_two ?? null
            );
            $this->setHouseUsedStatus((int) $house->pk, 1);
            $message = 'Possession details added successfully.';
        }

        // AJAX submit: return JSON so client redirects (avoids 302 + cancelled fetch in Network tab)
        if ($request->expectsJson() || $request->ajax()) {
            session()->flash('success', $message);
            return response()->json([
                'success' => true,
                'redirect' => route('admin.estate.possession-details'),
            ]);
        }
        return redirect()->route('admin.estate.possession-details')->with('success', $message);
    }

    /**
     * Estate Possession for Others - Listing (dynamic from DB, estate_possession_other).
     */
    public function possessionForOthers(EstatePossessionOtherDataTable $dataTable)
    {
        return $dataTable->render('admin.estate.estate_possession_for_others');
    }

    /**
     * Delete LBSNAA Possession Details record.
     */
    public function destroyPossessionDetails(Request $request, $id)
    {
        if (! (hasRole('Admin') || hasRole('Estate') || hasRole('Super Admin'))) {
            $message = 'You are not authorized to delete this possession.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }
            return redirect()->route('admin.estate.possession-details')->with('error', $message);
        }

        $record = DB::table('estate_possession_details')
            ->where('pk', (int) $id)
            ->first();

        if (! $record) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
            }
            return redirect()->route('admin.estate.possession-details')->with('error', 'Record not found.');
        }

        $hasMeterReadings = DB::table('estate_month_reading_details')
            ->where('estate_possession_details_pk', (int) $id)
            ->exists();

        if ($hasMeterReadings) {
            $message = 'This possession cannot be deleted because meter readings already exist for it.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->route('admin.estate.possession-details')->with('error', $message);
        }

        DB::table('estate_possession_details')->where('pk', (int) $id)->delete();
        $this->refreshHouseUsedStatusFromPossession((int) ($record->estate_house_master_pk ?? 0));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Possession details deleted successfully.']);
        }
        return redirect()->route('admin.estate.possession-details')->with('success', 'Possession details deleted successfully.');
    }

    /**
     * Bulk delete LBSNAA Possession Details records.
     * Skips records which already have meter readings.
     */
    public function destroyPossessionDetailsBulk(Request $request)
    {
        if (! (hasRole('Admin') || hasRole('Estate') || hasRole('Super Admin'))) {
            $message = 'You are not authorized to delete possessions.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }
            return redirect()->route('admin.estate.possession-details')->with('error', $message);
        }

        $ids = $request->input('ids', []);
        if (! is_array($ids)) {
            $ids = [];
        }
        $ids = array_values(array_unique(array_filter(array_map(static fn ($v) => (int) $v, $ids), static fn ($v) => $v > 0)));

        if (empty($ids)) {
            $message = 'Please select at least one record to delete.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->route('admin.estate.possession-details')->with('error', $message);
        }

        $idsWithReadings = DB::table('estate_month_reading_details')
            ->whereIn('estate_possession_details_pk', $ids)
            ->distinct()
            ->pluck('estate_possession_details_pk')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $blocked = array_values(array_unique(array_filter(array_map('intval', $idsWithReadings))));
        $deletable = array_values(array_diff($ids, $blocked));

        $deleted = 0;
        if (! empty($deletable)) {
            $deleted = (int) DB::table('estate_possession_details')->whereIn('pk', $deletable)->delete();
        }

        $message = "Deleted {$deleted} record(s).";
        if (! empty($blocked)) {
            $message .= ' Skipped ' . count($blocked) . ' record(s) because meter readings already exist.';
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted' => $deleted,
                'skipped' => count($blocked),
                'skipped_ids' => $blocked,
            ]);
        }
        return redirect()->route('admin.estate.possession-details')->with('success', $message);
    }

    /**
     * Delete Estate Possession.
     */
    public function destroyPossession(Request $request, $id)
    {
        if (! (hasRole('Admin') || hasRole('Estate') || hasRole('Super Admin'))) {
            $message = 'You are not authorized to delete this possession.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }
            return redirect()->route('admin.estate.possession-for-others')->with('error', $message);
        }

        $record = EstatePossessionOther::find($id);
        if (!$record) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
            }
            return redirect()->route('admin.estate.possession-for-others')->with('error', 'Record not found.');
        }

        $record->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Possession deleted successfully.']);
        }
        return redirect()->route('admin.estate.possession-for-others')->with('success', 'Possession deleted successfully.');
    }

    /**
     * Bulk delete Estate Possession (Others).
     */
    public function destroyPossessionBulk(Request $request)
    {
        if (! (hasRole('Admin') || hasRole('Estate') || hasRole('Super Admin'))) {
            $message = 'You are not authorized to delete possessions.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }
            return redirect()->route('admin.estate.possession-for-others')->with('error', $message);
        }

        $ids = $request->input('ids', []);
        if (! is_array($ids)) {
            $ids = [];
        }
        $ids = array_values(array_unique(array_filter(array_map(static fn ($v) => (int) $v, $ids), static fn ($v) => $v > 0)));

        if (empty($ids)) {
            $message = 'Please select at least one record to delete.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->route('admin.estate.possession-for-others')->with('error', $message);
        }

        $deleted = EstatePossessionOther::whereIn('pk', $ids)->delete();

        $message = $deleted
            ? ($deleted . ' possession(s) deleted successfully.')
            : 'No records were deleted.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'deleted' => (int) $deleted]);
        }
        return redirect()->route('admin.estate.possession-for-others')->with('success', $message);
    }

    /**
     * Return House - Listing (estate_possession_other where return_home_status = 1).
     * Pass requesters & campuses for Add Request Details modal.
     */
    public function returnHouse(EstateReturnHouseDataTable $dataTable)
    {
        $requesters = EstateOtherRequest::orderBy('emp_name')
            ->get(['pk', 'emp_name', 'request_no_oth', 'section']);
        $campuses = DB::table('estate_campus_master')
            ->orderBy('campus_name')
            ->get(['pk', 'campus_name']);
        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
        $unitTypesByCampus = DB::table('estate_campus_master as a')
            ->join('estate_house_master as b', 'a.pk', '=', 'b.estate_campus_master_pk')
            ->join('estate_unit_sub_type_master as eust', 'b.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->when($hasUnitTypeOnSubType, function ($q) {
                $q->join('estate_unit_type_master as c', 'eust.estate_unit_type_master_pk', '=', 'c.pk');
            }, function ($q) {
                $q->join('estate_unit_type_master as c', 'b.estate_unit_master_pk', '=', 'c.pk');
            })
            ->select('a.pk as campus_pk', 'c.pk as unit_type_pk', 'c.unit_type')
            ->distinct()
            ->orderBy('a.pk')
            ->orderBy('c.unit_type')
            ->get()
            ->groupBy('campus_pk')
            ->map(fn ($rows) => $rows->map(fn ($r) => ['pk' => $r->unit_type_pk, 'unit_type' => $r->unit_type])->values()->all())
            ->all();

        return $dataTable->render('admin.estate.return_house', compact('requesters', 'campuses', 'unitTypesByCampus'));
    }

    /**
     * Mark house as returned (set return_home_status = 1 in estate_possession_other).
     */
    public function markReturnHouse(Request $request, $id)
    {
        $record = EstatePossessionOther::find($id);
        if (! $record) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
            }
            return redirect()->route('admin.estate.return-house')->with('error', 'Record not found.');
        }

        $housePk = (int) ($record->estate_house_master_pk ?? 0);
        $record->return_home_status = 1;
        $record->save();
        $this->refreshHouseUsedStatusFromPossession($housePk);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'House marked as returned successfully.']);
        }
        return redirect()->route('admin.estate.return-house')->with('success', 'House marked as returned successfully.');
    }

    /**
     * API: Get requester details (request_no_oth, section) when requester selected.
     */
    public function getRequesterDetails(Request $request)
    {
        $pk = $request->get('pk');
        $req = EstateOtherRequest::find($pk);
        if (!$req) {
            return response()->json(['status' => false, 'data' => null]);
        }
        return response()->json([
            'status' => true,
            'data' => [
                'request_no_oth' => $req->request_no_oth,
                'section' => $req->section,
            ],
        ]);
    }

    /**
     * API: Return House - Get employees list by type (LBSNAA = estate_possession_details + estate_home_request_details, Other = estate_other_req).
     */
    public function getReturnHouseEmployees(Request $request)
    {
        $type = $request->get('employee_type', 'Other Employee');
        if ($type === 'LBSNAA') {
            // LBSNAA: use the same "allotted" source as Request For Estate listing,
            // but only for active (not-yet-returned) possessions and without pending change requests.
            $nameSelect = "COALESCE(NULLIF(TRIM(ehrd.emp_name), ''), NULLIF(TRIM(ehrd.employee_id), ''), CONCAT('Request #', ehrd.req_id))";

            $query = DB::table('estate_home_request_details as ehrd')
                ->join('estate_possession_details as epd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                // Consider both Pending (0) and Allotted (1) requests as long as they have an active possession.
                // This handles legacy cases where possession exists but status was never flipped to 1.
                ->whereIn('ehrd.status', [0, 1])
                ->whereNotNull('ehrd.current_alot')
                ->whereRaw("TRIM(COALESCE(ehrd.current_alot, '')) != ''")
                ->whereNotNull('epd.estate_house_master_pk')
                // Active possessions only: treat NULL or 0 as "not yet returned"
                ->when(\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status'), function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNull('epd.return_home_status')
                            ->orWhere('epd.return_home_status', 0);
                    });
                })
                // Exclude home requests that already have a *pending* change request
                ->whereNotExists(function ($sub) {
                    $sub->from('estate_change_home_req_details as ch')
                        ->whereColumn('ch.estate_home_req_details_pk', 'ehrd.pk')
                        ->where('ch.change_ap_dis_status', 0);
                });

            // LBSNAA: non-admin (permanent employee) sees only their own name;
            // Estate/Admin/Super Admin see full list (same as Request For Estate behavior).
            $user = Auth::user();
            if ($user && ! (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'))) {
                $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
                if (!empty($employeeIds)) {
                    $query->whereIn('ehrd.employee_pk', $employeeIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            $list = $query
                ->select('ehrd.pk as id', DB::raw($nameSelect . ' as name'), 'ehrd.req_id as request_no')
                ->distinct()
                ->orderByRaw($nameSelect . ' asc')
                ->get();
        } else {
            $otherNameSelect = "COALESCE(NULLIF(TRIM(eor.emp_name), ''), NULLIF(TRIM(eor.request_no_oth), ''), CONCAT('Request #', eor.pk))";

            // Other Employee: show ONLY those whose latest possession row is still active
            // (legacy rows may keep return_home_status as NULL; treat that same as 0),
            // actually mapped to a house, AND that house is currently marked used (used_home_status = 1).
            // This keeps the dropdown perfectly aligned with the effective occupancy flag.
            $list = DB::table('estate_other_req as eor')
                ->join('estate_possession_other as epo', 'epo.estate_other_req_pk', '=', 'eor.pk')
                ->join('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
                ->whereNotNull('epo.estate_house_master_pk')
                ->where(function ($q) {
                    $q->whereNull('epo.return_home_status')
                        ->orWhere('epo.return_home_status', 0);
                })
                ->where('ehm.used_home_status', 1)
                ->whereRaw("
                    epo.pk = (
                        SELECT MAX(epo2.pk)
                        FROM estate_possession_other AS epo2
                        WHERE epo2.estate_other_req_pk = eor.pk
                          AND epo2.estate_house_master_pk IS NOT NULL
                    )
                ")
                ->select(
                    'eor.pk as id',
                    DB::raw($otherNameSelect . ' as name'),
                    'eor.request_no_oth as request_no',
                    'eor.section'
                )
                ->distinct()
                ->orderByRaw($otherNameSelect . ' asc')
                ->get();
        }
        return response()->json(['status' => true, 'data' => $list]);
    }

    /**
     * API: Return House - Get full request details for mapping (section, estate, unit, building, house, dates).
     * Other: estate_other_req (section) + latest estate_possession_other. LBSNAA: estate_possession_details + house/campus/block/unit.
     */
    public function getReturnHouseRequestDetails(Request $request)
    {
        $type = $request->get('employee_type', 'Other Employee');
        $id = $request->get('id');
        if (!$id) {
            return response()->json(['status' => false, 'data' => null]);
        }
        if ($type === 'LBSNAA') {
            $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
            $rowQ = DB::table('estate_home_request_details as ehrd')
                ->join('estate_possession_details as epd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
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
                ->leftJoin('employee_master as em', 'ehrd.employee_pk', '=', 'em.pk')
                ->leftJoin('department_master as dm', 'em.department_master_pk', '=', 'dm.pk')
                ->where('ehrd.pk', $id)
                ->where(function ($q) {
                    $q->whereNull('epd.return_home_status')
                        ->orWhere('epd.return_home_status', 0);
                })
                ->whereNotNull('epd.estate_house_master_pk')
                ->select(
                    'ec.pk as estate_campus_master_pk',
                    'ec.campus_name',
                    'eb.pk as estate_block_master_pk',
                    'eb.block_name',
                    'eut.pk as estate_unit_type_master_pk',
                    'eut.unit_type as unit_type_name',
                    'eust.pk as estate_unit_sub_type_master_pk',
                    'eust.unit_sub_type',
                    'ehm.pk as estate_house_master_pk',
                    'ehm.house_no',
                    'epd.allotment_date',
                    'epd.possession_date as possession_date_oth',
                    DB::raw('COALESCE(NULLIF(TRIM(dm.department_name), ""), NULLIF(TRIM(ehrd.remarks), ""), "") as section_display')
                )
                ->orderByDesc('epd.pk');
            $row = $rowQ->first();
            if (!$row) {
                return response()->json(['status' => false, 'data' => null]);
            }
            $section = $row->section_display ?? '';
            unset($row->section_display);
            $row->section = $section;
            $row->possession_date_oth = $row->possession_date_oth ? (\Carbon\Carbon::parse($row->possession_date_oth)->format('Y-m-d')) : null;
            $row->allotment_date = $row->allotment_date ? (is_string($row->allotment_date) ? (date('Y-m-d', strtotime($row->allotment_date)) ?: $row->allotment_date) : \Carbon\Carbon::parse($row->allotment_date)->format('Y-m-d')) : null;
            return response()->json(['status' => true, 'data' => $row]);
        }
        // Other: estate_other_req (section) + latest estate_possession_other
        $req = EstateOtherRequest::find($id);
        if (!$req) {
            return response()->json(['status' => false, 'data' => null]);
        }
        // Possession rows often store only estate_house_master_pk on live/legacy data; campus/block/unit
        // then come from estate_house_master. Joining only epo.*_pk leaves campus null and the Return House
        // UI clears the house. COALESCE(epo, ehm) matches the LBSNAA path and fixes prefill.
        $hasUnitTypeOnSubTypeOther = Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
        $posQuery = DB::table('estate_possession_other as epo')
            ->leftJoin('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
            ->leftJoin('estate_campus_master as ec_epo', 'epo.estate_campus_master_pk', '=', 'ec_epo.pk')
            ->leftJoin('estate_campus_master as ec_hm', 'ehm.estate_campus_master_pk', '=', 'ec_hm.pk')
            ->leftJoin('estate_block_master as eb_epo', 'epo.estate_block_master_pk', '=', 'eb_epo.pk')
            ->leftJoin('estate_block_master as eb_hm', 'ehm.estate_block_master_pk', '=', 'eb_hm.pk')
            ->leftJoin('estate_unit_sub_type_master as eust_epo', 'epo.estate_unit_sub_type_master_pk', '=', 'eust_epo.pk')
            ->leftJoin('estate_unit_sub_type_master as eust_hm', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust_hm.pk')
            ->leftJoin('estate_unit_type_master as eut_fb', function ($join) {
                $join->whereRaw('eut_fb.pk = COALESCE(epo.estate_unit_type_master_pk, ehm.estate_unit_master_pk)');
            });
        if ($hasUnitTypeOnSubTypeOther) {
            $posQuery
                ->leftJoin('estate_unit_type_master as eut_st_epo', 'eust_epo.estate_unit_type_master_pk', '=', 'eut_st_epo.pk')
                ->leftJoin('estate_unit_type_master as eut_st_hm', 'eust_hm.estate_unit_type_master_pk', '=', 'eut_st_hm.pk');
        }
        $unitTypePkExpr = $hasUnitTypeOnSubTypeOther
            ? 'COALESCE(eut_st_epo.pk, eut_st_hm.pk, eut_fb.pk)'
            : 'eut_fb.pk';
        $unitTypeNameExpr = $hasUnitTypeOnSubTypeOther
            ? 'COALESCE(eut_st_epo.unit_type, eut_st_hm.unit_type, eut_fb.unit_type)'
            : 'eut_fb.unit_type';
        $pos = $posQuery
            ->where('epo.estate_other_req_pk', $id)
            ->where(function ($q) {
                $q->whereNull('epo.return_home_status')
                    ->orWhere('epo.return_home_status', 0);
            })
            ->whereNotNull('epo.estate_house_master_pk')
            ->orderBy('epo.pk', 'desc')
            ->select(
                DB::raw('COALESCE(ec_epo.pk, ec_hm.pk) as estate_campus_master_pk'),
                DB::raw('COALESCE(ec_epo.campus_name, ec_hm.campus_name) as campus_name'),
                DB::raw('COALESCE(eb_epo.pk, eb_hm.pk) as estate_block_master_pk'),
                DB::raw('COALESCE(eb_epo.block_name, eb_hm.block_name) as block_name'),
                DB::raw($unitTypePkExpr.' as estate_unit_type_master_pk'),
                DB::raw($unitTypeNameExpr.' as unit_type_name'),
                DB::raw('COALESCE(eust_epo.pk, eust_hm.pk) as estate_unit_sub_type_master_pk'),
                DB::raw('COALESCE(eust_epo.unit_sub_type, eust_hm.unit_sub_type) as unit_sub_type'),
                'ehm.pk as estate_house_master_pk',
                'ehm.house_no',
                'epo.allotment_date',
                'epo.possession_date_oth'
            )
            ->first();
        $section = $req->section ?? '';
        if ($pos) {
            $pos->section = $section;
            $pos->possession_date_oth = $pos->possession_date_oth ? (\Carbon\Carbon::parse($pos->possession_date_oth)->format('Y-m-d')) : null;
            $pos->allotment_date = $pos->allotment_date ? (is_string($pos->allotment_date) ? date('Y-m-d', strtotime($pos->allotment_date)) : \Carbon\Carbon::parse($pos->allotment_date)->format('Y-m-d')) : null;
            return response()->json(['status' => true, 'data' => $pos]);
        }
        return response()->json([
            'status' => true,
            'data' => (object) [
                'section' => $section,
                'estate_campus_master_pk' => null,
                'campus_name' => null,
                'estate_block_master_pk' => null,
                'block_name' => null,
                'estate_unit_type_master_pk' => null,
                'unit_type_name' => null,
                'estate_unit_sub_type_master_pk' => null,
                'unit_sub_type' => null,
                'estate_house_master_pk' => null,
                'house_no' => null,
                'allotment_date' => null,
                'possession_date_oth' => null,
            ],
        ]);
    }

    /**
     * Update Meter Reading of Other - Form page.
     * Unit types per campus (same as possession-view): only unit types that have houses in that campus.
     * When possession_pks (comma-separated) is present, loads selected possessions and passes prefill + card-wise data.
     */
    public function updateMeterReadingOfOther()
    {
        $campuses = DB::table('estate_campus_master')
            ->orderBy('campus_name')
            ->get(['pk', 'campus_name']);

        // All unit types (for initial/default dropdown rendering, same as permanent meter reading screen)
        $unitTypes = DB::table('estate_unit_type_master')
            ->orderBy('unit_type')
            ->get(['pk', 'unit_type']);

        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
        $unitTypesByCampusQ = DB::table('estate_campus_master as a')
            ->join('estate_house_master as b', 'a.pk', '=', 'b.estate_campus_master_pk')
            ->join('estate_unit_sub_type_master as eust', 'b.estate_unit_sub_type_master_pk', '=', 'eust.pk');

        if ($hasUnitTypeOnSubType) {
            $unitTypesByCampusQ->join('estate_unit_type_master as c', 'eust.estate_unit_type_master_pk', '=', 'c.pk');
        } else {
            $unitTypesByCampusQ->join('estate_unit_type_master as c', 'b.estate_unit_master_pk', '=', 'c.pk');
        }

        $unitTypesByCampus = $unitTypesByCampusQ
            ->select('a.pk as campus_pk', 'c.pk as unit_type_pk', 'c.unit_type')
            ->distinct()
            ->orderBy('a.pk')
            ->orderBy('c.unit_type')
            ->get()
            ->groupBy('campus_pk')
            ->map(fn ($rows) => $rows->map(fn ($r) => ['pk' => $r->unit_type_pk, 'unit_type' => $r->unit_type])->values()->all())
            ->all();

        $billMonths = EstateMonthReadingDetailsOther::select('bill_year', 'bill_month')
            ->whereNotNull('bill_year')
            ->whereNotNull('bill_month')
            ->groupBy('bill_year', 'bill_month')
            ->orderBy('bill_year', 'desc')
            ->get();

        $unitSubTypes = DB::table('estate_unit_sub_type_master')
            ->orderBy('unit_sub_type')
            ->get(['pk', 'unit_sub_type']);

        $prefill = null;
        $selectedPossessions = [];
        $possessionPks = '';

        $possessionPksParam = request('possession_pks');
        if ($possessionPksParam !== null && $possessionPksParam !== '') {
            $ids = is_array($possessionPksParam)
                ? array_map('intval', $possessionPksParam)
                : array_map('intval', array_filter(explode(',', (string) $possessionPksParam)));
            $ids = array_values(array_unique(array_filter($ids)));
            if (!empty($ids)) {
                $possessionsQuery = EstatePossessionOther::whereIn('pk', $ids)
                    ->with('estateOtherRequest')
                    ->orderBy('pk');
                if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_other', 'return_home_status')) {
                    $possessionsQuery->where(function ($q) {
                        $q->whereNull('return_home_status')
                            ->orWhere('return_home_status', 0);
                    });
                }
                $possessions = $possessionsQuery->get();
                foreach ($possessions as $p) {
                    $req = $p->estateOtherRequest;
                    $selectedPossessions[] = [
                        'pk' => $p->pk,
                        'name' => $req ? ($req->emp_name ?? 'N/A') : 'N/A',
                        'request_no_oth' => $req ? ($req->request_no_oth ?? '') : '',
                        'estate_campus_master_pk' => $p->estate_campus_master_pk,
                        'estate_block_master_pk' => $p->estate_block_master_pk,
                        'estate_unit_type_master_pk' => $p->estate_unit_type_master_pk,
                        'estate_unit_sub_type_master_pk' => $p->estate_unit_sub_type_master_pk,
                    ];
                }
                $activeIds = $possessions->pluck('pk')->map(fn ($x) => (int) $x)->values()->all();
                $possessionPks = ! empty($activeIds) ? implode(',', $activeIds) : '';
                $first = $possessions->first();
                if ($first) {
                    $billMonthYm = now()->format('Y-m');
                    $billMonthQuery = request('bill_month');
                    if (is_string($billMonthQuery) && preg_match('/^(\d{4})-(\d{2})$/', trim($billMonthQuery), $mm)) {
                        $dataY = (int) $mm[1];
                        $dataM = (int) $mm[2];
                        if ($dataM >= 1 && $dataM <= 12) {
                            $billMonthYm = sprintf('%04d-%02d', $dataY, $dataM);
                        }
                    }

                    $unitTypePk = $first->estate_unit_type_master_pk;
                    $unitTypeName = null;

                    if ($unitTypePk) {
                        $unitTypeName = DB::table('estate_unit_type_master')
                            ->where('pk', $unitTypePk)
                            ->value('unit_type');
                    }

                    $prefill = [
                        'bill_month' => $billMonthYm,
                        'estate_campus_master_pk' => $first->estate_campus_master_pk,
                        'estate_block_master_pk' => $first->estate_block_master_pk,
                        'estate_unit_type_master_pk' => $unitTypePk,
                        'estate_unit_type_name' => $unitTypeName,
                        'estate_unit_sub_type_master_pk' => $first->estate_unit_sub_type_master_pk,
                    ];
                    $readingPkRawO = request('reading_pk');
                    if ($readingPkRawO !== null && $readingPkRawO !== '' && is_numeric($readingPkRawO) && ! empty($activeIds)) {
                        $rpo = (int) $readingPkRawO;
                        if ($rpo > 0 && EstateMonthReadingDetailsOther::where('pk', $rpo)
                            ->whereIn('estate_possession_other_pk', $activeIds)
                            ->exists()) {
                            $prefill['reading_pk'] = $rpo;
                        }
                    }
                }
            }
        }

        return view('admin.estate.update_meter_reading_of_other', compact(
            'campuses', 'unitTypes', 'unitTypesByCampus', 'billMonths', 'unitSubTypes',
            'prefill', 'selectedPossessions', 'possessionPks'
        ));
    }

    /**
     * Update Meter Reading - main page (employee/regular possession).
     * When possession_pk and bill_month are in query string (e.g. from List Meter Reading Edit),
     * loads prefill data so the form and table can be prefilled and auto-loaded.
     * Only Estate / Admin / Super Admin can access; regular users can only view the list at update-meter-no.
     */
    public function updateMeterReading()
    {
        if (! hasRole('Estate') && ! hasRole('Admin') && ! hasRole('Super Admin')) {
            abort(403, 'You do not have permission to update reading and meter no. You can only view the list.');
        }

        $campuses = DB::table('estate_campus_master')
            ->orderBy('campus_name')
            ->get(['pk', 'campus_name']);

        $unitTypes = DB::table('estate_unit_type_master')
            ->orderBy('unit_type')
            ->get(['pk', 'unit_type']);

        $billMonths = EstateMonthReadingDetails::select('bill_year', 'bill_month')
            ->whereNotNull('bill_year')
            ->whereNotNull('bill_month')
            ->groupBy('bill_year', 'bill_month')
            ->orderBy('bill_year', 'desc')
            ->get();

        $unitSubTypes = DB::table('estate_unit_sub_type_master')
            ->orderBy('unit_sub_type')
            ->get(['pk', 'unit_sub_type']);

        $prefill = null;
        $possessionPk = request('possession_pk');
        $billMonthInput = request('bill_month');
        $readingPkForPrefill = null;
        $readingPkRaw = request('reading_pk');
        if ($readingPkRaw !== null && $readingPkRaw !== '' && is_numeric($readingPkRaw) && $possessionPk) {
            $rp = (int) $readingPkRaw;
            if ($rp > 0 && EstateMonthReadingDetails::where('pk', $rp)
                ->where('estate_possession_details_pk', (int) $possessionPk)
                ->exists()) {
                $readingPkForPrefill = $rp;
            }
        }
        if ($possessionPk && $billMonthInput) {
            $parts = explode('-', $billMonthInput);
            $billYear = count($parts) >= 1 ? (string) ((int) $parts[0]) : null;
            $monthNum = count($parts) >= 2 ? (int) $parts[1] : null;
            $billMonthName = $monthNum ? date('F', mktime(0, 0, 0, $monthNum, 1)) : null;
            // List/edit links pass Y-m matching bill_month/bill_year (same as Meter Change Month).
            $uiBillMonthYm = ($monthNum >= 1 && $monthNum <= 12 && $billYear)
                ? \Carbon\Carbon::createFromDate((int) $billYear, $monthNum, 1)->format('Y-m')
                : $billMonthInput;

            $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
            $possessionQ = DB::table('estate_possession_details as epd')
                ->join('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
                ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                ->where('epd.pk', $possessionPk)
                ->whereNotNull('epd.estate_house_master_pk');

            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
                $possessionQ->where(function ($q) {
                    $q->whereNull('epd.return_home_status')
                        ->orWhere('epd.return_home_status', 0);
                });
            }

            $possession = $possessionQ
                ->selectRaw(
                    'ehm.estate_campus_master_pk as campus_id, ' .
                    'ehm.estate_block_master_pk as block_id, ' .
                    ($hasUnitTypeOnSubType ? 'eust.estate_unit_type_master_pk' : 'ehm.estate_unit_master_pk') . ' as unit_type_id, ' .
                    'ehm.estate_unit_sub_type_master_pk as unit_sub_type_id'
                )
                ->first();

            $meterReadingDate = null;
            if ($readingPkForPrefill) {
                $reading = EstateMonthReadingDetails::where('pk', $readingPkForPrefill)->select('to_date')->first();
                if ($reading && $reading->to_date) {
                    $meterReadingDate = $reading->to_date->format('Y-m-d');
                }
            } elseif ($billYear && $billMonthName) {
                $reading = EstateMonthReadingDetails::where('estate_possession_details_pk', $possessionPk)
                    ->where('bill_year', $billYear)
                    ->where('bill_month', $billMonthName)
                    ->select('to_date')
                    ->first();
                if ($reading && $reading->to_date) {
                    $meterReadingDate = $reading->to_date->format('Y-m-d');
                }
            }

            if ($possession) {
                $prefill = [
                    'possession_pk' => (int) $possessionPk,
                    'bill_month' => $uiBillMonthYm,
                    'bill_year' => $billYear,
                    'bill_month_name' => $billMonthName,
                    'campus_id' => (int) $possession->campus_id,
                    'block_id' => (int) $possession->block_id,
                    'unit_type_id' => $possession->unit_type_id ? (int) $possession->unit_type_id : null,
                    'unit_sub_type_id' => $possession->unit_sub_type_id ? (int) $possession->unit_sub_type_id : null,
                    'meter_reading_date' => $meterReadingDate,
                ];
                if ($readingPkForPrefill !== null) {
                    $prefill['reading_pk'] = $readingPkForPrefill;
                }
            }
        }

        return view('admin.estate.update_meter_reading', compact(
            'campuses', 'unitTypes', 'billMonths', 'unitSubTypes', 'prefill'
        ));
    }

    /**
     * API: Get meter reading list for "Update Meter Reading" (filtered).
     * Matches rows for the selected Meter Change Month OR legacy rows stored as the previous calendar month.
     */
    public function getMeterReadingList(Request $request)
    {
        $billMonth = $request->get('bill_month');
        $billYear = $request->get('bill_year');
        $campusId = $request->get('campus_id');
        $blockId = $request->get('block_id');
        $unitTypeId = $request->get('unit_type_id');
        $unitSubTypeId = $request->get('unit_sub_type_id');

        $possessionPkParam = $request->get('possession_pk');
        $possessionPkScoped = null;
        if ($possessionPkParam !== null && $possessionPkParam !== '') {
            $possessionPkScoped = (int) $possessionPkParam;
            if ($possessionPkScoped <= 0) {
                $possessionPkScoped = null;
            }
        }

        $readingPkParam = $request->get('reading_pk');
        $readingPkScoped = null;
        if ($readingPkParam !== null && $readingPkParam !== '' && is_numeric($readingPkParam)) {
            $readingPkScoped = (int) $readingPkParam;
            if ($readingPkScoped <= 0) {
                $readingPkScoped = null;
            }
        }

        // estate_unit_master may be empty - use estate_eligibility_mapping for unit type → unit sub type
        $unitSubTypeIdsForUnitType = null;
        if ($unitTypeId) {
            $unitSubTypeIdsForUnitType = DB::table('estate_eligibility_mapping')
                ->where('estate_unit_type_master_pk', $unitTypeId)
                ->whereNotNull('estate_unit_sub_type_master_pk')
                ->distinct()
                ->pluck('estate_unit_sub_type_master_pk')
                ->filter()
                ->values()
                ->toArray();
        }

        $hasSecondaryPossessionReadingCol = \Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2');

        $query = EstateMonthReadingDetails::query()
            ->from('estate_month_reading_details as emrd')
            ->select([
                'emrd.pk',
                'emrd.from_date',
                'emrd.to_date',
                'emrd.last_month_elec_red',
                'emrd.curr_month_elec_red',
                'emrd.last_month_elec_red2',
                'emrd.curr_month_elec_red2',
                'emrd.house_no',
                'emrd.meter_one as emrd_meter_one',
                'emrd.meter_two as emrd_meter_two',
                'ehm.meter_one as ehm_meter_one',
                'ehm.meter_two as ehm_meter_two',
                'ehrd.emp_name',
                'epd.electric_meter_reading as epd_electric_meter_reading',
            ])
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->orderBy('emrd.house_no');

        if ($hasSecondaryPossessionReadingCol) {
            $query->addSelect('epd.electric_meter_reading_2 as epd_electric_meter_reading_2');
        } else {
            $query->addSelect(\Illuminate\Support\Facades\DB::raw('NULL as epd_electric_meter_reading_2'));
        }

        // RBAC: Non-admin/estate/super-admin/training/IST users should only see/update their own meter readings.
        if (! (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST'))) {
            $user = \Illuminate\Support\Facades\Auth::user();
            if ($user) {
                $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
                if (!empty($employeeIds)) {
                    $query->whereIn('ehrd.employee_pk', $employeeIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($readingPkScoped !== null) {
            $query->where('emrd.pk', $readingPkScoped);
            if ($possessionPkScoped !== null) {
                $query->where('epd.pk', $possessionPkScoped);
            }
        } else {
            $this->applyMeterReadingListBillPeriodOrLegacy($query, 'emrd.bill_month', 'emrd.bill_year', $billMonth, $billYear);
            // List Meter Reading "Edit" deep-links with possession_pk only: skip excludes (see reading_pk for single row).
            if ($possessionPkScoped === null) {
                $this->applyMeterReadingExcludeReadingDateInUiMonth($query, 'emrd.to_date', 'emrd.from_date', $billMonth, $billYear);
                $this->applyMeterReadingExcludePossessionIfAnyReadingDateInUiMonth($query, $billMonth, $billYear, 'epd.pk', 'regular');
            }
            if ($possessionPkScoped !== null) {
                $query->where('epd.pk', $possessionPkScoped);
            }
        }

        if ($readingPkScoped === null) {
            if ($campusId) {
                $query->where('ehm.estate_campus_master_pk', $campusId);
            }
            if ($blockId) {
                $query->where('ehm.estate_block_master_pk', $blockId);
            }
            if ($unitTypeId && !empty($unitSubTypeIdsForUnitType)) {
                $query->whereIn('ehm.estate_unit_sub_type_master_pk', $unitSubTypeIdsForUnitType);
            }
            if ($unitSubTypeId) {
                $query->where('ehm.estate_unit_sub_type_master_pk', $unitSubTypeId);
            }
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
            $query->where(function ($inner) {
                $inner->whereNull('epd.return_home_status')
                    ->orWhere('epd.return_home_status', 0);
            });
        }

        $rows = collect();
        foreach ($query->get() as $row) {
            // Use estate_house_master meter numbers when estate_month_reading_details has 0/null
            $meterOne = $row->emrd_meter_one ?? $row->ehm_meter_one;
            $meterTwo = $row->emrd_meter_two ?? $row->ehm_meter_two;
            $hasMeterOne = $meterOne !== null && $meterOne !== '' && (int) $meterOne !== 0;
            $hasMeterTwo = $meterTwo !== null && $meterTwo !== '' && (int) $meterTwo !== 0;

            $lastReadingDate = 'N/A';
            if (! empty($row->to_date)) {
                $lastReadingDate = \Carbon\Carbon::parse($row->to_date)->format('d/m/Y');
            } elseif (! empty($row->from_date)) {
                $lastReadingDate = \Carbon\Carbon::parse($row->from_date)->format('d/m/Y');
            }

            $base = [
                'pk' => $row->pk,
                'house_no' => $row->house_no ?? 'N/A',
                'name' => $row->emp_name ?? 'N/A',
                'last_reading_date' => $lastReadingDate,
            ];
            $pushed = false;

            // One list row for houses with two meters (same stacked UI as Update Meter Reading of Other).
            if ($hasMeterOne && $hasMeterTwo) {
                $curr1 = $row->curr_month_elec_red;
                $baselineMin1 = $curr1 !== null
                    ? (int) $curr1
                    : $this->effectiveLastMonthElecBaselineForMeterSlot(
                        $row->last_month_elec_red,
                        $row->epd_electric_meter_reading,
                        $row->epd_electric_meter_reading_2,
                        1
                    );
                $displayElectric1 = $curr1 !== null ? (string) (int) $curr1 : 'N/A';
                $newNo1 = $row->emrd_meter_one !== null && $row->emrd_meter_one !== '' ? (string) $row->emrd_meter_one : (string) $meterOne;

                $curr2 = $row->curr_month_elec_red2;
                $baselineMin2 = $curr2 !== null
                    ? (int) $curr2
                    : $this->effectiveLastMonthElecBaselineForMeterSlot(
                        $row->last_month_elec_red2,
                        $row->epd_electric_meter_reading,
                        $row->epd_electric_meter_reading_2,
                        2
                    );
                $displayElectric2 = $curr2 !== null ? (string) (int) $curr2 : 'N/A';
                $newNo2 = $row->emrd_meter_two !== null && $row->emrd_meter_two !== '' ? (string) $row->emrd_meter_two : (string) $meterTwo;

                $rows->push(array_merge($base, [
                    'dual_meter' => true,
                    'm1' => [
                        'meter_slot' => 1,
                        'old_meter_no' => (string) $meterOne,
                        'electric_meter_reading' => $displayElectric1,
                        'baseline_min_reading' => $baselineMin1,
                        'new_meter_no' => $newNo1,
                    ],
                    'm2' => [
                        'meter_slot' => 2,
                        'old_meter_no' => (string) $meterTwo,
                        'electric_meter_reading' => $displayElectric2,
                        'baseline_min_reading' => $baselineMin2,
                        'new_meter_no' => $newNo2,
                    ],
                ]));
                continue;
            }

            // Meter 1 — Electric column shows saved curr_month_elec_red; New Meter Reading stays blank until user enters.
            // baseline_min_reading aligns client min validation with server (curr when set, else last/possession).
            if ($hasMeterOne) {
                $curr1 = $row->curr_month_elec_red;
                $baselineMin1 = $curr1 !== null
                    ? (int) $curr1
                    : $this->effectiveLastMonthElecBaselineForMeterSlot(
                        $row->last_month_elec_red,
                        $row->epd_electric_meter_reading,
                        $row->epd_electric_meter_reading_2,
                        1
                    );
                $unit1 = null;

                $displayElectric1 = $curr1 !== null ? (string) (int) $curr1 : 'N/A';

                $rows->push(array_merge($base, [
                    'meter_slot' => 1,
                    'old_meter_no' => (string) $meterOne,
                    'electric_meter_reading' => $displayElectric1,
                    'baseline_min_reading' => $baselineMin1,
                    'new_meter_no' => $row->emrd_meter_one !== null && $row->emrd_meter_one !== '' ? (string) $row->emrd_meter_one : (string) $meterOne,
                    'new_meter_reading' => '',
                    'unit' => $unit1,
                ]));
                $pushed = true;
            }
            if ($hasMeterTwo) {
                $curr2 = $row->curr_month_elec_red2;
                $baselineMin2 = $curr2 !== null
                    ? (int) $curr2
                    : $this->effectiveLastMonthElecBaselineForMeterSlot(
                        $row->last_month_elec_red2,
                        $row->epd_electric_meter_reading,
                        $row->epd_electric_meter_reading_2,
                        2
                    );
                $unit2 = null;

                $displayElectric2 = $curr2 !== null ? (string) (int) $curr2 : 'N/A';

                $rows->push(array_merge($base, [
                    'meter_slot' => 2,
                    'old_meter_no' => (string) $meterTwo,
                    'electric_meter_reading' => $displayElectric2,
                    'baseline_min_reading' => $baselineMin2,
                    'new_meter_no' => $row->emrd_meter_two !== null && $row->emrd_meter_two !== '' ? (string) $row->emrd_meter_two : (string) $meterTwo,
                    'new_meter_reading' => '',
                    'unit' => $unit2,
                ]));
                $pushed = true;
            }
            // Fallback when neither meter has value
            if (!$pushed) {
                $lastMeter = $meterOne ?? $meterTwo ?? 'N/A';
                $currReadingRaw = $row->curr_month_elec_red ?? $row->curr_month_elec_red2;
                $lastReadingRaw = $row->last_month_elec_red ?? $row->last_month_elec_red2;
                $baselineMinFb = $currReadingRaw !== null
                    ? (int) $currReadingRaw
                    : $this->effectiveLastMonthElecBaselineForMeterSlot(
                        $lastReadingRaw,
                        $row->epd_electric_meter_reading,
                        $row->epd_electric_meter_reading_2,
                        1
                    );

                $displayFallback = $currReadingRaw !== null ? (string) (int) $currReadingRaw : 'N/A';

                $rows->push(array_merge($base, [
                    'meter_slot' => 1,
                    'old_meter_no' => (string) $lastMeter,
                    'electric_meter_reading' => $displayFallback,
                    'baseline_min_reading' => $baselineMinFb,
                    'new_meter_no' => '',
                    'new_meter_reading' => '',
                    'unit' => null,
                ]));
            }
        }

        return response()->json(['status' => true, 'data' => $rows->values()]);
    }

    /**
     * API: Get blocks for meter reading filter (by campus) - regular possession.
     */
    public function getMeterReadingBlocks(Request $request)
    {
        $campusId = $request->get('campus_id');
        if (!$campusId) {
            return response()->json(['status' => true, 'data' => []]);
        }
        // NOTE: Do not depend on estate_month_reading_details here.
        // The "Update Meter Reading" screen should list buildings based on current house/possession mapping,
        // even if month reading rows don't exist yet (otherwise some buildings disappear from dropdown).
        $q = DB::table('estate_possession_details as epd')
            ->join('estate_house_master as h', 'epd.estate_house_master_pk', '=', 'h.pk')
            ->join('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->whereNotNull('epd.estate_house_master_pk')
            ->where('h.estate_campus_master_pk', $campusId);

        // Prefer showing only active possessions (if column exists).
        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
            $q->where(function ($inner) {
                $inner->whereNull('epd.return_home_status')
                    ->orWhere('epd.return_home_status', 0);
            });
        }

        $blocks = $q->select('b.pk', 'b.block_name')
            ->distinct()
            ->orderBy('b.block_name')
            ->get();
        return response()->json(['status' => true, 'data' => $blocks]);
    }

    /**
     * API: Get meter reading dates for selected bill month - regular possession.
     */
    public function getMeterReadingDates(Request $request)
    {
        $billMonth = $request->get('bill_month');
        $billYear = $request->get('bill_year');
        [$probeM, $probeY] = $this->meterReadingParseUiMonthYear($billMonth, $billYear);
        if ($probeM === null || $probeY === null) {
            return response()->json(['status' => true, 'data' => []]);
        }
        $datesQuery = EstateMonthReadingDetails::query();
        $this->applyMeterReadingListBillPeriodOrLegacy($datesQuery, 'bill_month', 'bill_year', $billMonth, $billYear);
        $this->applyMeterReadingExcludeReadingDateInUiMonth(
            $datesQuery,
            'estate_month_reading_details.to_date',
            'estate_month_reading_details.from_date',
            $billMonth,
            $billYear
        );
        $this->applyMeterReadingExcludePossessionIfAnyReadingDateInUiMonth(
            $datesQuery,
            $billMonth,
            $billYear,
            'estate_month_reading_details.estate_possession_details_pk',
            'regular'
        );
        $dates = $datesQuery->select('to_date')
            ->distinct()
            ->orderBy('to_date')
            ->get()
            ->map(fn($r) => ['value' => $r->to_date->format('Y-m-d'), 'label' => $r->to_date->format('d/m/Y')]);
        return response()->json(['status' => true, 'data' => $dates]);
    }

    /**
     * API: Get unit sub types for meter reading filter (by campus + block) - regular possession.
     */
    public function getMeterReadingUnitSubTypes(Request $request)
    {
        $campusId = $request->get('campus_id');
        $blockId = $request->get('block_id');
        if (!$campusId || !$blockId) {
            return response()->json(['status' => true, 'data' => []]);
        }
        // Keep consistent with getMeterReadingBlocks(): depend on possession/house mapping, not month reading rows.
        $q = DB::table('estate_possession_details as epd')
            ->join('estate_house_master as h', 'epd.estate_house_master_pk', '=', 'h.pk')
            ->join('estate_unit_sub_type_master as u', 'h.estate_unit_sub_type_master_pk', '=', 'u.pk')
            ->whereNotNull('epd.estate_house_master_pk')
            ->where('h.estate_campus_master_pk', $campusId)
            ->where('h.estate_block_master_pk', $blockId);

        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
            $q->where(function ($inner) {
                $inner->whereNull('epd.return_home_status')
                    ->orWhere('epd.return_home_status', 0);
            });
        }

        $items = $q->select('u.pk', 'u.unit_sub_type')
            ->distinct()
            ->orderBy('u.unit_sub_type')
            ->get();
        return response()->json(['status' => true, 'data' => $items]);
    }

    /**
     * Store/Update meter readings for "Update Meter Reading" (regular possession).
     * Only Estate / Admin / Super Admin can store; regular users can only view the list.
     */
    public function storeMeterReadings(Request $request)
    {
        if (! hasRole('Estate') && ! hasRole('Admin') && ! hasRole('Super Admin')) {
            abort(403, 'You do not have permission to update reading and meter no.');
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'readings' => 'required|array',
            'readings.*.pk' => 'required|exists:estate_month_reading_details,pk',
            'readings.*.meter_slot' => 'nullable|in:1,2',
            'readings.*.selected' => 'nullable|in:1',
            'readings.*.curr_month_elec_red' => 'nullable|regex:/^[0-9]{1,20}$/',
            'readings.*.new_meter_no' => 'nullable|string|max:50|regex:/^[0-9]+$/',
            'reading_bill_month' => 'required|date_format:Y-m',
            'reading_current_date' => 'required|date',
            'reading_campus_id' => 'nullable|integer|exists:estate_campus_master,pk',
            'reading_block_id' => 'nullable|integer|exists:estate_block_master,pk',
            'reading_unit_type_id' => 'nullable|integer|exists:estate_unit_type_master,pk',
            'reading_unit_sub_type_id' => 'nullable|integer|exists:estate_unit_sub_type_master,pk',
        ]);

        $validator->after(function ($v) use ($request) {
            $readings = (array) $request->input('readings', []);
            $selectedPks = [];
            foreach ($readings as $item) {
                if (is_array($item) && isset($item['selected']) && (string) $item['selected'] === '1' && ! empty($item['pk'])) {
                    $selectedPks[] = (int) $item['pk'];
                }
            }
            $selectedPks = array_values(array_unique(array_filter($selectedPks)));
            if (! empty($selectedPks) && \Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
                $hasReturnedPossession = DB::table('estate_month_reading_details as emrd')
                    ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
                    ->whereIn('emrd.pk', $selectedPks)
                    ->whereNotNull('epd.return_home_status')
                    ->where('epd.return_home_status', '<>', 0)
                    ->exists();
                if ($hasReturnedPossession) {
                    $v->errors()->add('readings', 'Meter reading cannot be updated for houses that have been returned.');

                    return;
                }
            }

            $pks = collect($readings)->pluck('pk')->filter()->map(fn ($x) => (int) $x)->values()->all();
            if (empty($pks)) {
                return;
            }
            $rows = DB::table('estate_month_reading_details as emrd')
                ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
                ->whereIn('emrd.pk', $pks)
                ->select([
                    'emrd.pk',
                    'emrd.last_month_elec_red',
                    'emrd.curr_month_elec_red',
                    'emrd.last_month_elec_red2',
                    'emrd.curr_month_elec_red2',
                    'epd.electric_meter_reading as epd_electric_meter_reading',
                    'epd.electric_meter_reading_2 as epd_electric_meter_reading_2',
                ])
                ->get()
                ->keyBy('pk');

            foreach ($readings as $idx => $item) {
                $currVal = $item['curr_month_elec_red'] ?? null;
                $isSelected = isset($item['selected']) && (string) $item['selected'] === '1';

                // If row is selected for update, new meter reading is mandatory.
                if ($isSelected && ($currVal === null || $currVal === '')) {
                    $v->errors()->add(
                        "readings.$idx.curr_month_elec_red",
                        'Please fill the New Meter Reading for selected rows.'
                    );
                    continue;
                }

                // If not provided (and not selected), skip further validation.
                if ($currVal === null || $currVal === '') {
                    continue;
                }

                // Do not validate min reading for rows that are not selected (form still posts all inputs).
                if (! $isSelected) {
                    continue;
                }

                $pk = isset($item['pk']) ? (int) $item['pk'] : 0;
                $row = $rows->get($pk);
                if (! $row) {
                    continue;
                }

                $meterSlot = isset($item['meter_slot']) ? (int) $item['meter_slot'] : 1;
                $curr = (int) $currVal;
                if ($meterSlot === 2) {
                    $prev = $this->effectiveLastMonthElecBaselineForMeterSlot(
                        $row->last_month_elec_red2,
                        $row->epd_electric_meter_reading,
                        $row->epd_electric_meter_reading_2,
                        2
                    );
                    $existingCurr = $row->curr_month_elec_red2 !== null ? (int) $row->curr_month_elec_red2 : null;
                    $field = "readings.$idx.curr_month_elec_red";
                } else {
                    $prev = $this->effectiveLastMonthElecBaselineForMeterSlot(
                        $row->last_month_elec_red,
                        $row->epd_electric_meter_reading,
                        $row->epd_electric_meter_reading_2,
                        1
                    );
                    $existingCurr = $row->curr_month_elec_red !== null ? (int) $row->curr_month_elec_red : null;
                    $field = "readings.$idx.curr_month_elec_red";
                }

                // New reading must be >= saved curr when present; otherwise >= last/possession baseline (first entry).
                $minAllowed = $existingCurr !== null ? $existingCurr : $prev;
                if ($curr < $minAllowed) {
                    $v->errors()->add(
                        $field,
                        'New meter reading cannot be less than the saved current reading, or than the opening reading when no current reading exists yet.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($validator);
        }

        $validated = $validator->validated();

        $readings = array_values($validated['readings']);
        $readingBillMonth = $validated['reading_bill_month'] ?? null;
        $readingCurrentDate = $validated['reading_current_date'] ?? null;
        $lastMeterReadingDate = null;
        $currentMeterReadingDate = null;
        if (! empty($readingCurrentDate)) {
            try {
                $currentMeterReadingDate = \Carbon\Carbon::parse((string) $readingCurrentDate)->toDateString();
            } catch (\Throwable $e) {
                $currentMeterReadingDate = null;
            }
        }
        // bill_month / bill_year = selected Meter Change Month (reading_bill_month Y-m): full English name + 4-digit year.
        $storeBillMonthName = null;
        $storeBillYearStr = null;
        if (! empty($readingBillMonth)) {
            try {
                $sel = \Carbon\Carbon::createFromFormat('Y-m', (string) $readingBillMonth)->startOfMonth();
                $storeBillMonthName = $sel->format('F');
                $storeBillYearStr = $sel->format('Y');
                $lastMeterReadingDate = $sel->copy()->subMonth()->endOfMonth()->toDateString();
            } catch (\Throwable $e) {
                $storeBillMonthName = null;
                $storeBillYearStr = null;
            }
        }
        $hasLastMeterDateCol = \Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'last_meter_reading_date');
        $hasCurrentMeterDateCol = \Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'current_meter_reading_date');
        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
        $hasSecondaryPossessionReadingCol = \Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2');
        $emrdHasWaterCharges = \Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details', 'water_charges');
        $emrdHasLicenceFees = \Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details', 'licence_fees');

        // Step 1: Aggregate form data by pk (so slot 1 + slot 2 for same row are merged; one update per pk = meter_two_consume_unit never overwritten)
        $byPk = [];
        foreach ($readings as $item) {
            $isSelected = isset($item['selected']) && (string) $item['selected'] === '1';
            if (! $isSelected) {
                continue;
            }
            $rowPk = (int) ($item['pk'] ?? 0);
            if ($rowPk <= 0) {
                continue;
            }
            $meterSlot = (int) ($item['meter_slot'] ?? 1);
            $readingVal = array_key_exists('curr_month_elec_red', $item) ? $item['curr_month_elec_red'] : null;
            $readingNum = ($readingVal !== null && $readingVal !== '') ? (int) $readingVal : null;
            $newMeterNoRaw = isset($item['new_meter_no']) ? trim((string) $item['new_meter_no']) : '';
            $newMeterNo = preg_replace('/\D/', '', $newMeterNoRaw ?? '');

            if (! isset($byPk[$rowPk])) {
                $byPk[$rowPk] = ['curr1' => null, 'curr2' => null, 'meter_one' => '', 'meter_two' => ''];
            }
            if ($meterSlot === 2) {
                $byPk[$rowPk]['curr2'] = $readingNum;
                if ($newMeterNo !== '') {
                    $byPk[$rowPk]['meter_two'] = $newMeterNo;
                }
            } else {
                $byPk[$rowPk]['curr1'] = $readingNum;
                if ($newMeterNo !== '') {
                    $byPk[$rowPk]['meter_one'] = $newMeterNo;
                }
            }
        }

        // Step 2: One fetch + update (or insert when saving a new bill period so prior month rows are not overwritten).
        foreach ($byPk as $formRowPk => $data) {
            $resolvePk = $formRowPk;
            $allowRetargetToTargetPeriodRow = true;
            while (true) {
            $update = [];
            $curr1Form = $data['curr1'];
            $curr2Form = $data['curr2'];
            if ($data['meter_one'] !== '') {
                $update['meter_one'] = $data['meter_one'];
            }
            if ($data['meter_two'] !== '') {
                $update['meter_two'] = $data['meter_two'];
            }
            $rowSelect = [
                'emrd.bill_month',
                'emrd.bill_year',
                'emrd.from_date',
                'emrd.to_date',
                'emrd.last_month_elec_red',
                'emrd.curr_month_elec_red',
                'emrd.last_month_elec_red2',
                'emrd.curr_month_elec_red2',
                'emrd.meter_one_consume_unit',
                'emrd.meter_two_consume_unit',
                'emrd.meter_one_elec_charge',
                'emrd.meter_two_elec_charge',
                'emrd.electricty_charges',
                'emrd.meter_one as emrd_meter_one_before',
                'emrd.meter_two as emrd_meter_two_before',
                'emrd.estate_possession_details_pk',
                'ehm.pk as audit_estate_house_pk',
                'ehm.estate_campus_master_pk as audit_campus_pk',
                'ehm.estate_block_master_pk as audit_block_pk',
                'ehm.estate_unit_sub_type_master_pk as audit_unit_sub_type_pk',
                'ehm.house_no as audit_ehm_house_no',
                'emrd.house_no as audit_emrd_house_no',
                'ehrd.employee_pk as audit_employee_pk',
                'epd.electric_meter_reading as epd_electric_meter_reading',
                $hasSecondaryPossessionReadingCol ? 'epd.electric_meter_reading_2 as epd_electric_meter_reading_2' : \Illuminate\Support\Facades\DB::raw('NULL as epd_electric_meter_reading_2'),
                DB::raw(($hasUnitTypeOnSubType ? 'eust.estate_unit_type_master_pk' : 'ehm.estate_unit_master_pk') . ' as unit_type_pk'),
                DB::raw(($hasUnitTypeOnSubType ? 'eust.estate_unit_type_master_pk' : 'ehm.estate_unit_master_pk') . ' as audit_unit_master_pk'),
            ];
            if ($emrdHasWaterCharges) {
                $rowSelect[] = 'emrd.water_charges';
            }
            if ($emrdHasLicenceFees) {
                $rowSelect[] = 'emrd.licence_fees';
            }
            $row = DB::table('estate_month_reading_details as emrd')
                ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
                ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                ->where('emrd.pk', $resolvePk)
                ->select($rowSelect)
                ->first();

            if (
                $row
                && \Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')
            ) {
                $rh = DB::table('estate_possession_details as epd')
                    ->join('estate_month_reading_details as emrd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
                    ->where('emrd.pk', $resolvePk)
                    ->value('epd.return_home_status');
                if ($rh !== null && (int) $rh !== 0) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'Meter reading cannot be updated for houses that have been returned.');
                }
            }

            if ($row) {
                // Opening reading for units: saved curr when set, else last_month / possession (matches list baseline_min_reading).
                $epdPrimary = (isset($row->epd_electric_meter_reading) && $row->epd_electric_meter_reading !== null && (int) $row->epd_electric_meter_reading > 0) ? (int) $row->epd_electric_meter_reading : null;
                $epdSecondary = (isset($row->epd_electric_meter_reading_2) && $row->epd_electric_meter_reading_2 !== null && (int) $row->epd_electric_meter_reading_2 > 0) ? (int) $row->epd_electric_meter_reading_2 : null;
                $last1Raw = isset($row->last_month_elec_red) ? (int) $row->last_month_elec_red : 0;
                $last2Raw = isset($row->last_month_elec_red2) ? (int) $row->last_month_elec_red2 : 0;
                $prev1 = ($last1Raw > 0) ? $last1Raw : ($epdPrimary ?? $epdSecondary ?? 0);
                $prev2 = ($last2Raw > 0) ? $last2Raw : ($epdSecondary ?? $epdPrimary ?? 0);
                $curr1Existing = $row->curr_month_elec_red !== null ? (int) $row->curr_month_elec_red : null;
                $curr2Existing = $row->curr_month_elec_red2 !== null ? (int) $row->curr_month_elec_red2 : null;

                if ($curr1Form !== null) {
                    $update['curr_month_elec_red'] = $curr1Form;
                    if ($row->curr_month_elec_red !== null) {
                        $update['last_month_elec_red'] = (int) $row->curr_month_elec_red;
                    }
                }
                if ($curr2Form !== null) {
                    $update['curr_month_elec_red2'] = $curr2Form;
                    if ($row->curr_month_elec_red2 !== null) {
                        $update['last_month_elec_red2'] = (int) $row->curr_month_elec_red2;
                    }
                }

                $curr1New = $curr1Form !== null ? $curr1Form : $curr1Existing;
                $curr2New = $curr2Form !== null ? $curr2Form : $curr2Existing;

                $baseline1 = $curr1Existing !== null ? $curr1Existing : $prev1;
                $baseline2 = $curr2Existing !== null ? $curr2Existing : $prev2;

                $u1 = ($curr1New !== null && $curr1New >= $baseline1) ? (int) ($curr1New - $baseline1) : 0;
                $u2 = ($curr2New !== null && $curr2New >= $baseline2) ? (int) ($curr2New - $baseline2) : 0;

                $unitTypePk = isset($row->unit_type_pk) ? (int) $row->unit_type_pk : null;
                $m1Charge = $u1 > 0 ? $this->calculateElectricChargeForUnits($unitTypePk, $u1) : 0.0;
                $m2Charge = $u2 > 0 ? $this->calculateElectricChargeForUnits($unitTypePk, $u2) : 0.0;

                $update['meter_one_consume_unit'] = $u1;
                $update['meter_two_consume_unit'] = $u2;
                $update['meter_one_elec_charge'] = $m1Charge;
                $update['meter_two_elec_charge'] = $m2Charge;
                $update['electricty_charges'] = $m1Charge + $m2Charge;
                if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details', 'per_unit')) {
                    $update['per_unit'] = $u1 + $u2;
                }
            }

            if ($row && $storeBillMonthName && $storeBillYearStr) {
                $storedMonth = (string) ($row->bill_month ?? '');
                $storedYear = (string) ($row->bill_year ?? '');
                $periodChanged = $storedMonth === '' || $storedYear === ''
                    || $storedMonth !== (string) $storeBillMonthName
                    || $storedYear !== (string) $storeBillYearStr;
                $update['bill_month'] = $storeBillMonthName;
                $update['bill_year'] = $storeBillYearStr;
                // New bill period must start un-notified (same row often carries notify=1 from a verified earlier month).
                if ($periodChanged) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details', 'notify_employee_status')) {
                        $update['notify_employee_status'] = 0;
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details', 'process_status')) {
                        $update['process_status'] = 0;
                    }
                }
            }

            if ($currentMeterReadingDate) {
                $update['to_date'] = $currentMeterReadingDate;
                // from_date = first day of selected Meter Change Month (same as other-employee flow);
                // do not use current row's to_date (wrong after retarget to a row with month-end to_date).
                if (! empty($readingBillMonth)) {
                    try {
                        $update['from_date'] = \Carbon\Carbon::createFromFormat('Y-m', (string) $readingBillMonth)
                            ->startOfMonth()
                            ->toDateString();
                    } catch (\Throwable $e) {
                        if ($row && ! empty($row->to_date)) {
                            try {
                                $update['from_date'] = \Carbon\Carbon::parse((string) $row->to_date)
                                    ->addDay()
                                    ->toDateString();
                            } catch (\Throwable $e2) {
                            }
                        }
                    }
                } elseif ($row && ! empty($row->to_date)) {
                    try {
                        $update['from_date'] = \Carbon\Carbon::parse((string) $row->to_date)
                            ->addDay()
                            ->toDateString();
                    } catch (\Throwable $e) {
                    }
                }
            }

            $hasStoredBillPeriod = $row
                && trim((string) ($row->bill_month ?? '')) !== ''
                && trim((string) ($row->bill_year ?? '')) !== '';
            $targetYm = null;
            if (! empty($readingBillMonth)) {
                try {
                    $targetYm = \Carbon\Carbon::createFromFormat('Y-m', (string) $readingBillMonth)->startOfMonth()->format('Y-m');
                } catch (\Throwable $e) {
                    $targetYm = null;
                }
            }
            $rowYm = $row ? $this->meterReadingYmFromRowColumns($row->bill_month, $row->bill_year) : null;
            // Different Meter Change Month than this row → new estate_month_reading_details row (do not overwrite prior month).
            $periodMismatch = $row
                && $storeBillMonthName
                && $storeBillYearStr
                && $hasStoredBillPeriod
                && $targetYm
                && (
                    $rowYm === null
                        ? (
                            (string) $row->bill_month !== (string) $storeBillMonthName
                            || (string) $row->bill_year !== (string) $storeBillYearStr
                        )
                        : ($targetYm !== $rowYm)
                );

            if ($periodMismatch && ! empty($update)) {
                $existingTarget = DB::table('estate_month_reading_details')
                    ->where('estate_possession_details_pk', (int) $row->estate_possession_details_pk)
                    ->where('bill_month', $storeBillMonthName)
                    ->where('bill_year', $storeBillYearStr)
                    ->first();
                if ($existingTarget && $allowRetargetToTargetPeriodRow && (int) $existingTarget->pk !== $resolvePk) {
                    $resolvePk = (int) $existingTarget->pk;
                    $allowRetargetToTargetPeriodRow = false;

                    continue;
                }
                if (! $existingTarget) {
                    $insertData = array_merge(
                        [
                            'estate_possession_details_pk' => (int) $row->estate_possession_details_pk,
                            'created_date' => now(),
                            'house_no' => trim((string) ($row->audit_emrd_house_no ?? '')) !== ''
                                ? $row->audit_emrd_house_no
                                : ($row->audit_ehm_house_no ?? null),
                        ],
                        $update
                    );
                    if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details', 'notify_employee_status')
                        && ! array_key_exists('notify_employee_status', $insertData)) {
                        $insertData['notify_employee_status'] = 0;
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details', 'process_status')
                        && ! array_key_exists('process_status', $insertData)) {
                        $insertData['process_status'] = 0;
                    }
                    if ($emrdHasWaterCharges && ! array_key_exists('water_charges', $insertData) && property_exists($row, 'water_charges')) {
                        $insertData['water_charges'] = $row->water_charges;
                    }
                    if ($emrdHasLicenceFees && ! array_key_exists('licence_fees', $insertData) && property_exists($row, 'licence_fees')) {
                        $insertData['licence_fees'] = $row->licence_fees;
                    }
                    $resolvePk = (int) DB::table('estate_month_reading_details')->insertGetId($insertData);

                    if (
                        $row
                        && Schema::hasTable('estate_update_reading')
                        && (array_key_exists('meter_one', $update) || array_key_exists('meter_two', $update))
                    ) {
                        $beforeM1 = (int) ($row->emrd_meter_one_before ?? 0);
                        $beforeM2 = (int) ($row->emrd_meter_two_before ?? 0);
                        $afterM1 = array_key_exists('meter_one', $update) ? (int) $update['meter_one'] : $beforeM1;
                        $afterM2 = array_key_exists('meter_two', $update) ? (int) $update['meter_two'] : $beforeM2;
                        if ($afterM1 !== $beforeM1 || $afterM2 !== $beforeM2) {
                            $curr1Existing = $row->curr_month_elec_red !== null ? (int) $row->curr_month_elec_red : null;
                            $curr2Existing = $row->curr_month_elec_red2 !== null ? (int) $row->curr_month_elec_red2 : null;
                            $newR1 = array_key_exists('curr_month_elec_red', $update) ? (int) $update['curr_month_elec_red'] : $curr1Existing;
                            $newR2 = array_key_exists('curr_month_elec_red2', $update) ? (int) $update['curr_month_elec_red2'] : $curr2Existing;
                            $billM = $storeBillMonthName !== null && $storeBillMonthName !== '' ? $storeBillMonthName : (string) ($row->bill_month ?? '');
                            $billY = $storeBillYearStr !== null && $storeBillYearStr !== '' ? $storeBillYearStr : (string) ($row->bill_year ?? '');
                            $mcm = trim($billM . ' ' . $billY);
                            $houseForAudit = trim((string) ($row->audit_emrd_house_no ?? ''));
                            if ($houseForAudit === '') {
                                $houseForAudit = trim((string) ($row->audit_ehm_house_no ?? ''));
                            }
                            $this->logEstateMeterNumberChange(
                                'l',
                                (int) $row->estate_possession_details_pk,
                                isset($row->audit_estate_house_pk) ? (int) $row->audit_estate_house_pk : null,
                                isset($row->audit_campus_pk) ? (int) $row->audit_campus_pk : null,
                                isset($row->audit_unit_master_pk) ? (int) $row->audit_unit_master_pk : null,
                                isset($row->audit_block_pk) ? (int) $row->audit_block_pk : null,
                                isset($row->audit_unit_sub_type_pk) ? (int) $row->audit_unit_sub_type_pk : null,
                                $houseForAudit,
                                $mcm,
                                $beforeM1,
                                $beforeM2,
                                $afterM1,
                                $afterM2,
                                $curr1Existing,
                                $curr2Existing,
                                $newR1,
                                $newR2,
                                isset($row->audit_employee_pk) ? (int) $row->audit_employee_pk : null
                            );
                        }
                    }

                    if (
                        $resolvePk > 0
                        && ($lastMeterReadingDate || $currentMeterReadingDate)
                        && ($hasLastMeterDateCol || $hasCurrentMeterDateCol)
                    ) {
                        $possessionPk = (int) $row->estate_possession_details_pk;
                        if ($possessionPk) {
                            $epdUpdate = [];
                            if ($hasLastMeterDateCol && $lastMeterReadingDate) {
                                $epdUpdate['last_meter_reading_date'] = $lastMeterReadingDate;
                            }
                            if ($hasCurrentMeterDateCol && $currentMeterReadingDate) {
                                $epdUpdate['current_meter_reading_date'] = $currentMeterReadingDate;
                            }
                            if (! empty($epdUpdate)) {
                                DB::table('estate_possession_details')
                                    ->where('pk', $possessionPk)
                                    ->update($epdUpdate);
                            }
                        }
                    }

                    $this->syncEstatePossessionElectricReadingsFromEmrdUpdate((int) $row->estate_possession_details_pk, $update);

                    break;
                }
            }

            if (! empty($update)) {
                DB::table('estate_month_reading_details')->where('pk', $resolvePk)->update($update);
            }

            if ($row && ! empty($update)) {
                $this->syncEstatePossessionElectricReadingsFromEmrdUpdate((int) $row->estate_possession_details_pk, $update);
            }

            if (
                $row
                && ! empty($update)
                && Schema::hasTable('estate_update_reading')
                && (array_key_exists('meter_one', $update) || array_key_exists('meter_two', $update))
            ) {
                $beforeM1 = (int) ($row->emrd_meter_one_before ?? 0);
                $beforeM2 = (int) ($row->emrd_meter_two_before ?? 0);
                $afterM1 = array_key_exists('meter_one', $update) ? (int) $update['meter_one'] : $beforeM1;
                $afterM2 = array_key_exists('meter_two', $update) ? (int) $update['meter_two'] : $beforeM2;
                if ($afterM1 !== $beforeM1 || $afterM2 !== $beforeM2) {
                    $curr1Existing = $row->curr_month_elec_red !== null ? (int) $row->curr_month_elec_red : null;
                    $curr2Existing = $row->curr_month_elec_red2 !== null ? (int) $row->curr_month_elec_red2 : null;
                    $newR1 = array_key_exists('curr_month_elec_red', $update) ? (int) $update['curr_month_elec_red'] : $curr1Existing;
                    $newR2 = array_key_exists('curr_month_elec_red2', $update) ? (int) $update['curr_month_elec_red2'] : $curr2Existing;
                    $billM = $storeBillMonthName !== null && $storeBillMonthName !== '' ? $storeBillMonthName : (string) ($row->bill_month ?? '');
                    $billY = $storeBillYearStr !== null && $storeBillYearStr !== '' ? $storeBillYearStr : (string) ($row->bill_year ?? '');
                    $mcm = trim($billM . ' ' . $billY);
                    $houseForAudit = trim((string) ($row->audit_emrd_house_no ?? ''));
                    if ($houseForAudit === '') {
                        $houseForAudit = trim((string) ($row->audit_ehm_house_no ?? ''));
                    }
                    $this->logEstateMeterNumberChange(
                        'l',
                        (int) $row->estate_possession_details_pk,
                        isset($row->audit_estate_house_pk) ? (int) $row->audit_estate_house_pk : null,
                        isset($row->audit_campus_pk) ? (int) $row->audit_campus_pk : null,
                        isset($row->audit_unit_master_pk) ? (int) $row->audit_unit_master_pk : null,
                        isset($row->audit_block_pk) ? (int) $row->audit_block_pk : null,
                        isset($row->audit_unit_sub_type_pk) ? (int) $row->audit_unit_sub_type_pk : null,
                        $houseForAudit,
                        $mcm,
                        $beforeM1,
                        $beforeM2,
                        $afterM1,
                        $afterM2,
                        $curr1Existing,
                        $curr2Existing,
                        $newR1,
                        $newR2,
                        isset($row->audit_employee_pk) ? (int) $row->audit_employee_pk : null
                    );
                }
            }

            if (
                $resolvePk > 0
                && ($lastMeterReadingDate || $currentMeterReadingDate)
                && ($hasLastMeterDateCol || $hasCurrentMeterDateCol)
            ) {
                $possessionPk = DB::table('estate_month_reading_details')
                    ->where('pk', $resolvePk)
                    ->value('estate_possession_details_pk');

                if ($possessionPk) {
                    $epdUpdate = [];
                    if ($hasLastMeterDateCol && $lastMeterReadingDate) {
                        $epdUpdate['last_meter_reading_date'] = $lastMeterReadingDate;
                    }
                    if ($hasCurrentMeterDateCol && $currentMeterReadingDate) {
                        $epdUpdate['current_meter_reading_date'] = $currentMeterReadingDate;
                    }
                    if (!empty($epdUpdate)) {
                        DB::table('estate_possession_details')
                            ->where('pk', (int) $possessionPk)
                            ->update($epdUpdate);
                    }
                }
            }

            break;
            }
        }

        $meterNoQuery = [];
        if (! empty($storeBillYearStr) && ! empty($storeBillMonthName)) {
            try {
                $meterNoQuery['bill_year'] = $storeBillYearStr;
                $meterNoQuery['bill_month'] = (int) \Carbon\Carbon::parse('1 ' . $storeBillMonthName . ' ' . $storeBillYearStr)->format('n');
            } catch (\Throwable $e) {
                $meterNoQuery = [];
            }
        }

        return redirect()
            ->route('admin.estate.update-meter-no', $meterNoQuery)
            ->with('success', 'Meter readings updated successfully.');
    }

    /**
     * Update Meter No. - Listing page (estate_month_reading_details with old/new meter nos and readings).
     */
    public function updateMeterNo()
    {
        return view('admin.estate.update_meter_no');
    }

    /**
     * API: Get Update Meter No. list for DataTable (permanent: estate_month_reading_details; other: estate_month_reading_details_other).
     * Old meter nos fall back to estate_update_reading (type l = permanent, type o = other; FK column stores possession pk).
     * When bill_month/bill_year not provided: show ALL saved readings. When provided: filter by that month only.
     */
    public function getUpdateMeterNoList(Request $request)
    {
        // This endpoint can scan many rows; allow more time in dev/staging.
        @ini_set('max_execution_time', '120');
        @set_time_limit(120);

        $isDataTables = $request->has('draw');
        $draw = (int) $request->get('draw', 0);
        $start = max(0, (int) $request->get('start', 0));
        // DataTables sends length=-1 for "Show All"; must not reset to 10.
        $lengthParam = $request->get('length', 10);
        $length = is_numeric($lengthParam) ? (int) $lengthParam : 10;
        $showAllRows = ($length === -1);
        if (! $showAllRows && $length <= 0) {
            $length = 10;
        }
        $searchValue = trim((string) data_get($request->all(), 'search.value', ''));

        $billMonth = $request->get('bill_month');
        $billYear = $request->get('bill_year');
        $filterByMonth = $billMonth && $billYear;
        if ($filterByMonth) {
            $billYear = (string) $billYear;
            if (is_numeric($billMonth) && (int) $billMonth >= 1 && (int) $billMonth <= 12) {
                $billMonth = date('F', mktime(0, 0, 0, (int) $billMonth, 1));
            }
        }

        $hasUnitTypeOnSubType = Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
        $hasEpdReading2 = Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2');
        $hasEpdReading1 = Schema::hasColumn('estate_possession_details', 'electric_meter_reading');
        $hasEpoReading1 = Schema::hasColumn('estate_possession_other', 'meter_reading_oth');
        $hasEpoReading2 = Schema::hasColumn('estate_possession_other', 'meter_reading_oth1');
        $hasEurTable = Schema::hasTable('estate_update_reading');

        $canSeeAllUpdateMeterNo = hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin')
            || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST');

        $eurLatestPermSub = '(SELECT eur.* FROM estate_update_reading eur INNER JOIN (
            SELECT estate_possession_details_pk, meter_change_month, MAX(pk) AS max_pk
            FROM estate_update_reading
            WHERE LOWER(TRIM(COALESCE(type, \'\'))) = \'l\'
            GROUP BY estate_possession_details_pk, meter_change_month
        ) z ON eur.pk = z.max_pk)';

        $eurLatestOtherSub = '(SELECT eur.* FROM estate_update_reading eur INNER JOIN (
            SELECT estate_possession_details_pk, meter_change_month, MAX(pk) AS max_pk
            FROM estate_update_reading
            WHERE LOWER(TRIM(COALESCE(type, \'\'))) = \'o\'
            GROUP BY estate_possession_details_pk, meter_change_month
        ) z ON eur.pk = z.max_pk)';

        // LAG previous meter no. per possession (permanent).
        $emrdSub = DB::table('estate_month_reading_details as emrd')
            ->select([
                'emrd.pk',
                'emrd.estate_possession_details_pk',
                'emrd.house_no',
                'emrd.to_date',
                'emrd.bill_month',
                'emrd.bill_year',
                'emrd.meter_one as emrd_meter_one',
                'emrd.meter_two as emrd_meter_two',
                'emrd.last_month_elec_red',
                'emrd.curr_month_elec_red',
                'emrd.last_month_elec_red2',
                'emrd.curr_month_elec_red2',
                DB::raw('LAG(emrd.meter_one) OVER (PARTITION BY emrd.estate_possession_details_pk ORDER BY emrd.to_date, emrd.pk) as prev_meter_one'),
                DB::raw('LAG(emrd.meter_two) OVER (PARTITION BY emrd.estate_possession_details_pk ORDER BY emrd.to_date, emrd.pk) as prev_meter_two'),
            ]);
        if ($filterByMonth) {
            $emrdSub->where('emrd.bill_month', $billMonth)->where('emrd.bill_year', $billYear);
        }

        $permBase = DB::query()
            ->fromSub($emrdSub, 'emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->leftJoin('estate_block_master as b', 'ehm.estate_block_master_pk', '=', 'b.pk')
            ->leftJoin('estate_unit_sub_type_master as ust', 'ehm.estate_unit_sub_type_master_pk', '=', 'ust.pk')
            ->when($hasUnitTypeOnSubType, function ($q) {
                $q->leftJoin('estate_unit_type_master as ut', 'ust.estate_unit_type_master_pk', '=', 'ut.pk');
            }, function ($q) {
                $q->leftJoin('estate_unit_type_master as ut', 'ehm.estate_unit_master_pk', '=', 'ut.pk');
            })
            ->leftJoin('employee_master as em', function ($join) {
                if (Schema::hasColumn('employee_master', 'pk_old')) {
                    $join->whereRaw('(ehrd.employee_pk = em.pk OR ehrd.employee_pk = em.pk_old)');
                } else {
                    $join->on('ehrd.employee_pk', '=', 'em.pk');
                }
            })
            ->leftJoin('employee_type_master as etm', 'em.emp_type', '=', 'etm.pk')
            ->whereNotNull('epd.estate_house_master_pk');

        if ($hasEurTable) {
            $permBase->leftJoin(DB::raw($eurLatestPermSub . ' AS eur_hist'), function ($join) {
                $join->on('eur_hist.estate_possession_details_pk', '=', 'emrd.estate_possession_details_pk')
                    ->whereRaw("TRIM(COALESCE(eur_hist.meter_change_month, '')) = TRIM(CONCAT(IFNULL(emrd.bill_month, ''), ' ', IFNULL(emrd.bill_year, '')))");
            });
        }

        if (! $canSeeAllUpdateMeterNo) {
            $user = Auth::user();
            if ($user) {
                $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
                if (! empty($employeeIds)) {
                    $allowPks = array_values(array_unique(array_merge(
                        $employeeIds,
                        Schema::hasColumn('employee_master', 'pk_old')
                            ? DB::table('employee_master')->whereIn('pk', $employeeIds)->whereNotNull('pk_old')->pluck('pk_old')->map(fn ($v) => (int) $v)->all()
                            : []
                    )));
                    $permBase->whereIn('ehrd.employee_pk', $allowPks);
                } else {
                    $permBase->whereRaw('1 = 0');
                }
            } else {
                $permBase->whereRaw('1 = 0');
            }
        }

        // Other (estate_month_reading_details_other): same LAG pattern; estate_update_reading.type = o links via estate_possession_other.pk.
        $emroSub = DB::table('estate_month_reading_details_other as emro')
            ->select([
                'emro.pk',
                'emro.estate_possession_other_pk',
                'emro.house_no',
                'emro.to_date',
                'emro.bill_month',
                'emro.bill_year',
                'emro.meter_one as emrd_meter_one',
                'emro.meter_two as emrd_meter_two',
                'emro.last_month_elec_red',
                'emro.curr_month_elec_red',
                'emro.last_month_elec_red2',
                'emro.curr_month_elec_red2',
                DB::raw('LAG(emro.meter_one) OVER (PARTITION BY emro.estate_possession_other_pk ORDER BY emro.to_date, emro.pk) as prev_meter_one'),
                DB::raw('LAG(emro.meter_two) OVER (PARTITION BY emro.estate_possession_other_pk ORDER BY emro.to_date, emro.pk) as prev_meter_two'),
            ]);
        if ($filterByMonth) {
            $emroSub->where('emro.bill_month', $billMonth)->where('emro.bill_year', $billYear);
        }

        $otherBase = DB::query()
            ->fromSub($emroSub, 'emro')
            ->join('estate_possession_other as epo', 'emro.estate_possession_other_pk', '=', 'epo.pk')
            ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
            ->leftJoin('estate_house_master as eohm', 'epo.estate_house_master_pk', '=', 'eohm.pk')
            ->leftJoin('estate_block_master as bob', 'eohm.estate_block_master_pk', '=', 'bob.pk')
            ->leftJoin('estate_unit_sub_type_master as oust', 'eohm.estate_unit_sub_type_master_pk', '=', 'oust.pk')
            ->when($hasUnitTypeOnSubType, function ($q) {
                $q->leftJoin('estate_unit_type_master as out', 'oust.estate_unit_type_master_pk', '=', 'out.pk');
            }, function ($q) {
                $q->leftJoin('estate_unit_type_master as out', 'eohm.estate_unit_master_pk', '=', 'out.pk');
            })
            ->whereNotNull('epo.estate_house_master_pk');

        if (Schema::hasColumn('estate_possession_other', 'return_home_status')) {
            $otherBase->where(function ($inner) {
                $inner->whereNull('epo.return_home_status')->orWhere('epo.return_home_status', 0);
            });
        }

        if ($hasEurTable) {
            $otherBase->leftJoin(DB::raw($eurLatestOtherSub . ' AS eur_hist_o'), function ($join) {
                $join->on('eur_hist_o.estate_possession_details_pk', '=', 'emro.estate_possession_other_pk')
                    ->whereRaw("TRIM(COALESCE(eur_hist_o.meter_change_month, '')) = TRIM(CONCAT(IFNULL(emro.bill_month, ''), ' ', IFNULL(emro.bill_year, '')))");
            });
        }

        if (! $canSeeAllUpdateMeterNo) {
            $otherBase->whereRaw('1 = 0');
        }

        $unionColsPerm = [
            'emrd.pk as reading_pk',
            DB::raw("'p' as src_kind"),
            'emrd.bill_month',
            'emrd.bill_year',
            'emrd.house_no',
            'emrd.to_date',
            'emrd.emrd_meter_one',
            'emrd.emrd_meter_two',
            'emrd.prev_meter_one',
            'emrd.prev_meter_two',
            'emrd.last_month_elec_red',
            'emrd.curr_month_elec_red',
            'emrd.last_month_elec_red2',
            'emrd.curr_month_elec_red2',
            'ehrd.emp_name',
            'etm.category_type_name as employee_type',
            'ut.unit_type',
            'ust.unit_sub_type',
            'b.block_name as building_name',
        ];
        if ($hasEurTable) {
            $unionColsPerm[] = 'eur_hist.old_meter_no_one as eur_old_meter_one';
            $unionColsPerm[] = 'eur_hist.old_meter_no_two as eur_old_meter_two';
            $unionColsPerm[] = 'eur_hist.old_meter_reading_one as eur_old_reading_one';
            $unionColsPerm[] = 'eur_hist.old_meter_reading_two as eur_old_reading_two';
        } else {
            $unionColsPerm[] = DB::raw('NULL as eur_old_meter_one');
            $unionColsPerm[] = DB::raw('NULL as eur_old_meter_two');
            $unionColsPerm[] = DB::raw('NULL as eur_old_reading_one');
            $unionColsPerm[] = DB::raw('NULL as eur_old_reading_two');
        }
        $unionColsPerm[] = $hasEpdReading1 ? 'epd.electric_meter_reading as poss_reading1' : DB::raw('NULL as poss_reading1');
        $unionColsPerm[] = $hasEpdReading2 ? 'epd.electric_meter_reading_2 as poss_reading2' : DB::raw('NULL as poss_reading2');

        $unionColsOther = [
            'emro.pk as reading_pk',
            DB::raw("'o' as src_kind"),
            'emro.bill_month',
            'emro.bill_year',
            'emro.house_no',
            'emro.to_date',
            'emro.emrd_meter_one',
            'emro.emrd_meter_two',
            'emro.prev_meter_one',
            'emro.prev_meter_two',
            'emro.last_month_elec_red',
            'emro.curr_month_elec_red',
            'emro.last_month_elec_red2',
            'emro.curr_month_elec_red2',
            'eor.emp_name',
            'eor.designation as employee_type',
            'out.unit_type',
            'oust.unit_sub_type',
            'bob.block_name as building_name',
        ];
        if ($hasEurTable) {
            $unionColsOther[] = 'eur_hist_o.old_meter_no_one as eur_old_meter_one';
            $unionColsOther[] = 'eur_hist_o.old_meter_no_two as eur_old_meter_two';
            $unionColsOther[] = 'eur_hist_o.old_meter_reading_one as eur_old_reading_one';
            $unionColsOther[] = 'eur_hist_o.old_meter_reading_two as eur_old_reading_two';
        } else {
            $unionColsOther[] = DB::raw('NULL as eur_old_meter_one');
            $unionColsOther[] = DB::raw('NULL as eur_old_meter_two');
            $unionColsOther[] = DB::raw('NULL as eur_old_reading_one');
            $unionColsOther[] = DB::raw('NULL as eur_old_reading_two');
        }
        $unionColsOther[] = $hasEpoReading1 ? 'epo.meter_reading_oth as poss_reading1' : DB::raw('NULL as poss_reading1');
        $unionColsOther[] = $hasEpoReading2 ? 'epo.meter_reading_oth1 as poss_reading2' : DB::raw('NULL as poss_reading2');

        $unionQuery = (clone $permBase)->select($unionColsPerm)->unionAll((clone $otherBase)->select($unionColsOther));

        if ($isDataTables) {
            $countSub = DB::query()->fromSub(clone $unionQuery, 'umc');
            $recordsTotal = (int) $countSub->count();

            $filteredSub = DB::query()->fromSub(clone $unionQuery, 'um');
            if ($searchValue !== '') {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchValue) . '%';
                $filteredSub->where(function ($q) use ($like) {
                    $q->where('um.emp_name', 'like', $like)
                        ->orWhere('um.building_name', 'like', $like)
                        ->orWhere('um.house_no', 'like', $like)
                        ->orWhere('um.unit_type', 'like', $like)
                        ->orWhere('um.unit_sub_type', 'like', $like)
                        ->orWhere('um.employee_type', 'like', $like);
                });
            }

            if ($searchValue === '') {
                $recordsFiltered = $recordsTotal;
            } else {
                $recordsFiltered = (int) (clone $filteredSub)->count();
            }

            $orderCol = (int) data_get($request->all(), 'order.0.column', 0);
            $orderDir = strtolower((string) data_get($request->all(), 'order.0.dir', 'desc')) === 'desc' ? 'desc' : 'asc';
            $orderMap = [
                0 => 'um.reading_pk',
                1 => 'um.emp_name',
                2 => 'um.employee_type',
                3 => 'um.unit_type',
                4 => 'um.unit_sub_type',
                5 => 'um.building_name',
                6 => 'um.house_no',
                7 => 'um.reading_pk',
            ];
            $orderBy = $orderMap[$orderCol] ?? 'um.reading_pk';
            if ($orderCol === 0 && $orderDir === 'desc') {
                $filteredSub->orderByRaw('um.to_date IS NULL ASC')
                    ->orderByDesc('um.to_date')
                    ->orderByDesc('um.reading_pk')
                    ->orderBy('um.src_kind');
            } else {
                $filteredSub->orderBy($orderBy, $orderDir)->orderBy('um.reading_pk', 'desc')->orderBy('um.src_kind');
            }

            // MySQL rejects OFFSET without LIMIT; "show all" must still use LIMIT (use exact remainder count).
            if ($showAllRows) {
                $take = max(0, $recordsFiltered - $start);
                $rows = $take > 0
                    ? $filteredSub->offset($start)->limit($take)->get()
                    : collect();
            } else {
                $rows = $filteredSub->offset($start)->limit($length)->get();
            }

            $data = [];
            $sno = $start + 1;
            foreach ($rows as $r) {
                $row = $this->mapUpdateMeterNoListRow($r);
                $row['sn'] = $sno++;
                $data[] = $row;
            }

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ]);
        }

        $legacyOuter = DB::query()->fromSub(clone $unionQuery, 'um')
            ->orderByDesc('um.bill_year')
            ->orderByRaw("FIELD(um.bill_month, 'December','November','October','September','August','July','June','May','April','March','February','January')")
            ->orderBy('um.building_name')
            ->orderBy('um.house_no');

        $data = [];
        $sno = 1;
        foreach ($legacyOuter->get() as $r) {
            $row = $this->mapUpdateMeterNoListRow($r);
            $row['sn'] = $sno++;
            $data[] = $row;
        }

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * API: Get meter reading list for "Update Meter Reading of Other" (filtered).
     * Selected month OR legacy previous-month rows (same dual rule as permanent).
     * List payload: last_month_reading shows curr_month_elec_red (fallback last_month_elec_red if curr empty);
     * curr_month_reading is always null so the current-month input stays blank until the user enters a value.
     */
    public function getMeterReadingListOther(Request $request)
    {
        $billMonth = $request->get('bill_month');
        $billYear = $request->get('bill_year');
        $campusId = $request->get('campus_id');
        $blockId = $request->get('block_id');
        $unitTypeId = $request->get('unit_type_id');
        $unitSubTypeId = $request->get('unit_sub_type_id');

        // Same as regular estate meter reading: unit type filter uses eligibility sub-types when mapped.
        $unitSubTypeIdsForUnitType = null;
        if ($unitTypeId) {
            $unitSubTypeIdsForUnitType = DB::table('estate_eligibility_mapping')
                ->where('estate_unit_type_master_pk', $unitTypeId)
                ->whereNotNull('estate_unit_sub_type_master_pk')
                ->distinct()
                ->pluck('estate_unit_sub_type_master_pk')
                ->filter()
                ->values()
                ->toArray();
        }

        [$probeM, $probeY] = $this->meterReadingParseUiMonthYear($billMonth, $billYear);
        if ($probeM === null || $probeY === null) {
            return response()->json(['status' => true, 'data' => []]);
        }

        $possessionPksParamEarly = $request->get('possession_pks');
        $possessionIdsScoped = [];
        if ($possessionPksParamEarly !== null && $possessionPksParamEarly !== '') {
            $possessionIdsScoped = is_array($possessionPksParamEarly)
                ? array_map('intval', $possessionPksParamEarly)
                : array_map('intval', array_filter(explode(',', (string) $possessionPksParamEarly)));
            $possessionIdsScoped = array_values(array_unique(array_filter($possessionIdsScoped, fn ($id) => $id > 0)));
        }

        $readingPkParamOther = $request->get('reading_pk');
        $readingPkScopedOther = null;
        if ($readingPkParamOther !== null && $readingPkParamOther !== '' && is_numeric($readingPkParamOther)) {
            $readingPkScopedOther = (int) $readingPkParamOther;
            if ($readingPkScopedOther <= 0) {
                $readingPkScopedOther = null;
            }
        }

        $query = EstateMonthReadingDetailsOther::query()
            ->select([
                'estate_month_reading_details_other.pk',
                'estate_month_reading_details_other.estate_possession_other_pk',
                'estate_month_reading_details_other.from_date',
                'estate_month_reading_details_other.to_date',
                'estate_month_reading_details_other.last_month_elec_red',
                'estate_month_reading_details_other.curr_month_elec_red',
                'estate_month_reading_details_other.last_month_elec_red2',
                'estate_month_reading_details_other.curr_month_elec_red2',
                'estate_month_reading_details_other.house_no',
                'estate_month_reading_details_other.meter_one as emro_meter_one',
                'estate_month_reading_details_other.meter_two as emro_meter_two',
                'ehm.meter_one as ehm_meter_one',
                'ehm.meter_two as ehm_meter_two',
            ])
            ->join('estate_possession_other as epo', 'estate_month_reading_details_other.estate_possession_other_pk', '=', 'epo.pk')
            ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
            ->leftJoin('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
            ->orderBy('estate_month_reading_details_other.house_no');

        if ($readingPkScopedOther !== null) {
            $query->where('estate_month_reading_details_other.pk', $readingPkScopedOther);
            if ($possessionIdsScoped !== []) {
                $query->whereIn('epo.pk', $possessionIdsScoped);
            }
        } else {
            $this->applyMeterReadingListBillPeriodOrLegacyOther(
                $query,
                'estate_month_reading_details_other.bill_month',
                'estate_month_reading_details_other.bill_year',
                $billMonth,
                $billYear
            );
            if ($possessionIdsScoped === []) {
                $this->applyMeterReadingExcludeReadingDateInUiMonth(
                    $query,
                    'estate_month_reading_details_other.to_date',
                    'estate_month_reading_details_other.from_date',
                    $billMonth,
                    $billYear
                );
                $this->applyMeterReadingExcludePossessionIfAnyReadingDateInUiMonth($query, $billMonth, $billYear, 'epo.pk', 'other');
            }
        }

        if ($readingPkScopedOther === null) {
            if ($campusId) {
                $query->where('epo.estate_campus_master_pk', $campusId);
            }
            if ($blockId) {
                $query->where('epo.estate_block_master_pk', $blockId);
            }
            if ($unitTypeId && ! empty($unitSubTypeIdsForUnitType)) {
                $query->whereIn('epo.estate_unit_sub_type_master_pk', $unitSubTypeIdsForUnitType);
            } elseif ($unitTypeId) {
                $query->where('epo.estate_unit_type_master_pk', $unitTypeId);
            }
            if ($unitSubTypeId) {
                $query->where('epo.estate_unit_sub_type_master_pk', $unitSubTypeId);
            }
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_other', 'return_home_status')) {
            $query->where(function ($inner) {
                $inner->whereNull('epo.return_home_status')
                    ->orWhere('epo.return_home_status', 0);
            });
        }

        if ($possessionIdsScoped !== []) {
            $query->whereIn('epo.pk', $possessionIdsScoped);
        }

        $query->with('estatePossessionOther.estateOtherRequest');

        $rows = collect();
        foreach ($query->get() as $row) {
            $poss = $row->estatePossessionOther;
            $req = $poss ? $poss->estateOtherRequest : null;
            $name = $req ? ($req->emp_name ?? 'N/A') : 'N/A';
            // Show period end date as "Last Month Electric Reading Date" (e.g. 28/02/2026 for February row).
            $lastReadingDate = $row->to_date ? $row->to_date->format('d/m/Y') : ($row->from_date ? $row->from_date->format('d/m/Y') : 'N/A');

            // Match regular "Update Meter Reading": use define-house meters when month-reading row has null/0.
            $meterOne = $row->emro_meter_one ?? $row->ehm_meter_one;
            $meterTwo = $row->emro_meter_two ?? $row->ehm_meter_two;
            $hasMeterOne = $meterOne !== null && $meterOne !== '' && (int) $meterOne !== 0;
            $hasMeterTwo = $meterTwo !== null && $meterTwo !== '' && (int) $meterTwo !== 0;

            $displayLast1 = null;
            if ($hasMeterOne) {
                if ($row->curr_month_elec_red !== null && $row->curr_month_elec_red !== '') {
                    $displayLast1 = (int) $row->curr_month_elec_red;
                } elseif ($row->last_month_elec_red !== null && $row->last_month_elec_red !== '') {
                    $displayLast1 = (int) $row->last_month_elec_red;
                }
            }
            $displayLast2 = null;
            if ($hasMeterTwo) {
                if ($row->curr_month_elec_red2 !== null && $row->curr_month_elec_red2 !== '') {
                    $displayLast2 = (int) $row->curr_month_elec_red2;
                } elseif ($row->last_month_elec_red2 !== null && $row->last_month_elec_red2 !== '') {
                    $displayLast2 = (int) $row->last_month_elec_red2;
                }
            }

            // One list row for houses with two meters (UI); save still posts two readings[] entries (meter_slot 1 & 2).
            if ($hasMeterOne && $hasMeterTwo) {
                $newNo1 = $row->emro_meter_one !== null && $row->emro_meter_one !== '' ? (string) $row->emro_meter_one : (string) $meterOne;
                $newNo2 = $row->emro_meter_two !== null && $row->emro_meter_two !== '' ? (string) $row->emro_meter_two : (string) $meterTwo;
                $rows->push([
                    'pk' => $row->pk,
                    'dual_meter' => true,
                    'house_no' => $row->house_no ?? 'N/A',
                    'name' => $name,
                    'last_reading_date' => $lastReadingDate,
                    'm1' => [
                        'meter_slot' => 1,
                        'old_meter_no' => (string) $meterOne,
                        'new_meter_no' => $newNo1,
                        'last_month_reading' => $displayLast1 !== null ? $displayLast1 : 'N/A',
                    ],
                    'm2' => [
                        'meter_slot' => 2,
                        'old_meter_no' => (string) $meterTwo,
                        'new_meter_no' => $newNo2,
                        'last_month_reading' => $displayLast2 !== null ? $displayLast2 : 'N/A',
                    ],
                ]);
            } elseif ($hasMeterOne) {
                $rows->push([
                    'pk' => $row->pk,
                    'dual_meter' => false,
                    'meter_slot' => 1,
                    'house_no' => $row->house_no ?? 'N/A',
                    'name' => $name,
                    'last_reading_date' => $lastReadingDate,
                    'meter_no' => (string) $meterOne,
                    'old_meter_no' => (string) $meterOne,
                    'new_meter_no' => $row->emro_meter_one !== null && $row->emro_meter_one !== '' ? (string) $row->emro_meter_one : (string) $meterOne,
                    'last_month_reading' => $displayLast1 !== null ? $displayLast1 : 'N/A',
                    'curr_month_reading' => null,
                    'unit' => 'N/A',
                ]);
            } elseif ($hasMeterTwo) {
                $rows->push([
                    'pk' => $row->pk,
                    'dual_meter' => false,
                    'meter_slot' => 2,
                    'house_no' => $row->house_no ?? 'N/A',
                    'name' => $name,
                    'last_reading_date' => $lastReadingDate,
                    'meter_no' => (string) $meterTwo,
                    'old_meter_no' => (string) $meterTwo,
                    'new_meter_no' => $row->emro_meter_two !== null && $row->emro_meter_two !== '' ? (string) $row->emro_meter_two : (string) $meterTwo,
                    'last_month_reading' => $displayLast2 !== null ? $displayLast2 : 'N/A',
                    'curr_month_reading' => null,
                    'unit' => 'N/A',
                ]);
            }

            // Fallback: no valid meter numbers, keep previous behaviour (single combined row).
            if (! $hasMeterOne && ! $hasMeterTwo) {
                $displayLast = null;
                if ($row->curr_month_elec_red !== null && $row->curr_month_elec_red !== '') {
                    $displayLast = (int) $row->curr_month_elec_red;
                } elseif ($row->last_month_elec_red !== null && $row->last_month_elec_red !== '') {
                    $displayLast = (int) $row->last_month_elec_red;
                }

                $fallbackMeter = $meterOne ?? $meterTwo ?? 'N/A';
                $rows->push([
                    'pk' => $row->pk,
                    'dual_meter' => false,
                    'meter_slot' => 1,
                    'house_no' => $row->house_no ?? 'N/A',
                    'name' => $name,
                    'last_reading_date' => $lastReadingDate,
                    'meter_no' => $fallbackMeter,
                    'old_meter_no' => (string) $fallbackMeter,
                    'new_meter_no' => '',
                    'last_month_reading' => $displayLast !== null ? $displayLast : 'N/A',
                    'curr_month_reading' => null,
                    'unit' => 'N/A',
                ]);
            }
        }

        return response()->json(['status' => true, 'data' => $rows->values()]);
    }

    /**
     * API: Get blocks for meter reading filter (by campus).
     */
    public function getMeterReadingBlocksOther(Request $request)
    {
        $campusId = $request->get('campus_id');
        if (!$campusId) {
            return response()->json(['status' => true, 'data' => []]);
        }
        $q = DB::table('estate_possession_other as epo')
            ->join('estate_block_master as b', 'epo.estate_block_master_pk', '=', 'b.pk')
            ->where('epo.estate_campus_master_pk', $campusId);
        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_other', 'return_home_status')) {
            $q->where(function ($inner) {
                $inner->whereNull('epo.return_home_status')
                    ->orWhere('epo.return_home_status', 0);
            });
        }
        $blocks = $q->select('b.pk', 'b.block_name')
            ->distinct()
            ->orderBy('b.block_name')
            ->get();
        return response()->json(['status' => true, 'data' => $blocks]);
    }

    /**
     * API: Get meter reading dates for selected bill month (Other).
     */
    public function getMeterReadingDatesOther(Request $request)
    {
        $billMonth = $request->get('bill_month');
        $billYear = $request->get('bill_year');
        [$probeM, $probeY] = $this->meterReadingParseUiMonthYear($billMonth, $billYear);
        if ($probeM === null || $probeY === null) {
            return response()->json(['status' => true, 'data' => []]);
        }
        $datesQuery = EstateMonthReadingDetailsOther::query();
        $this->applyMeterReadingListBillPeriodOrLegacyOther(
            $datesQuery,
            'bill_month',
            'bill_year',
            $billMonth,
            $billYear
        );
        $this->applyMeterReadingExcludeReadingDateInUiMonth(
            $datesQuery,
            'estate_month_reading_details_other.to_date',
            'estate_month_reading_details_other.from_date',
            $billMonth,
            $billYear
        );
        $this->applyMeterReadingExcludePossessionIfAnyReadingDateInUiMonth(
            $datesQuery,
            $billMonth,
            $billYear,
            'estate_month_reading_details_other.estate_possession_other_pk',
            'other'
        );
        $dates = $datesQuery->select('to_date')
            ->distinct()
            ->orderBy('to_date')
            ->get()
            ->map(fn($r) => ['value' => $r->to_date->format('Y-m-d'), 'label' => $r->to_date->format('d/m/Y')]);
        return response()->json(['status' => true, 'data' => $dates]);
    }

    /**
     * Parse UI Meter Change / bill month from the request (1–12 or English name + year).
     *
     * @return array{0: ?int, 1: ?int} [month 1–12, year] or [null, null]
     */
    private function meterReadingParseUiMonthYear($billMonth, $billYear): array
    {
        $y = is_numeric($billYear) ? (int) $billYear : null;
        if ($y === null || $y < 1) {
            return [null, null];
        }
        $m = null;
        if (is_numeric($billMonth)) {
            $mn = (int) $billMonth;
            $m = ($mn >= 1 && $mn <= 12) ? $mn : null;
        } else {
            $s = trim((string) $billMonth);
            if ($s === '') {
                return [null, null];
            }
            try {
                $m = (int) \Carbon\Carbon::parse('15 ' . $s . ' ' . $y)->month;
            } catch (\Throwable $e) {
                $m = null;
            }
        }
        if ($m === null || $m < 1 || $m > 12) {
            return [null, null];
        }

        return [$m, $y];
    }

    /**
     * Exclude rows where COALESCE(to_date, from_date) lies in the selected UI calendar month (Meter Change Month).
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    private function applyMeterReadingExcludeReadingDateInUiMonth($query, string $toDateColumn, string $fromDateColumn, $billMonth, $billYear): void
    {
        [$uiM, $uiY] = $this->meterReadingParseUiMonthYear($billMonth, $billYear);
        if ($uiM === null || $uiY === null) {
            return;
        }
        $startS = \Carbon\Carbon::createFromDate($uiY, $uiM, 1)->toDateString();
        $endS = \Carbon\Carbon::createFromDate($uiY, $uiM, 1)->endOfMonth()->toDateString();
        $coalesce = 'COALESCE(' . $toDateColumn . ', ' . $fromDateColumn . ')';
        $query->where(function ($q) use ($coalesce, $startS, $endS) {
            $q->whereRaw($coalesce . ' IS NULL')
                ->orWhereRaw('DATE(' . $coalesce . ') < ?', [$startS])
                ->orWhereRaw('DATE(' . $coalesce . ') > ?', [$endS]);
        });
    }

    /**
     * Drop the whole possession from the list when any month-reading row for that possession already has a
     * reading date in the selected UI month. Needed because bill_period_or_legacy can match an older period
     * row (e.g. February) while a newer row (March) already completed the same meter-change month.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  string  $outerPossessionRef  Outer qualified column (e.g. epd.pk, estate_month_reading_details.estate_possession_details_pk)
     * @param  'regular'|'other'  $kind
     */
    private function applyMeterReadingExcludePossessionIfAnyReadingDateInUiMonth(
        $query,
        $billMonth,
        $billYear,
        string $outerPossessionRef,
        string $kind
    ): void {
        [$uiM, $uiY] = $this->meterReadingParseUiMonthYear($billMonth, $billYear);
        if ($uiM === null || $uiY === null) {
            return;
        }
        $startS = \Carbon\Carbon::createFromDate($uiY, $uiM, 1)->toDateString();
        $endS = \Carbon\Carbon::createFromDate($uiY, $uiM, 1)->endOfMonth()->toDateString();

        if ($kind === 'other') {
            $query->whereNotExists(function ($sub) use ($startS, $endS, $outerPossessionRef) {
                $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('estate_month_reading_details_other as emro_any')
                    ->whereColumn('emro_any.estate_possession_other_pk', $outerPossessionRef)
                    ->whereRaw('DATE(COALESCE(emro_any.to_date, emro_any.from_date)) BETWEEN ? AND ?', [$startS, $endS]);
            });

            return;
        }

        $query->whereNotExists(function ($sub) use ($startS, $endS, $outerPossessionRef) {
            $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                ->from('estate_month_reading_details as emrd_any')
                ->whereColumn('emrd_any.estate_possession_details_pk', $outerPossessionRef)
                ->whereRaw('DATE(COALESCE(emrd_any.to_date, emrd_any.from_date)) BETWEEN ? AND ?', [$startS, $endS]);
        });
    }

    /**
     * Update Meter Reading list: rows may be stored as the selected Meter Change Month (new) or the previous
     * calendar month (legacy). Match either so Load Data works for both.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    private function applyMeterReadingListBillPeriodOrLegacy($query, string $billMonthColumn, string $billYearColumn, $billMonth, $billYear): void
    {
        [$uiM, $uiY] = $this->meterReadingParseUiMonthYear($billMonth, $billYear);
        if ($uiM === null || $uiY === null) {
            $query->whereRaw('1 = 0');

            return;
        }
        $uiBillMonthName = date('F', mktime(0, 0, 0, $uiM, 1));
        $uiYearStr = (string) $uiY;
        [$dataM, $dataY] = $this->meterReadingUiToDataPeriod($uiM, $uiY);
        $dataBillMonthName = ($dataM !== null && $dataY !== null)
            ? date('F', mktime(0, 0, 0, $dataM, 1))
            : null;
        $dataYearStr = $dataY !== null ? (string) $dataY : null;

        $query->where(function ($q) use ($billMonthColumn, $billYearColumn, $uiBillMonthName, $uiYearStr, $dataBillMonthName, $dataYearStr) {
            $q->where(function ($q2) use ($billMonthColumn, $billYearColumn, $uiBillMonthName, $uiYearStr) {
                $q2->where($billMonthColumn, $uiBillMonthName)->where($billYearColumn, $uiYearStr);
            });
            if ($dataBillMonthName !== null && $dataYearStr !== null
                && ! ($dataBillMonthName === $uiBillMonthName && $dataYearStr === $uiYearStr)) {
                $q->orWhere(function ($q2) use ($billMonthColumn, $billYearColumn, $dataBillMonthName, $dataYearStr) {
                    $q2->where($billMonthColumn, $dataBillMonthName)->where($billYearColumn, $dataYearStr);
                });
            }
        });
    }

    /**
     * Same dual match for estate_month_reading_details_other (normalized bill_month).
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    private function applyMeterReadingListBillPeriodOrLegacyOther($query, string $billMonthColumn, string $billYearColumn, $billMonth, $billYear): void
    {
        [$uiM, $uiY] = $this->meterReadingParseUiMonthYear($billMonth, $billYear);
        if ($uiM === null || $uiY === null) {
            $query->whereRaw('1 = 0');

            return;
        }
        $uiStr = $this->normalizeBillMonthForOther((string) $uiM);
        $uiYearStr = (string) $uiY;
        [$dataM, $dataY] = $this->meterReadingUiToDataPeriod($uiM, $uiY);
        $dataStr = ($dataM !== null && $dataY !== null)
            ? $this->normalizeBillMonthForOther((string) $dataM)
            : null;
        $dataYearStr = $dataY !== null ? (string) $dataY : null;

        $query->where(function ($q) use ($billMonthColumn, $billYearColumn, $uiStr, $uiYearStr, $dataStr, $dataYearStr) {
            $q->where(function ($q2) use ($billMonthColumn, $billYearColumn, $uiStr, $uiYearStr) {
                $q2->where($billMonthColumn, $uiStr)->where($billYearColumn, $uiYearStr);
            });
            if ($dataStr !== null && $dataYearStr !== null
                && ! ($dataStr === $uiStr && $dataYearStr === $uiYearStr)) {
                $q->orWhere(function ($q2) use ($billMonthColumn, $billYearColumn, $dataStr, $dataYearStr) {
                    $q2->where($billMonthColumn, $dataStr)->where($billYearColumn, $dataYearStr);
                });
            }
        });
    }

    /**
     * Effective "last month" baseline for meter-reading validation — must match getMeterReadingList()
     * (possession fallbacks when last_month_elec_red is empty or zero).
     */
    private function effectiveLastMonthElecBaselineForMeterSlot(
        $lastMonthRaw,
        $epdElectricPrimary,
        $epdElectricSecondary,
        int $meterSlot
    ): int {
        if ($lastMonthRaw !== null && $lastMonthRaw !== '' && (int) $lastMonthRaw > 0) {
            return (int) $lastMonthRaw;
        }
        $epdPrimary = ($epdElectricPrimary !== null && $epdElectricPrimary !== '' && (int) $epdElectricPrimary > 0)
            ? (int) $epdElectricPrimary
            : null;
        $epdSecondary = ($epdElectricSecondary !== null && $epdElectricSecondary !== '' && (int) $epdElectricSecondary > 0)
            ? (int) $epdElectricSecondary
            : null;
        if ($meterSlot === 2) {
            return (int) ($epdSecondary ?? $epdPrimary ?? 0);
        }

        return (int) ($epdPrimary ?? $epdSecondary ?? 0);
    }

    /**
     * "Bill month" / Meter Change Month on the form: user selects e.g. March 2026 → rows are for February 2026.
     * $billMonth may be 1–12 (Other screen) or English month name (regular screen JS).
     *
     * @return array{0: ?int, 1: ?int} [1–12 month, year] for estate_month_reading_details / _other bill period
     */
    private function meterReadingUiToDataPeriod($billMonth, $billYear): array
    {
        [$m, $y] = $this->meterReadingParseUiMonthYear($billMonth, $billYear);
        if ($m === null || $y === null) {
            return [null, null];
        }
        $d = \Carbon\Carbon::createFromDate($y, $m, 1)->subMonth();

        return [(int) $d->month, (int) $d->year];
    }

    /**
     * Canonical Y-m from estate_month_reading_details(_other) bill_month + bill_year (for period match / new row).
     */
    private function meterReadingYmFromRowColumns($billMonthStored, $billYearStored): ?string
    {
        [$m, $y] = $this->meterReadingParseUiMonthYear($billMonthStored, $billYearStored);
        if ($m === null || $y === null) {
            return null;
        }

        return \Carbon\Carbon::createFromDate($y, $m, 1)->format('Y-m');
    }

    /**
     * Normalize bill_month for estate_month_reading_details_other: if numeric (1-12), convert to month name.
     */
    private function normalizeBillMonthForOther($billMonth): string
    {
        $m = is_numeric($billMonth) ? (int) $billMonth : null;
        if ($m >= 1 && $m <= 12) {
            return date('F', mktime(0, 0, 0, $m, 1));
        }
        return (string) $billMonth;
    }

    /**
     * API: Get unit sub types for meter reading filter (by campus + block).
     */
    public function getMeterReadingUnitSubTypesOther(Request $request)
    {
        $campusId = $request->get('campus_id');
        $blockId = $request->get('block_id');
        if (!$campusId || !$blockId) {
            return response()->json(['status' => true, 'data' => []]);
        }
        $q = DB::table('estate_possession_other as epo')
            ->join('estate_unit_sub_type_master as u', 'epo.estate_unit_sub_type_master_pk', '=', 'u.pk')
            ->where('epo.estate_campus_master_pk', $campusId)
            ->where('epo.estate_block_master_pk', $blockId);
        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_other', 'return_home_status')) {
            $q->where(function ($inner) {
                $inner->whereNull('epo.return_home_status')
                    ->orWhere('epo.return_home_status', 0);
            });
        }
        $items = $q->select('u.pk', 'u.unit_sub_type')
            ->distinct()
            ->orderBy('u.unit_sub_type')
            ->get();
        return response()->json(['status' => true, 'data' => $items]);
    }

    /**
     * Store/Update meter readings for "Update Meter Reading of Other".
     */
    public function storeMeterReadingsOther(Request $request)
    {
        $readingsIn = $request->input('readings', []);
        if (is_array($readingsIn)) {
            foreach ($readingsIn as $i => $r) {
                if (! is_array($r)) {
                    continue;
                }
                if (array_key_exists('new_meter_no', $r) && trim((string) ($r['new_meter_no'] ?? '')) === '') {
                    $readingsIn[$i]['new_meter_no'] = null;
                }
            }
            $request->merge(['readings' => $readingsIn]);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'readings' => 'required|array',
            'readings.*.pk' => 'required|integer|exists:estate_month_reading_details_other,pk',
            'readings.*.meter_slot' => 'nullable|in:1,2',
            'readings.*.selected' => 'nullable|in:1',
            'readings.*.curr_month_elec_red' => 'nullable|integer|min:0',
            'readings.*.new_meter_no' => 'nullable|string|max:50|regex:/^[0-9]+$/',
            'reading_bill_month' => 'required|date_format:Y-m',
            'reading_meter_reading_date' => 'required|date_format:Y-m-d',
        ]);

        $validator->after(function ($v) use ($request) {
            $readings = (array) $request->input('readings', []);
            $selected = collect($readings)->filter(fn ($r) => !empty($r['selected']));
            if ($selected->isEmpty()) {
                $v->errors()->add('readings', 'Please select at least one row to update.');
                return;
            }

            $pks = $selected->pluck('pk')->filter()->map(fn ($x) => (int) $x)->values()->all();
            if (! empty($pks) && \Illuminate\Support\Facades\Schema::hasColumn('estate_possession_other', 'return_home_status')) {
                $hasReturnedPossession = DB::table('estate_month_reading_details_other as emrd')
                    ->join('estate_possession_other as epo', 'emrd.estate_possession_other_pk', '=', 'epo.pk')
                    ->whereIn('emrd.pk', $pks)
                    ->whereNotNull('epo.return_home_status')
                    ->where('epo.return_home_status', '<>', 0)
                    ->exists();
                if ($hasReturnedPossession) {
                    $v->errors()->add('readings', 'Meter reading cannot be updated for houses that have been returned.');

                    return;
                }
            }
            $rows = EstateMonthReadingDetailsOther::whereIn('pk', $pks)
                ->get(['pk', 'house_no', 'last_month_elec_red', 'curr_month_elec_red', 'last_month_elec_red2', 'curr_month_elec_red2'])
                ->keyBy('pk');

            foreach ($readings as $idx => $item) {
                if (empty($item['selected'])) {
                    continue;
                }
                $pk = isset($item['pk']) ? (int) $item['pk'] : 0;
                $currVal = $item['curr_month_elec_red'] ?? null;
                $currProvided = !($currVal === null || $currVal === '');
                if (!$currProvided) {
                    $v->errors()->add("readings.$idx.curr_month_elec_red", 'Current month reading is required for selected rows.');
                    continue;
                }

                $curr = (int) $currVal;
                $row = $rows->get($pk);
                if (!$row) {
                    $v->errors()->add("readings.$idx.pk", 'Selected meter reading row not found.');
                    continue;
                }

                $meterSlot = isset($item['meter_slot']) ? (int) $item['meter_slot'] : 1;
                if ($meterSlot === 2) {
                    $baseline = null;
                    if ($row->curr_month_elec_red2 !== null && $row->curr_month_elec_red2 !== '') {
                        $baseline = (int) $row->curr_month_elec_red2;
                    } elseif ($row->last_month_elec_red2 !== null && $row->last_month_elec_red2 !== '') {
                        $baseline = (int) $row->last_month_elec_red2;
                    } else {
                        $baseline = 0;
                    }
                } else {
                    $baseline = null;
                    if ($row->curr_month_elec_red !== null && $row->curr_month_elec_red !== '') {
                        $baseline = (int) $row->curr_month_elec_red;
                    } elseif ($row->last_month_elec_red !== null && $row->last_month_elec_red !== '') {
                        $baseline = (int) $row->last_month_elec_red;
                    } else {
                        $baseline = 0;
                    }
                }
                if ($curr < $baseline) {
                    $house = $row->house_no ? (" (House: {$row->house_no})") : '';
                    $v->errors()->add(
                        "readings.$idx.curr_month_elec_red",
                        "Current month reading must be greater than or equal to last month meter reading{$house}."
                    );
                }
            }
        });

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($validator);
        }

        $readingMeterDateForStore = trim((string) $request->input('reading_meter_reading_date', ''));

        $readingBillMonthOther = trim((string) $request->input('reading_bill_month', ''));
        $storeBillMonthNameOther = null;
        $storeBillYearStrOther = null;
        if ($readingBillMonthOther !== '') {
            try {
                $sel = \Carbon\Carbon::createFromFormat('Y-m', $readingBillMonthOther)->startOfMonth();
                $storeBillMonthNameOther = $this->normalizeBillMonthForOther($sel->format('n'));
                $storeBillYearStrOther = $sel->format('Y');
            } catch (\Throwable $e) {
                $storeBillMonthNameOther = null;
                $storeBillYearStrOther = null;
            }
        }

        $readings = (array) $request->input('readings', []);
        $selected = collect($readings)->filter(fn ($r) => !empty($r['selected']))->values();
        $hasUnitTypeOnSubTypeForOtherElectric = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
        // When a new bill-period row is inserted, map form pk → new pk so a second POST line (other meter slot) updates the same new row.
        $resolvedOtherReadingPkByFormPk = [];
        $emroHasWaterCharges = \Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details_other', 'water_charges');
        $emroHasLicenceFees = \Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details_other', 'licence_fees');
        $emroHasBillNo = \Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details_other', 'bill_no');
        $emroHasPayrollRecovery = \Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details_other', 'payroll_recovery_head_amount');

        foreach ($selected as $item) {
            $formPk = (int) ($item['pk'] ?? 0);
            $currVal = $item['curr_month_elec_red'] ?? null;
            $curr = ($currVal !== null && $currVal !== '') ? (int) $currVal : null;
            if ($formPk <= 0 || $curr === null) {
                continue;
            }

            $resolvePk = $resolvedOtherReadingPkByFormPk[$formPk] ?? $formPk;
            $allowRetargetToTargetPeriodRow = true;

            while (true) {
                $row = EstateMonthReadingDetailsOther::where('pk', $resolvePk)->first();
                if (! $row) {
                    break;
                }

                $otherPossessionCtx = null;
                $electricUnitTypePkOther = null;
                if (! empty($row->estate_possession_other_pk)) {
                    $otherEpoQuery = DB::table('estate_possession_other as epo')
                        ->leftJoin('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
                        ->where('epo.pk', (int) $row->estate_possession_other_pk);
                    if ($hasUnitTypeOnSubTypeForOtherElectric) {
                        $otherEpoQuery->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk');
                    }
                    $houseDerivedExpr = $hasUnitTypeOnSubTypeForOtherElectric
                        ? 'eust.estate_unit_type_master_pk'
                        : 'ehm.estate_unit_master_pk';
                    $otherPossessionCtx = $otherEpoQuery
                        ->select([
                            'epo.estate_campus_master_pk',
                            'epo.estate_unit_type_master_pk',
                            'epo.estate_block_master_pk',
                            'epo.estate_unit_sub_type_master_pk',
                            'epo.pk as possession_pk',
                            'ehm.pk as house_pk',
                            'ehm.house_no as ehm_house_no',
                            DB::raw('COALESCE(epo.estate_unit_type_master_pk, ' . $houseDerivedExpr . ') as electric_unit_type_pk_resolved'),
                        ])
                        ->first();
                    if ($otherPossessionCtx && isset($otherPossessionCtx->electric_unit_type_pk_resolved)) {
                        $u = (int) $otherPossessionCtx->electric_unit_type_pk_resolved;
                        $electricUnitTypePkOther = $u > 0 ? $u : null;
                    }
                }

                if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_other', 'return_home_status')
                    && ! empty($row->estate_possession_other_pk)) {
                    $possReturned = DB::table('estate_possession_other')
                        ->where('pk', (int) $row->estate_possession_other_pk)
                        ->whereNotNull('return_home_status')
                        ->where('return_home_status', '<>', 0)
                        ->exists();
                    if ($possReturned) {
                        return redirect()
                            ->back()
                            ->withInput()
                            ->with('error', 'Meter reading cannot be updated for houses that have been returned.');
                    }
                }

                $meterSlot = isset($item['meter_slot']) ? (int) $item['meter_slot'] : 1;
                $newMeterNoRaw = isset($item['new_meter_no']) ? trim((string) $item['new_meter_no']) : '';
                $newMeterNoDigits = preg_replace('/\D/', '', $newMeterNoRaw ?? '');
                $update = [];

                if ($meterSlot === 2) {
                    $oldCurr2 = $row->curr_month_elec_red2;
                    if ($oldCurr2 !== null && $oldCurr2 !== '') {
                        $update['last_month_elec_red2'] = (int) $oldCurr2;
                    }
                    $baseline2 = ($oldCurr2 !== null && $oldCurr2 !== '')
                        ? (int) $oldCurr2
                        : (int) ($row->last_month_elec_red2 ?? 0);
                    $units = $curr >= $baseline2 ? $curr - $baseline2 : 0;
                    $charge = $units > 0 ? $this->calculateElectricChargeForUnits($electricUnitTypePkOther, $units) : 0.0;

                    $update['curr_month_elec_red2'] = $curr;
                    $update['meter_two_elec_charge'] = $charge;
                    $m1 = (float) ($row->meter_one_elec_charge ?? 0);
                    $update['electricty_charges'] = $m1 + $charge;
                } else {
                    $oldCurr = $row->curr_month_elec_red;
                    if ($oldCurr !== null && $oldCurr !== '') {
                        $update['last_month_elec_red'] = (int) $oldCurr;
                    }
                    $baseline = ($oldCurr !== null && $oldCurr !== '')
                        ? (int) $oldCurr
                        : (int) ($row->last_month_elec_red ?? 0);
                    $units = $curr >= $baseline ? $curr - $baseline : 0;
                    $charge = $units > 0 ? $this->calculateElectricChargeForUnits($electricUnitTypePkOther, $units) : 0.0;

                    $update['curr_month_elec_red'] = $curr;
                    $update['meter_one_elec_charge'] = $charge;
                    $m2 = (float) ($row->meter_two_elec_charge ?? 0);
                    $update['electricty_charges'] = $charge + $m2;
                }

                if ($newMeterNoDigits !== '') {
                    if ($meterSlot === 2) {
                        $update['meter_two'] = $newMeterNoDigits;
                    } else {
                        $update['meter_one'] = $newMeterNoDigits;
                    }
                }

                // For reference, store units in per_unit (matches previous behaviour for primary meter).
                $update['per_unit'] = $units;

                // Meter reading date = period end (to_date). Period start = first day of selected bill month
                // (not the current row's to_date — that breaks when retargeting to a row whose to_date is month-end).
                if ($readingMeterDateForStore !== '') {
                    $update['to_date'] = $readingMeterDateForStore;
                    if ($readingBillMonthOther !== '') {
                        try {
                            $update['from_date'] = \Carbon\Carbon::createFromFormat('Y-m', $readingBillMonthOther)
                                ->startOfMonth()
                                ->toDateString();
                        } catch (\Throwable $e) {
                            if (! empty($row->to_date)) {
                                try {
                                    $update['from_date'] = \Carbon\Carbon::parse((string) $row->to_date)
                                        ->addDay()
                                        ->toDateString();
                                } catch (\Throwable $e2) {
                                }
                            }
                        }
                    } elseif (! empty($row->to_date)) {
                        try {
                            $update['from_date'] = \Carbon\Carbon::parse((string) $row->to_date)
                                ->addDay()
                                ->toDateString();
                        } catch (\Throwable $e) {
                        }
                    }
                }

                if ($storeBillMonthNameOther && $storeBillYearStrOther) {
                    $storedMonth = (string) ($row->bill_month ?? '');
                    $storedYear = (string) ($row->bill_year ?? '');
                    $periodChanged = $storedMonth === '' || $storedYear === ''
                        || $storedMonth !== (string) $storeBillMonthNameOther
                        || $storedYear !== (string) $storeBillYearStrOther;
                    $update['bill_month'] = $storeBillMonthNameOther;
                    $update['bill_year'] = $storeBillYearStrOther;
                    if ($periodChanged) {
                        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details_other', 'notify_employee_status')) {
                            $update['notify_employee_status'] = 0;
                        }
                        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details_other', 'process_status')) {
                            $update['process_status'] = 0;
                        }
                    }
                }

                $hasStoredBillPeriod = trim((string) ($row->bill_month ?? '')) !== ''
                    && trim((string) ($row->bill_year ?? '')) !== '';
                $targetYmOther = null;
                if ($readingBillMonthOther !== '') {
                    try {
                        $targetYmOther = \Carbon\Carbon::createFromFormat('Y-m', $readingBillMonthOther)->startOfMonth()->format('Y-m');
                    } catch (\Throwable $e) {
                        $targetYmOther = null;
                    }
                }
                $rowYmOther = $this->meterReadingYmFromRowColumns($row->bill_month, $row->bill_year);
                $periodMismatch = $storeBillMonthNameOther && $storeBillYearStrOther && $hasStoredBillPeriod
                    && $targetYmOther
                    && (
                        $rowYmOther === null
                            ? (
                                (string) $row->bill_month !== (string) $storeBillMonthNameOther
                                || (string) $row->bill_year !== (string) $storeBillYearStrOther
                            )
                            : ($targetYmOther !== $rowYmOther)
                    );

                if ($periodMismatch && ! empty($update)) {
                    $existingTarget = DB::table('estate_month_reading_details_other')
                        ->where('estate_possession_other_pk', (int) $row->estate_possession_other_pk)
                        ->where('bill_month', $storeBillMonthNameOther)
                        ->where('bill_year', $storeBillYearStrOther)
                        ->first();
                    if ($existingTarget && $allowRetargetToTargetPeriodRow && (int) $existingTarget->pk !== $resolvePk) {
                        $resolvePk = (int) $existingTarget->pk;
                        $allowRetargetToTargetPeriodRow = false;

                        continue;
                    }
                    if (! $existingTarget) {
                        $dateOrNull = static function ($d) {
                            if ($d === null || $d === '') {
                                return null;
                            }
                            try {
                                return \Carbon\Carbon::parse((string) $d)->toDateString();
                            } catch (\Throwable $e) {
                                return null;
                            }
                        };
                        $insertData = [
                            'estate_possession_other_pk' => (int) $row->estate_possession_other_pk,
                            'house_no' => $row->house_no,
                            'meter_one' => $row->meter_one,
                            'meter_two' => $row->meter_two,
                            'last_month_elec_red' => $row->last_month_elec_red,
                            'curr_month_elec_red' => $row->curr_month_elec_red,
                            'last_month_elec_red2' => $row->last_month_elec_red2,
                            'curr_month_elec_red2' => $row->curr_month_elec_red2,
                            'meter_one_elec_charge' => $row->meter_one_elec_charge ?? 0,
                            'meter_two_elec_charge' => $row->meter_two_elec_charge ?? 0,
                            'electricty_charges' => $row->electricty_charges ?? 0,
                            'per_unit' => $row->per_unit ?? 0,
                            'from_date' => $dateOrNull($row->from_date),
                            'to_date' => $dateOrNull($row->to_date),
                            'created_date' => now(),
                        ];
                        if ($emroHasWaterCharges) {
                            $insertData['water_charges'] = $row->water_charges ?? null;
                        }
                        if ($emroHasLicenceFees) {
                            $insertData['licence_fees'] = $row->licence_fees ?? null;
                        }
                        if ($emroHasBillNo) {
                            $insertData['bill_no'] = $row->bill_no ?? null;
                        }
                        if ($emroHasPayrollRecovery) {
                            $insertData['payroll_recovery_head_amount'] = $row->payroll_recovery_head_amount ?? null;
                        }
                        $insertData = array_merge($insertData, $update);
                        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details_other', 'notify_employee_status')
                            && ! array_key_exists('notify_employee_status', $insertData)) {
                            $insertData['notify_employee_status'] = 0;
                        }
                        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details_other', 'process_status')
                            && ! array_key_exists('process_status', $insertData)) {
                            $insertData['process_status'] = 0;
                        }
                        $newPk = (int) DB::table('estate_month_reading_details_other')->insertGetId($insertData);
                        $resolvedOtherReadingPkByFormPk[$formPk] = $newPk;

                        if (
                            $newMeterNoDigits !== ''
                            && $otherPossessionCtx
                            && Schema::hasTable('estate_update_reading')
                        ) {
                            $beforeM1o = (int) ($row->meter_one ?? 0);
                            $beforeM2o = (int) ($row->meter_two ?? 0);
                            $afterM1o = ($meterSlot !== 2) ? (int) $newMeterNoDigits : $beforeM1o;
                            $afterM2o = ($meterSlot === 2) ? (int) $newMeterNoDigits : $beforeM2o;
                            if ($afterM1o !== $beforeM1o || $afterM2o !== $beforeM2o) {
                                $oldR1o = $row->curr_month_elec_red !== null && $row->curr_month_elec_red !== '' ? (int) $row->curr_month_elec_red : null;
                                $oldR2o = $row->curr_month_elec_red2 !== null && $row->curr_month_elec_red2 !== '' ? (int) $row->curr_month_elec_red2 : null;
                                $newR1o = array_key_exists('curr_month_elec_red', $update) ? (int) $update['curr_month_elec_red'] : $oldR1o;
                                $newR2o = array_key_exists('curr_month_elec_red2', $update) ? (int) $update['curr_month_elec_red2'] : $oldR2o;
                                $billMo = $storeBillMonthNameOther !== null && $storeBillMonthNameOther !== '' ? $storeBillMonthNameOther : (string) ($row->bill_month ?? '');
                                $billYo = $storeBillYearStrOther !== null && $storeBillYearStrOther !== '' ? $storeBillYearStrOther : (string) ($row->bill_year ?? '');
                                $mcmo = trim($billMo . ' ' . $billYo);
                                $houseO = trim((string) ($row->house_no ?? ''));
                                if ($houseO === '') {
                                    $houseO = trim((string) ($otherPossessionCtx->ehm_house_no ?? ''));
                                }
                                $this->logEstateMeterNumberChange(
                                    'o',
                                    (int) $otherPossessionCtx->possession_pk,
                                    isset($otherPossessionCtx->house_pk) ? (int) $otherPossessionCtx->house_pk : null,
                                    isset($otherPossessionCtx->estate_campus_master_pk) ? (int) $otherPossessionCtx->estate_campus_master_pk : null,
                                    isset($otherPossessionCtx->estate_unit_type_master_pk) ? (int) $otherPossessionCtx->estate_unit_type_master_pk : null,
                                    isset($otherPossessionCtx->estate_block_master_pk) ? (int) $otherPossessionCtx->estate_block_master_pk : null,
                                    isset($otherPossessionCtx->estate_unit_sub_type_master_pk) ? (int) $otherPossessionCtx->estate_unit_sub_type_master_pk : null,
                                    $houseO,
                                    $mcmo,
                                    $beforeM1o,
                                    $beforeM2o,
                                    $afterM1o,
                                    $afterM2o,
                                    $oldR1o,
                                    $oldR2o,
                                    $newR1o,
                                    $newR2o,
                                    null
                                );
                            }
                        }

                        if (! empty($row->estate_possession_other_pk)) {
                            $possessionUpdate = [];
                            if ($meterSlot === 2) {
                                $possessionUpdate['meter_reading_oth1'] = $curr;
                            } else {
                                $possessionUpdate['meter_reading_oth'] = $curr;
                            }
                            DB::table('estate_possession_other')
                                ->where('pk', (int) $row->estate_possession_other_pk)
                                ->update($possessionUpdate);
                        }

                        break;
                    }
                }

                EstateMonthReadingDetailsOther::where('pk', $resolvePk)->update($update);
                $resolvedOtherReadingPkByFormPk[$formPk] = $resolvePk;

                if (
                    $newMeterNoDigits !== ''
                    && $otherPossessionCtx
                    && Schema::hasTable('estate_update_reading')
                ) {
                    $beforeM1o = (int) ($row->meter_one ?? 0);
                    $beforeM2o = (int) ($row->meter_two ?? 0);
                    $afterM1o = ($meterSlot !== 2) ? (int) $newMeterNoDigits : $beforeM1o;
                    $afterM2o = ($meterSlot === 2) ? (int) $newMeterNoDigits : $beforeM2o;
                    if ($afterM1o !== $beforeM1o || $afterM2o !== $beforeM2o) {
                        $oldR1o = $row->curr_month_elec_red !== null && $row->curr_month_elec_red !== '' ? (int) $row->curr_month_elec_red : null;
                        $oldR2o = $row->curr_month_elec_red2 !== null && $row->curr_month_elec_red2 !== '' ? (int) $row->curr_month_elec_red2 : null;
                        $newR1o = array_key_exists('curr_month_elec_red', $update) ? (int) $update['curr_month_elec_red'] : $oldR1o;
                        $newR2o = array_key_exists('curr_month_elec_red2', $update) ? (int) $update['curr_month_elec_red2'] : $oldR2o;
                        $billMo = $storeBillMonthNameOther !== null && $storeBillMonthNameOther !== '' ? $storeBillMonthNameOther : (string) ($row->bill_month ?? '');
                        $billYo = $storeBillYearStrOther !== null && $storeBillYearStrOther !== '' ? $storeBillYearStrOther : (string) ($row->bill_year ?? '');
                        $mcmo = trim($billMo . ' ' . $billYo);
                        $houseO = trim((string) ($row->house_no ?? ''));
                        if ($houseO === '') {
                            $houseO = trim((string) ($otherPossessionCtx->ehm_house_no ?? ''));
                        }
                        $this->logEstateMeterNumberChange(
                            'o',
                            (int) $otherPossessionCtx->possession_pk,
                            isset($otherPossessionCtx->house_pk) ? (int) $otherPossessionCtx->house_pk : null,
                            isset($otherPossessionCtx->estate_campus_master_pk) ? (int) $otherPossessionCtx->estate_campus_master_pk : null,
                            isset($otherPossessionCtx->estate_unit_type_master_pk) ? (int) $otherPossessionCtx->estate_unit_type_master_pk : null,
                            isset($otherPossessionCtx->estate_block_master_pk) ? (int) $otherPossessionCtx->estate_block_master_pk : null,
                            isset($otherPossessionCtx->estate_unit_sub_type_master_pk) ? (int) $otherPossessionCtx->estate_unit_sub_type_master_pk : null,
                            $houseO,
                            $mcmo,
                            $beforeM1o,
                            $beforeM2o,
                            $afterM1o,
                            $afterM2o,
                            $oldR1o,
                            $oldR2o,
                            $newR1o,
                            $newR2o,
                            null
                        );
                    }
                }

                if (! empty($row->estate_possession_other_pk)) {
                    $possessionUpdate = [];
                    if ($meterSlot === 2) {
                        $possessionUpdate['meter_reading_oth1'] = $curr;
                    } else {
                        $possessionUpdate['meter_reading_oth'] = $curr;
                    }
                    DB::table('estate_possession_other')
                        ->where('pk', (int) $row->estate_possession_other_pk)
                        ->update($possessionUpdate);
                }

                break;
            }
        }

        return redirect()
            ->route('admin.estate.possession-for-others')
            ->with('success', 'Meter readings updated successfully.');
    }

    /**
     * Admin / Estate / Super Admin opening estate with ?scope=self (e.g. Home sidebar) should see only their own rows.
     */
    private function isEstateAuthorityPersonalScope(\Illuminate\Http\Request $request): bool
    {
        return $request->get('scope') === 'self'
            && (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'));
    }

    /**
     * Limit generate-bill / print queries (ehrd alias) to the current user's employee_pk(s).
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    private function applyGenerateEstateBillEmployeeSelfFilter($query): void
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (! $user) {
            $query->whereRaw('1 = 0');

            return;
        }
        $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
        if (! empty($employeeIds)) {
            $query->whereIn('ehrd.employee_pk', $employeeIds);
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    /**
     * Filter estate_month_reading_details (alias emrd) for Generate Bill / print: match selected Y-m by
     * to_date's calendar month when set (actual reading month), else bill_month + bill_year.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    private function applyEstateGenerateBillMonthFilter($query, string $year, string $month): void
    {
        $monthName = date('F', mktime(0, 0, 0, (int) $month, 1));
        $y = (int) $year;
        $m = (int) $month;
        $query->where(function ($q) use ($y, $m, $year, $monthName) {
            $q->where(function ($q2) use ($y, $m) {
                $q2->whereNotNull('emrd.to_date')
                    ->whereYear('emrd.to_date', $y)
                    ->whereMonth('emrd.to_date', $m);
            })->orWhere(function ($q2) use ($year, $monthName) {
                $q2->whereNull('emrd.to_date')
                    ->where('emrd.bill_year', $year)
                    ->where('emrd.bill_month', $monthName);
            });
        });
    }

    /**
     * Newest bills first: by meter reading end date (to_date), then by row pk. Rows with null to_date sort after dated rows.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Query\Builder
     */
    private function orderEstateGenerateBillQueryLatestFirst($query)
    {
        return $query->orderByRaw('(emrd.to_date IS NOT NULL) DESC')
            ->orderByDesc('emrd.to_date')
            ->orderByDesc('emrd.pk');
    }

    /**
     * One branch of estate bill search: any column may match this token (OR).
     *
     * @param  \Illuminate\Database\Query\Builder  $q
     */
    private function appendGenerateEstateBillSearchOrConditions($q, string $likeToken, string $pkCastLike, string $driver): void
    {
        $q->where('emrd.bill_no', 'like', $likeToken)
            ->orWhereRaw($pkCastLike, [$likeToken])
            ->orWhere('emrd.house_no', 'like', $likeToken)
            ->orWhere('ehm.house_no', 'like', $likeToken)
            ->orWhere('ehrd.emp_name', 'like', $likeToken)
            ->orWhere('ehrd.emp_designation', 'like', $likeToken)
            ->orWhere('ehrd.employee_id', 'like', $likeToken)
            ->orWhere('emrd.bill_month', 'like', $likeToken)
            ->orWhere('emrd.bill_year', 'like', $likeToken)
            ->orWhere('eust.unit_sub_type', 'like', $likeToken)
            ->orWhere('etm_gen_bill.category_type_name', 'like', $likeToken);

        if (Schema::hasColumn('estate_home_request_details', 'remarks')) {
            $q->orWhere('ehrd.remarks', 'like', $likeToken);
        }
        if (Schema::hasColumn('employee_master', 'emp_id')) {
            $q->orWhere('em_gen_bill.emp_id', 'like', $likeToken);
        }
        if (Schema::hasColumn('employee_master', 'employee_id')) {
            $q->orWhere('em_gen_bill.employee_id', 'like', $likeToken);
        }
        if (Schema::hasColumn('employee_master', 'first_name')) {
            if ($driver === 'sqlite') {
                $q->orWhere('em_gen_bill.first_name', 'like', $likeToken);
                if (Schema::hasColumn('employee_master', 'middle_name')) {
                    $q->orWhere('em_gen_bill.middle_name', 'like', $likeToken);
                }
                if (Schema::hasColumn('employee_master', 'last_name')) {
                    $q->orWhere('em_gen_bill.last_name', 'like', $likeToken);
                }
            } else {
                $nameParts = ['COALESCE(em_gen_bill.first_name,\'\')'];
                if (Schema::hasColumn('employee_master', 'middle_name')) {
                    $nameParts[] = 'COALESCE(em_gen_bill.middle_name,\'\')';
                }
                if (Schema::hasColumn('employee_master', 'last_name')) {
                    $nameParts[] = 'COALESCE(em_gen_bill.last_name,\'\')';
                }
                $nameExpr = 'TRIM(CONCAT_WS(\' \', ' . implode(', ', $nameParts) . '))';
                $q->orWhereRaw("{$nameExpr} LIKE ?", [$likeToken]);
            }
        }

        $q->orWhereRaw(
            "CONCAT(COALESCE(eust.unit_sub_type,''), '-(', COALESCE(emrd.house_no,''), ')') LIKE ?",
            [$likeToken]
        )
            ->orWhereRaw(
                "CONCAT(COALESCE(eust.unit_sub_type,''), '-(', COALESCE(ehm.house_no,''), ')') LIKE ?",
                [$likeToken]
            )
            ->orWhereRaw(
                "CONCAT(COALESCE(emrd.bill_month,''), ' ', COALESCE(emrd.bill_year,'')) LIKE ?",
                [$likeToken]
            );
    }

    /**
     * Search on Generate Estate Bill: joins employee for type/ids; multi-word = every word must match somewhere (AND),
     * so "ADESH KUMAR" still finds "ADESH SINGH KUMAR" (substring "ADESH KUMAR" would not).
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    private function applyGenerateEstateBillSearchFilter($query, string $search): void
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($search));
        if ($normalized === '') {
            return;
        }

        $query->leftJoin('employee_master as em_gen_bill', function ($join) {
            if (Schema::hasColumn('employee_master', 'pk_old')) {
                $join->whereRaw('(ehrd.employee_pk = em_gen_bill.pk OR ehrd.employee_pk = em_gen_bill.pk_old)');
            } else {
                $join->on('ehrd.employee_pk', '=', 'em_gen_bill.pk');
            }
        })
            ->leftJoin('employee_type_master as etm_gen_bill', 'em_gen_bill.emp_type', '=', 'etm_gen_bill.pk');

        $driver = DB::connection()->getDriverName();
        $pkCastLike = match ($driver) {
            'pgsql' => 'CAST(emrd.pk AS TEXT) LIKE ?',
            'sqlite' => 'CAST(emrd.pk AS TEXT) LIKE ?',
            default => 'CAST(emrd.pk AS CHAR) LIKE ?',
        };

        $monthNameForYm = null;
        $ymY = null;
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $normalized, $ymMatch)) {
            $ymY = $ymMatch[1];
            $ymM = (int) $ymMatch[2];
            if ($ymM >= 1 && $ymM <= 12) {
                $monthNameForYm = date('F', mktime(0, 0, 0, $ymM, 1));
            }
        }

        $tokens = array_values(array_filter(explode(' ', $normalized), fn ($t) => $t !== ''));

        $query->where(function ($outer) use ($tokens, $ymY, $monthNameForYm, $pkCastLike, $driver) {
            $outer->where(function ($andGroup) use ($tokens, $pkCastLike, $driver) {
                foreach ($tokens as $token) {
                    $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $token);
                    $likeToken = '%' . $escaped . '%';
                    $andGroup->where(function ($q) use ($likeToken, $pkCastLike, $driver) {
                        $this->appendGenerateEstateBillSearchOrConditions($q, $likeToken, $pkCastLike, $driver);
                    });
                }
            });
            if ($monthNameForYm !== null && $ymY !== null) {
                $outer->orWhere(function ($q2) use ($ymY, $monthNameForYm) {
                    $q2->where('emrd.bill_year', $ymY)
                        ->where('emrd.bill_month', $monthNameForYm);
                });
            }
        });
    }

    /**
     * Generate Estate Bill / Estate Bill Summary - filters and list of bill cards.
     */
    public function generateEstateBill(Request $request)
    {
        $unitSubTypes = DB::table('estate_unit_sub_type_master')
            ->orderBy('unit_sub_type')
            ->get(['pk', 'unit_sub_type']);

        $billMonth = $request->get('bill_month'); // e.g. 2025-09
        $currentYm = date('Y-m');
        if ($billMonth && $billMonth > $currentYm) {
            $billMonth = $currentYm;
        }
        $unitSubTypePk = $request->get('unit_sub_type_pk');
        $searchRaw = trim((string) $request->get('search', ''));
        $bills = collect();

        // Search: Setup → Estate only, for Admin / Estate / Super Admin (not Home ?scope=self). Others cannot use search (UI + query ignored).
        $showGenerateEstateBillSearch = (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'))
            && ! $this->isEstateAuthorityPersonalScope($request);
        $search = $showGenerateEstateBillSearch ? $searchRaw : '';

        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');

        if ($billMonth) {
            [$year, $month] = explode('-', $billMonth);

            $hasEpdReading2 = \Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2');
            $selectCols = [
                'emrd.pk',
                'emrd.bill_no',
                'emrd.bill_month',
                'emrd.bill_year',
                'emrd.from_date',
                'emrd.to_date',
                'emrd.last_month_elec_red',
                'emrd.curr_month_elec_red',
                'emrd.last_month_elec_red2',
                'emrd.curr_month_elec_red2',
                'emrd.electricty_charges',
                'emrd.water_charges',
                'emrd.licence_fees',
                'emrd.house_no',
                'emrd.meter_one',
                'emrd.meter_two',
                'emrd.meter_one_elec_charge',
                'emrd.meter_one_consume_unit',
                'emrd.meter_two_elec_charge',
                'emrd.meter_two_consume_unit',
                ($hasUnitTypeOnSubType ? 'eust.estate_unit_type_master_pk as unit_type_pk' : 'ehm.estate_unit_master_pk as unit_type_pk'),
                'ehm.estate_unit_sub_type_master_pk as unit_sub_type_pk',
                'ehrd.emp_name',
                'ehrd.employee_id',
                'ehrd.emp_designation',
                'eust.unit_sub_type',
                'ehm.water_charge as ehm_water_charge',
                'ehm.licence_fee as ehm_licence_fee',
                'ehm.electric_charge as ehm_electric_charge',
            ];
            if ($hasEpdReading2) {
                $selectCols[] = 'epd.electric_meter_reading_2 as epd_electric_meter_reading_2';
            }
            $query = DB::table('estate_month_reading_details as emrd')
                ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
                ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                ->select($selectCols);

            if ($search !== '') {
                $this->applyGenerateEstateBillSearchFilter($query, $search);
            }

            $this->applyEstateGenerateBillMonthFilter($query, $year, $month);

            // RBAC: Non-admin/estate/super-admin/training/IST users should only see/generate their own bills.
            if (! (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST'))) {
                $this->applyGenerateEstateBillEmployeeSelfFilter($query);
            } elseif ($this->isEstateAuthorityPersonalScope($request)) {
                $this->applyGenerateEstateBillEmployeeSelfFilter($query);
            }

            if (!empty($unitSubTypePk)) {
                $query->where('ehm.estate_unit_sub_type_master_pk', $unitSubTypePk);
            }

            $bills = $this->orderEstateGenerateBillQueryLatestFirst($query)->get();

            foreach ($bills as $b) {
                $b->bill_no = $this->resolveBillNumber($b->bill_no ?? null, $b->pk ?? null);
                $b->from_date_formatted = $b->from_date ? \Carbon\Carbon::parse($b->from_date)->format('d-m-Y') : '—';
                $b->to_date_formatted = $b->to_date ? \Carbon\Carbon::parse($b->to_date)->format('d-m-Y') : '—';
                $b->house_display = $b->unit_sub_type && $b->house_no ? $b->unit_sub_type . '-(' . $b->house_no . ')' : ($b->house_no ?? '—');

                // Electricity: use charges/units saved on the bill (set when meter reading was saved). Do not recalculate from current Define Electric Slab.
                $prev1 = isset($b->last_month_elec_red) ? (int) $b->last_month_elec_red : 0;
                $curr1 = isset($b->curr_month_elec_red) ? (int) $b->curr_month_elec_red : 0;
                $u1 = isset($b->meter_one_consume_unit) && $b->meter_one_consume_unit !== null
                    ? (int) $b->meter_one_consume_unit
                    : (($curr1 >= $prev1) ? $curr1 - $prev1 : 0);

                $prev2 = isset($b->last_month_elec_red2) && (int) $b->last_month_elec_red2 > 0 ? (int) $b->last_month_elec_red2 : 0;
                if ($prev2 === 0 && isset($b->meter_two) && (int) $b->meter_two > 0 && $hasEpdReading2 && isset($b->epd_electric_meter_reading_2) && (int) $b->epd_electric_meter_reading_2 > 0) {
                    $prev2 = (int) $b->epd_electric_meter_reading_2;
                    $b->last_month_elec_red2 = $prev2;
                }
                $curr2 = isset($b->curr_month_elec_red2) ? (int) $b->curr_month_elec_red2 : 0;
                $u2 = isset($b->meter_two_consume_unit) && $b->meter_two_consume_unit !== null
                    ? (int) $b->meter_two_consume_unit
                    : (($curr2 >= $prev2) ? $curr2 - $prev2 : 0);

                if ($b->meter_one_consume_unit === null && ($u1 > 0 || $curr1 > 0 || $prev1 > 0)) {
                    $b->meter_one_consume_unit = $u1;
                }
                if ($b->meter_two_consume_unit === null && ($u2 > 0 || $curr2 > 0 || $prev2 > 0)) {
                    $b->meter_two_consume_unit = $u2;
                }

                $b->total_consumed_unit = (int) ($b->meter_one_consume_unit ?? 0) + (int) ($b->meter_two_consume_unit ?? 0);

                // Fallback: when reading has 0/null water or licence, use estate_house_master (Define House) values
                $billWater = (float) ($b->water_charges ?? 0);
                $billLicence = (float) ($b->licence_fees ?? 0);
                if ($billWater <= 0 && isset($b->ehm_water_charge) && ($b->ehm_water_charge !== null && $b->ehm_water_charge !== '')) {
                    $b->water_charges = (float) $b->ehm_water_charge;
                }
                if ($billLicence <= 0 && isset($b->ehm_licence_fee) && ($b->ehm_licence_fee !== null && $b->ehm_licence_fee !== '')) {
                    $b->licence_fees = (float) $b->ehm_licence_fee;
                }

                // Fallback for electricity: when still 0/null, use house master electric charge
                $billElectric = (float) ($b->electricty_charges ?? 0);
                if ($billElectric <= 0 && isset($b->ehm_electric_charge) && ($b->ehm_electric_charge !== null && $b->ehm_electric_charge !== '')) {
                    $b->electricty_charges = (float) $b->ehm_electric_charge;
                }

                $b->grand_total = (float) ($b->electricty_charges ?? 0) + (float) ($b->water_charges ?? 0) + (float) ($b->licence_fees ?? 0);
            }
        }

        $showUnitSubTypeFilter = (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'))
            && ! $this->isEstateAuthorityPersonalScope($request);

        $estateBillIsPersonalView = $this->isEstateAuthorityPersonalScope($request)
            || ! (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin'));

        return view('admin.estate.generate_estate_bill', compact(
            'unitSubTypes',
            'bills',
            'billMonth',
            'unitSubTypePk',
            'search',
            'showUnitSubTypeFilter',
            'showGenerateEstateBillSearch',
            'estateBillIsPersonalView'
        ));
    }

    /**
     * Verify Selected Bills (LBSNAA): set notify_employee_status = 1 for selected estate_month_reading_details rows.
     * Verified bills then appear in "List Bill" / Bill Report Grid (notify_employee_status = 1).
     */
    public function verifySelectedBillsLbsna(Request $request)
    {
        if (! hasRole('Estate') && ! hasRole('Admin') && ! hasRole('Super Admin')) {
            abort(403, 'You do not have permission to notify selected bills.');
        }

        $validated = $request->validate([
            'pks' => 'required|array',
            'pks.*' => 'integer|exists:estate_month_reading_details,pk',
        ]);
        $pks = array_map('intval', $validated['pks']);
        $updated = DB::table('estate_month_reading_details')->whereIn('pk', $pks)->update(['notify_employee_status' => 1]);

        // Send in-app notification to each employee whose bill was notified.
        // Bell icon loads notifications by receiver_user_id = Auth::user()->user_id (user_credentials.user_id).
        // Resolve employee_pk to that user_id so the correct user sees the notification.
        $rows = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->whereIn('emrd.pk', $pks)
            ->select('emrd.pk as reading_pk', 'ehrd.employee_pk', 'emrd.bill_no', 'emrd.bill_month', 'emrd.bill_year')
            ->get();
        try {
            $notificationService = app(NotificationService::class);
            foreach ($rows as $row) {
                $employeePk = (int) ($row->employee_pk ?? 0);
                if ($employeePk <= 0) {
                    continue;
                }
                $receiverUserId = $this->resolveReceiverUserIdForEmployee($employeePk);
                if ($receiverUserId <= 0) {
                    continue;
                }
                $billNo = isset($row->bill_no) ? trim((string) $row->bill_no) : '';
                $billMonth = isset($row->bill_month) ? trim((string) $row->bill_month) : '';
                $billYear = isset($row->bill_year) ? trim((string) $row->bill_year) : '';
                $monthYear = trim($billMonth . ' ' . $billYear);
                if ((int) $billNo > 0) {
                    $billLabel = $monthYear ? "Bill no. {$billNo} for {$monthYear}" : "Bill no. {$billNo}";
                } else {
                    $billLabel = $monthYear ?: '';
                }
                $billText = $billLabel ? "Your estate bill {$billLabel} is ready. You can view it in the Bill Report." : 'Your estate bill has been generated and is ready to view. You can view it in the Bill Report.';
                $notificationService->create(
                    $receiverUserId,
                    'estate',
                    'EstateBill',
                    (int) $row->reading_pk,
                    'Estate bill ready',
                    $billText,
                    null
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Estate: failed to send in-app notifications for verified bills.', ['pks' => $pks, 'error' => $e->getMessage()]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => true,
                'message' => count($pks) . ' bill(s) verified successfully. They will now appear in the notified bill list.',
                'updated' => $updated,
            ]);
        }
        return redirect()->route('admin.estate.generate-estate-bill')
            ->with('success', count($pks) . ' bill(s) verified successfully.');
    }

    /**
     * Resolve employee_master pk (from estate) to user_credentials.user_id so notifications show in bell.
     * Bell loads by receiver_user_id = Auth::user()->user_id (user_credentials.user_id).
     */
    private function resolveReceiverUserIdForEmployee(int $employeePk): int
    {
        if ($employeePk <= 0) {
            return 0;
        }
        if (! Schema::hasTable('user_credentials') || ! Schema::hasColumn('user_credentials', 'user_id')) {
            return $employeePk;
        }
        $uc = DB::table('user_credentials')
            ->where('user_id', $employeePk)
            ->when(Schema::hasColumn('user_credentials', 'user_category'), function ($q) {
                $q->where('user_category', '!=', 'S');
            })
            ->value('user_id');
        if ($uc !== null) {
            return (int) $uc;
        }
        if (Schema::hasTable('employee_master')) {
            $empQuery = DB::table('employee_master')->where('pk', $employeePk);
            if (Schema::hasColumn('employee_master', 'pk_old')) {
                $empQuery->orWhere('pk_old', $employeePk);
            }
            $emp = $empQuery->select('pk')->addSelect(Schema::hasColumn('employee_master', 'pk_old') ? 'pk_old' : DB::raw('pk as pk_old'))->first();
            if ($emp) {
                $ids = array_filter(array_unique([(int) ($emp->pk ?? 0), (int) ($emp->pk_old ?? 0)]));
                $uid = DB::table('user_credentials')
                    ->whereIn('user_id', $ids)
                    ->when(Schema::hasColumn('user_credentials', 'user_category'), function ($q) {
                        $q->where('user_category', '!=', 'S');
                    })
                    ->value('user_id');
                if ($uid !== null) {
                    return (int) $uid;
                }
            }
        }
        return 0;
    }

    /**
     * Save As Draft (LBSNAA): set process_status = 0 and notify_employee_status = 0 for selected estate_month_reading_details rows.
     */
    public function saveAsDraftBillsLbsna(Request $request)
    {
        $validated = $request->validate([
            'pks' => 'required|array',
            'pks.*' => 'integer|exists:estate_month_reading_details,pk',
        ]);
        $pks = array_map('intval', $validated['pks']);
        $data = ['notify_employee_status' => 0];
        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details', 'process_status')) {
            $data['process_status'] = 0;
        }
        $updated = DB::table('estate_month_reading_details')->whereIn('pk', $pks)->update($data);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => true,
                'message' => count($pks) . ' bill(s) saved as draft.',
                'updated' => $updated,
            ]);
        }
        return redirect()->route('admin.estate.generate-estate-bill')
            ->with('success', count($pks) . ' bill(s) saved as draft.');
    }

    /**
     * API: Bill Report Print - employees list by category (LBSNAA = permanent, Other = other employees).
     * Optional month/year filter to show only employees who have a bill for that period.
     */
    public function getBillReportPrintEmployees(Request $request)
    {
        $employeeCategory = trim((string) $request->get('employee_category', 'LBSNAA'));
        $month = $request->get('month');
        $year = is_string($request->get('year')) && trim($request->get('year')) !== '' ? trim($request->get('year')) : null;

        $resolveMonthVariants = function ($m): array {
            $m = trim((string) $m);
            if ($m === '') return [];
            if (preg_match('/^\d{1,2}$/', $m)) {
                $n = (int) $m;
                if ($n < 1 || $n > 12) return [$m];
                $full = date('F', mktime(0, 0, 0, $n, 1));
                $short = date('M', mktime(0, 0, 0, $n, 1));
                return array_values(array_unique([$full, $short, (string) $n, str_pad((string) $n, 2, '0', STR_PAD_LEFT)]));
            }
            $ts = strtotime('1 ' . $m . ' 2000');
            if ($ts !== false) {
                $n = (int) date('n', $ts);
                $full = date('F', mktime(0, 0, 0, $n, 1));
                $short = date('M', mktime(0, 0, 0, $n, 1));
                return array_values(array_unique([$full, $short, (string) $n, str_pad((string) $n, 2, '0', STR_PAD_LEFT)]));
            }
            return [$m];
        };

        $listMonthVariants = $month ? $resolveMonthVariants($month) : [];
        $employeeCategoryNorm = strtolower(preg_replace('/\s+/', ' ', $employeeCategory));
        $isOtherEmployee = in_array($employeeCategoryNorm, ['other', 'other employee', 'other_employee'], true);

        $employees = $isOtherEmployee
            ? DB::table('estate_month_reading_details_other as emro')
                ->join('estate_possession_other as epo', 'emro.estate_possession_other_pk', '=', 'epo.pk')
                ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
                ->when(! empty($listMonthVariants), fn ($q) => $q->whereIn('emro.bill_month', $listMonthVariants))
                ->when($year, fn ($q) => $q->where('emro.bill_year', $year))
                ->whereNotNull('eor.emp_name')
                ->select('eor.pk', 'eor.emp_name', DB::raw('NULL as employee_id'))
                ->distinct()
                ->orderBy('eor.emp_name')
                ->get()
            : DB::table('estate_month_reading_details as emrd')
                ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->when(! empty($listMonthVariants), fn ($q) => $q->whereIn('emrd.bill_month', $listMonthVariants))
                ->when($year, fn ($q) => $q->where('emrd.bill_year', $year))
                ->whereNotNull('ehrd.employee_pk')
                ->select('ehrd.employee_pk as pk', 'ehrd.emp_name', 'ehrd.employee_id')
                ->distinct()
                ->orderBy('ehrd.emp_name')
                ->get();

        $data = $employees->map(function ($e) {
            return [
                'pk' => (int) $e->pk,
                'emp_name' => $e->emp_name ?? '',
                'employee_id' => isset($e->employee_id) ? trim((string) $e->employee_id) : null,
            ];
        })->values()->all();

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * Estate bill PDF logo: Dompdf often fails on remote http(s) URLs; embed as data URI.
     * Prefer local raster assets. Skip admin logo.svg for Dompdf — it embeds a seal bitmap plus vector text;
     * Dompdf mostly paints the seal tiny in a wide box. Official PNG is a single seal suited to a small square beside the title block.
     */
    private function estateBillPdfLogoForDompdf(): string
    {
        foreach ([
            public_path('images/lbsnaa_logo.jpg'),
            public_path('images/lbsnaa_logo.png'),
            public_path('admin_assets/images/logos/logo.png'),
        ] as $path) {
            $uri = $this->estatePdfTryFileToDataUri($path);
            if ($uri !== null) {
                return $uri;
            }
        }

        $officialPng = 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';
        $embedded = $this->estatePdfTryHttpToDataUri($officialPng, 'image/png');
        if ($embedded !== null) {
            return $embedded;
        }

        foreach ([
            public_path('admin_assets/images/logos/logo.svg'),
            public_path('admin_assets/images/logos/logo-icon.svg'),
        ] as $path) {
            $uri = $this->estatePdfTryFileToDataUri($path);
            if ($uri !== null) {
                return $uri;
            }
        }

        return $officialPng;
    }

    private function estatePdfTryFileToDataUri(string $path): ?string
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            default => 'image/jpeg',
        };

        return 'data:'.$mime.';base64,'.base64_encode($raw);
    }

    private function estatePdfTryHttpToDataUri(string $url, string $mime): ?string
    {
        try {
            $response = Http::timeout(20)->connectTimeout(8)->get($url);
            if ($response->successful()) {
                $body = $response->body();
                if ($body !== '' && strlen($body) > 100) {
                    return 'data:'.$mime.';base64,'.base64_encode($body);
                }
            }
        } catch (\Throwable $e) {
            // Dompdf will not reliably load remote URLs; caller falls back.
        }

        return null;
    }

    /**
     * Estate Bill Report for Print - filters (month, year, employee type, employee) and single bill.
     * Also supports direct link with bill_no, month, year query params.
     */
    public function estateBillReportPrint(Request $request)
    {
        $billNo = $request->get('bill_no');
        $month = $request->get('month');
        $year = $request->get('year');
        $employeeTypePk = $request->get('employee_type_pk');
        $employeePk = $request->get('employee_pk');
        $employeeCategory = trim((string) $request->get('employee_category', 'LBSNAA'));
        $bill = null;

        $resolveMonthVariants = function ($m): array {
            $m = trim((string) $m);
            if ($m === '') return [];
            // Numeric month: "3" / "03"
            if (preg_match('/^\d{1,2}$/', $m)) {
                $n = (int) $m;
                if ($n < 1 || $n > 12) return [$m];
                $full = date('F', mktime(0, 0, 0, $n, 1));
                $short = date('M', mktime(0, 0, 0, $n, 1));
                $num = (string) $n;
                $pad = str_pad($num, 2, '0', STR_PAD_LEFT);
                return array_values(array_unique([$full, $short, $num, $pad]));
            }
            // Month name: try full/short normalization
            $ts = strtotime('1 ' . $m . ' 2000');
            if ($ts !== false) {
                $n = (int) date('n', $ts);
                $full = date('F', mktime(0, 0, 0, $n, 1));
                $short = date('M', mktime(0, 0, 0, $n, 1));
                $num = (string) $n;
                $pad = str_pad($num, 2, '0', STR_PAD_LEFT);
                return array_values(array_unique([$full, $short, $num, $pad]));
            }
            return [$m];
        };

        // Filter dropdown data from both LBSNAA + Other bills
        $years = collect()
            ->merge(DB::table('estate_month_reading_details')->whereNotNull('bill_year')->where('bill_year', '!=', '')->pluck('bill_year'))
            ->merge(DB::table('estate_month_reading_details_other')->whereNotNull('bill_year')->where('bill_year', '!=', '')->pluck('bill_year'))
            ->filter(fn ($y) => trim((string) $y) !== '')
            ->unique()
            ->sortDesc()
            ->values();

        if ($years->isEmpty()) {
            $years = collect([(string) date('Y')]);
        }

        // Keep month picker stable (avoid DB variations like "03"/"Mar")
        $months = collect(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']);

        $employeeTypes = DB::table('estate_unit_sub_type_master')
            ->orderBy('unit_sub_type')
            ->get(['pk', 'unit_sub_type']);

        $employeeCategoryNorm = strtolower(preg_replace('/\s+/', ' ', $employeeCategory));
        $isOtherEmployee = in_array($employeeCategoryNorm, ['other', 'other employee', 'other_employee'], true);

        $listMonthVariants = $month ? $resolveMonthVariants($month) : [];
        $listYear = is_string($year) && trim($year) !== '' ? trim($year) : null;

        // Employee list for filters: show all who have a bill for selected month/year (including draft/un-notified) so dropdown is never empty
        $employees = $isOtherEmployee
            ? DB::table('estate_month_reading_details_other as emro')
                ->join('estate_possession_other as epo', 'emro.estate_possession_other_pk', '=', 'epo.pk')
                ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
                ->when(!empty($listMonthVariants), fn ($q) => $q->whereIn('emro.bill_month', $listMonthVariants))
                ->when($listYear, fn ($q) => $q->where('emro.bill_year', $listYear))
                ->whereNotNull('eor.emp_name')
                ->select('eor.pk', 'eor.emp_name', DB::raw('NULL as employee_id'))
                ->distinct()
                ->orderBy('eor.emp_name')
                ->get()
            : DB::table('estate_month_reading_details as emrd')
                ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->when(!empty($listMonthVariants), fn ($q) => $q->whereIn('emrd.bill_month', $listMonthVariants))
                ->when($listYear, fn ($q) => $q->where('emrd.bill_year', $listYear))
                ->whereNotNull('ehrd.employee_pk')
                // Use employee_pk as the stable key (matches employee_master.pk)
                ->select('ehrd.employee_pk as pk', 'ehrd.emp_name', 'ehrd.employee_id')
                ->distinct()
                ->orderBy('ehrd.emp_name')
                ->get();

        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
        $hasEpdReading2Print = \Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2');

        $baseQuery = function () use ($hasUnitTypeOnSubType, $hasEpdReading2Print) {
            $cols = [
                'emrd.pk',
                'emrd.bill_no',
                'emrd.bill_month',
                'emrd.bill_year',
                'emrd.from_date',
                'emrd.to_date',
                'emrd.last_month_elec_red',
                'emrd.curr_month_elec_red',
                'emrd.last_month_elec_red2',
                'emrd.curr_month_elec_red2',
                'emrd.electricty_charges',
                'emrd.water_charges',
                'emrd.licence_fees',
                'emrd.house_no',
                'emrd.meter_one',
                'emrd.meter_one_elec_charge',
                'emrd.meter_one_consume_unit',
                'emrd.meter_two',
                'emrd.meter_two_elec_charge',
                'emrd.meter_two_consume_unit',
                ($hasUnitTypeOnSubType ? 'eust.estate_unit_type_master_pk as unit_type_pk' : 'ehm.estate_unit_master_pk as unit_type_pk'),
                'ehm.estate_unit_sub_type_master_pk as unit_sub_type_pk',
                'ehm.water_charge as ehm_water_charge',
                'ehm.electric_charge as ehm_electric_charge',
                'ehm.licence_fee as ehm_licence_fee',
                'ehrd.emp_name',
                'ehrd.employee_id',
                'ehrd.emp_designation',
                'eust.unit_sub_type',
            ];
            if ($hasEpdReading2Print) {
                $cols[] = 'epd.electric_meter_reading_2 as epd_electric_meter_reading_2';
            }
            return DB::table('estate_month_reading_details as emrd')
                ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
                ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                ->select($cols);
        };

        $baseQueryOther = function () use ($hasUnitTypeOnSubType) {
            return DB::table('estate_month_reading_details_other as emro')
                ->join('estate_possession_other as epo', 'emro.estate_possession_other_pk', '=', 'epo.pk')
                ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
                ->leftJoin('estate_unit_sub_type_master as eust', 'epo.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                ->select(
                    'emro.pk',
                    'emro.bill_no',
                    'emro.bill_month',
                    'emro.bill_year',
                    'emro.from_date',
                    'emro.to_date',
                    'emro.last_month_elec_red',
                    'emro.curr_month_elec_red',
                    'emro.last_month_elec_red2',
                    'emro.curr_month_elec_red2',
                    'emro.electricty_charges',
                    'emro.water_charges',
                    'emro.licence_fees',
                    'emro.house_no',
                    'emro.meter_one',
                    'emro.meter_one_elec_charge',
                    DB::raw('NULL as meter_one_consume_unit'),
                    'emro.meter_two',
                    'emro.meter_two_elec_charge',
                    DB::raw('NULL as meter_two_consume_unit'),
                    ($hasUnitTypeOnSubType ? 'eust.estate_unit_type_master_pk as unit_type_pk' : 'epo.estate_unit_type_master_pk as unit_type_pk'),
                    'epo.estate_unit_sub_type_master_pk as unit_sub_type_pk',
                    DB::raw('NULL as ehm_water_charge'),
                    DB::raw('NULL as ehm_electric_charge'),
                    DB::raw('NULL as ehm_licence_fee'),
                    'eor.emp_name',
                    DB::raw('NULL as employee_id'),
                    DB::raw("COALESCE(NULLIF(TRIM(eor.designation), ''), NULLIF(TRIM(eor.section), ''), '—') as emp_designation"),
                    'eust.unit_sub_type'
                );
        };

        // Resolve bill: either by bill_no+month+year (direct link) or by month+year+employee_type+employee (filter form)
        if ($billNo && $month && $year) {
            $monthVariants = $resolveMonthVariants($month);
            $bill = $baseQuery()
                ->whereIn('emrd.bill_month', $monthVariants)
                ->where('emrd.bill_year', $year)
                ->where(function ($q) use ($billNo) {
                    $q->where('emrd.bill_no', $billNo)
                        ->orWhere('emrd.pk', $billNo);
                })
                ->first();

            if (! $bill) {
                $bill = $baseQueryOther()
                    ->whereIn('emro.bill_month', $monthVariants)
                    ->where('emro.bill_year', $year)
                    ->where(function ($q) use ($billNo) {
                        $q->where('emro.bill_no', $billNo)
                            ->orWhere('emro.pk', $billNo);
                    })
                    ->first();
            }
        } elseif ($month && $year && $employeePk) {
            $monthVariants = $resolveMonthVariants($month);
            // Allow print preview for any bill (draft or notified) when filtering by employee
            if ($isOtherEmployee) {
                $query = $baseQueryOther()
                    ->whereIn('emro.bill_month', $monthVariants)
                    ->where('emro.bill_year', $year)
                    ->where('eor.pk', $employeePk);
                if (! empty($employeeTypePk)) {
                    $query->where('epo.estate_unit_sub_type_master_pk', $employeeTypePk);
                }
                $bill = $query->first();
            } else {
                $query = $baseQuery()
                    ->whereIn('emrd.bill_month', $monthVariants)
                    ->where('emrd.bill_year', $year)
                    ->where(function ($q) use ($employeePk) {
                        // Primary: filter by employee_master pk
                        $q->where('ehrd.employee_pk', $employeePk)
                            // Backward compatibility: some callers may still send ehrd.pk
                            ->orWhere('ehrd.pk', $employeePk);
                    });
                if (! empty($employeeTypePk)) {
                    $query->where('ehm.estate_unit_sub_type_master_pk', $employeeTypePk);
                }
                $bill = $query->first();
            }
        }

        if ($bill) {
            $bill->bill_no = $this->resolveBillNumber($bill->bill_no ?? null, $bill->pk ?? null);
            $bill->from_date_formatted = $bill->from_date ? \Carbon\Carbon::parse($bill->from_date)->format('d.m.Y') : '—';
            $bill->to_date_formatted = $bill->to_date ? \Carbon\Carbon::parse($bill->to_date)->format('d.m.Y') : '—';
            $bill->house_display = $bill->unit_sub_type && $bill->house_no ? $bill->unit_sub_type . '-(' . $bill->house_no . ')' : ($bill->house_no ?? '—');

            // Electricity: use charges saved on the bill row (snapshot when meter reading was saved). Do not recalculate from current Define Electric Slab.
            $prev1 = isset($bill->last_month_elec_red) ? (int) $bill->last_month_elec_red : 0;
            $curr1 = isset($bill->curr_month_elec_red) ? (int) $bill->curr_month_elec_red : 0;
            $prev2 = isset($bill->last_month_elec_red2) && (int) $bill->last_month_elec_red2 > 0 ? (int) $bill->last_month_elec_red2 : 0;
            if ($prev2 === 0 && isset($bill->meter_two) && (int) $bill->meter_two > 0 && $hasEpdReading2Print && isset($bill->epd_electric_meter_reading_2) && (int) $bill->epd_electric_meter_reading_2 > 0) {
                $prev2 = (int) $bill->epd_electric_meter_reading_2;
                $bill->last_month_elec_red2 = $prev2;
            }
            $curr2 = isset($bill->curr_month_elec_red2) ? (int) $bill->curr_month_elec_red2 : 0;
            $u1 = isset($bill->meter_one_consume_unit) && $bill->meter_one_consume_unit !== null
                ? (int) $bill->meter_one_consume_unit
                : (($curr1 >= $prev1) ? $curr1 - $prev1 : 0);
            $u2 = isset($bill->meter_two_consume_unit) && $bill->meter_two_consume_unit !== null
                ? (int) $bill->meter_two_consume_unit
                : (($curr2 >= $prev2) ? $curr2 - $prev2 : 0);

            $bill->meter_one_consume_unit = $u1 > 0 ? $u1 : (($curr1 > 0 || $prev1 > 0) ? 0 : null);
            $bill->meter_two_consume_unit = $u2 > 0 ? $u2 : (($curr2 > 0 || $prev2 > 0) ? 0 : null);

            // 2) Fallback: when emrd has 0/null, use estate_house_master (via epd.estate_house_master_pk) for electricity, water, licence
            $billElectric = (float) ($bill->electricty_charges ?? 0);
            $billWater = (float) ($bill->water_charges ?? 0);
            $billLicence = (float) ($bill->licence_fees ?? 0);
            if ($billElectric <= 0 && property_exists($bill, 'ehm_electric_charge')) {
                $bill->electricty_charges = (float) ($bill->ehm_electric_charge ?? 0);
            }
            if ($billWater <= 0 && property_exists($bill, 'ehm_water_charge')) {
                $bill->water_charges = (float) ($bill->ehm_water_charge ?? 0);
            }
            if ($billLicence <= 0 && property_exists($bill, 'ehm_licence_fee')) {
                $bill->licence_fees = (float) ($bill->ehm_licence_fee ?? 0);
            }

            $bill->grand_total = (float) ($bill->electricty_charges ?? 0) + (float) ($bill->water_charges ?? 0) + (float) ($bill->licence_fees ?? 0);

            // Direct bill link (from Generate Estate Bill): when bill_no is present,
            // return a streamed PDF so browser shows PDF preview.
            if (!empty($billNo)) {
                $estateBillLogoSrc = $this->estateBillPdfLogoForDompdf();
                $pdf = Pdf::loadView('admin.estate.estate_bill_report_print_single_pdf', compact('bill', 'estateBillLogoSrc'))
                    ->setPaper('a4', 'portrait');
                $filename = 'estate-bill-' . preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($bill->bill_no ?? 'bill')) . '.pdf';
                return $pdf->stream($filename);
            }
        }

        return view('admin.estate.estate_bill_report_print', compact('bill', 'years', 'months', 'employeeTypes', 'employees'));
    }

    /**
     * Calculate electricity charge for given units using Define Electric Slab (progressive slabs per unit type).
     * Used when persisting meter readings (snapshot on the bill row), not when displaying existing bills.
     * Progressive slab: each slab's rate applies only to units falling in that slab's range.
     * e.g. 1-100 @ ₹2, 101-300 @ ₹2.5 → 150 units = 100×2 + 50×2.5 = 200 + 125 = 325.
     */
    private function calculateElectricChargeForUnits(?int $unitTypePk, int $units): float
    {
        if ($units <= 0) {
            return 0.0;
        }

        // Use slabs from estate_electric_slab:
        // - When unitTypePk is set: use that type's slabs, else fallback to all.
        // - When unitTypePk is null (Other/contract): use only slabs for NULL type, else one default type's slabs (do not mix types).
        $baseQuery = DB::table('estate_electric_slab')
            ->orderBy('start_unit_range');

        if ($unitTypePk) {
            $typed = (clone $baseQuery)
                ->where('estate_unit_type_master_pk', $unitTypePk)
                ->get();
            $slabs = $typed->isNotEmpty()
                ? $typed
                : $baseQuery->get();
        } else {
            // Other/contract: use slabs with NULL unit type, or else first available type's slabs (single set, no mixing)
            $slabsNull = (clone $baseQuery)->whereNull('estate_unit_type_master_pk')->get();
            if ($slabsNull->isNotEmpty()) {
                $slabs = $slabsNull;
            } else {
                $defaultTypePk = DB::table('estate_electric_slab')
                    ->whereNotNull('estate_unit_type_master_pk')
                    ->orderBy('estate_unit_type_master_pk')
                    ->value('estate_unit_type_master_pk');
                $slabs = $defaultTypePk !== null
                    ? (clone $baseQuery)->where('estate_unit_type_master_pk', $defaultTypePk)->get()
                    : $baseQuery->get();
            }
        }

        if ($slabs->isEmpty()) {
            return 0.0;
        }

        // Progressive slab: charge = (units in slab1 × rate1) + (units in slab2 × rate2) + ...
        $totalCharge = 0.0;
        $remainingUnits = $units;

        foreach ($slabs as $slab) {
            if ($remainingUnits <= 0) {
                break;
            }
            $start = (int) $slab->start_unit_range;
            $endRaw = $slab->end_unit_range;
            $end = ($endRaw === null || (int) $endRaw === 0) ? PHP_INT_MAX : (int) $endRaw;
            $slabCapacity = $end - $start + 1;
            $unitsInThisSlab = min($remainingUnits, $slabCapacity);
            $rate = (float) $slab->rate_per_unit;
            $totalCharge += $unitsInThisSlab * $rate;
            $remainingUnits -= $unitsInThisSlab;
        }

        return $totalCharge;
    }

    /**
     * Estate Bill Report – Print All: show all bills for the given bill_month and unit_sub_type_pk
     * in one page with options to print at once or download as PDF.
     */
    public function estateBillReportPrintAll(Request $request)
    {
        $billMonth = $request->get('bill_month');
        $unitSubTypePk = $request->get('unit_sub_type_pk');
        $bills = collect();
        $isSelectedPrint = false;
        $isOtherSelected = false;
        $backUrl = route('admin.estate.generate-estate-bill', array_filter([
            'bill_month' => $billMonth,
            'unit_sub_type_pk' => $unitSubTypePk,
            'scope' => $this->isEstateAuthorityPersonalScope($request) ? 'self' : null,
        ], static fn ($v) => $v !== null && $v !== ''));

        $selectedPksRaw = $request->get('selected_pks');
        $selectedPks = collect(is_array($selectedPksRaw) ? $selectedPksRaw : explode(',', (string) $selectedPksRaw))
            ->map(function ($v) {
                return (int) trim((string) $v);
            })
            ->filter(function ($v) {
                return $v > 0;
            })
            ->unique()
            ->values()
            ->all();

        if (!empty($selectedPks) && $request->boolean('is_other')) {
            $isSelectedPrint = true;
            $isOtherSelected = true;
            $backUrl = route('admin.estate.generate-estate-bill-for-other', ['bill_month' => $billMonth]);

            $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
            $rows = DB::table('estate_month_reading_details_other as emro')
                ->join('estate_possession_other as epo', 'emro.estate_possession_other_pk', '=', 'epo.pk')
                ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
                ->leftJoin('estate_unit_sub_type_master as eust', 'epo.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                ->leftJoin('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
                ->whereIn('emro.pk', $selectedPks)
                ->select(
                    'emro.pk',
                    'emro.bill_no',
                    'emro.bill_month',
                    'emro.bill_year',
                    'emro.from_date',
                    'emro.to_date',
                    'emro.last_month_elec_red',
                    'emro.curr_month_elec_red',
                    'emro.last_month_elec_red2',
                    'emro.curr_month_elec_red2',
                    'emro.electricty_charges',
                    'emro.water_charges',
                    'emro.licence_fees',
                    'emro.house_no',
                    'emro.meter_one',
                    'emro.meter_one_elec_charge',
                    DB::raw('NULL as meter_one_consume_unit'),
                    'emro.meter_two',
                    'emro.meter_two_elec_charge',
                    DB::raw('NULL as meter_two_consume_unit'),
                    ($hasUnitTypeOnSubType ? 'eust.estate_unit_type_master_pk as unit_type_pk' : 'epo.estate_unit_type_master_pk as unit_type_pk'),
                    'epo.estate_unit_sub_type_master_pk as unit_sub_type_pk',
                    'ehm.water_charge as ehm_water_charge',
                    'ehm.licence_fee as ehm_licence_fee',
                    'eor.emp_name',
                    DB::raw("COALESCE(NULLIF(TRIM(eor.designation), ''), NULLIF(TRIM(eor.section), ''), '—') as emp_designation"),
                    'eust.unit_sub_type'
                )
                ->orderBy('emro.bill_year', 'desc')
                ->orderBy('emro.bill_month')
                ->orderBy('emro.bill_no')
                ->get();

            foreach ($rows as $b) {
                $b->bill_no = $this->resolveBillNumber($b->bill_no ?? null, $b->pk ?? null);
                $b->from_date_formatted = $b->from_date ? \Carbon\Carbon::parse($b->from_date)->format('d.m.Y') : '—';
                $b->to_date_formatted = $b->to_date ? \Carbon\Carbon::parse($b->to_date)->format('d.m.Y') : '—';
                $b->house_display = $b->unit_sub_type && $b->house_no ? $b->unit_sub_type . '-(' . $b->house_no . ')' : ($b->house_no ?? '—');

                // Electricity: use amounts saved on the bill row; display-only consumption from readings.
                $prev1 = (int) ($b->last_month_elec_red ?? 0);
                $curr1 = (int) ($b->curr_month_elec_red ?? 0);
                $prev2 = (int) ($b->last_month_elec_red2 ?? 0);
                $curr2 = (int) ($b->curr_month_elec_red2 ?? 0);
                $u1 = ($curr1 >= $prev1) ? $curr1 - $prev1 : 0;
                $u2 = ($curr2 >= $prev2) ? $curr2 - $prev2 : 0;
                $b->meter_one_consume_unit = ($u1 > 0 || $curr1 > 0 || $prev1 > 0) ? $u1 : null;
                $b->meter_two_consume_unit = ($u2 > 0 || $curr2 > 0 || $prev2 > 0) ? $u2 : null;

                $billWater = (float) ($b->water_charges ?? 0);
                $billLicence = (float) ($b->licence_fees ?? 0);
                if ($billWater <= 0 && isset($b->ehm_water_charge) && $b->ehm_water_charge !== null && $b->ehm_water_charge !== '') {
                    $b->water_charges = (float) $b->ehm_water_charge;
                }
                if ($billLicence <= 0 && isset($b->ehm_licence_fee) && $b->ehm_licence_fee !== null && $b->ehm_licence_fee !== '') {
                    $b->licence_fees = (float) $b->ehm_licence_fee;
                }
                $b->grand_total = (float) ($b->electricty_charges ?? 0) + (float) ($b->water_charges ?? 0) + (float) ($b->licence_fees ?? 0);
            }

            $bills = $rows;
        }

        if (!$isOtherSelected && $billMonth) {
            [$year, $month] = explode('-', $billMonth);

            $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
            $hasEpdReading2 = \Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2');

            $selectCols = [
                'emrd.pk',
                'emrd.bill_no',
                'emrd.bill_month',
                'emrd.bill_year',
                'emrd.from_date',
                'emrd.to_date',
                'emrd.last_month_elec_red',
                'emrd.curr_month_elec_red',
                'emrd.electricty_charges',
                'emrd.water_charges',
                'emrd.licence_fees',
                'emrd.house_no',
                'emrd.meter_one',
                'emrd.meter_one_elec_charge',
                'emrd.meter_one_consume_unit',
                ($hasUnitTypeOnSubType ? 'eust.estate_unit_type_master_pk as unit_type_pk' : 'ehm.estate_unit_master_pk as unit_type_pk'),
                'ehm.estate_unit_sub_type_master_pk as unit_sub_type_pk',
                'ehrd.emp_name',
                'ehrd.employee_id',
                'ehrd.emp_designation',
                'eust.unit_sub_type',
                'ehm.water_charge as ehm_water_charge',
                'ehm.licence_fee as ehm_licence_fee',
            ];
            if ($hasEpdReading2) {
                $selectCols[] = 'epd.electric_meter_reading_2 as epd_electric_meter_reading_2';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details', 'last_month_elec_red2')) {
                $selectCols[] = 'emrd.last_month_elec_red2';
                $selectCols[] = 'emrd.curr_month_elec_red2';
                $selectCols[] = 'emrd.meter_two';
                $selectCols[] = 'emrd.meter_two_elec_charge';
                $selectCols[] = 'emrd.meter_two_consume_unit';
            }

            $query = DB::table('estate_month_reading_details as emrd')
                ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
                ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                ->select($selectCols);
            $this->applyEstateGenerateBillMonthFilter($query, $year, $month);

            if (!empty($unitSubTypePk)) {
                $query->where('ehm.estate_unit_sub_type_master_pk', $unitSubTypePk);
            }

            if ($this->isEstateAuthorityPersonalScope($request)) {
                $this->applyGenerateEstateBillEmployeeSelfFilter($query);
            }

            if (!empty($selectedPks)) {
                $isSelectedPrint = true;
                $query->whereIn('emrd.pk', $selectedPks);
                $backUrl = route('admin.estate.generate-estate-bill', array_filter([
                    'bill_month' => $billMonth,
                    'unit_sub_type_pk' => $unitSubTypePk,
                    'scope' => $this->isEstateAuthorityPersonalScope($request) ? 'self' : null,
                ], static fn ($v) => $v !== null && $v !== ''));
            }

            $bills = $this->orderEstateGenerateBillQueryLatestFirst($query)->get();

            foreach ($bills as $b) {
                $b->bill_no = $this->resolveBillNumber($b->bill_no ?? null, $b->pk ?? null);
                $b->from_date_formatted = $b->from_date ? \Carbon\Carbon::parse($b->from_date)->format('d.m.Y') : '—';
                $b->to_date_formatted = $b->to_date ? \Carbon\Carbon::parse($b->to_date)->format('d.m.Y') : '—';
                $b->house_display = $b->unit_sub_type && $b->house_no ? $b->unit_sub_type . '-(' . $b->house_no . ')' : ($b->house_no ?? '—');

                // Electricity: use charges/units saved on the bill; fill display-only consumption when null (legacy).
                $prev1 = (int) ($b->last_month_elec_red ?? 0);
                $curr1 = (int) ($b->curr_month_elec_red ?? 0);
                $u1 = isset($b->meter_one_consume_unit) && $b->meter_one_consume_unit !== null
                    ? (int) $b->meter_one_consume_unit
                    : (($curr1 >= $prev1) ? $curr1 - $prev1 : 0);
                $prev2 = 0;
                $curr2 = 0;
                if (isset($b->last_month_elec_red2) && (int) $b->last_month_elec_red2 > 0) {
                    $prev2 = (int) $b->last_month_elec_red2;
                } elseif ($hasEpdReading2 && isset($b->epd_electric_meter_reading_2) && (int) $b->epd_electric_meter_reading_2 > 0) {
                    $prev2 = (int) $b->epd_electric_meter_reading_2;
                }
                if (isset($b->curr_month_elec_red2)) {
                    $curr2 = (int) $b->curr_month_elec_red2;
                }
                $u2 = isset($b->meter_two_consume_unit) && $b->meter_two_consume_unit !== null
                    ? (int) $b->meter_two_consume_unit
                    : (($curr2 >= $prev2) ? $curr2 - $prev2 : 0);
                if ($b->meter_one_consume_unit === null && ($u1 > 0 || $curr1 > 0 || $prev1 > 0)) {
                    $b->meter_one_consume_unit = $u1;
                }
                if ($b->meter_two_consume_unit === null && ($u2 > 0 || $curr2 > 0 || $prev2 > 0)) {
                    $b->meter_two_consume_unit = $u2;
                }

                $billWater = (float) ($b->water_charges ?? 0);
                $billLicence = (float) ($b->licence_fees ?? 0);
                if ($billWater <= 0 && isset($b->ehm_water_charge) && $b->ehm_water_charge !== null && $b->ehm_water_charge !== '') {
                    $b->water_charges = (float) $b->ehm_water_charge;
                }
                if ($billLicence <= 0 && isset($b->ehm_licence_fee) && $b->ehm_licence_fee !== null && $b->ehm_licence_fee !== '') {
                    $b->licence_fees = (float) $b->ehm_licence_fee;
                }
                $b->grand_total = (float) ($b->electricty_charges ?? 0) + (float) ($b->water_charges ?? 0) + (float) ($b->licence_fees ?? 0);
            }
        }

        return view('admin.estate.estate_bill_report_print_all', compact('bills', 'billMonth', 'unitSubTypePk', 'isSelectedPrint', 'isOtherSelected', 'backUrl'));
    }

    /**
     * Download all estate bills for the given filters as a single PDF.
     */
    public function estateBillReportPrintAllPdf(Request $request)
    {
        $billMonth = $request->get('bill_month');
        $unitSubTypePk = $request->get('unit_sub_type_pk');
        $bills = collect();

        if ($billMonth) {
            [$year, $month] = explode('-', $billMonth);

            $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');
            $hasEpdReading2 = \Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2');

            $selectCols = [
                'emrd.pk',
                'emrd.bill_no',
                'emrd.bill_month',
                'emrd.bill_year',
                'emrd.from_date',
                'emrd.to_date',
                'emrd.last_month_elec_red',
                'emrd.curr_month_elec_red',
                'emrd.electricty_charges',
                'emrd.water_charges',
                'emrd.licence_fees',
                'emrd.house_no',
                'emrd.meter_one',
                'emrd.meter_one_elec_charge',
                'emrd.meter_one_consume_unit',
                ($hasUnitTypeOnSubType ? 'eust.estate_unit_type_master_pk as unit_type_pk' : 'ehm.estate_unit_master_pk as unit_type_pk'),
                'ehm.estate_unit_sub_type_master_pk as unit_sub_type_pk',
                'ehrd.emp_name',
                'ehrd.employee_id',
                'ehrd.emp_designation',
                'eust.unit_sub_type',
                'ehm.water_charge as ehm_water_charge',
                'ehm.licence_fee as ehm_licence_fee',
            ];
            if ($hasEpdReading2) {
                $selectCols[] = 'epd.electric_meter_reading_2 as epd_electric_meter_reading_2';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_month_reading_details', 'last_month_elec_red2')) {
                $selectCols[] = 'emrd.last_month_elec_red2';
                $selectCols[] = 'emrd.curr_month_elec_red2';
                $selectCols[] = 'emrd.meter_two';
                $selectCols[] = 'emrd.meter_two_elec_charge';
                $selectCols[] = 'emrd.meter_two_consume_unit';
            }

            $query = DB::table('estate_month_reading_details as emrd')
                ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
                ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                ->select($selectCols);
            $this->applyEstateGenerateBillMonthFilter($query, $year, $month);

            if (!empty($unitSubTypePk)) {
                $query->where('ehm.estate_unit_sub_type_master_pk', $unitSubTypePk);
            }

            if ($this->isEstateAuthorityPersonalScope($request)) {
                $this->applyGenerateEstateBillEmployeeSelfFilter($query);
            }

            $bills = $this->orderEstateGenerateBillQueryLatestFirst($query)->get();

            foreach ($bills as $b) {
                $b->bill_no = $this->resolveBillNumber($b->bill_no ?? null, $b->pk ?? null);
                $b->from_date_formatted = $b->from_date ? \Carbon\Carbon::parse($b->from_date)->format('d.m.Y') : '—';
                $b->to_date_formatted = $b->to_date ? \Carbon\Carbon::parse($b->to_date)->format('d.m.Y') : '—';
                $b->house_display = $b->unit_sub_type && $b->house_no ? $b->unit_sub_type . '-(' . $b->house_no . ')' : ($b->house_no ?? '—');

                // Electricity: use charges/units saved on the bill; fill display-only consumption when null (legacy).
                $prev1 = (int) ($b->last_month_elec_red ?? 0);
                $curr1 = (int) ($b->curr_month_elec_red ?? 0);
                $u1 = isset($b->meter_one_consume_unit) && $b->meter_one_consume_unit !== null
                    ? (int) $b->meter_one_consume_unit
                    : (($curr1 >= $prev1) ? $curr1 - $prev1 : 0);
                $prev2 = 0;
                $curr2 = 0;
                if (isset($b->last_month_elec_red2) && (int) $b->last_month_elec_red2 > 0) {
                    $prev2 = (int) $b->last_month_elec_red2;
                } elseif ($hasEpdReading2 && isset($b->epd_electric_meter_reading_2) && (int) $b->epd_electric_meter_reading_2 > 0) {
                    $prev2 = (int) $b->epd_electric_meter_reading_2;
                }
                if (isset($b->curr_month_elec_red2)) {
                    $curr2 = (int) $b->curr_month_elec_red2;
                }
                $u2 = isset($b->meter_two_consume_unit) && $b->meter_two_consume_unit !== null
                    ? (int) $b->meter_two_consume_unit
                    : (($curr2 >= $prev2) ? $curr2 - $prev2 : 0);
                if ($b->meter_one_consume_unit === null && ($u1 > 0 || $curr1 > 0 || $prev1 > 0)) {
                    $b->meter_one_consume_unit = $u1;
                }
                if ($b->meter_two_consume_unit === null && ($u2 > 0 || $curr2 > 0 || $prev2 > 0)) {
                    $b->meter_two_consume_unit = $u2;
                }

                $billWater = (float) ($b->water_charges ?? 0);
                $billLicence = (float) ($b->licence_fees ?? 0);
                if ($billWater <= 0 && isset($b->ehm_water_charge) && $b->ehm_water_charge !== null && $b->ehm_water_charge !== '') {
                    $b->water_charges = (float) $b->ehm_water_charge;
                }
                if ($billLicence <= 0 && isset($b->ehm_licence_fee) && $b->ehm_licence_fee !== null && $b->ehm_licence_fee !== '') {
                    $b->licence_fees = (float) $b->ehm_licence_fee;
                }
                $b->grand_total = (float) ($b->electricty_charges ?? 0) + (float) ($b->water_charges ?? 0) + (float) ($b->licence_fees ?? 0);
            }
        }

        if ($bills->isEmpty()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'No bills found.'], 404);
            }
            return redirect()->route('admin.estate.generate-estate-bill')
                ->with('error', 'No bills found for the selected filters.');
        }

        $estateBillLogoSrc = $this->estateBillPdfLogoForDompdf();
        $pdf = Pdf::loadView('admin.estate.estate_bill_report_print_all_pdf', compact('bills', 'estateBillLogoSrc'))
            ->setPaper('a4', 'portrait');

        $filename = 'estate-bills-' . str_replace('-', '', $billMonth ?? 'all') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Generate next request number (oth-req-1, oth-req-2, ...).
     * Uses max of numeric part of request_no_oth, not pk, so sequence stays correct
     * even when pk has gaps or jumps (e.g. after 793 we get oth-req-794, not oth-req-723001).
     */
    private function generateRequestNo(): string
    {
        $maxNum = EstateOtherRequest::query()
            ->whereNotNull('request_no_oth')
            ->where('request_no_oth', '!=', '')
            ->pluck('request_no_oth')
            ->map(function ($v) {
                $v = trim((string) $v);
                if (preg_match('/^oth-req-(\d+)$/', $v, $m)) {
                    return (int) $m[1];
                }
                if (ctype_digit($v)) {
                    return (int) $v;
                }
                return 0;
            })
            ->max() ?: 0;

        return 'oth-req-' . ($maxNum + 1);
    }

    /**
     * Some legacy rows have bill_no as 0/NULL. For display and links,
     * use the row pk as a stable fallback bill number.
     */
    private function resolveBillNumber($billNo, $pk): string
    {
        $billNoStr = trim((string) ($billNo ?? ''));
        if ($billNoStr !== '' && $billNoStr !== '0') {
            return $billNoStr;
        }

        $pkStr = trim((string) ($pk ?? ''));
        return $pkStr !== '' ? $pkStr : '—';
    }

    /**
     * List Meter Reading - view with Bill Month and Building Name filters.
     */
    public function listMeterReading()
    {
        $billMonths = EstateMonthReadingDetails::select('bill_year', 'bill_month')
            ->whereNotNull('bill_year')
            ->whereNotNull('bill_month')
            ->groupBy('bill_year', 'bill_month')
            ->orderByRaw('CAST(bill_year AS UNSIGNED) DESC, CAST(bill_month AS UNSIGNED) DESC')
            ->limit(24)
            ->get();
        $blocks = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_house_master as h', 'epd.estate_house_master_pk', '=', 'h.pk')
            ->join('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->select('b.pk', 'b.block_name')
            ->distinct()
            ->orderBy('b.block_name')
            ->get();
        if ($blocks->isEmpty()) {
            $blocks = DB::table('estate_month_reading_details_other as emro')
                ->join('estate_possession_other as epo', 'emro.estate_possession_other_pk', '=', 'epo.pk')
                ->join('estate_block_master as b', 'epo.estate_block_master_pk', '=', 'b.pk')
                ->select('b.pk', 'b.block_name')
                ->distinct()
                ->orderBy('b.block_name')
                ->get();
        }
        return view('admin.estate.list_meter_reading', compact('billMonths', 'blocks'));
    }

    /**
     * API: Get list meter reading data (filtered by bill month and building).
     */
    public function getListMeterReadingData(Request $request)
    {
        // Can be a large result set (client-side DataTable); avoid 30s timeout.
        @ini_set('max_execution_time', '120');
        @set_time_limit(120);

        $billMonth = $request->get('bill_month');
        $blockId = $request->get('block_id');
        $employeeType = trim((string) $request->get('employee_type', 'LBSNAA'));
        $isDataTables = $request->has('draw');
        $draw = (int) $request->get('draw', 0);
        $start = max(0, (int) $request->get('start', 0));
        $length = (int) $request->get('length', 10);
        if ($length <= 0) {
            $length = 10;
        }
        $searchValue = (string) data_get($request->all(), 'search.value', '');
        $searchValue = trim($searchValue);

        if (!$billMonth) {
            if ($isDataTables) {
                return response()->json([
                    'draw' => $draw,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Please select Bill Month.',
                ]);
            }
            return response()->json(['status' => true, 'data' => [], 'message' => 'Please select Bill Month.']);
        }
        // Parse Y-m format – DB stores bill_month as full month name, bill_year as 4-digit (estate_month_reading_details).
        $parts = is_string($billMonth) ? explode('-', trim($billMonth)) : [];
        $billYearStr = (count($parts) >= 1 && is_numeric($parts[0])) ? (string) ((int) $parts[0]) : (string) date('Y');
        $monthNum = (count($parts) >= 2 && is_numeric($parts[1])) ? (int) $parts[1] : (int) date('n');
        if ($monthNum < 1 || $monthNum > 12) {
            return response()->json(['status' => true, 'data' => [], 'message' => 'Invalid bill month.']);
        }
        $billMonthStr = date('F', mktime(0, 0, 0, $monthNum, 1)); // e.g. "December"
        $billMonthShortStr = date('M', mktime(0, 0, 0, $monthNum, 1)); // e.g. "Dec"
        $billMonthNumStr = (string) $monthNum; // e.g. "12"
        $billMonthNumPadded = str_pad($billMonthNumStr, 2, '0', STR_PAD_LEFT); // e.g. "12"/"02"

        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');

        $employeeTypeNormalized = strtolower(preg_replace('/\s+/', ' ', $employeeType));
        $isOtherEmployee = in_array($employeeTypeNormalized, ['other', 'other employee', 'other_employee'], true);

        // --- OTHER EMPLOYEE (estate_month_reading_details_other + estate_possession_other) ---
        if ($isOtherEmployee) {
            $baseQueryOther = DB::table('estate_month_reading_details_other as emro')
                ->join('estate_possession_other as epo', 'emro.estate_possession_other_pk', '=', 'epo.pk')
                ->leftJoin('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
                ->leftJoin('estate_block_master as b', 'epo.estate_block_master_pk', '=', 'b.pk')
                ->leftJoin('estate_unit_sub_type_master as ust', 'epo.estate_unit_sub_type_master_pk', '=', 'ust.pk')
                ->when($hasUnitTypeOnSubType, function ($q) {
                    $q->leftJoin('estate_unit_type_master as ut', 'ust.estate_unit_type_master_pk', '=', 'ut.pk');
                }, function ($q) {
                    $q->leftJoin('estate_unit_type_master as ut', 'epo.estate_unit_type_master_pk', '=', 'ut.pk');
                })
                ->where(function ($q) use ($billMonthStr, $billMonthShortStr, $billMonthNumStr, $billMonthNumPadded) {
                    $q->where('emro.bill_month', $billMonthStr)
                        ->orWhere('emro.bill_month', $billMonthShortStr)
                        ->orWhere('emro.bill_month', $billMonthNumStr)
                        ->orWhere('emro.bill_month', $billMonthNumPadded);
                })
                ->where('emro.bill_year', $billYearStr)
                ->whereNotNull('epo.estate_house_master_pk');

            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_other', 'return_home_status')) {
                $baseQueryOther->where(function ($q) {
                    $q->whereNull('epo.return_home_status')
                        ->orWhere('epo.return_home_status', 0);
                });
            }

            if ($blockId && $blockId !== 'all' && $blockId !== '') {
                $baseQueryOther->where('epo.estate_block_master_pk', $blockId);
            }

            if ($isDataTables) {
                $query = clone $baseQueryOther;

                $recordsTotal = (clone $baseQueryOther)->count('emro.pk');

                if ($searchValue !== '') {
                    $query->where(function ($q) use ($searchValue) {
                        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchValue) . '%';
                        $q->where('eor.emp_name', 'like', $like)
                            ->orWhere('emro.house_no', 'like', $like)
                            ->orWhere('b.block_name', 'like', $like)
                            ->orWhere('ust.unit_sub_type', 'like', $like)
                            ->orWhere('ut.unit_type', 'like', $like)
                            ->orWhere('eor.section', 'like', $like)
                            ->orWhere('eor.designation', 'like', $like);
                    });
                }

                $recordsFiltered = (clone $query)->count('emro.pk');

                $orderCol = (int) data_get($request->all(), 'order.0.column', 0);
                $orderDir = strtolower((string) data_get($request->all(), 'order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
                $orderMap = [
                    0 => 'b.block_name',
                    1 => 'eor.emp_name',
                    2 => 'eor.designation',
                    3 => 'eor.section',
                    4 => 'ut.unit_type',
                    5 => 'ust.unit_sub_type',
                    6 => 'b.block_name',
                    7 => 'emro.house_no',
                    8 => 'emro.curr_month_elec_red',
                    9 => 'emro.curr_month_elec_red2',
                ];
                $orderBy = $orderMap[$orderCol] ?? 'b.block_name';
                $query->orderBy($orderBy, $orderDir)->orderBy('emro.house_no', 'asc');

                $rows = $query
                    ->select([
                        'emro.pk',
                        'emro.house_no',
                        'emro.curr_month_elec_red',
                        'emro.curr_month_elec_red2',
                        'emro.last_month_elec_red',
                        'emro.last_month_elec_red2',
                        'eor.emp_name',
                        'eor.designation as emp_designation',
                        'eor.section',
                        'ut.unit_type',
                        'ust.unit_sub_type',
                        'b.block_name as building_name',
                        'epo.pk as possession_pk',
                    ])
                    ->offset($start)
                    ->limit($length)
                    ->get();

                $data = [];
                $sno = $start + 1;
                foreach ($rows as $r) {
                    $m1 = $r->curr_month_elec_red ?? $r->last_month_elec_red;
                    $m2 = $r->curr_month_elec_red2 ?? $r->last_month_elec_red2;
                    $data[] = [
                        'sno' => $sno++,
                        'name' => $r->emp_name ?? 'N/A',
                        'employee_type' => 'Other Employee',
                        'section' => $r->section ?? 'N/A',
                        'unit_type' => $r->unit_type ?? 'N/A',
                        'unit_sub_type' => $r->unit_sub_type ?? 'N/A',
                        'building_name' => $r->building_name ?? 'N/A',
                        'house_no' => $r->house_no ?? 'N/A',
                        'meter1_reading' => $m1 !== null && $m1 !== '' ? (string) $m1 : 'N/A',
                        'meter2_reading' => $m2 !== null && $m2 !== '' ? (string) $m2 : 'N/A',
                        'edit_url' => route('admin.estate.update-meter-reading-of-other') . '?possession_pks=' . $r->possession_pk . '&bill_month=' . urlencode($billMonth) . '&reading_pk=' . $r->pk,
                    ];
                }

                return response()->json([
                    'draw' => $draw,
                    'recordsTotal' => (int) $recordsTotal,
                    'recordsFiltered' => (int) $recordsFiltered,
                    'data' => $data,
                ]);
            }
        }

        $baseQuery = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->leftJoin('estate_block_master as b', 'ehm.estate_block_master_pk', '=', 'b.pk')
            ->leftJoin('estate_unit_sub_type_master as ust', 'ehm.estate_unit_sub_type_master_pk', '=', 'ust.pk')
            ->when($hasUnitTypeOnSubType, function ($q) {
                $q->leftJoin('estate_unit_type_master as ut', 'ust.estate_unit_type_master_pk', '=', 'ut.pk');
            }, function ($q) {
                $q->leftJoin('estate_unit_type_master as ut', 'ehm.estate_unit_master_pk', '=', 'ut.pk');
            })
            ->leftJoin('employee_master as em', 'ehrd.employee_pk', '=', 'em.' . $this->estateEmployeePkColumn())
            ->leftJoin('employee_type_master as etm', 'em.emp_type', '=', 'etm.pk')
            ->leftJoin('department_master as dm', 'em.department_master_pk', '=', 'dm.pk')
            ->where(function ($q) use ($billMonthStr, $billMonthShortStr, $billMonthNumStr, $billMonthNumPadded) {
                $q->where('emrd.bill_month', $billMonthStr)
                    ->orWhere('emrd.bill_month', $billMonthShortStr)
                    ->orWhere('emrd.bill_month', $billMonthNumStr)
                    ->orWhere('emrd.bill_month', $billMonthNumPadded);
            })
            ->where('emrd.bill_year', $billYearStr)
            ->whereNotNull('epd.estate_house_master_pk');

        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
            $baseQuery->where(function ($q) {
                $q->whereNull('epd.return_home_status')
                    ->orWhere('epd.return_home_status', 0);
            });
        }

        // RBAC: Non-admin/estate/super-admin/training/IST users should only see their own meter readings in List Meter Reading.
        if (! (hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST'))) {
            $user = \Illuminate\Support\Facades\Auth::user();
            if ($user) {
                $employeeIds = getEmployeeIdsForUser($user->user_id ?? $user->pk ?? null);
                if (!empty($employeeIds)) {
                    $baseQuery->whereIn('ehrd.employee_pk', $employeeIds);
                } else {
                    $baseQuery->whereRaw('1 = 0');
                }
            } else {
                $baseQuery->whereRaw('1 = 0');
            }
        }

        if ($blockId && $blockId !== 'all' && $blockId !== '') {
            $baseQuery->where('ehm.estate_block_master_pk', $blockId);
        }

        // DataTables server-side mode
        if ($isDataTables) {
            $query = clone $baseQuery;

            $recordsTotal = (clone $baseQuery)->count('emrd.pk');

            if ($searchValue !== '') {
                $query->where(function ($q) use ($searchValue) {
                    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchValue) . '%';
                    $q->where('ehrd.emp_name', 'like', $like)
                        ->orWhere('ehm.house_no', 'like', $like)
                        ->orWhere('b.block_name', 'like', $like)
                        ->orWhere('ust.unit_sub_type', 'like', $like)
                        ->orWhere('ut.unit_type', 'like', $like)
                        ->orWhere('dm.department_name', 'like', $like)
                        ->orWhere('etm.category_type_name', 'like', $like)
                        ->orWhere('ehrd.emp_designation', 'like', $like)
                        ->orWhere('ehrd.remarks', 'like', $like);
                });
            }

            $recordsFiltered = (clone $query)->count('emrd.pk');

            // Order mapping (column index from front-end)
            $orderCol = (int) data_get($request->all(), 'order.0.column', 0);
            $orderDir = strtolower((string) data_get($request->all(), 'order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
            $orderMap = [
                0 => 'b.block_name',
                1 => 'ehrd.emp_name',
                2 => 'etm.category_type_name',
                3 => null, // section: COALESCE(dept, designation, remarks) — ordered below
                4 => 'ut.unit_type',
                5 => 'ust.unit_sub_type',
                6 => 'b.block_name',
                7 => 'emrd.house_no',
                8 => 'emrd.curr_month_elec_red',
                9 => 'emrd.curr_month_elec_red2',
            ];
            $orderBy = $orderMap[$orderCol] ?? 'b.block_name';
            if ($orderCol === 3) {
                $query->orderByRaw(
                    "COALESCE(NULLIF(TRIM(dm.department_name), ''), NULLIF(TRIM(ehrd.emp_designation), ''), NULLIF(TRIM(ehrd.remarks), '')) {$orderDir}"
                )->orderBy('emrd.house_no', 'asc');
            } else {
                $query->orderBy($orderBy, $orderDir)->orderBy('emrd.house_no', 'asc');
            }

            $rows = $query
                ->select([
                    'emrd.pk',
                    'emrd.house_no',
                    'emrd.curr_month_elec_red',
                    'emrd.curr_month_elec_red2',
                    'emrd.last_month_elec_red',
                    'emrd.last_month_elec_red2',
                    'ehrd.emp_name',
                    'ehrd.emp_designation',
                    'etm.category_type_name as employee_type',
                    // Section: department is often unset on employee_master; fall back like bill/return-house UIs.
                    DB::raw("COALESCE(NULLIF(TRIM(dm.department_name), ''), NULLIF(TRIM(ehrd.emp_designation), ''), NULLIF(TRIM(ehrd.remarks), '')) as section"),
                    'ut.unit_type',
                    'ust.unit_sub_type',
                    'b.block_name as building_name',
                    'epd.pk as possession_pk',
                ])
                ->offset($start)
                ->limit($length)
                ->get();

            $data = [];
            $sno = $start + 1;
            foreach ($rows as $r) {
                $m1 = $r->curr_month_elec_red ?? $r->last_month_elec_red;
                $m2 = $r->curr_month_elec_red2 ?? $r->last_month_elec_red2;
                $data[] = [
                    'sno' => $sno++,
                    'name' => $r->emp_name ?? 'N/A',
                    'employee_type' => $r->employee_type ?? $r->emp_designation ?? 'N/A',
                    'section' => $r->section ?? 'N/A',
                    'unit_type' => $r->unit_type ?? 'N/A',
                    'unit_sub_type' => $r->unit_sub_type ?? 'N/A',
                    'building_name' => $r->building_name ?? 'N/A',
                    'house_no' => $r->house_no ?? 'N/A',
                    'meter1_reading' => $m1 !== null && $m1 !== '' ? (string) $m1 : 'N/A',
                    'meter2_reading' => $m2 !== null && $m2 !== '' ? (string) $m2 : 'N/A',
                    'edit_url' => route('admin.estate.update-meter-reading') . '?possession_pk=' . $r->possession_pk . '&bill_month=' . urlencode($billMonth) . '&reading_pk=' . $r->pk,
                ];
            }

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int) $recordsTotal,
                'recordsFiltered' => (int) $recordsFiltered,
                'data' => $data,
            ]);
        }

        // Legacy non-DataTables mode (used by old fetch-based UI)
        $rows = (clone $baseQuery)
            ->select([
                'emrd.pk',
                'emrd.house_no',
                'emrd.curr_month_elec_red',
                'emrd.curr_month_elec_red2',
                'emrd.last_month_elec_red',
                'emrd.last_month_elec_red2',
                'ehrd.emp_name',
                'ehrd.emp_designation',
                'etm.category_type_name as employee_type',
                DB::raw("COALESCE(NULLIF(TRIM(dm.department_name), ''), NULLIF(TRIM(ehrd.emp_designation), ''), NULLIF(TRIM(ehrd.remarks), '')) as section"),
                'ut.unit_type',
                'ust.unit_sub_type',
                'b.block_name as building_name',
                'epd.pk as possession_pk',
            ])
            ->orderBy('b.block_name')
            ->orderBy('emrd.house_no')
            ->get();

        $data = [];
        $sno = 1;
        if ($rows->isEmpty()) {
            $otherQuery = DB::table('estate_month_reading_details_other as emro')
                ->join('estate_possession_other as epo', 'emro.estate_possession_other_pk', '=', 'epo.pk')
                ->leftJoin('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
                ->leftJoin('estate_block_master as b', 'epo.estate_block_master_pk', '=', 'b.pk')
                ->leftJoin('estate_unit_type_master as ut', 'epo.estate_unit_type_master_pk', '=', 'ut.pk')
                ->leftJoin('estate_unit_sub_type_master as ust', 'epo.estate_unit_sub_type_master_pk', '=', 'ust.pk')
                ->where(function ($q) use ($billMonthStr, $billMonthShortStr, $billMonthNumStr, $billMonthNumPadded) {
                    $q->where('emro.bill_month', $billMonthStr)
                        ->orWhere('emro.bill_month', $billMonthShortStr)
                        ->orWhere('emro.bill_month', $billMonthNumStr)
                        ->orWhere('emro.bill_month', $billMonthNumPadded);
                })
                ->where('emro.bill_year', $billYearStr)
                ->select([
                    'emro.pk',
                    'emro.house_no',
                    'emro.curr_month_elec_red',
                    'emro.curr_month_elec_red2',
                    'emro.last_month_elec_red',
                    'emro.last_month_elec_red2',
                    'eor.emp_name',
                    'eor.section',
                    'ut.unit_type',
                    'ust.unit_sub_type',
                    'b.block_name as building_name',
                    'epo.pk as possession_pk',
                ])
                ->orderBy('b.block_name')
                ->orderBy('emro.house_no');

            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_other', 'return_home_status')) {
                $otherQuery->where(function ($q) {
                    $q->whereNull('epo.return_home_status')
                        ->orWhere('epo.return_home_status', 0);
                });
            }

            if ($blockId && $blockId !== 'all' && $blockId !== '') {
                $otherQuery->where('epo.estate_block_master_pk', $blockId);
            }

            $rows = $otherQuery->get();

            foreach ($rows as $r) {
                $m1 = $r->curr_month_elec_red ?? $r->last_month_elec_red;
                $m2 = $r->curr_month_elec_red2 ?? $r->last_month_elec_red2;
                $data[] = [
                    'sno' => $sno++,
                    'name' => $r->emp_name ?? 'N/A',
                    'employee_type' => 'Other Employee',
                    'section' => $r->section ?? 'N/A',
                    'unit_type' => $r->unit_type ?? 'N/A',
                    'unit_sub_type' => $r->unit_sub_type ?? 'N/A',
                    'building_name' => $r->building_name ?? 'N/A',
                    'house_no' => $r->house_no ?? 'N/A',
                    'meter1_reading' => $m1 !== null && $m1 !== '' ? (string) $m1 : 'N/A',
                    'meter2_reading' => $m2 !== null && $m2 !== '' ? (string) $m2 : 'N/A',
                    'edit_url' => route('admin.estate.update-meter-reading-of-other') . '?possession_pks=' . $r->possession_pk . '&bill_month=' . urlencode($billMonth) . '&reading_pk=' . $r->pk,
                ];
            }
        } else {
            foreach ($rows as $r) {
                $m1 = $r->curr_month_elec_red ?? $r->last_month_elec_red;
                $m2 = $r->curr_month_elec_red2 ?? $r->last_month_elec_red2;
                $data[] = [
                    'sno' => $sno++,
                    'name' => $r->emp_name ?? 'N/A',
                    'employee_type' => $r->employee_type ?? $r->emp_designation ?? 'N/A',
                    'section' => $r->section ?? 'N/A',
                    'unit_type' => $r->unit_type ?? 'N/A',
                    'unit_sub_type' => $r->unit_sub_type ?? 'N/A',
                    'building_name' => $r->building_name ?? 'N/A',
                    'house_no' => $r->house_no ?? 'N/A',
                    'meter1_reading' => $m1 !== null && $m1 !== '' ? (string) $m1 : 'N/A',
                    'meter2_reading' => $m2 !== null && $m2 !== '' ? (string) $m2 : 'N/A',
                    'edit_url' => route('admin.estate.update-meter-reading') . '?possession_pk=' . $r->possession_pk . '&bill_month=' . urlencode($billMonth) . '&reading_pk=' . $r->pk,
                ];
            }
        }

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * Estate Bill Report - Grid View, filtered by bill month.
     * LBSNA: only notified bills (notify_employee_status = 1). Other: all bills (with or without notify).
     * Data mapping: estate_month_reading_details (LBSNA) + estate_month_reading_details_other (Other).
     */
    public function getBillReportGridData(Request $request)
    {
        $billMonth = $request->get('bill_month');
        $isDataTables = $request->has('draw');
        $draw = (int) $request->get('draw', 0);
        $start = max(0, (int) $request->get('start', 0));
        $length = (int) $request->get('length', 10);
        if ($length <= 0) {
            $length = 10;
        }
        $searchValue = trim((string) data_get($request->all(), 'search.value', ''));

        if (! $billMonth || ! is_string($billMonth)) {
            if ($isDataTables) {
                return response()->json([
                    'draw' => $draw,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Please select Bill Month.',
                ]);
            }
            return response()->json(['status' => true, 'data' => [], 'message' => 'Please select Bill Month.']);
        }
        $parts = explode('-', trim($billMonth));
        $billYearStr = (count($parts) >= 1 && is_numeric($parts[0])) ? (string) ((int) $parts[0]) : (string) date('Y');
        $monthNum = (count($parts) >= 2 && is_numeric($parts[1])) ? (int) $parts[1] : (int) date('n');
        if ($monthNum < 1 || $monthNum > 12) {
            return response()->json(['status' => true, 'data' => [], 'message' => 'Invalid bill month.']);
        }
        $billMonthStr = date('F', mktime(0, 0, 0, $monthNum, 1));
        $billMonthShortStr = date('M', mktime(0, 0, 0, $monthNum, 1));
        $billMonthNumStr = (string) $monthNum;
        $billMonthNumPadded = str_pad($billMonthNumStr, 2, '0', STR_PAD_LEFT);
        // Normalise for case-insensitive + trim match (DB may store 'March', 'march', '3', '  March  ', etc.)
        $billMonthVariants = array_map('trim', [$billMonthStr, $billMonthShortStr, $billMonthNumStr, $billMonthNumPadded]);
        $billMonthVariants = array_unique(array_filter($billMonthVariants, fn ($v) => $v !== ''));

        // Restrict to current user's bills unless Estate/Admin (or similar) role
        $filterByUser = ! hasRole('Estate') && ! hasRole('Admin');
        $employeeIds = [];
        $currentUserId = null;
        $currentUserEmail = null;
        if ($filterByUser && Auth::check()) {
            $user = Auth::user();
            $currentUserId = $user->user_id ?? $user->pk ?? null;
            $employeeIds = getEmployeeIdsForUser($currentUserId);
            $employeeIds = array_filter(array_map('intval', $employeeIds));
            if (isset($user->email)) {
                $currentUserEmail = trim((string) $user->email);
            }
        }

        // Build a union query so DataTables can paginate/search/order on DB side.
        $lbsnaQ = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            // Same building + house no. as month-reading row (covers stale/wrong possession house FK vs define-house row)
            ->leftJoin('estate_house_master as ehm_bn', function ($join) {
                $join->on('ehm_bn.estate_block_master_pk', '=', 'ehm.estate_block_master_pk')
                    // Avoid utf8mb4_unicode_ci vs utf8mb4_0900_ai_ci mix on '=' (MySQL 1267).
                    ->whereRaw('CONVERT(LOWER(TRIM(COALESCE(ehm_bn.house_no, \'\'))) USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(LOWER(TRIM(COALESCE(emrd.house_no, \'\'))) USING utf8mb4) COLLATE utf8mb4_unicode_ci');
            })
            ->leftJoin('estate_block_master as b', 'ehm.estate_block_master_pk', '=', 'b.pk')
            ->leftJoin('employee_master as em', 'ehrd.employee_pk', '=', 'em.' . $this->estateEmployeePkColumn())
            ->leftJoin('employee_type_master as etm', 'em.emp_type', '=', 'etm.pk')
            ->leftJoin('department_master as dm', 'em.department_master_pk', '=', 'dm.pk')
            ->where(function ($q) use ($billMonthVariants) {
                $first = true;
                foreach ($billMonthVariants as $v) {
                    if ($first) {
                        $q->whereRaw('LOWER(TRIM(emrd.bill_month)) = ?', [strtolower($v)]);
                        $first = false;
                    } else {
                        $q->orWhereRaw('LOWER(TRIM(emrd.bill_month)) = ?', [strtolower($v)]);
                    }
                }
            })
            ->whereRaw('TRIM(CAST(emrd.bill_year AS CHAR)) = ?', [$billYearStr])
            ->where('emrd.notify_employee_status', 1)
            ->where('epd.return_home_status', 0)
            ->whereNotNull('epd.estate_house_master_pk');
        if ($filterByUser && ! empty($employeeIds)) {
            $lbsnaQ->whereIn('ehrd.employee_pk', $employeeIds);
        } elseif ($filterByUser && empty($employeeIds)) {
            $lbsnaQ->whereRaw('1 = 0'); // no employee mapping: show no LBSNA rows
        }
        $lbsnaQ = $lbsnaQ->select([
                DB::raw("COALESCE(NULLIF(TRIM(etm.category_type_name), ''), 'LBSNA Employee') as employee_type"),
                'ehrd.emp_name as name',
                // Section: same fallbacks as getReturnHouseRequestDetails — dept is often unset on employee_master.
                DB::raw("COALESCE(NULLIF(TRIM(dm.department_name), ''), NULLIF(TRIM(ehrd.emp_designation), ''), NULLIF(TRIM(ehrd.remarks), '')) as section"),
                'b.block_name as building_name',
                'emrd.house_no',
                'emrd.from_date',
                'emrd.to_date',
                'emrd.meter_one',
                'emrd.meter_two',
                'emrd.last_month_elec_red',
                'emrd.curr_month_elec_red',
                'emrd.last_month_elec_red2',
                'emrd.curr_month_elec_red2',
                'emrd.meter_one_consume_unit',
                'emrd.meter_two_consume_unit',
                'emrd.electricty_charges',
                // Define House: estate_house_master.water_charge / licence_fee. Reading rows often NULL or 0 — fall back; NULLIF avoids COALESCE(0, real) swallowing the good row.
                DB::raw('IF((emrd.water_charges IS NOT NULL AND emrd.water_charges <> 0), emrd.water_charges, COALESCE(NULLIF(ehm.water_charge, 0), NULLIF(ehm_bn.water_charge, 0), ehm.water_charge, ehm_bn.water_charge, emrd.water_charges)) as water_charges'),
                DB::raw('IF((emrd.licence_fees IS NOT NULL AND emrd.licence_fees <> 0), emrd.licence_fees, COALESCE(NULLIF(ehm.licence_fee, 0), NULLIF(ehm_bn.licence_fee, 0), ehm.licence_fee, ehm_bn.licence_fee, emrd.licence_fees)) as licence_fees'),
            ]);

        $otherQ = DB::table('estate_month_reading_details_other as emro')
            ->join('estate_possession_other as epo', 'emro.estate_possession_other_pk', '=', 'epo.pk')
            ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
            ->leftJoin('estate_house_master as ehm_o', 'epo.estate_house_master_pk', '=', 'ehm_o.pk')
            ->leftJoin('estate_house_master as ehm_o_bn', function ($join) {
                $join->on('ehm_o_bn.estate_block_master_pk', '=', 'epo.estate_block_master_pk')
                    ->whereRaw('CONVERT(LOWER(TRIM(COALESCE(ehm_o_bn.house_no, \'\'))) USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(LOWER(TRIM(COALESCE(emro.house_no, \'\'))) USING utf8mb4) COLLATE utf8mb4_unicode_ci');
            })
            ->leftJoin('estate_block_master as b', 'epo.estate_block_master_pk', '=', 'b.pk')
            ->where(function ($q) use ($billMonthVariants) {
                $first = true;
                foreach ($billMonthVariants as $v) {
                    if ($first) {
                        $q->whereRaw('LOWER(TRIM(emro.bill_month)) = ?', [strtolower($v)]);
                        $first = false;
                    } else {
                        $q->orWhereRaw('LOWER(TRIM(emro.bill_month)) = ?', [strtolower($v)]);
                    }
                }
            })
            ->whereRaw('TRIM(CAST(emro.bill_year AS CHAR)) = ?', [$billYearStr])
            ->where('epo.return_home_status', 0);
        if ($filterByUser) {
            if (Schema::hasColumn('estate_other_req', 'user_id') && $currentUserId !== null) {
                $otherQ->where('eor.user_id', $currentUserId);
            } elseif ($currentUserEmail !== null && Schema::hasColumn('estate_other_req', 'email')) {
                $otherQ->where('eor.email', $currentUserEmail);
            } else {
                $otherQ->whereRaw('1 = 0'); // no user link: show no Other rows for this user
            }
        }
        $otherQ = $otherQ->select([
                DB::raw("TRIM(CONCAT('Other Employee', IF(CHAR_LENGTH(TRIM(COALESCE(eor.designation, ''))) > 0, CONCAT(' — ', TRIM(eor.designation)), ''))) as employee_type"),
                'eor.emp_name as name',
                'eor.section as section',
                'b.block_name as building_name',
                'emro.house_no',
                'emro.from_date',
                'emro.to_date',
                'emro.meter_one',
                'emro.meter_two',
                'emro.last_month_elec_red',
                'emro.curr_month_elec_red',
                'emro.last_month_elec_red2',
                'emro.curr_month_elec_red2',
                DB::raw('NULL as meter_one_consume_unit'),
                DB::raw('NULL as meter_two_consume_unit'),
                'emro.electricty_charges',
                DB::raw('IF((emro.water_charges IS NOT NULL AND emro.water_charges <> 0), emro.water_charges, COALESCE(NULLIF(ehm_o.water_charge, 0), NULLIF(ehm_o_bn.water_charge, 0), ehm_o.water_charge, ehm_o_bn.water_charge, emro.water_charges)) as water_charges'),
                DB::raw('IF((emro.licence_fees IS NOT NULL AND emro.licence_fees <> 0), emro.licence_fees, COALESCE(NULLIF(ehm_o.licence_fee, 0), NULLIF(ehm_o_bn.licence_fee, 0), ehm_o.licence_fee, ehm_o_bn.licence_fee, emro.licence_fees)) as licence_fees'),
            ]);

        $union = $lbsnaQ->unionAll($otherQ);
        $base = DB::query()->fromSub($union, 'u');

        if (! $isDataTables) {
            // Keep legacy response for any existing callers.
            $rawRows = (clone $base)
                ->orderBy('u.building_name')
                ->orderBy('u.house_no')
                ->get();
            $data = [];
            $sno = 1;
            foreach ($rawRows as $r) {
                $prev = (int) ($r->last_month_elec_red ?? 0);
                $curr = (int) ($r->curr_month_elec_red ?? 0);
                $prev2 = (int) ($r->last_month_elec_red2 ?? 0);
                $curr2 = (int) ($r->curr_month_elec_red2 ?? 0);
                $u1 = $r->meter_one_consume_unit !== null ? (int) $r->meter_one_consume_unit : (($curr >= $prev) ? $curr - $prev : 0);
                $u2 = $r->meter_two_consume_unit !== null ? (int) $r->meter_two_consume_unit : (($curr2 >= $prev2) ? $curr2 - $prev2 : 0);
                $units = $u1 + $u2;
                $totalCharge = (float) ($r->electricty_charges ?? 0);
                $licence = (float) ($r->licence_fees ?? 0);
                $water = (float) ($r->water_charges ?? 0);
                $data[] = [
                    'sno' => $sno++,
                    'employee_type' => $r->employee_type ?? '—',
                    'name' => $r->name ?? '—',
                    'section' => $r->section ?? '—',
                    'building_name' => $r->building_name ?? '—',
                    'house_no' => $r->house_no ?? '—',
                    'from_date' => $r->from_date ? \Carbon\Carbon::parse($r->from_date)->format('d-m-Y') : '—',
                    'to_date' => $r->to_date ? \Carbon\Carbon::parse($r->to_date)->format('d-m-Y') : '—',
                    'meter_no' => trim(($r->meter_one ?? '') . ((string) ($r->meter_two ?? '') !== '' ? "\n" . $r->meter_two : '')),
                    'prev_reading' => (string) $prev . (($prev2 > 0 || $curr2 > 0) ? "\n" . $prev2 : ''),
                    'curr_reading' => (string) $curr . (($prev2 > 0 || $curr2 > 0) ? "\n" . $curr2 : ''),
                    'unit_consumed' => (string) $units,
                    'total_charge' => $totalCharge,
                    'licence_fee' => $licence,
                    'water_charges' => $water,
                    'grand_total' => $totalCharge + $licence + $water,
                ];
            }
            return response()->json(['status' => true, 'data' => $data]);
        }

        $recordsTotal = (clone $base)->count();

        $filteredQuery = clone $base;
        if ($searchValue !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchValue) . '%';
            $filteredQuery->where(function ($q) use ($like) {
                $q->where('u.employee_type', 'like', $like)
                    ->orWhere('u.name', 'like', $like)
                    ->orWhere('u.section', 'like', $like)
                    ->orWhere('u.building_name', 'like', $like)
                    ->orWhere('u.house_no', 'like', $like);
            });
        }
        $recordsFiltered = (clone $filteredQuery)->count();

        $orderCol = (int) data_get($request->all(), 'order.0.column', 0);
        $orderDir = strtolower((string) data_get($request->all(), 'order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $orderMap = [
            0 => 'u.building_name', // sno (fallback)
            1 => 'u.employee_type',
            2 => 'u.name',
            3 => 'u.section',
            4 => 'u.building_name',
            5 => 'u.house_no',
            6 => 'u.from_date',
            7 => 'u.to_date',
        ];
        $orderBy = $orderMap[$orderCol] ?? 'u.building_name';

        $pageRows = $filteredQuery
            ->orderBy($orderBy, $orderDir)
            ->orderBy('u.house_no', 'asc')
            ->offset($start)
            ->limit($length)
            ->get();

        $data = [];
        $sno = $start + 1;
        foreach ($pageRows as $r) {
            $prev = (int) ($r->last_month_elec_red ?? 0);
            $curr = (int) ($r->curr_month_elec_red ?? 0);
            $prev2 = (int) ($r->last_month_elec_red2 ?? 0);
            $curr2 = (int) ($r->curr_month_elec_red2 ?? 0);
            $u1 = $r->meter_one_consume_unit !== null ? (int) $r->meter_one_consume_unit : (($curr >= $prev) ? $curr - $prev : 0);
            $u2 = $r->meter_two_consume_unit !== null ? (int) $r->meter_two_consume_unit : (($curr2 >= $prev2) ? $curr2 - $prev2 : 0);
            $units = $u1 + $u2;
            $totalCharge = (float) ($r->electricty_charges ?? 0);
            $licence = (float) ($r->licence_fees ?? 0);
            $water = (float) ($r->water_charges ?? 0);
            $data[] = [
                'sno' => $sno++,
                'employee_type' => $r->employee_type ?? '—',
                'name' => $r->name ?? '—',
                'section' => $r->section ?? '—',
                'building_name' => $r->building_name ?? '—',
                'house_no' => $r->house_no ?? '—',
                'from_date' => $r->from_date ? \Carbon\Carbon::parse($r->from_date)->format('d-m-Y') : '—',
                'to_date' => $r->to_date ? \Carbon\Carbon::parse($r->to_date)->format('d-m-Y') : '—',
                'meter_no' => trim(($r->meter_one ?? '') . ((string) ($r->meter_two ?? '') !== '' ? "\n" . $r->meter_two : '')),
                'prev_reading' => (string) $prev . (($prev2 > 0 || $curr2 > 0) ? "\n" . $prev2 : ''),
                'curr_reading' => (string) $curr . (($prev2 > 0 || $curr2 > 0) ? "\n" . $curr2 : ''),
                'unit_consumed' => (string) $units,
                'total_charge' => $totalCharge,
                'licence_fee' => $licence,
                'water_charges' => $water,
                'grand_total' => $totalCharge + $licence + $water,
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Generate Estate Bill for Other – page for contract employees.
     * Lists bills from estate_month_reading_details_other for selected bill month.
     */
    public function generateEstateBillForOther()
    {
        return view('admin.estate.generate_estate_bill_for_other');
    }

    /**
     * API: Get bill list for "Generate Estate Bill for Other" (contract employees only).
     * Data mapping: estate_month_reading_details_other + estate_possession_other + estate_other_req + estate_block_master.
     * Active possession only (return_home_status NULL or 0 when column exists); notify_employee_status not required for listing.
     */
    public function getGenerateEstateBillForOtherData(Request $request)
    {
        $billMonth = $request->get('bill_month');
        if (! $billMonth || ! is_string($billMonth)) {
            return response()->json(['status' => true, 'data' => [], 'message' => 'Please select Bill Month.']);
        }
        $parts = explode('-', trim($billMonth));
        $billYearStr = (count($parts) >= 1 && is_numeric($parts[0])) ? (string) ((int) $parts[0]) : (string) date('Y');
        $monthNum = (count($parts) >= 2 && is_numeric($parts[1])) ? (int) $parts[1] : (int) date('n');
        if ($monthNum < 1 || $monthNum > 12) {
            return response()->json(['status' => true, 'data' => [], 'message' => 'Invalid bill month.']);
        }
        $selectedMonthStart = \Carbon\Carbon::createFromDate((int) $billYearStr, $monthNum, 1)->startOfMonth();
        $currentMonthStart = \Carbon\Carbon::now()->startOfMonth();
        if ($selectedMonthStart->gt($currentMonthStart)) {
            return response()->json(['status' => true, 'data' => [], 'message' => 'Future bill month is not allowed.']);
        }
        $billMonthStr = date('F', mktime(0, 0, 0, $monthNum, 1));

        $other = DB::table('estate_month_reading_details_other as emro')
            ->join('estate_possession_other as epo', 'emro.estate_possession_other_pk', '=', 'epo.pk')
            ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
            ->leftJoin('estate_block_master as b', 'epo.estate_block_master_pk', '=', 'b.pk')
            ->leftJoin('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
            ->where('emro.bill_month', $billMonthStr)
            ->where('emro.bill_year', $billYearStr)
            ->whereNotNull('epo.estate_house_master_pk');

        if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_other', 'return_home_status')) {
            $other->where(function ($q) {
                $q->whereNull('epo.return_home_status')
                    ->orWhere('epo.return_home_status', 0);
            });
        }

        $other = $other
            ->select([
                'emro.pk',
                'emro.bill_no',
                'emro.bill_month',
                'emro.bill_year',
                'emro.from_date',
                'emro.to_date',
                'emro.house_no',
                'emro.meter_one',
                'emro.meter_two',
                'emro.last_month_elec_red',
                'emro.curr_month_elec_red',
                'emro.last_month_elec_red2',
                'emro.curr_month_elec_red2',
                'emro.electricty_charges',
                'emro.water_charges',
                'emro.licence_fees',
                'ehm.water_charge as ehm_water_charge',
                'ehm.licence_fee as ehm_licence_fee',
                'eor.emp_name',
                'eor.section',
                'b.block_name as building_name',
            ])
            ->orderByDesc('emro.pk')
            ->get();

        $rows = [];
        foreach ($other as $r) {
            $prev = (int) ($r->last_month_elec_red ?? 0);
            $curr = (int) ($r->curr_month_elec_red ?? 0);
            $prev2 = (int) ($r->last_month_elec_red2 ?? 0);
            $curr2 = (int) ($r->curr_month_elec_red2 ?? 0);
            $units = (($curr >= $prev) ? $curr - $prev : 0) + (($curr2 >= $prev2) ? $curr2 - $prev2 : 0);
            // Use electricity amount saved on the bill (set when meter reading was saved), not current slab rates.
            $totalCharge = (float) ($r->electricty_charges ?? 0);
            // Prefer Define House (estate_house_master) licence_fee so changes in Define House reflect here
            $licence = (float) ($r->licence_fees ?? 0);
            if (isset($r->ehm_licence_fee) && $r->ehm_licence_fee !== null && $r->ehm_licence_fee !== '') {
                $licence = (float) $r->ehm_licence_fee;
            }
            // Prefer Define House water_charge so changes in Define House reflect here
            $water = (float) ($r->water_charges ?? 0);
            if (isset($r->ehm_water_charge) && $r->ehm_water_charge !== null && $r->ehm_water_charge !== '') {
                $water = (float) $r->ehm_water_charge;
            }
            $grandTotal = $totalCharge + $licence + $water;
            $rows[] = [
                'pk' => $r->pk,
                'bill_no' => $this->resolveBillNumber($r->bill_no ?? null, $r->pk ?? null),
                'bill_month' => $r->bill_month ?? $billMonthStr,
                'bill_year' => $r->bill_year ?? $billYearStr,
                'name' => $r->emp_name ?? 'N/A',
                'section' => $r->section ?? '—',
                'house_no' => $r->house_no ?? '—',
                'from_date' => $r->from_date ? \Carbon\Carbon::parse($r->from_date)->format('d-m-Y') : '—',
                'to_date' => $r->to_date ? \Carbon\Carbon::parse($r->to_date)->format('d-m-Y') : '—',
                'meter_no' => trim(($r->meter_one ?? '') . (isset($r->meter_two) && (string) $r->meter_two !== '' ? "\n" . $r->meter_two : '')),
                'prev_reading' => (string) $prev . (($prev2 > 0 || $curr2 > 0) ? "\n" . $prev2 : ''),
                'curr_reading' => (string) $curr . (($prev2 > 0 || $curr2 > 0) ? "\n" . $curr2 : ''),
                'unit_consumed' => (string) $units,
                'total_charge' => $totalCharge,
                'licence_fee' => $licence,
                'water_charges' => $water,
                'grand_total' => $grandTotal,
                'building_name' => $r->building_name ?? '—',
            ];
        }

        $data = collect($rows)->map(function ($row, $index) {
            $row['sno'] = $index + 1;
            return $row;
        })->values()->all();

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * Verify Selected Bills (Other): set notify_employee_status = 1 for selected estate_month_reading_details_other rows.
     * Verified bills then appear in "List Bill" / Bill Report Grid (notify_employee_status = 1).
     */
    public function verifySelectedBillsForOther(Request $request)
    {
        $validated = $request->validate([
            'pks' => 'required|array',
            'pks.*' => 'integer|exists:estate_month_reading_details_other,pk',
        ]);
        $pks = array_map('intval', $validated['pks']);
        $updated = EstateMonthReadingDetailsOther::whereIn('pk', $pks)->update(['notify_employee_status' => 1]);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => true,
                'message' => count($pks) . ' bill(s) verified successfully. They will now appear in the notified bill list.',
                'updated' => $updated,
            ]);
        }
        return redirect()->route('admin.estate.generate-estate-bill-for-other')
            ->with('success', count($pks) . ' bill(s) verified successfully.');
    }

    /**
     * Save As Draft (Other): set process_status = 0 and notify_employee_status = 0 for selected rows.
     * Draft bills stay out of the "notified" list until verified.
     */
    public function saveAsDraftBillsForOther(Request $request)
    {
        $validated = $request->validate([
            'pks' => 'required|array',
            'pks.*' => 'integer|exists:estate_month_reading_details_other,pk',
        ]);
        $pks = array_map('intval', $validated['pks']);
        $updated = EstateMonthReadingDetailsOther::whereIn('pk', $pks)->update([
            'process_status' => 0,
            'notify_employee_status' => 0,
        ]);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => true,
                'message' => count($pks) . ' bill(s) saved as draft.',
                'updated' => $updated,
            ]);
        }
        return redirect()->route('admin.estate.generate-estate-bill-for-other')
            ->with('success', count($pks) . ' bill(s) saved as draft.');
    }

    /**
     * Pending Meter Reading report - view with bill month filter.
     * Tables: estate_possession_details / estate_month_reading_details (LBSNAA) and
     * estate_possession_other / estate_month_reading_details_other (other employees).
     */
    public function pendingMeterReading()
    {
        return view('admin.estate.pending_meter_reading');
    }

    /**
     * API: Get pending meter reading list for selected bill month.
     * Returns active possessions missing a monthly reading row for that bill_month/bill_year:
     * LBSNAA (estate_month_reading_details) and other employees (estate_month_reading_details_other).
     */
    public function getPendingMeterReadingData(Request $request)
    {
        $billMonth = $request->get('bill_month');
        $billYear = $request->get('bill_year');

        if (!$billMonth || !$billYear) {
            return response()->json(['status' => true, 'data' => [], 'message' => 'Please select bill month and year.']);
        }

        // Parse Y-m format (e.g. 2025-12) – DB stores bill_month as full month name, bill_year as 4-digit string (estate_module_tables SQL).
        $parts = is_string($billMonth) ? explode('-', trim($billMonth)) : [];
        $year = (count($parts) >= 1 && is_numeric($parts[0])) ? (int) $parts[0] : (int) $billYear;
        $month = (count($parts) >= 2 && is_numeric($parts[1])) ? (int) $parts[1] : 0;
        $billYearStr = (string) $year;
        if ($month < 1 || $month > 12) {
            return response()->json(['status' => true, 'data' => [], 'message' => 'Invalid bill month.']);
        }

        $selectedMonthStart = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $currentMonthStart = \Carbon\Carbon::now()->startOfMonth();
        if ($selectedMonthStart->gt($currentMonthStart)) {
            return response()->json([
                'status' => true,
                'data' => [],
                'message' => 'Future bill month is not allowed.'
            ]);
        }

        $billMonthStr = date('F', mktime(0, 0, 0, $month, 1)); // e.g. "December" – matches estate_month_reading_details.bill_month

        // Strict month-year filter: if no reading entries exist for selected month/year in either stream,
        // do not return generic active-house list.
        $hasReadingsRegular = DB::table('estate_month_reading_details')
            ->where('bill_month', $billMonthStr)
            ->where('bill_year', $billYearStr)
            ->exists();
        $hasReadingsOther = Schema::hasTable('estate_month_reading_details_other')
            && DB::table('estate_month_reading_details_other')
                ->where('bill_month', $billMonthStr)
                ->where('bill_year', $billYearStr)
                ->exists();
        if (! $hasReadingsRegular && ! $hasReadingsOther) {
            return response()->json([
                'status' => true,
                'data' => [],
                'message' => 'No meter reading entries found for selected month/year.'
            ]);
        }

        $epdSelect = [
            'epd.pk as possession_pk',
            'ehm.house_no',
            'ehrd.emp_name',
            Schema::hasColumn('estate_home_request_details', 'remarks')
                ? DB::raw("COALESCE(NULLIF(TRIM(ehrd.emp_designation), ''), NULLIF(TRIM(ehrd.remarks), ''), NULL) as employee_type")
                : 'ehrd.emp_designation as employee_type',
            'epd.electric_meter_reading as epd_electric_meter_reading',
        ];
        if (Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2')) {
            $epdSelect[] = 'epd.electric_meter_reading_2 as epd_electric_meter_reading_2';
        }

        $pending = DB::table('estate_possession_details as epd')
            ->join('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_month_reading_details as emrd', function ($join) use ($billMonthStr, $billYearStr) {
                $join->on('emrd.estate_possession_details_pk', '=', 'epd.pk')
                    ->whereRaw('emrd.bill_month = ? AND emrd.bill_year = ?', [$billMonthStr, $billYearStr]);
            })
            ->whereNotNull('epd.estate_house_master_pk')
            ->where('epd.return_home_status', 0)
            // Align with Possession Details listing: only completed possessions (not placeholder 1900-01-01 rows).
            ->where('epd.possession_date', '>', '1900-01-01')
            ->whereNull('emrd.pk')
            ->select($epdSelect)
            ->orderBy('ehm.house_no')
            ->get();

        $possessionIds = $pending->pluck('possession_pk')->unique()->values()->all();

        $pendingOther = collect();
        if (Schema::hasTable('estate_possession_other')
            && Schema::hasTable('estate_month_reading_details_other')
            && Schema::hasTable('estate_other_req')) {
            $epoSelect = [
                'epo.pk as possession_pk',
                'ehm.house_no',
                'eor.emp_name',
                // Match other-estate UIs: section is often populated when designation is blank.
                DB::raw("COALESCE(NULLIF(TRIM(eor.designation), ''), NULLIF(TRIM(eor.section), ''), NULL) as employee_type"),
                'epo.meter_reading_oth as epd_electric_meter_reading',
            ];
            if (Schema::hasColumn('estate_possession_other', 'meter_reading_oth1')) {
                $epoSelect[] = 'epo.meter_reading_oth1 as epd_electric_meter_reading_2';
            }

            $otherQ = DB::table('estate_possession_other as epo')
                ->join('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
                ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
                ->leftJoin('estate_month_reading_details_other as emro', function ($join) use ($billMonthStr, $billYearStr) {
                    $join->on('emro.estate_possession_other_pk', '=', 'epo.pk')
                        ->whereRaw('emro.bill_month = ? AND emro.bill_year = ?', [$billMonthStr, $billYearStr]);
                })
                ->whereNotNull('epo.estate_house_master_pk')
                ->whereNull('emro.pk');

            if (Schema::hasColumn('estate_possession_other', 'return_home_status')) {
                $otherQ->where(function ($q) {
                    $q->whereNull('epo.return_home_status')
                        ->orWhere('epo.return_home_status', 0);
                });
            }
            if (Schema::hasColumn('estate_possession_other', 'possession_date_oth')) {
                $otherQ->where('epo.possession_date_oth', '>', '1900-01-01');
            }

            $pendingOther = $otherQ->select($epoSelect)->orderBy('ehm.house_no')->get();
        }

        $possessionOtherIds = $pendingOther->pluck('possession_pk')->unique()->values()->all();

        $monthOrderSql = "FIELD(emrd.bill_month, 'January','February','March','April','May','June','July','August','September','October','November','December')";
        $monthOrderSqlEmro = "FIELD(emro.bill_month, 'January','February','March','April','May','June','July','August','September','October','November','December')";
        $currentMonthOrder = (int) array_search($billMonthStr, ['January','February','March','April','May','June','July','August','September','October','November','December'], true) + 1;

        $hasEmrdReading2 = Schema::hasColumn('estate_month_reading_details', 'curr_month_elec_red2');
        $hasEpdReading2 = Schema::hasColumn('estate_possession_details', 'electric_meter_reading_2');
        $hasEmroReading2 = Schema::hasTable('estate_month_reading_details_other')
            && Schema::hasColumn('estate_month_reading_details_other', 'curr_month_elec_red2');
        $hasEpoReading2 = Schema::hasTable('estate_possession_other')
            && Schema::hasColumn('estate_possession_other', 'meter_reading_oth1');
        $formatDualMeterReading = static function ($primary, $secondary, bool $hasSecondaryCol): string {
            $seg = static function ($v) {
                return ($v !== null && trim((string) $v) !== '') ? (string) $v : '—';
            };
            $secRaw = $hasSecondaryCol ? ($secondary ?? null) : null;
            $secStr = $secRaw !== null ? trim((string) $secRaw) : '';
            $hasSecondaryEntered = $hasSecondaryCol
                && $secStr !== ''
                && ! (is_numeric($secStr) && (int) $secStr === 0);

            if ($hasSecondaryEntered) {
                return $seg($primary) . '/' . $seg($secondary);
            }

            $p = $primary;
            if ($p !== null && trim((string) $p) !== '') {
                return (string) $p;
            }

            return 'N/A';
        };

        $lastReadings = [];
        if (!empty($possessionIds)) {
            $previousReadings = DB::table('estate_month_reading_details as emrd')
                ->whereIn('emrd.estate_possession_details_pk', $possessionIds)
                ->where(function ($q) use ($billYearStr, $billMonthStr, $monthOrderSql, $currentMonthOrder) {
                    $q->where('emrd.bill_year', '<', $billYearStr)
                        ->orWhere(function ($q2) use ($billYearStr, $monthOrderSql, $currentMonthOrder) {
                            $q2->where('emrd.bill_year', '=', $billYearStr)
                                ->whereRaw($monthOrderSql . ' < ?', [$currentMonthOrder]);
                        });
                })
                ->select('emrd.estate_possession_details_pk', 'emrd.curr_month_elec_red', 'emrd.curr_month_elec_red2', 'emrd.to_date')
                ->orderByRaw('CAST(emrd.bill_year AS UNSIGNED) DESC, ' . $monthOrderSql . ' DESC')
                ->get();

            foreach ($previousReadings as $row) {
                $pk = $row->estate_possession_details_pk;
                if (!isset($lastReadings[$pk])) {
                    $lastReadings[$pk] = [
                        'reading' => $formatDualMeterReading(
                            $row->curr_month_elec_red ?? null,
                            $hasEmrdReading2 ? ($row->curr_month_elec_red2 ?? null) : null,
                            $hasEmrdReading2
                        ),
                        'date' => $row->to_date ? \Carbon\Carbon::parse($row->to_date)->format('d/m/Y') : 'N/A',
                    ];
                }
            }
        }

        $lastReadingsOther = [];
        if (! empty($possessionOtherIds) && Schema::hasTable('estate_month_reading_details_other')) {
            $previousReadingsOther = DB::table('estate_month_reading_details_other as emro')
                ->whereIn('emro.estate_possession_other_pk', $possessionOtherIds)
                ->where(function ($q) use ($billYearStr, $monthOrderSqlEmro, $currentMonthOrder) {
                    $q->where('emro.bill_year', '<', $billYearStr)
                        ->orWhere(function ($q2) use ($billYearStr, $monthOrderSqlEmro, $currentMonthOrder) {
                            $q2->where('emro.bill_year', '=', $billYearStr)
                                ->whereRaw($monthOrderSqlEmro . ' < ?', [$currentMonthOrder]);
                        });
                })
                ->select('emro.estate_possession_other_pk', 'emro.curr_month_elec_red', 'emro.curr_month_elec_red2', 'emro.to_date')
                ->orderByRaw('CAST(emro.bill_year AS UNSIGNED) DESC, ' . $monthOrderSqlEmro . ' DESC')
                ->get();

            foreach ($previousReadingsOther as $row) {
                $pk = $row->estate_possession_other_pk;
                if (! isset($lastReadingsOther[$pk])) {
                    $lastReadingsOther[$pk] = [
                        'reading' => $formatDualMeterReading(
                            $row->curr_month_elec_red ?? null,
                            $hasEmroReading2 ? ($row->curr_month_elec_red2 ?? null) : null,
                            $hasEmroReading2
                        ),
                        'date' => $row->to_date ? \Carbon\Carbon::parse($row->to_date)->format('d/m/Y') : 'N/A',
                    ];
                }
            }
        }

        // Due date for capturing this bill month (period end), not an actual submitted reading date.
        $expectedReadingDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('d/m/Y');

        $rows = [];
        foreach ($pending as $row) {
            $fromEmrd = $lastReadings[$row->possession_pk] ?? null;
            if ($fromEmrd !== null) {
                $lastReadingDisplay = $fromEmrd['reading'];
            } else {
                // Match Possession Details screen: when no prior monthly row, use on-file possession readings.
                $lastReadingDisplay = $formatDualMeterReading(
                    $row->epd_electric_meter_reading ?? null,
                    $hasEpdReading2 ? ($row->epd_electric_meter_reading_2 ?? null) : null,
                    $hasEpdReading2
                );
            }
            $rows[] = [
                'employee_type' => $row->employee_type ?? 'N/A',
                'name' => $row->emp_name ?? 'N/A',
                'house_no' => $row->house_no ?? 'N/A',
                'meter_reading_date' => $expectedReadingDate,
                'last_meter_reading' => $lastReadingDisplay,
            ];
        }

        foreach ($pendingOther as $row) {
            $fromEmro = $lastReadingsOther[$row->possession_pk] ?? null;
            if ($fromEmro !== null) {
                $lastReadingDisplayOther = $fromEmro['reading'];
            } else {
                $lastReadingDisplayOther = $formatDualMeterReading(
                    $row->epd_electric_meter_reading ?? null,
                    $hasEpoReading2 ? ($row->epd_electric_meter_reading_2 ?? null) : null,
                    $hasEpoReading2
                );
            }
            $rows[] = [
                'employee_type' => $row->employee_type ?? 'N/A',
                'name' => $row->emp_name ?? 'N/A',
                'house_no' => $row->house_no ?? 'N/A',
                'meter_reading_date' => $expectedReadingDate,
                'last_meter_reading' => $lastReadingDisplayOther,
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            return strcmp((string) ($a['house_no'] ?? ''), (string) ($b['house_no'] ?? ''));
        });
        $sno = 1;
        foreach ($rows as $i => $r) {
            $rows[$i]['sno'] = $sno++;
        }

        return response()->json(['status' => true, 'data' => $rows]);
    }

    /**
     * House Status report - view.
     * Tables: estate_unit_sub_type_master, estate_house_master, estate_eligibility_mapping,
     * salary_grade_master, estate_possession_details, estate_possession_other.
     */
    public function houseStatus()
    {
        return view('admin.estate.house_status');
    }

    /**
     * Estate Migration Report (1998–2026) – historical allotment data with filters.
     * Filter options come from distinct values in the report table.
     */
    public function estateMigrationReport(EstateMigrationReportDataTable $dataTable)
    {
        $years = EstateMigrationReport::select('allotment_year')
            ->whereNotNull('allotment_year')
            ->distinct()
            ->orderBy('allotment_year', 'desc')
            ->pluck('allotment_year');

        $campuses = EstateMigrationReport::select('campus_name')
            ->whereNotNull('campus_name')
            ->where('campus_name', '!=', '')
            ->distinct()
            ->orderBy('campus_name')
            ->pluck('campus_name');

        $buildings = EstateMigrationReport::select('building_name')
            ->whereNotNull('building_name')
            ->where('building_name', '!=', '')
            ->distinct()
            ->orderBy('building_name')
            ->pluck('building_name');

        $buildingTypes = EstateMigrationReport::select('type_of_building')
            ->whereNotNull('type_of_building')
            ->where('type_of_building', '!=', '')
            ->distinct()
            ->orderBy('type_of_building')
            ->pluck('type_of_building');

        $houseNos = EstateMigrationReport::select('house_no')
            ->whereNotNull('house_no')
            ->where('house_no', '!=', '')
            ->distinct()
            ->orderBy('house_no')
            ->pluck('house_no');

        $employeeNames = EstateMigrationReport::select('employee_name')
            ->whereNotNull('employee_name')
            ->where('employee_name', '!=', '')
            ->distinct()
            ->orderBy('employee_name')
            ->pluck('employee_name')
            ->merge(
                EmployeeMaster::query()
                    ->select(DB::raw("TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) as employee_name"))
                    ->whereRaw("TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) != ''")
                    ->distinct()
                    ->orderBy('employee_name')
                    ->pluck('employee_name')
            )
            ->filter(fn ($name) => !empty($name))
            ->unique()
            ->sort()
            ->values();

        $departments = EstateMigrationReport::select('department_name')
            ->whereNotNull('department_name')
            ->where('department_name', '!=', '')
            ->distinct()
            ->orderBy('department_name')
            ->pluck('department_name');

        $employeeTypes = EstateMigrationReport::select('employee_type')
            ->whereNotNull('employee_type')
            ->where('employee_type', '!=', '')
            ->distinct()
            ->orderBy('employee_type')
            ->pluck('employee_type');

        return $dataTable->render('admin.estate.estate_migration_report', compact(
            'years', 'campuses', 'buildings', 'buildingTypes', 'houseNos', 'employeeNames', 'departments', 'employeeTypes'
        ));
    }

    /**
     * API: Get cascading filter options for Estate Migration Report.
     * Options depend on upstream filters: year → campus → building → type → department → employee type.
     * Each dropdown only considers filters that come before it in the chain.
     */
    public function getEstateMigrationReportFilterOptions(Request $request)
    {
        $year = $request->query('year');
        $campus = $request->query('campus');
        $building = $request->query('building');
        $type = $request->query('type');
        $houseNo = $request->query('house_no');
        $employeeName = $request->query('employee_name');
        $department = $request->query('department');
        $employeeType = $request->query('employee_type');

        $response = [];

        // Years: no upstream filters
        $yearsQuery = EstateMigrationReport::query();
        $response['years'] = $yearsQuery->select('allotment_year')
            ->whereNotNull('allotment_year')
            ->distinct()
            ->orderBy('allotment_year', 'desc')
            ->pluck('allotment_year');

        // Campuses: filtered by year
        $campusesQuery = EstateMigrationReport::query();
        if ($year !== null && $year !== '') {
            $campusesQuery->where('allotment_year', (int) $year);
        }
        $response['campuses'] = $campusesQuery->select('campus_name')
            ->whereNotNull('campus_name')
            ->where('campus_name', '!=', '')
            ->distinct()
            ->orderBy('campus_name')
            ->pluck('campus_name');

        // Buildings: filtered by year, campus
        $buildingsQuery = EstateMigrationReport::query();
        if ($year !== null && $year !== '') {
            $buildingsQuery->where('allotment_year', (int) $year);
        }
        if ($campus !== null && $campus !== '') {
            $buildingsQuery->where('campus_name', $campus);
        }
        $response['buildings'] = $buildingsQuery->select('building_name')
            ->whereNotNull('building_name')
            ->where('building_name', '!=', '')
            ->distinct()
            ->orderBy('building_name')
            ->pluck('building_name');

        // Type of building: filtered by year, campus, building
        $typesQuery = EstateMigrationReport::query();
        if ($year !== null && $year !== '') {
            $typesQuery->where('allotment_year', (int) $year);
        }
        if ($campus !== null && $campus !== '') {
            $typesQuery->where('campus_name', $campus);
        }
        if ($building !== null && $building !== '') {
            $typesQuery->where('building_name', $building);
        }
        $response['buildingTypes'] = $typesQuery->select('type_of_building')
            ->whereNotNull('type_of_building')
            ->where('type_of_building', '!=', '')
            ->distinct()
            ->orderBy('type_of_building')
            ->pluck('type_of_building');

        // House numbers: filtered by year, campus, building, type
        $houseQuery = EstateMigrationReport::query();
        if ($year !== null && $year !== '') {
            $houseQuery->where('allotment_year', (int) $year);
        }
        if ($campus !== null && $campus !== '') {
            $houseQuery->where('campus_name', $campus);
        }
        if ($building !== null && $building !== '') {
            $houseQuery->where('building_name', $building);
        }
        if ($type !== null && $type !== '') {
            $houseQuery->where('type_of_building', $type);
        }
        $response['houseNos'] = $houseQuery->select('house_no')
            ->whereNotNull('house_no')
            ->where('house_no', '!=', '')
            ->distinct()
            ->orderBy('house_no')
            ->pluck('house_no');

        // Employee names: filtered by year, campus, building, type, house no
        $empNameQuery = EstateMigrationReport::query();
        if ($year !== null && $year !== '') {
            $empNameQuery->where('allotment_year', (int) $year);
        }
        if ($campus !== null && $campus !== '') {
            $empNameQuery->where('campus_name', $campus);
        }
        if ($building !== null && $building !== '') {
            $empNameQuery->where('building_name', $building);
        }
        if ($type !== null && $type !== '') {
            $empNameQuery->where('type_of_building', $type);
        }
        if ($houseNo !== null && $houseNo !== '') {
            $empNameQuery->where('house_no', $houseNo);
        }
        $response['employeeNames'] = $empNameQuery->select('employee_name')
            ->whereNotNull('employee_name')
            ->where('employee_name', '!=', '')
            ->distinct()
            ->orderBy('employee_name')
            ->pluck('employee_name')
            ->merge(
                EmployeeMaster::query()
                    ->select(DB::raw("TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) as employee_name"))
                    ->whereRaw("TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) != ''")
                    ->distinct()
                    ->orderBy('employee_name')
                    ->pluck('employee_name')
            )
            ->filter(fn ($name) => !empty($name))
            ->unique()
            ->sort()
            ->values();

        // Departments: filtered by year, campus, building, type, house no, employee name
        $deptQuery = EstateMigrationReport::query();
        if ($year !== null && $year !== '') {
            $deptQuery->where('allotment_year', (int) $year);
        }
        if ($campus !== null && $campus !== '') {
            $deptQuery->where('campus_name', $campus);
        }
        if ($building !== null && $building !== '') {
            $deptQuery->where('building_name', $building);
        }
        if ($type !== null && $type !== '') {
            $deptQuery->where('type_of_building', $type);
        }
        if ($houseNo !== null && $houseNo !== '') {
            $deptQuery->where('house_no', $houseNo);
        }
        if ($employeeName !== null && $employeeName !== '') {
            $deptQuery->where('employee_name', $employeeName);
        }
        $response['departments'] = $deptQuery->select('department_name')
            ->whereNotNull('department_name')
            ->where('department_name', '!=', '')
            ->distinct()
            ->orderBy('department_name')
            ->pluck('department_name');

        // Employee types: filtered by year, campus, building, type, house no, employee name, department
        $empTypeQuery = EstateMigrationReport::query();
        if ($year !== null && $year !== '') {
            $empTypeQuery->where('allotment_year', (int) $year);
        }
        if ($campus !== null && $campus !== '') {
            $empTypeQuery->where('campus_name', $campus);
        }
        if ($building !== null && $building !== '') {
            $empTypeQuery->where('building_name', $building);
        }
        if ($type !== null && $type !== '') {
            $empTypeQuery->where('type_of_building', $type);
        }
        if ($houseNo !== null && $houseNo !== '') {
            $empTypeQuery->where('house_no', $houseNo);
        }
        if ($employeeName !== null && $employeeName !== '') {
            $empTypeQuery->where('employee_name', $employeeName);
        }
        if ($department !== null && $department !== '') {
            $empTypeQuery->where('department_name', $department);
        }
        $response['employeeTypes'] = $empTypeQuery->select('employee_type')
            ->whereNotNull('employee_type')
            ->where('employee_type', '!=', '')
            ->distinct()
            ->orderBy('employee_type')
            ->pluck('employee_type');

        // Keep selected employee type in chain if present (supports narrow employee name list while preserving current selection behavior).
        if ($employeeType !== null && $employeeType !== '') {
            $response['employeeTypes'] = collect($response['employeeTypes'])->contains($employeeType)
                ? $response['employeeTypes']
                : collect($response['employeeTypes'])->push($employeeType)->sort()->values();
        }

        return response()->json($response);
    }

    /**
     * API: Get house status data for report (one row per quarter/house).
     * Columns: Sno., QtrNo, Building Name, Type, Allottee Name, Section/Designation,
     * Mobile Number, Alloted Date, Occupied Date, Vacated Date, Status.
     *
     * Mapping rules (current occupancy-centric):
     * - If there is an active LBSNAA possession (return_home_status = 0, estate_change_id = -1 or null),
     *   show it as Occupied with name/dates from possession_details + home_request_details.
     * - Else if there is an active Other possession (estate_possession_other.return_home_status = 0),
     *   show it as Occupied with name/dates from possession_other + estate_other_req.
     * - If neither stream has an active possession for a house, mark it as Vacant with VACANT label and blank dates.
     * - Under Renovation houses show status "Under Renovation" (no letter code).
     */
    public function getHouseStatusData(Request $request)
    {
        $hasEmployeeMobile = \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'mobile');
        $hasUnitTypeOnSubType = \Illuminate\Support\Facades\Schema::hasColumn('estate_unit_sub_type_master', 'estate_unit_type_master_pk');

        // Base house list (include vacant_renovation_status + used_home_status so report aligns with Define House)
        $houses = DB::table('estate_house_master as ehm')
            ->leftJoin('estate_block_master as eb', 'ehm.estate_block_master_pk', '=', 'eb.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->when($hasUnitTypeOnSubType, function ($q) {
                $q->leftJoin('estate_unit_type_master as eut', 'eust.estate_unit_type_master_pk', '=', 'eut.pk');
            }, function ($q) {
                $q->leftJoin('estate_unit_type_master as eut', 'ehm.estate_unit_master_pk', '=', 'eut.pk');
            })
            ->select(
                'ehm.pk as house_pk',
                'ehm.house_no',
                'ehm.used_home_status',
                'ehm.vacant_renovation_status',
                'eb.block_name',
                'eut.unit_type'
            )
            ->orderBy('eb.block_name')
            ->orderBy('ehm.house_no')
            ->get();

        $housePks = $houses->pluck('house_pk')->all();

        $lbsnaaActive = collect();
        $otherActive = collect();

        if (! empty($housePks)) {
            // Active LBSNAA possessions: return_home_status = 0 and (estate_change_id = -1 OR NULL — new allotments set null)
            $lbsnaaQuery = DB::table('estate_possession_details as epd')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->leftJoin('employee_master as em', 'ehrd.employee_pk', '=', 'em.' . $this->estateEmployeePkColumn())
                ->whereIn('epd.estate_house_master_pk', $housePks)
                ->whereNotNull('epd.estate_house_master_pk')
                ->where(function ($q) {
                    $q->where('epd.estate_change_id', -1)->orWhereNull('epd.estate_change_id');
                });
            if (\Illuminate\Support\Facades\Schema::hasColumn('estate_possession_details', 'return_home_status')) {
                $lbsnaaQuery->where('epd.return_home_status', 0);
            }
            $lbsnaaActive = $lbsnaaQuery->select(
                    'epd.estate_house_master_pk as house_pk',
                    'epd.allotment_date',
                    'epd.possession_date',
                    DB::raw('COALESCE(NULLIF(TRIM(ehrd.emp_name), \'\'), CONCAT(COALESCE(em.first_name, \'\'), \' \', COALESCE(em.last_name, \'\'))) as allottee_name'),
                    'ehrd.emp_designation as section_designation',
                    $hasEmployeeMobile ? 'em.mobile as mobile_number' : DB::raw('NULL as mobile_number'),
                    'epd.pk as possession_pk'
                )
                ->orderBy('epd.pk', 'desc')
                ->get()
                // latest possession per house
                ->unique('house_pk')
                ->keyBy('house_pk');

            // Active Other possessions (return_home_status = 0)
            $otherActive = DB::table('estate_possession_other as epo')
                ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
                ->whereIn('epo.estate_house_master_pk', $housePks)
                ->where('epo.return_home_status', 0)
                ->whereNotNull('epo.estate_house_master_pk')
                ->select(
                    'epo.estate_house_master_pk as house_pk',
                    'epo.allotment_date',
                    'epo.possession_date_oth as possession_date',
                    'eor.emp_name as allottee_name',
                    DB::raw('COALESCE(NULLIF(TRIM(eor.section), \'\'), eor.designation) as section_designation'),
                    'eor.mobile as mobile_number',
                    'epo.pk as possession_pk'
                )
                ->orderBy('epo.pk', 'desc')
                ->get()
                ->unique('house_pk')
                ->keyBy('house_pk');
        }

        $rows = [];
        $sno = 0;

        foreach ($houses as $h) {
            $sno++;
            $hpk = $h->house_pk;

            $lbsnaa = $lbsnaaActive->get($hpk);
            $other = $otherActive->get($hpk);

            // Prefer LBSNAA stream when both are somehow present for same house
            $pos = $lbsnaa ?: $other;

            $used = (int) ($h->used_home_status ?? 0);
            $vr = (int) ($h->vacant_renovation_status ?? 1);

            // High-level status character (O / V) driven by possession + used_home_status
            if ($vr === 0) {
                // Under Renovation
                $statusChar = 'V';
                $statusLabel = 'Under Renovation';
            } elseif ($pos || $used === 1) {
                // Any active possession or used_home_status=1 → Occupied
                $statusChar = 'O';
                $statusLabel = 'Occupied';
            } else {
                // No active possession and used_home_status=0 → Vacant
                $statusChar = 'V';
                $statusLabel = 'Vacant';
            }

            $allotmentDate = $pos->allotment_date ?? null;
            $occupiedDate = $pos->possession_date ?? null;

            $rows[] = [
                'sno' => $sno,
                'qtr_no' => $h->house_no ?? '—',
                'building_name' => $h->block_name ?? '—',
                'type' => $h->unit_type ?? '—',
                'allottee_name' => $pos && $pos->allottee_name ? trim($pos->allottee_name) : 'VACANT',
                'section_designation' => $pos && $pos->section_designation ? trim($pos->section_designation) : '',
                'mobile_number' => $pos && $pos->mobile_number ? trim((string) $pos->mobile_number) : '',
                'alloted_date' => $allotmentDate ? \Carbon\Carbon::parse($allotmentDate)->format('d/m/Y') : '',
                'occupied_date' => $occupiedDate ? \Carbon\Carbon::parse($occupiedDate)->format('d/m/Y') : '',
                'vacated_date' => '', // current implementation focuses on active occupancy; vacated date can be added later
                'status' => $statusLabel,
            ];
        }

        return response()->json(['status' => true, 'data' => $rows]);
    }
}
