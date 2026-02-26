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
use App\Models\EstateHouse;
use App\Models\EstateMonthReadingDetails;
use App\Models\EstateChangeHomeReqDetails;
use App\Models\EstateMonthReadingDetailsOther;
use App\Models\EstateHomeRequestDetails;
use App\Models\EstateHomeReqApprovalMgmt;
use App\Models\EstateMigrationReport;
use App\Models\EstateOtherRequest;
use App\Models\EstatePossessionOther;
use App\Models\EmployeeMaster;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class EstateController extends Controller
{
    /**
     * In estate module, employee_master is used with pk_old when present (payroll_salary_master references pk_old).
     */
    private function estateEmployeePkColumn(): string
    {
        return \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'pk_old') ? 'pk_old' : 'pk';
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
        // Only unit sub types that exist in estate_eligibility_mapping (mapped data)
        $eligibilityTypes = DB::table('estate_eligibility_mapping as eem')
            ->join('estate_unit_sub_type_master as ust', 'eem.estate_unit_sub_type_master_pk', '=', 'ust.pk')
            ->whereNotNull('eem.estate_unit_sub_type_master_pk')
            ->select('ust.pk', 'ust.unit_sub_type')
            ->distinct()
            ->orderBy('ust.unit_sub_type')
            ->pluck('ust.unit_sub_type', 'ust.pk');

        View::share('eligibilityTypes', $eligibilityTypes);

        return $dataTable->render('admin.estate.request_for_estate', compact('eligibilityTypes'));
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
        $validated = $request->validate($rules);

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
            'employee_pk' => (int) ($request->input('employee_pk', 0)),
            'app_status' => (int) ($request->input('app_status', 0)),
            'hac_status' => (int) ($request->input('hac_status', 0)),
            'f_status' => (int) ($request->input('f_status', 0)),
            'change_status' => (int) ($request->input('change_status', 0)),
        ];

        if ($request->filled('id')) {
            $record = EstateHomeRequestDetails::findOrFail($request->id);
            $record->update($data);
            $message = 'Estate request updated successfully.';
        } else {
            $data['employee_pk'] = $data['employee_pk'] ?: 0;
            EstateHomeRequestDetails::create($data);
            $message = 'Estate request created successfully.';
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
        $empPkCol = $this->estateEmployeePkColumn();
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

        $query = DB::table('estate_house_master as h')
            ->join('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->join('estate_campus_master as a', 'h.estate_campus_master_pk', '=', 'a.pk')
            ->join('estate_unit_type_master as c', 'h.estate_unit_master_pk', '=', 'c.pk')
            ->join('estate_eligibility_mapping as e', function ($join) use ($hasEligibilityUnitType) {
                if ($hasEligibilityUnitType) {
                    $join->on('c.pk', '=', 'e.estate_unit_type_master_pk');
                } else {
                    $join->on('h.estate_unit_sub_type_master_pk', '=', 'e.estate_unit_sub_type_master_pk');
                }
            })
            ->join('salary_grade_master as sg', 'e.salary_grade_master_pk', '=', 'sg.pk')
            ->join('payroll_salary_master as ps', "sg.pk", '=', "ps.$salaryGradeCol")
            ->join('employee_master as em', 'ps.employee_master_pk', '=', 'em.' . $empPkCol)
            ->leftJoin('designation_master as d', 'em.designation_master_pk', '=', 'd.pk')
            ->select(
                'em.' . $empPkCol . ' as pk',
                DB::raw("TRIM(CONCAT(COALESCE(em.first_name, ''), ' ', COALESCE(em.middle_name, ''), ' ', COALESCE(em.last_name, ''))) as emp_name"),
                DB::raw($employeeIdSelect . ' as employee_id'),
                DB::raw("COALESCE(d.designation_name, '') as emp_designation")
            )
            ->where('em.status', 1)
            ->where('em.payroll', 0)
            ->distinct()
            ->orderByRaw("TRIM(CONCAT(COALESCE(em.first_name, ''), ' ', COALESCE(em.middle_name, ''), ' ', COALESCE(em.last_name, ''))) asc")
            ->orderBy('em.' . $empPkCol);

        $rows = $query->get();

        // Fallback: if no employees from estate-eligibility chain (e.g. no houses/payroll/eligibility setup), load from employee_master so dropdown shows names
        if ($rows->isEmpty()) {
            $rows = DB::table('employee_master as em')
                ->leftJoin('designation_master as d', 'em.designation_master_pk', '=', 'd.pk')
                ->select(
                    'em.' . $empPkCol . ' as pk',
                    DB::raw("TRIM(CONCAT(COALESCE(em.first_name, ''), ' ', COALESCE(em.middle_name, ''), ' ', COALESCE(em.last_name, ''))) as emp_name"),
                    DB::raw($employeeIdSelect . ' as employee_id'),
                    DB::raw("COALESCE(d.designation_name, '') as emp_designation")
                )
                ->where('em.status', 1)
                ->orderByRaw("TRIM(CONCAT(COALESCE(em.first_name, ''), ' ', COALESCE(em.middle_name, ''), ' ', COALESCE(em.last_name, ''))) asc")
                ->orderBy('em.' . $empPkCol)
                ->get();
        }

        $includePk = (int) $request->query('include_pk', 0);
        if ($includePk > 0) {
            $currentReq = EstateHomeRequestDetails::find($includePk);
            if ($currentReq) {
                $currentEmployeePk = (int) ($currentReq->employee_pk ?? 0);
                if ($currentEmployeePk > 0 && ! $rows->contains(fn ($r) => (int) $r->pk === $currentEmployeePk)) {
                    $extra = DB::table('employee_master as em')
                        ->leftJoin('designation_master as d', 'em.designation_master_pk', '=', 'd.pk')
                        ->select(
                            'em.' . $empPkCol . ' as pk',
                            DB::raw("TRIM(CONCAT(COALESCE(em.first_name, ''), ' ', COALESCE(em.middle_name, ''), ' ', COALESCE(em.last_name, ''))) as emp_name"),
                            DB::raw($employeeIdSelect . ' as employee_id'),
                            DB::raw("COALESCE(d.designation_name, '') as emp_designation")
                        )
                        ->where('em.' . $empPkCol, $currentEmployeePk)
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

        return response()->json($list);
    }

    /**
     * Get one employee's details for Request For Estate form (by employee_master pk).
     * Backward compatible: if pk is not employee_master, tries estate_home_request_details.
     */
    public function getRequestForEstateEmployeeDetails($pk)
    {
        $pk = (int) $pk;
        $empPkCol = $this->estateEmployeePkColumn();
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

        $employee = DB::table('employee_master as e')
            ->leftJoin('designation_master as d', 'e.designation_master_pk', '=', 'd.pk')
            ->select(
                'e.pk',
                DB::raw("TRIM(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, ''))) as emp_name"),
                DB::raw($employeeIdSelect . ' as employee_id'),
                DB::raw("COALESCE(d.designation_name, '') as emp_designation"),
                'e.doj',
                'e.payroll_date'
            )
            ->where('e.' . $empPkCol, $pk)
            ->first();

        if ($employee) {
            $salary = DB::table('payroll_salary_master as p')
                ->join('salary_grade_master as s', "p.$salaryGradeCol", '=', 's.pk')
                ->select("p.$salaryGradeCol as salary_grade_pk", 's.salary_grade', 'p.modified_date')
                ->where('p.employee_master_pk', $pk)
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

            return response()->json([
                'emp_name' => (string) ($employee->emp_name ?? ''),
                'employee_id' => (string) ($employee->employee_id ?? ''),
                'emp_designation' => (string) ($employee->emp_designation ?? ''),
                'pay_scale' => (string) ($salary?->salary_grade ?? ''),
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
            ->pluck('estate_house_master_pk')
            ->merge(
                DB::table('estate_possession_other')
                    ->whereNotNull('estate_house_master_pk')
                    ->pluck('estate_house_master_pk')
            )
            ->unique()
            ->values();

        $query = DB::table('estate_house_master as h')
            ->join('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->where('h.estate_unit_sub_type_master_pk', $eligibilityTypePk)
            ->where('h.used_home_status', 0)
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
            return (object) [
                'pk' => (int) $row->pk,
                'estate_change_req_ID' => $row->estate_change_req_ID ?? ('Chg-' . $row->pk),
                'change_house_no' => $row->change_house_no ?? '—',
                'change_req_date' => $row->change_req_date ? \Carbon\Carbon::parse($row->change_req_date)->format('d-m-Y H:i') : '—',
                'remarks' => $row->remarks ?? '—',
                'f_status_label' => (int) ($row->f_status ?? 0) === 1 ? 'Pending approval' : 'Decision made',
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
     */
    public function changeRequestDetails($id = null)
    {
        $changeRequestOptions = DB::table('estate_change_home_req_details')
            ->orderByDesc('pk')
            ->limit(300)
            ->get(['pk', 'estate_change_req_ID']);

        $selectedId = $id ? (int) $id : (int) ($changeRequestOptions->first()->pk ?? 0);
        $mapped = $selectedId > 0 ? $this->mapChangeRequestDetail($selectedId) : null;

        return view('admin.estate.change_request_details', [
            'detail' => $mapped['detail'] ?? null,
            'selectedChangeRequestId' => $selectedId > 0 ? $selectedId : null,
            'changeRequestOptions' => $changeRequestOptions,
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
     */
    public function changeRequestDetailsModal($id)
    {
        $mapped = $this->mapChangeRequestDetail((int) $id);
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
     */
    public function updateChangeRequestDetails(Request $request, $id)
    {
        $record = DB::table('estate_change_home_req_details')->where('pk', (int) $id)->first();
        if (! $record) {
            return redirect()->back()->with('error', 'Change request record not found.');
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
        ];

        $estateCampuses = DB::table('estate_campus_master')
            ->orderBy('campus_name')
            ->get(['pk', 'campus_name']);

        $unitTypes = DB::table('estate_unit_type_master')
            ->orderBy('unit_type')
            ->get(['pk', 'unit_type']);

        $buildings = DB::table('estate_block_master')
            ->orderBy('block_name')
            ->get(['pk', 'block_name']);

        $unitSubTypes = DB::table('estate_unit_sub_type_master')
            ->orderBy('unit_sub_type')
            ->get(['pk', 'unit_sub_type']);

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
            ->select('h.pk', 'h.house_no', 'h.estate_campus_master_pk', 'h.estate_unit_master_pk', 'h.estate_block_master_pk', 'h.estate_unit_sub_type_master_pk', 'b.block_name')
            ->when($detail->estate_campus_master_pk, fn ($q) => $q->where('h.estate_campus_master_pk', $detail->estate_campus_master_pk))
            ->when($detail->estate_unit_type_master_pk, fn ($q) => $q->where('h.estate_unit_master_pk', $detail->estate_unit_type_master_pk))
            ->when($detail->estate_block_master_pk, fn ($q) => $q->where('h.estate_block_master_pk', $detail->estate_block_master_pk))
            ->when($detail->estate_unit_sub_type_master_pk, fn ($q) => $q->where('h.estate_unit_sub_type_master_pk', $detail->estate_unit_sub_type_master_pk))
            ->where(function ($q) use ($occupiedHousePks, $detail) {
                $q->where(function ($nested) use ($occupiedHousePks) {
                    $nested->where('h.used_home_status', 0);
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
     */
    public function requestForHouse()
    {
        $latestPossessionSub = DB::table('estate_possession_details as ep')
            ->select('ep.estate_home_request_details', DB::raw('MAX(ep.pk) as latest_possession_pk'))
            ->whereNotNull('ep.estate_home_request_details')
            ->groupBy('ep.estate_home_request_details');

        $requests = DB::table('estate_change_home_req_details as ec')
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
                'epd.possession_date'
            )
            ->orderByDesc('ec.pk')
            ->get()
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

                return (object) [
                    'pk' => $row->pk,
                    'request_id' => $row->estate_change_req_ID ?: ('Chg-Req-' . $row->pk),
                    'request_date' => $row->change_req_date ? \Carbon\Carbon::parse($row->change_req_date)->format('d-m-Y') : '—',
                    'name' => $row->emp_name ?: '—',
                    'emp_id' => $row->employee_id ?: '—',
                    'doj_academy' => $row->doj_academic ? \Carbon\Carbon::parse($row->doj_academic)->format('d-m-Y') : '—',
                    'status' => $statusLabel,
                    'alloted_house' => $allottedHouse,
                    'eligibility_type' => $eligibilityLabel,
                    'possession_from' => $row->possession_date ? \Carbon\Carbon::parse($row->possession_date)->format('d-m-Y') : '—',
                    'possession_to' => '—',
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

        // Fetch vacant houses by eligibility type (same logic as getVacantHousesForEstateRequest)
        $occupiedHousePks = DB::table('estate_possession_details')
            ->whereNotNull('estate_house_master_pk')
            ->pluck('estate_house_master_pk')
            ->merge(
                DB::table('estate_possession_other')
                    ->whereNotNull('estate_house_master_pk')
                    ->pluck('estate_house_master_pk')
            )
            ->unique()
            ->values();

        $housesQuery = DB::table('estate_house_master as h')
            ->join('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->where('h.estate_unit_sub_type_master_pk', $eligibilityTypePk)
            ->where('h.used_home_status', 0)
            ->select('h.pk', 'h.house_no', 'b.block_name')
            ->orderBy('b.block_name')
            ->orderBy('h.house_no');

        if ($occupiedHousePks->isNotEmpty()) {
            $housesQuery->whereNotIn('h.pk', $occupiedHousePks->toArray());
        }

        $vacantHouses = $housesQuery->get()->map(function ($row) {
            $houseNo = trim($row->house_no ?? '') ?: (string) $row->pk;
            return [
                'pk' => (int) $row->pk,
                'house_no' => $row->house_no ?? '',
                'block_name' => $row->block_name ?? '',
                'label' => ($row->block_name ? $row->block_name . ' - ' : '') . $houseNo,
            ];
        });

        // Campuses and unit types per campus for dependent dropdowns
        $campuses = DB::table('estate_campus_master')
            ->orderBy('campus_name')
            ->get(['pk', 'campus_name']);

        $unitTypesByCampus = DB::table('estate_campus_master as a')
            ->join('estate_house_master as b', 'a.pk', '=', 'b.estate_campus_master_pk')
            ->join('estate_unit_type_master as c', 'b.estate_unit_master_pk', '=', 'c.pk')
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
    private function getEligibleHousePksByEmployeePk(int $employeePk): \Illuminate\Support\Collection
    {
        $housePks = collect();
        $empPkCol = $this->estateEmployeePkColumn();

        // Source 1 (requested): salary grades using payroll_salary_master.salary_grade_pk
        if (\Illuminate\Support\Facades\Schema::hasColumn('payroll_salary_master', 'salary_grade_pk')) {
            $gradeSql = "
                SELECT DISTINCT c.pk
                FROM employee_master a
                INNER JOIN payroll_salary_master b ON a.{$empPkCol} = b.employee_master_pk
                INNER JOIN salary_grade_master c ON b.salary_grade_pk = c.pk
                WHERE a.{$empPkCol} = ?
            ";

            $gradeRows = DB::select($gradeSql, [$employeePk]);
            $salaryGradePks = collect($gradeRows)->pluck('pk')->map(fn ($v) => (int) $v)->filter()->unique()->values();

            if ($salaryGradePks->isNotEmpty()) {
                $source1HousePks = DB::table('estate_eligibility_mapping as d')
                    ->join('estate_house_master as f', 'd.estate_unit_sub_type_master_pk', '=', 'f.estate_unit_sub_type_master_pk')
                    ->whereIn('d.salary_grade_master_pk', $salaryGradePks->all())
                    ->where('f.used_home_status', 0)
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
            WHERE a.{$empPkCol} = ?
            AND f.used_home_status = 0
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

        $empPkCol = $this->estateEmployeePkColumn();
        $employeePk = $homeReq->employee_pk ? (int) $homeReq->employee_pk : null;
        if (! $employeePk && $homeReq->employee_id) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'emp_id')) {
                $employeePk = DB::table('employee_master')->where('emp_id', $homeReq->employee_id)->value($empPkCol);
            }
            if (! $employeePk && \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'employee_id')) {
                $employeePk = DB::table('employee_master')->where('employee_id', $homeReq->employee_id)->value($empPkCol);
            }
            $employeePk = $employeePk ? (int) $employeePk : null;
        }

        $eligibilityTypePk = (int) ($homeReq->eligibility_type_pk ?? 62);
        $eligibilityLabel = 'Type-' . ($eligibilityTypePk == 61 ? 'I' : ($eligibilityTypePk == 62 ? 'II' : ($eligibilityTypePk == 63 ? 'III' : 'IV')));

        $occupiedHousePks = DB::table('estate_possession_details')
            ->whereNotNull('estate_house_master_pk')
            ->pluck('estate_house_master_pk')
            ->merge(
                DB::table('estate_possession_other')
                    ->whereNotNull('estate_house_master_pk')
                    ->pluck('estate_house_master_pk')
            )
            ->unique()
            ->values();

        // Dropdown = wahi query: employee → payroll_salary_master → salary_grade → estate_eligibility_mapping → unit_sub_type → estate_house_master, used_home_status=0 (occupied exclude)
        $eligibleHousePks = $employeePk ? $this->getEligibleHousePksByEmployeePk($employeePk)->unique()->values() : collect();
        $eligibleHousesCountFromQuery = $eligibleHousePks->count();

        $housesQuery = DB::table('estate_house_master as h')
            ->join('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->join('estate_campus_master as a', 'h.estate_campus_master_pk', '=', 'a.pk')
            ->join('estate_unit_type_master as c', 'h.estate_unit_master_pk', '=', 'c.pk')
            ->where('h.used_home_status', 0)
            ->where('h.estate_unit_sub_type_master_pk', $eligibilityTypePk)
            ->select(
                'h.pk',
                'h.house_no',
                'b.block_name',
                'h.estate_campus_master_pk as campus_pk',
                'a.campus_name',
                'h.estate_unit_master_pk as unit_type_pk',
                'c.unit_type'
            )
            ->orderBy('b.block_name')
            ->orderBy('h.house_no');

        if ($eligibleHousePks->isNotEmpty()) {
            $housesQuery->whereIn('h.pk', $eligibleHousePks->toArray());
        }

        if ($occupiedHousePks->isNotEmpty()) {
            $housesQuery->whereNotIn('h.pk', $occupiedHousePks->toArray());
        }

        $vacantHouses = $housesQuery->get()->map(function ($row) {
            $houseNo = trim($row->house_no ?? '') ?: (string) $row->pk;
            return [
                'pk' => (int) $row->pk,
                'house_no' => $row->house_no ?? '',
                'block_name' => $row->block_name ?? '',
                'campus_pk' => (int) ($row->campus_pk ?? 0),
                'campus_name' => $row->campus_name ?? '',
                'unit_type_pk' => (int) ($row->unit_type_pk ?? 0),
                'unit_type' => $row->unit_type ?? '',
                'label' => ($row->block_name ? $row->block_name . ' - ' : '') . $houseNo,
            ];
        });

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
     * Allot house to new request - insert into estate_possession_details (moves to Possession Details).
     */
    public function allotNewRequest(Request $request, $id)
    {
        $homeReq = EstateHomeRequestDetails::where('hac_status', 1)
            ->where('change_status', 0)
            ->findOrFail($id);

        $request->validate([
            'estate_house_master_pk' => 'required|integer|exists:estate_house_master,pk',
        ]);

        $estateHouseMasterPk = (int) $request->estate_house_master_pk;
        $employeePk = $homeReq->employee_pk ? (int) $homeReq->employee_pk : null;

        // If employee_pk is missing, try to resolve from employee_id via employee_master
        $empPkCol = $this->estateEmployeePkColumn();
        if (! $employeePk && $homeReq->employee_id) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'emp_id')) {
                $employeePk = DB::table('employee_master')->where('emp_id', $homeReq->employee_id)->value($empPkCol);
            }
            if (! $employeePk && \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'employee_id')) {
                $employeePk = DB::table('employee_master')->where('employee_id', $homeReq->employee_id)->value($empPkCol);
            }
            if ($employeePk) {
                $employeePk = (int) $employeePk;
                $homeReq->employee_pk = $employeePk;
                $homeReq->save();
            }
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

        $allotmentDate = now()->format('Y-m-d');

        if ($existingPossession) {
            $previousHousePk = (int) ($existingPossession->estate_house_master_pk ?? 0);
            DB::table('estate_possession_details')
                ->where('pk', $existingPossession->pk)
                ->update([
                    'estate_house_master_pk' => $estateHouseMasterPk,
                    'estate_change_id' => null,
                ]);
            $this->setHouseUsedStatus($estateHouseMasterPk, 1);
            if ($previousHousePk > 0 && $previousHousePk !== $estateHouseMasterPk) {
                $this->refreshHouseUsedStatusFromPossession($previousHousePk);
            }
        } else {
            DB::table('estate_possession_details')->insert([
                'estate_home_request_details' => $homeReq->pk,
                'emploee_master_pk' => $employeePk,
                'allotment_date' => $allotmentDate,
                'possession_date' => $allotmentDate,
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
                'message' => 'House allotted successfully. Record is now in Possession Details.',
            ]);
        }
        return redirect()->route('admin.estate.change-request-hac-approved')
            ->with('success', 'House allotted successfully. Record is now in Possession Details.');
    }

    /**
     * Approve change request - set change_ap_dis_status = 1 and allot house to employee.
     */
    public function approveChangeRequest(Request $request, $id)
    {
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

        // If employee_pk is missing, try to resolve from employee_id via employee_master
        $empPkCol = $this->estateEmployeePkColumn();
        if (! $employeePk && $homeReq->employee_id) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'emp_id')) {
                $employeePk = DB::table('employee_master')->where('emp_id', $homeReq->employee_id)->value($empPkCol);
            }
            if (! $employeePk && \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'employee_id')) {
                $employeePk = DB::table('employee_master')->where('employee_id', $homeReq->employee_id)->value($empPkCol);
            }
            if ($employeePk) {
                $employeePk = (int) $employeePk;
                $homeReq->employee_pk = $employeePk;
                $homeReq->save();
            }
        }

        if (! $employeePk) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Employee could not be resolved for this request. Please ensure the request has employee_pk or a valid employee_id linked in employee_master.'], 422);
            }
            return redirect()->route('admin.estate.change-request-hac-approved')
                ->with('error', 'Employee could not be resolved for this request. Please ensure the request has employee_pk or a valid employee_id linked in employee_master.');
        }

        // Check if employee already has possession (estate_possession_details)
        $existingPossession = DB::table('estate_possession_details')
            ->where('estate_home_request_details', $homeReqPk)
            ->first();

        $allotmentDate = now()->format('Y-m-d');

        if ($existingPossession) {
            $previousHousePk = (int) ($existingPossession->estate_house_master_pk ?? 0);
            DB::table('estate_possession_details')
                ->where('pk', $existingPossession->pk)
                ->update([
                    'estate_house_master_pk' => $estateHouseMasterPk,
                    'estate_change_id' => $record->estate_change_req_ID ?? null,
                ]);
            $this->setHouseUsedStatus($estateHouseMasterPk, 1);
            if ($previousHousePk > 0 && $previousHousePk !== $estateHouseMasterPk) {
                $this->refreshHouseUsedStatusFromPossession($previousHousePk);
            }
        } else {
            DB::table('estate_possession_details')->insert([
                'estate_home_request_details' => $homeReqPk,
                'emploee_master_pk' => $employeePk,
                'allotment_date' => $allotmentDate,
                'possession_date' => $allotmentDate,
                'electric_meter_reading' => 0,
                'estate_house_master_pk' => $estateHouseMasterPk,
                'estate_change_id' => $record->estate_change_req_ID ?? null,
            ]);
            $this->setHouseUsedStatus($estateHouseMasterPk, 1);
        }

        $record->change_ap_dis_status = 1;
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
        $request->validate([
            'disapprove_reason' => 'required|string|max:500',
        ]);

        $record = EstateChangeHomeReqDetails::where('estate_change_hac_status', 1)->findOrFail($id);
        $record->change_ap_dis_status = 2;
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
        $validated = $request->validate([
            'employee_name' => 'required|string|max:500',
            'father_name' => 'required|string|max:500',
            'section' => 'required|string|max:500',
            'doj_academy' => 'required|date',
            'designation' => 'nullable|string|max:500',
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

        // Unit types per campus: estate_campus_master a inner join estate_house_master b on a.pk=b.estate_campus_master_pk inner join estate_unit_type_master c on b.estate_unit_master_pk=c.pk
        $unitTypesByCampus = DB::table('estate_campus_master as a')
            ->join('estate_house_master as b', 'a.pk', '=', 'b.estate_campus_master_pk')
            ->join('estate_unit_type_master as c', 'b.estate_unit_master_pk', '=', 'c.pk')
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
        $validated = $request->validate([
            'estate_other_req_pk' => 'required|exists:estate_other_req,pk',
            'estate_campus_master_pk' => 'required|integer',
            'estate_block_master_pk' => 'required|integer',
            'estate_unit_sub_type_master_pk' => 'required|integer',
            'estate_house_master_pk' => 'required|integer',
            'possession_date_oth' => 'nullable|date',
            'allotment_date' => 'nullable|date',
            'returning_date' => 'nullable|date',
            'meter_reading_oth' => 'nullable|integer',
            'house_no' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:1000',
            'noc_document' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

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

        // Derive unit type from selected house (estate_house_master.estate_unit_master_pk)
        $derivedUnitTypePk = $house?->estate_unit_master_pk;

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
            'meter_reading_oth1' => null,
            'house_no' => $validated['house_no'] ?? ($house->house_no ?? null),
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
                    ->where('return_home_status', 0)
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
                $uploadedDocumentPath
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
                $validated['house_no'] ?? ($house->house_no ?? null),
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
                $validated['house_no'] ?? ($house->house_no ?? null),
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

    private function persistReturnHouseMeta(?int $possessionPk, ?string $remarks, ?string $uploadedDocumentPath): void
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

        $key = (string) $possessionPk;
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

        $isUsed = DB::table('estate_possession_details')
            ->where('estate_house_master_pk', $housePk)
            ->where('return_home_status', 0)
            ->exists()
            || DB::table('estate_possession_other')
                ->where('estate_house_master_pk', $housePk)
                ->where('return_home_status', 0)
                ->exists();

        $this->setHouseUsedStatus($housePk, $isUsed ? 1 : 0);
    }

    private function upsertMonthReadingOtherOnPossession(
        int $possessionPk,
        $possessionDate,
        $meterReading,
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

        $reading = EstateMonthReadingDetailsOther::where('estate_possession_other_pk', $possessionPk)
            ->where('bill_month', $billMonth)
            ->where('bill_year', $billYear)
            ->whereDate('to_date', $toDate)
            ->first();

        if ($reading) {
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
            if ($reading->last_month_elec_red === null && $meterReading !== null && $meterReading !== '') {
                $update['last_month_elec_red'] = (int) $meterReading;
            }
            $reading->update($update);
            return;
        }

        EstateMonthReadingDetailsOther::create([
            'estate_possession_other_pk' => $possessionPk,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'last_month_elec_red' => ($meterReading !== null && $meterReading !== '') ? (int) $meterReading : null,
            'curr_month_elec_red' => ($meterReading !== null && $meterReading !== '') ? (int) $meterReading : 0,
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
        $meterReading,
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

        $reading = EstateMonthReadingDetails::where('estate_possession_details_pk', $possessionPk)
            ->where('bill_month', $billMonth)
            ->where('bill_year', $billYear)
            ->whereDate('to_date', $toDate)
            ->first();

        if ($reading) {
            $update = [
                'house_no' => $houseNo,
                'meter_one' => $meterOne,
                'meter_two' => $meterTwo,
            ];
            if ($reading->last_month_elec_red === null && $meterReading !== null && $meterReading !== '') {
                $update['last_month_elec_red'] = (int) $meterReading;
            }
            $reading->update($update);
            return;
        }

        DB::table('estate_month_reading_details')->insert([
            'estate_possession_details_pk' => $possessionPk,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'last_month_elec_red' => ($meterReading !== null && $meterReading !== '') ? (int) $meterReading : null,
            'curr_month_elec_red' => ($meterReading !== null && $meterReading !== '') ? (int) $meterReading : 0,
            'bill_month' => $billMonth,
            'bill_year' => $billYear,
            'notify_employee_status' => 0,
            'process_status' => 0,
            'house_no' => $houseNo,
            'meter_one' => $meterOne,
            'meter_two' => $meterTwo,
            'created_date' => now(),
        ]);
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

        $blocks = DB::table('estate_house_master as h')
            ->join('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->where('h.estate_campus_master_pk', $campusId)
            ->when($unitTypeId, function ($q) use ($unitTypeId) {
                $q->where('h.estate_unit_master_pk', $unitTypeId);
            })
            ->select('b.pk', 'b.block_name')
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
     * API: Get blocks for Define House form (all blocks; optional campus filter for existing houses).
     */
    public function getDefineHouseBlocks(Request $request)
    {
        $campusId = $request->get('campus_id');
        if ($campusId) {
            $blocks = DB::table('estate_house_master as h')
                ->join('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
                ->where('h.estate_campus_master_pk', $campusId)
                ->select('b.pk', 'b.block_name')
                ->distinct()
                ->orderBy('b.block_name')
                ->get();
        } else {
            $blocks = DB::table('estate_block_master')
                ->orderBy('block_name')
                ->get(['pk', 'block_name']);
        }

        return response()->json(['status' => true, 'data' => $blocks]);
    }

    /**
     * Store new estate house(s) (estate_house_master).
     * Accepts multiple house rows: house_no[], meter_one[], meter_two[], licence_fee[], vacant_renovation_status[].
     * Common fields: estate_campus_master_pk, estate_unit_master_pk, estate_block_master_pk,
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
            'meter_one' => 'nullable|array',
            'meter_one.*' => 'nullable|string|max:30',
            'meter_two' => 'nullable|array',
            'meter_two.*' => 'nullable|string|max:30',
            'licence_fee' => 'nullable|array',
            'licence_fee.*' => 'nullable|numeric|min:0',
            'vacant_renovation_status' => 'required|array',
            'vacant_renovation_status.*' => 'required|in:0,1',
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

        $meterOnes = array_pad($validated['meter_one'] ?? [], $count, '');
        $meterTwos = array_pad($validated['meter_two'] ?? [], $count, '');
        $licenceFees = array_pad($validated['licence_fee'] ?? [], $count, 0);
        $statuses = array_pad($validated['vacant_renovation_status'] ?? [], $count, 1);

        $waterCharge = (float) ($validated['water_charge'] ?? 0);
        $electricCharge = (float) ($validated['electric_charge'] ?? 0);
        $remarks = $validated['remarks'] ?? '';

        for ($i = 0; $i < $count; $i++) {
            $data = [
                'estate_campus_master_pk' => $validated['estate_campus_master_pk'],
                'estate_unit_master_pk' => $validated['estate_unit_type_master_pk'],
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
        $query = DB::table('estate_house_master as h')
            ->leftJoin('estate_campus_master as c', 'h.estate_campus_master_pk', '=', 'c.pk')
            ->leftJoin('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->leftJoin('estate_unit_type_master as ut', 'h.estate_unit_master_pk', '=', 'ut.pk')
            ->leftJoin('estate_unit_sub_type_master as ust', 'h.estate_unit_sub_type_master_pk', '=', 'ust.pk')
            ->select(
                'h.pk',
                'c.campus_name as estate_name',
                'ut.unit_type',
                'b.block_name as building_name',
                'ust.unit_sub_type',
                'h.house_no',
                'h.water_charge',
                'h.electric_charge',
                'h.licence_fee',
                'h.vacant_renovation_status',
                'h.remarks'
            )
            ->orderBy('h.pk', 'desc');

        $total = $query->count();

        if ($request->filled('search.value')) {
            $term = $request->get('search')['value'];
            $query->where(function ($q) use ($term) {
                $q->where('c.campus_name', 'like', "%{$term}%")
                    ->orWhere('b.block_name', 'like', "%{$term}%")
                    ->orWhere('ut.unit_type', 'like', "%{$term}%")
                    ->orWhere('ust.unit_sub_type', 'like', "%{$term}%")
                    ->orWhere('h.house_no', 'like', "%{$term}%");
            });
        }

        $filtered = $query->count();

        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);
        if ($length > 0) {
            $query->offset($start)->limit($length);
        }

        $rows = $query->get();

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
        $row = DB::table('estate_house_master as h')
            ->leftJoin('estate_campus_master as c', 'h.estate_campus_master_pk', '=', 'c.pk')
            ->leftJoin('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->leftJoin('estate_unit_type_master as ut', 'h.estate_unit_master_pk', '=', 'ut.pk')
            ->leftJoin('estate_unit_sub_type_master as ust', 'h.estate_unit_sub_type_master_pk', '=', 'ust.pk')
            ->where('h.pk', $id)
            ->select(
                'h.pk',
                'h.estate_campus_master_pk',
                'h.estate_unit_master_pk',
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
                'h.remarks'
            )
            ->first();

        if (!$row) {
            return response()->json(['message' => 'House not found.'], 404);
        }

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
            'meter_one' => 'nullable|array',
            'meter_one.0' => 'nullable|string|max:30',
            'meter_two' => 'nullable|array',
            'meter_two.0' => 'nullable|string|max:30',
            'licence_fee' => 'nullable|array',
            'licence_fee.0' => 'nullable|numeric|min:0',
            'vacant_renovation_status' => 'required|array',
            'vacant_renovation_status.0' => 'required|in:0,1',
        ]);

        $house->estate_campus_master_pk = $validated['estate_campus_master_pk'];
        $house->estate_unit_master_pk = $validated['estate_unit_type_master_pk'];
        $house->estate_block_master_pk = $validated['estate_block_master_pk'];
        $house->estate_unit_sub_type_master_pk = $validated['estate_unit_sub_type_master_pk'];
        $house->house_no = $validated['house_no'][0];
        $house->water_charge = (float) ($validated['water_charge'] ?? 0);
        $house->electric_charge = (float) ($validated['electric_charge'] ?? 0);
        $house->licence_fee = (float) (($validated['licence_fee'] ?? [])[0] ?? 0);
        $house->meter_one = (int) preg_replace('/\D/', '', ($validated['meter_one'] ?? [])[0] ?? '') ?: 0;
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
     * Delete estate house.
     */
    public function destroyDefineHouse($id)
    {
        $house = EstateHouse::find($id);
        if (!$house) {
            return response()->json(['success' => false, 'message' => 'House not found.'], 404);
        }
        $house->delete();
        return response()->json(['success' => true, 'message' => 'Estate house deleted successfully.']);
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

        $items = DB::table('estate_house_master as h')
            ->join('estate_unit_sub_type_master as u', 'h.estate_unit_sub_type_master_pk', '=', 'u.pk')
            ->where('h.estate_campus_master_pk', $campusId)
            ->where('h.estate_block_master_pk', $blockId)
            ->when($unitTypeId, function ($q) use ($unitTypeId) {
                $q->where('h.estate_unit_master_pk', $unitTypeId);
            })
            ->select('u.pk', 'u.unit_sub_type')
            ->distinct()
            ->orderBy('u.unit_sub_type')
            ->get();

        return response()->json(['status' => true, 'data' => $items]);
    }

    /**
     * API: Get houses for estate possession (by campus + block + unit_sub_type).
     */
    public function getPossessionHouses(Request $request)
    {
        $campusId = $request->get('campus_id');
        $blockId = $request->get('block_id');
        $unitSubTypeId = $request->get('unit_sub_type_id');
        $unitTypeId = $request->get('unit_type_id');
        if (!$campusId || !$blockId || !$unitSubTypeId) {
            return response()->json(['status' => true, 'data' => []]);
        }

        $houses = DB::table('estate_house_master')
            ->where('estate_campus_master_pk', $campusId)
            ->where('estate_block_master_pk', $blockId)
            ->where('estate_unit_sub_type_master_pk', $unitSubTypeId)
            ->when($unitTypeId, function ($q) use ($unitTypeId) {
                $q->where('estate_unit_master_pk', $unitTypeId);
            })
            ->select('pk', 'house_no')
            ->orderBy('house_no')
            ->get();

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

        if ($includeHousePk) {
            $occupiedHousePks = $occupiedHousePks
                ->reject(fn ($pk) => (int) $pk === $includeHousePk)
                ->values();
        }

        $query = DB::table('estate_house_master')
            ->where('estate_block_master_pk', $blockId)
            ->where('estate_unit_sub_type_master_pk', $unitSubTypeId)
            ->where('used_home_status', 0)
            ->where(function ($q) {
                $q->whereNotNull('house_no')
                    ->where('house_no', '!=', '')
                    ->where('house_no', '!=', '0');
            })
            ->when($campusId, function ($q) use ($campusId) {
                $q->where('estate_campus_master_pk', $campusId);
            })
            ->when($unitTypeId, function ($q) use ($unitTypeId) {
                $q->where('estate_unit_master_pk', $unitTypeId);
            });

        if ($employeePk) {
            $eligibleHousePks = $this->getEligibleHousePksByEmployeePk($employeePk);
            if ($eligibleHousePks->isNotEmpty()) {
                $query->whereIn('pk', $eligibleHousePks->toArray());
            } else {
                return response()->json(['status' => true, 'data' => []]);
            }
        }

        if ($occupiedHousePks->isNotEmpty()) {
            $query->whereNotIn('pk', $occupiedHousePks->toArray());
        }

        $houses = $query->select('pk', 'house_no')
            ->orderBy('house_no')
            ->get();

        // Possession create pre-fill: include_house_pk (e.g. requester's allotted house) may be occupied;
        // include it in the list so House No. dropdown can pre-select it.
        if ($includeHousePk && $houses->where('pk', $includeHousePk)->isEmpty()) {
            $includeHouse = DB::table('estate_house_master')
                ->where('pk', $includeHousePk)
                ->where('estate_block_master_pk', $blockId)
                ->where('estate_unit_sub_type_master_pk', $unitSubTypeId)
                ->when($campusId, fn ($q) => $q->where('estate_campus_master_pk', $campusId))
                ->when($unitTypeId, fn ($q) => $q->where('estate_unit_master_pk', $unitTypeId))
                ->select('pk', 'house_no')
                ->first();
            if ($includeHouse) {
                $houses = $houses->prepend((object) ['pk' => (int) $includeHouse->pk, 'house_no' => $includeHouse->house_no])->values();
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
        $requesters = DB::table('estate_home_request_details as ehrd')
            ->join('estate_possession_details as epd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->where('ehrd.hac_status', 1)
            ->where(function ($q) {
                $q->whereNull('epd.estate_change_id')
                    ->orWhere('epd.estate_change_id', '!=', -1);
            })
            ->whereRaw('epd.pk = (SELECT MAX(epd2.pk) FROM estate_possession_details epd2 WHERE epd2.estate_home_request_details = ehrd.pk AND (epd2.estate_change_id IS NULL OR epd2.estate_change_id != -1))')
            ->select(
                'ehrd.pk',
                'ehrd.req_id',
                'ehrd.emp_name',
                'ehrd.emp_designation',
                'ehrd.employee_pk',
                'ehrd.employee_id',
                DB::raw('DATE(epd.allotment_date) as allotment_date'),
                DB::raw('DATE(epd.possession_date) as possession_date'),
                'epd.electric_meter_reading',
                'epd.estate_house_master_pk',
                'ehm.estate_campus_master_pk',
                'ehm.estate_block_master_pk',
                'ehm.estate_unit_master_pk as estate_unit_type_master_pk',
                'ehm.estate_unit_sub_type_master_pk'
            )
            ->orderBy('ehrd.emp_name')
            ->get();

        $campuses = DB::table('estate_campus_master')
            ->orderBy('campus_name')
            ->get(['pk', 'campus_name']);

        $unitTypesByCampus = DB::table('estate_campus_master as a')
            ->join('estate_house_master as b', 'a.pk', '=', 'b.estate_campus_master_pk')
            ->join('estate_unit_type_master as c', 'b.estate_unit_master_pk', '=', 'c.pk')
            ->select('a.pk as campus_pk', 'c.pk as unit_type_pk', 'c.unit_type')
            ->distinct()
            ->orderBy('a.pk')
            ->orderBy('c.unit_type')
            ->get()
            ->groupBy('campus_pk')
            ->map(fn ($rows) => $rows->map(fn ($r) => ['pk' => $r->unit_type_pk, 'unit_type' => $r->unit_type])->values()->all())
            ->all();

        $preselectedRequester = $request->query('requester_id');

        return view('admin.estate.possession_details_form', compact(
            'requesters',
            'campuses',
            'unitTypesByCampus',
            'preselectedRequester'
        ));
    }

    /**
     * Possession Details (LBSNAA) - Store form into estate_possession_details.
     */
    public function storePossessionDetails(Request $request)
    {
        $validated = $request->validate([
            'estate_home_request_details_pk' => 'required|integer|exists:estate_home_request_details,pk',
            'estate_campus_master_pk' => 'required|integer',
            'estate_block_master_pk' => 'required|integer',
            'estate_unit_sub_type_master_pk' => 'required|integer',
            'estate_house_master_pk' => 'required|integer|exists:estate_house_master,pk',
            'allotment_date' => 'required|date',
            'possession_date' => 'required|date',
            'electric_meter_reading' => 'nullable|integer|min:0',
        ]);

        $homeReq = EstateHomeRequestDetails::where('hac_status', 1)
            ->findOrFail((int) $validated['estate_home_request_details_pk']);

        $existingPossession = DB::table('estate_possession_details')
            ->where('estate_home_request_details', (int) $homeReq->pk)
            ->first();

        if (! $existingPossession) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Requester is not allotted yet. Please allot first from HAC Approved.');
        }

        $empPkCol = $this->estateEmployeePkColumn();
        $employeePk = $homeReq->employee_pk ? (int) $homeReq->employee_pk : null;
        if (! $employeePk && $homeReq->employee_id) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'emp_id')) {
                $employeePk = DB::table('employee_master')->where('emp_id', $homeReq->employee_id)->value($empPkCol);
            }
            if (! $employeePk && \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'employee_id')) {
                $employeePk = DB::table('employee_master')->where('employee_id', $homeReq->employee_id)->value($empPkCol);
            }
            if ($employeePk) {
                $employeePk = (int) $employeePk;
                $homeReq->employee_pk = $employeePk;
                $homeReq->save();
            }
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
            $eligibleHousePks = $this->getEligibleHousePksByEmployeePk($employeePk);
            if ($eligibleHousePks->isNotEmpty() && ! $eligibleHousePks->contains((int) $house->pk)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Selected house is not eligible for this requester.');
            }
        }

        $occupiedHousePks = DB::table('estate_possession_details')
            ->whereNotNull('estate_house_master_pk')
            ->pluck('estate_house_master_pk')
            ->merge(
                DB::table('estate_possession_other')
                    ->whereNotNull('estate_house_master_pk')
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
            'electric_meter_reading' => (int) ($validated['electric_meter_reading'] ?? 0),
            'estate_house_master_pk' => (int) $house->pk,
            'estate_change_id' => -1,
        ];

        if ($existingPossession) {
            $previousHousePk = (int) ($existingPossession->estate_house_master_pk ?? 0);
            DB::table('estate_possession_details')
                ->where('pk', (int) $existingPossession->pk)
                ->update($payload);
            $this->upsertMonthReadingOnPossession(
                (int) $existingPossession->pk,
                $validated['possession_date'] ?? null,
                $validated['electric_meter_reading'] ?? null,
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
                $validated['electric_meter_reading'] ?? null,
                $house->house_no ?? null,
                $house->meter_one ?? null,
                $house->meter_two ?? null
            );
            $this->setHouseUsedStatus((int) $house->pk, 1);
            $message = 'Possession details added successfully.';
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
     * Delete Estate Possession.
     */
    public function destroyPossession(Request $request, $id)
    {
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
        $unitTypesByCampus = DB::table('estate_campus_master as a')
            ->join('estate_house_master as b', 'a.pk', '=', 'b.estate_campus_master_pk')
            ->join('estate_unit_type_master as c', 'b.estate_unit_master_pk', '=', 'c.pk')
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
            $empPkCol = $this->estateEmployeePkColumn();
            $hasEmpId = \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'emp_id');
            $hasEmployeeId = \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'employee_id');

            $empIdJoinColumn = $hasEmpId
                ? 'em.emp_id'
                : ($hasEmployeeId ? 'em.employee_id' : null);

            $nameSelect = "COALESCE(NULLIF(TRIM(ehrd.emp_name), ''), NULLIF(TRIM(CONCAT(COALESCE(em.first_name, ''), ' ', COALESCE(em.last_name, ''))), ''), NULLIF(TRIM(ehrd.employee_id), ''), CONCAT('Request #', ehrd.pk))";

            $list = DB::table('estate_possession_details as epd')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->leftJoin('employee_master as em', function ($join) use ($empPkCol, $empIdJoinColumn) {
                    $join->on('em.' . $empPkCol, '=', 'ehrd.employee_pk');
                    if ($empIdJoinColumn) {
                        $join->orOn(DB::raw($empIdJoinColumn), '=', 'ehrd.employee_id');
                    }
                })
                ->where('epd.return_home_status', 0)
                ->whereNotNull('epd.estate_house_master_pk')
                ->where('epd.estate_change_id', -1)
                ->select('ehrd.pk as id', DB::raw($nameSelect . ' as name'), 'ehrd.req_id as request_no')
                ->distinct()
                ->orderByRaw($nameSelect . ' asc')
                ->get();
        } else {
            $otherNameSelect = "COALESCE(NULLIF(TRIM(eor.emp_name), ''), NULLIF(TRIM(eor.request_no_oth), ''), CONCAT('Request #', eor.pk))";

            $list = DB::table('estate_other_req as eor')
                ->join('estate_possession_other as epo', 'epo.estate_other_req_pk', '=', 'eor.pk')
                ->where('epo.return_home_status', 0)
                ->whereNotNull('epo.estate_house_master_pk')
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
            $row = DB::table('estate_home_request_details as ehrd')
                ->join('estate_possession_details as epd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
                ->leftJoin('estate_campus_master as ec', 'ehm.estate_campus_master_pk', '=', 'ec.pk')
                ->leftJoin('estate_block_master as eb', 'ehm.estate_block_master_pk', '=', 'eb.pk')
                ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                ->leftJoin('estate_unit_master as eum', 'ehm.estate_unit_master_pk', '=', 'eum.pk')
                ->leftJoin('estate_unit_type_master as eut', 'eum.estate_unit_type_master_pk', '=', 'eut.pk')
                ->where('ehrd.pk', $id)
                ->where('epd.return_home_status', 0)
                ->whereNotNull('epd.estate_house_master_pk')
                ->where('epd.estate_change_id', -1)
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
                    'ehrd.remarks as section_display'
                )
                ->orderByDesc('epd.pk')
                ->first();
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
        $pos = DB::table('estate_possession_other as epo')
            ->leftJoin('estate_campus_master as ec', 'epo.estate_campus_master_pk', '=', 'ec.pk')
            ->leftJoin('estate_block_master as eb', 'epo.estate_block_master_pk', '=', 'eb.pk')
            ->leftJoin('estate_unit_type_master as eut', 'epo.estate_unit_type_master_pk', '=', 'eut.pk')
            ->leftJoin('estate_unit_sub_type_master as eust', 'epo.estate_unit_sub_type_master_pk', '=', 'eust.pk')
            ->leftJoin('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
            ->where('epo.estate_other_req_pk', $id)
            ->where('epo.return_home_status', 0)
            ->whereNotNull('epo.estate_house_master_pk')
            ->orderBy('epo.pk', 'desc')
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
     */
    public function updateMeterReadingOfOther()
    {
        $campuses = DB::table('estate_campus_master')
            ->orderBy('campus_name')
            ->get(['pk', 'campus_name']);

        $unitTypesByCampus = DB::table('estate_campus_master as a')
            ->join('estate_house_master as b', 'a.pk', '=', 'b.estate_campus_master_pk')
            ->join('estate_unit_type_master as c', 'b.estate_unit_master_pk', '=', 'c.pk')
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

        return view('admin.estate.update_meter_reading_of_other', compact(
            'campuses', 'unitTypesByCampus', 'billMonths', 'unitSubTypes'
        ));
    }

    /**
     * Update Meter Reading - main page (employee/regular possession).
     * When possession_pk and bill_month are in query string (e.g. from List Meter Reading Edit),
     * loads prefill data so the form and table can be prefilled and auto-loaded.
     */
    public function updateMeterReading()
    {
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
        if ($possessionPk && $billMonthInput) {
            $parts = explode('-', $billMonthInput);
            $billYear = count($parts) >= 1 ? (string) ((int) $parts[0]) : null;
            $monthNum = count($parts) >= 2 ? (int) $parts[1] : null;
            $billMonthName = $monthNum ? date('F', mktime(0, 0, 0, $monthNum, 1)) : null;

            $possession = DB::table('estate_possession_details as epd')
                ->join('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
                ->where('epd.pk', $possessionPk)
                ->whereNotNull('epd.estate_house_master_pk')
                ->select(
                    'ehm.estate_campus_master_pk as campus_id',
                    'ehm.estate_block_master_pk as block_id',
                    'ehm.estate_unit_master_pk as unit_type_id',
                    'ehm.estate_unit_sub_type_master_pk as unit_sub_type_id'
                )
                ->first();

            $meterReadingDate = null;
            if ($billYear && $billMonthName) {
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
                    'bill_month' => $billMonthInput,
                    'bill_year' => $billYear,
                    'bill_month_name' => $billMonthName,
                    'campus_id' => (int) $possession->campus_id,
                    'block_id' => (int) $possession->block_id,
                    'unit_type_id' => $possession->unit_type_id ? (int) $possession->unit_type_id : null,
                    'unit_sub_type_id' => $possession->unit_sub_type_id ? (int) $possession->unit_sub_type_id : null,
                    'meter_reading_date' => $meterReadingDate,
                ];
            }
        }

        return view('admin.estate.update_meter_reading', compact(
            'campuses', 'unitTypes', 'billMonths', 'unitSubTypes', 'prefill'
        ));
    }

    /**
     * API: Get meter reading list for "Update Meter Reading" (filtered).
     */
    public function getMeterReadingList(Request $request)
    {
        $billMonth = $request->get('bill_month');
        $billYear = $request->get('bill_year');
        $meterReadingDate = $request->get('meter_reading_date');
        $campusId = $request->get('campus_id');
        $blockId = $request->get('block_id');
        $unitTypeId = $request->get('unit_type_id');
        $unitSubTypeId = $request->get('unit_sub_type_id');

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

        $query = EstateMonthReadingDetails::query()
            ->from('estate_month_reading_details as emrd')
            ->select([
                'emrd.pk',
                'emrd.from_date',
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
            ])
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->orderBy('emrd.house_no');

        if ($billMonth) {
            $query->where('emrd.bill_month', $billMonth);
        }
        if ($billYear) {
            $query->where('emrd.bill_year', $billYear);
        }
        if ($meterReadingDate) {
            $query->whereDate('emrd.to_date', $meterReadingDate);
        }
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

        $rows = collect();
        foreach ($query->get() as $row) {
            // Use estate_house_master meter numbers when estate_month_reading_details has 0/null
            $meterOne = $row->emrd_meter_one ?? $row->ehm_meter_one;
            $meterTwo = $row->emrd_meter_two ?? $row->ehm_meter_two;
            $hasMeterOne = $meterOne !== null && $meterOne !== '' && (int) $meterOne !== 0;
            $hasMeterTwo = $meterTwo !== null && $meterTwo !== '' && (int) $meterTwo !== 0;

            $base = [
                'pk' => $row->pk,
                'house_no' => $row->house_no ?? 'N/A',
                'name' => $row->emp_name ?? 'N/A',
                'last_reading_date' => $row->from_date ? \Carbon\Carbon::parse($row->from_date)->format('d/m/Y') : 'N/A',
            ];
            $pushed = false;
            // Meter 1 - prefill new_meter_no / new_meter_reading from existing when present
            if ($hasMeterOne) {
                $rows->push(array_merge($base, [
                    'meter_slot' => 1,
                    'old_meter_no' => (string) $meterOne,
                    'electric_meter_reading' => $row->last_month_elec_red !== null ? $row->last_month_elec_red : 'N/A',
                    'new_meter_no' => $row->emrd_meter_one !== null && $row->emrd_meter_one !== '' ? (string) $row->emrd_meter_one : '',
                    'new_meter_reading' => $row->curr_month_elec_red !== null && $row->curr_month_elec_red !== '' ? (string) $row->curr_month_elec_red : '',
                ]));
                $pushed = true;
            }
            // Meter 2
            if ($hasMeterTwo) {
                $rows->push(array_merge($base, [
                    'meter_slot' => 2,
                    'old_meter_no' => (string) $meterTwo,
                    'electric_meter_reading' => $row->last_month_elec_red2 !== null ? $row->last_month_elec_red2 : 'N/A',
                    'new_meter_no' => $row->emrd_meter_two !== null && $row->emrd_meter_two !== '' ? (string) $row->emrd_meter_two : '',
                    'new_meter_reading' => $row->curr_month_elec_red2 !== null && $row->curr_month_elec_red2 !== '' ? (string) $row->curr_month_elec_red2 : '',
                ]));
                $pushed = true;
            }
            // Fallback when neither meter has value
            if (!$pushed) {
                $lastMeter = $meterOne ?? $meterTwo ?? 'N/A';
                $lastReading = $row->last_month_elec_red ?? $row->last_month_elec_red2 ?? 'N/A';
                $rows->push(array_merge($base, [
                    'meter_slot' => 1,
                    'old_meter_no' => (string) $lastMeter,
                    'electric_meter_reading' => $lastReading,
                    'new_meter_no' => '',
                    'new_meter_reading' => $row->curr_month_elec_red !== null && $row->curr_month_elec_red !== '' ? (string) $row->curr_month_elec_red : '',
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
        $blocks = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_house_master as h', 'epd.estate_house_master_pk', '=', 'h.pk')
            ->join('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->where('h.estate_campus_master_pk', $campusId)
            ->select('b.pk', 'b.block_name')
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
        if (!$billMonth || !$billYear) {
            return response()->json(['status' => true, 'data' => []]);
        }
        $dates = EstateMonthReadingDetails::where('bill_month', $billMonth)
            ->where('bill_year', $billYear)
            ->select('to_date')
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
        $items = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_house_master as h', 'epd.estate_house_master_pk', '=', 'h.pk')
            ->join('estate_unit_sub_type_master as u', 'h.estate_unit_sub_type_master_pk', '=', 'u.pk')
            ->where('h.estate_campus_master_pk', $campusId)
            ->where('h.estate_block_master_pk', $blockId)
            ->select('u.pk', 'u.unit_sub_type')
            ->distinct()
            ->orderBy('u.unit_sub_type')
            ->get();
        return response()->json(['status' => true, 'data' => $items]);
    }

    /**
     * Store/Update meter readings for "Update Meter Reading" (regular possession).
     */
    public function storeMeterReadings(Request $request)
    {
        $validated = $request->validate([
            'readings' => 'required|array',
            'readings.*.pk' => 'required|exists:estate_month_reading_details,pk',
            'readings.*.meter_slot' => 'nullable|in:1,2',
            'readings.*.curr_month_elec_red' => 'nullable|numeric|min:0',
            'readings.*.new_meter_no' => 'nullable|string|max:50',
        ]);

        $readings = array_values($validated['readings']);

        foreach ($readings as $item) {
            $update = [];
            $meterSlot = (int) ($item['meter_slot'] ?? 1);
            $readingVal = array_key_exists('curr_month_elec_red', $item) ? $item['curr_month_elec_red'] : null;
            $readingNum = ($readingVal !== null && $readingVal !== '') ? (int) $readingVal : null;
            $newMeterNo = isset($item['new_meter_no']) ? trim((string) $item['new_meter_no']) : '';

            if ($meterSlot === 2) {
                $update['curr_month_elec_red2'] = $readingNum;
                if ($newMeterNo !== '') {
                    $update['meter_two'] = $newMeterNo;
                }
            } else {
                $update['curr_month_elec_red'] = $readingNum;
                if ($newMeterNo !== '') {
                    $update['meter_one'] = $newMeterNo;
                }
            }

            if (! empty($update)) {
                EstateMonthReadingDetails::where('pk', $item['pk'])->update($update);
            }
        }

        return redirect()
            ->route('admin.estate.update-meter-no')
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
     * API: Get Update Meter No. list for DataTable (one row per possession with old/new meter nos and readings).
     * Uses latest bill_month/bill_year if not provided.
     */
    public function getUpdateMeterNoList(Request $request)
    {
        $billMonth = $request->get('bill_month');
        $billYear = $request->get('bill_year');

        if (! $billMonth || ! $billYear) {
            $latest = EstateMonthReadingDetails::whereNotNull('bill_year')
                ->whereNotNull('bill_month')
                ->orderByDesc('bill_year')
                ->orderByRaw("FIELD(bill_month, 'January','February','March','April','May','June','July','August','September','October','November','December')")
                ->first(['bill_year', 'bill_month']);
            if ($latest) {
                $billYear = (string) $latest->bill_year;
                $billMonth = $latest->bill_month;
            } else {
                return response()->json(['status' => true, 'data' => []]);
            }
        } else {
            $billYear = (string) $billYear;
            if (is_numeric($billMonth) && (int) $billMonth >= 1 && (int) $billMonth <= 12) {
                $billMonth = date('F', mktime(0, 0, 0, (int) $billMonth, 1));
            }
        }

        $query = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->leftJoin('estate_block_master as b', 'ehm.estate_block_master_pk', '=', 'b.pk')
            ->leftJoin('estate_unit_type_master as ut', 'ehm.estate_unit_master_pk', '=', 'ut.pk')
            ->leftJoin('estate_unit_sub_type_master as ust', 'ehm.estate_unit_sub_type_master_pk', '=', 'ust.pk')
            ->leftJoin('employee_master as em', 'ehrd.employee_pk', '=', 'em.' . $this->estateEmployeePkColumn())
            ->leftJoin('employee_type_master as etm', 'em.emp_type', '=', 'etm.pk')
            ->where('epd.return_home_status', 0)
            ->whereNotNull('epd.estate_house_master_pk')
            ->select([
                'emrd.pk',
                'emrd.house_no',
                'emrd.meter_one as emrd_meter_one',
                'emrd.meter_two as emrd_meter_two',
                'emrd.last_month_elec_red',
                'emrd.curr_month_elec_red',
                'emrd.last_month_elec_red2',
                'emrd.curr_month_elec_red2',
                'ehm.meter_one as ehm_meter_one',
                'ehm.meter_two as ehm_meter_two',
                'ehrd.emp_name',
                'etm.category_type_name as employee_type',
                'ut.unit_type',
                'ust.unit_sub_type',
                'b.block_name as building_name',
            ])
            ->orderBy('b.block_name')
            ->orderBy('emrd.house_no');

        if ($billMonth && $billYear) {
            $query->where('emrd.bill_month', $billMonth)->where('emrd.bill_year', $billYear);
        }

        $rows = $query->get();

        $data = [];
        $sno = 1;
        foreach ($rows as $r) {
            $oldM1 = $r->ehm_meter_one !== null && $r->ehm_meter_one !== '' && (int) $r->ehm_meter_one !== 0 ? (string) $r->ehm_meter_one : '—';
            $newM1 = $r->emrd_meter_one !== null && $r->emrd_meter_one !== '' ? (string) $r->emrd_meter_one : '—';
            $oldM2 = $r->ehm_meter_two !== null && $r->ehm_meter_two !== '' && (int) $r->ehm_meter_two !== 0 ? (string) $r->ehm_meter_two : '—';
            $newM2 = $r->emrd_meter_two !== null && $r->emrd_meter_two !== '' ? (string) $r->emrd_meter_two : '—';
            $data[] = [
                'sn' => $sno++,
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
                'old_meter1_reading' => $r->last_month_elec_red !== null && $r->last_month_elec_red !== '' ? (string) $r->last_month_elec_red : '—',
                'new_meter1_reading' => $r->curr_month_elec_red !== null && $r->curr_month_elec_red !== '' ? (string) $r->curr_month_elec_red : '—',
                'old_meter2_reading' => $r->last_month_elec_red2 !== null && $r->last_month_elec_red2 !== '' ? (string) $r->last_month_elec_red2 : '—',
                'new_meter2_reading' => $r->curr_month_elec_red2 !== null && $r->curr_month_elec_red2 !== '' ? (string) $r->curr_month_elec_red2 : '—',
            ];
        }

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * API: Get meter reading list for "Update Meter Reading of Other" (filtered).
     * Accepts bill_month as numeric (1-12) from input type="month" and converts to month name for DB query.
     */
    public function getMeterReadingListOther(Request $request)
    {
        $billMonth = $request->get('bill_month');
        $billYear = $request->get('bill_year');
        $meterReadingDate = $request->get('meter_reading_date');
        $campusId = $request->get('campus_id');
        $blockId = $request->get('block_id');
        $unitTypeId = $request->get('unit_type_id');
        $unitSubTypeId = $request->get('unit_sub_type_id');

        $billMonthStr = $billMonth ? $this->normalizeBillMonthForOther($billMonth) : null;
        $billYearStr = $billYear ? (string) $billYear : null;

        $query = EstateMonthReadingDetailsOther::query()
            ->select([
                'estate_month_reading_details_other.pk',
                'estate_month_reading_details_other.estate_possession_other_pk',
                'estate_month_reading_details_other.from_date',
                'estate_month_reading_details_other.to_date',
                'estate_month_reading_details_other.last_month_elec_red',
                'estate_month_reading_details_other.curr_month_elec_red',
                'estate_month_reading_details_other.house_no',
                'estate_month_reading_details_other.meter_one',
                'estate_month_reading_details_other.meter_two',
            ])
            ->join('estate_possession_other as epo', 'estate_month_reading_details_other.estate_possession_other_pk', '=', 'epo.pk')
            ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
            ->orderBy('estate_month_reading_details_other.house_no');

        if ($billMonthStr) {
            $query->where('estate_month_reading_details_other.bill_month', $billMonthStr);
        }
        if ($billYearStr) {
            $query->where('estate_month_reading_details_other.bill_year', $billYearStr);
        }
        if ($meterReadingDate) {
            $query->whereDate('estate_month_reading_details_other.to_date', $meterReadingDate);
        }

        if ($campusId) {
            $query->where('epo.estate_campus_master_pk', $campusId);
        }
        if ($blockId) {
            $query->where('epo.estate_block_master_pk', $blockId);
        }
        if ($unitTypeId) {
            $query->where('epo.estate_unit_type_master_pk', $unitTypeId);
        }
        if ($unitSubTypeId) {
            $query->where('epo.estate_unit_sub_type_master_pk', $unitSubTypeId);
        }

        $query->with('estatePossessionOther.estateOtherRequest');
        $rows = $query->get()->map(function ($row) {
            $poss = $row->estatePossessionOther;
            $req = $poss ? $poss->estateOtherRequest : null;
            $last = $row->last_month_elec_red !== null ? (int) $row->last_month_elec_red : null;
            $curr = $row->curr_month_elec_red !== null ? (int) $row->curr_month_elec_red : null;
            $unit = ($last !== null && $curr !== null && $curr >= $last)
                ? ($curr - $last)
                : null;

            return [
                'pk' => $row->pk,
                'house_no' => $row->house_no ?? 'N/A',
                'name' => $req ? ($req->emp_name ?? 'N/A') : 'N/A',
                'last_reading_date' => $row->from_date ? $row->from_date->format('d/m/Y') : 'N/A',
                'meter_no' => $row->meter_one ?? $row->meter_two ?? 'N/A',
                'last_month_reading' => $last !== null ? $last : 'N/A',
                'curr_month_reading' => $curr,
                'unit' => $unit !== null ? $unit : 'N/A',
            ];
        });

        return response()->json(['status' => true, 'data' => $rows]);
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
        $blocks = DB::table('estate_possession_other as epo')
            ->join('estate_block_master as b', 'epo.estate_block_master_pk', '=', 'b.pk')
            ->where('epo.estate_campus_master_pk', $campusId)
            ->select('b.pk', 'b.block_name')
            ->distinct()
            ->orderBy('b.block_name')
            ->get();
        return response()->json(['status' => true, 'data' => $blocks]);
    }

    /**
     * API: Get meter reading dates for selected bill month.
     * Accepts bill_month as numeric (1-12) from input type="month" and converts to month name for DB query.
     */
    public function getMeterReadingDatesOther(Request $request)
    {
        $billMonth = $request->get('bill_month');
        $billYear = $request->get('bill_year');
        if (!$billMonth || !$billYear) {
            return response()->json(['status' => true, 'data' => []]);
        }
        $billMonthStr = $this->normalizeBillMonthForOther($billMonth);
        $billYearStr = (string) $billYear;
        $dates = EstateMonthReadingDetailsOther::where('bill_month', $billMonthStr)
            ->where('bill_year', $billYearStr)
            ->select('to_date')
            ->distinct()
            ->orderBy('to_date')
            ->get()
            ->map(fn($r) => ['value' => $r->to_date->format('Y-m-d'), 'label' => $r->to_date->format('d/m/Y')]);
        return response()->json(['status' => true, 'data' => $dates]);
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
        $items = DB::table('estate_possession_other as epo')
            ->join('estate_unit_sub_type_master as u', 'epo.estate_unit_sub_type_master_pk', '=', 'u.pk')
            ->where('epo.estate_campus_master_pk', $campusId)
            ->where('epo.estate_block_master_pk', $blockId)
            ->select('u.pk', 'u.unit_sub_type')
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
        $validated = $request->validate([
            'readings' => 'required|array',
            'readings.*.pk' => 'required|exists:estate_month_reading_details_other,pk',
            'readings.*.curr_month_elec_red' => 'nullable|integer|min:0',
        ]);

        foreach ($validated['readings'] as $item) {
            EstateMonthReadingDetailsOther::where('pk', $item['pk'])
                ->update(['curr_month_elec_red' => $item['curr_month_elec_red'] ?? null]);
        }

        return redirect()
            ->route('admin.estate.update-meter-reading-of-other')
            ->with('success', 'Meter readings updated successfully.');
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
        $unitSubTypePk = $request->get('unit_sub_type_pk');
        $bills = collect();

        if ($billMonth) {
            [$year, $month] = explode('-', $billMonth);
            $monthName = date('F', mktime(0, 0, 0, (int) $month, 1));
            $shortMonth = date('M', mktime(0, 0, 0, (int) $month, 1));

            $query = DB::table('estate_month_reading_details as emrd')
                ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
                ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                ->where('emrd.bill_year', $year)
                ->where('emrd.bill_month', $monthName)
                ->select(
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
                    'ehrd.emp_name',
                    'ehrd.employee_id',
                    'ehrd.emp_designation',
                    'eust.unit_sub_type'
                );

            if (!empty($unitSubTypePk)) {
                $query->where('ehm.estate_unit_sub_type_master_pk', $unitSubTypePk);
            }

            $bills = $query->orderBy('emrd.bill_no')->get();

            foreach ($bills as $b) {
                $b->from_date_formatted = $b->from_date ? \Carbon\Carbon::parse($b->from_date)->format('d-m-Y') : '—';
                $b->to_date_formatted = $b->to_date ? \Carbon\Carbon::parse($b->to_date)->format('d-m-Y') : '—';
                $b->house_display = $b->unit_sub_type && $b->house_no ? $b->unit_sub_type . '-(' . $b->house_no . ')' : ($b->house_no ?? '—');
                $b->grand_total = (float) ($b->electricty_charges ?? 0) + (float) ($b->water_charges ?? 0) + (float) ($b->licence_fees ?? 0);
            }
        }

        return view('admin.estate.generate_estate_bill', compact('unitSubTypes', 'bills', 'billMonth', 'unitSubTypePk'));
    }

    /**
     * Verify Selected Bills (LBSNAA): set notify_employee_status = 1 for selected estate_month_reading_details rows.
     * Verified bills then appear in "List Bill" / Bill Report Grid (notify_employee_status = 1).
     */
    public function verifySelectedBillsLbsna(Request $request)
    {
        $validated = $request->validate([
            'pks' => 'required|array',
            'pks.*' => 'integer|exists:estate_month_reading_details,pk',
        ]);
        $pks = array_map('intval', $validated['pks']);
        $updated = DB::table('estate_month_reading_details')->whereIn('pk', $pks)->update(['notify_employee_status' => 1]);
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
        $bill = null;

        // Filter dropdown data from estate_month_reading_details (and related tables)
        $years = DB::table('estate_month_reading_details')
            ->whereNotNull('bill_year')
            ->where('bill_year', '!=', '')
            ->distinct()
            ->orderByDesc('bill_year')
            ->pluck('bill_year');

        if ($years->isEmpty()) {
            $years = collect([(string) date('Y')]);
        }

        $months = DB::table('estate_month_reading_details')
            ->whereNotNull('bill_month')
            ->where('bill_month', '!=', '')
            ->distinct()
            ->orderByRaw("FIELD(bill_month, 'January','February','March','April','May','June','July','August','September','October','November','December')")
            ->pluck('bill_month');

        if ($months->isEmpty()) {
            $months = collect(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']);
        }

        $employeeTypes = DB::table('estate_unit_sub_type_master')
            ->orderBy('unit_sub_type')
            ->get(['pk', 'unit_sub_type']);

        $employees = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->whereNotNull('ehrd.pk')
            ->select('ehrd.pk', 'ehrd.emp_name', 'ehrd.employee_id')
            ->distinct()
            ->orderBy('ehrd.emp_name')
            ->get();

        $baseQuery = function () {
            return DB::table('estate_month_reading_details as emrd')
                ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
                ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                ->select(
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
                    'ehrd.emp_name',
                    'ehrd.employee_id',
                    'ehrd.emp_designation',
                    'eust.unit_sub_type'
                );
        };

        // Resolve bill: either by bill_no+month+year (direct link) or by month+year+employee_type+employee (filter form)
        if ($billNo && $month && $year) {
            $bill = $baseQuery()
                ->where('emrd.bill_no', $billNo)
                ->where('emrd.bill_month', $month)
                ->where('emrd.bill_year', $year)
                ->first();
        } elseif ($month && $year && $employeePk) {
            $query = $baseQuery()
                ->where('emrd.bill_month', $month)
                ->where('emrd.bill_year', $year)
                ->where('ehrd.pk', $employeePk);
            if (!empty($employeeTypePk)) {
                $query->where('ehm.estate_unit_sub_type_master_pk', $employeeTypePk);
            }
            $bill = $query->first();
        }

        if ($bill) {
            $bill->from_date_formatted = $bill->from_date ? \Carbon\Carbon::parse($bill->from_date)->format('d.m.Y') : '—';
            $bill->to_date_formatted = $bill->to_date ? \Carbon\Carbon::parse($bill->to_date)->format('d.m.Y') : '—';
            $bill->house_display = $bill->unit_sub_type && $bill->house_no ? $bill->unit_sub_type . '-(' . $bill->house_no . ')' : ($bill->house_no ?? '—');
            $bill->grand_total = (float) ($bill->electricty_charges ?? 0) + (float) ($bill->water_charges ?? 0) + (float) ($bill->licence_fees ?? 0);
        }

        return view('admin.estate.estate_bill_report_print', compact('bill', 'years', 'months', 'employeeTypes', 'employees'));
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

        if ($billMonth) {
            [$year, $month] = explode('-', $billMonth);
            $monthName = date('F', mktime(0, 0, 0, (int) $month, 1));

            $query = DB::table('estate_month_reading_details as emrd')
                ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
                ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                ->where('emrd.bill_year', $year)
                ->where('emrd.bill_month', $monthName)
                ->select(
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
                    'ehrd.emp_name',
                    'ehrd.employee_id',
                    'ehrd.emp_designation',
                    'eust.unit_sub_type'
                );

            if (!empty($unitSubTypePk)) {
                $query->where('ehm.estate_unit_sub_type_master_pk', $unitSubTypePk);
            }

            $bills = $query->orderBy('emrd.bill_no')->get();

            foreach ($bills as $b) {
                $b->from_date_formatted = $b->from_date ? \Carbon\Carbon::parse($b->from_date)->format('d.m.Y') : '—';
                $b->to_date_formatted = $b->to_date ? \Carbon\Carbon::parse($b->to_date)->format('d.m.Y') : '—';
                $b->house_display = $b->unit_sub_type && $b->house_no ? $b->unit_sub_type . '-(' . $b->house_no . ')' : ($b->house_no ?? '—');
                $b->grand_total = (float) ($b->electricty_charges ?? 0) + (float) ($b->water_charges ?? 0) + (float) ($b->licence_fees ?? 0);
            }
        }

        return view('admin.estate.estate_bill_report_print_all', compact('bills', 'billMonth', 'unitSubTypePk'));
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
            $monthName = date('F', mktime(0, 0, 0, (int) $month, 1));

            $query = DB::table('estate_month_reading_details as emrd')
                ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
                ->leftJoin('estate_unit_sub_type_master as eust', 'ehm.estate_unit_sub_type_master_pk', '=', 'eust.pk')
                ->where('emrd.bill_year', $year)
                ->where('emrd.bill_month', $monthName)
                ->select(
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
                    'ehrd.emp_name',
                    'ehrd.employee_id',
                    'ehrd.emp_designation',
                    'eust.unit_sub_type'
                );

            if (!empty($unitSubTypePk)) {
                $query->where('ehm.estate_unit_sub_type_master_pk', $unitSubTypePk);
            }

            $bills = $query->orderBy('emrd.bill_no')->get();

            foreach ($bills as $b) {
                $b->from_date_formatted = $b->from_date ? \Carbon\Carbon::parse($b->from_date)->format('d.m.Y') : '—';
                $b->to_date_formatted = $b->to_date ? \Carbon\Carbon::parse($b->to_date)->format('d.m.Y') : '—';
                $b->house_display = $b->unit_sub_type && $b->house_no ? $b->unit_sub_type . '-(' . $b->house_no . ')' : ($b->house_no ?? '—');
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

        $pdf = Pdf::loadView('admin.estate.estate_bill_report_print_all_pdf', compact('bills'))
            ->setPaper('a4', 'portrait');

        $filename = 'estate-bills-' . str_replace('-', '', $billMonth ?? 'all') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Generate next request number (oth-req-1, oth-req-2, ...)
     */
    private function generateRequestNo(): string
    {
        $nextPk = (int) EstateOtherRequest::max('pk') + 1;
        return 'oth-req-' . $nextPk;
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
        $billMonth = $request->get('bill_month');
        $blockId = $request->get('block_id');

        if (!$billMonth) {
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

        $query = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->leftJoin('estate_block_master as b', 'ehm.estate_block_master_pk', '=', 'b.pk')
            ->leftJoin('estate_unit_type_master as ut', 'ehm.estate_unit_master_pk', '=', 'ut.pk')
            ->leftJoin('estate_unit_sub_type_master as ust', 'ehm.estate_unit_sub_type_master_pk', '=', 'ust.pk')
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
            ->where(function ($q) {
                $q->whereNull('epd.return_home_status')
                    ->orWhere('epd.return_home_status', 0);
            })
            ->whereNotNull('epd.estate_house_master_pk')
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
                'dm.department_name as section',
                'ut.unit_type',
                'ust.unit_sub_type',
                'b.block_name as building_name',
                'epd.pk as possession_pk',
            ])
            ->orderBy('b.block_name')
            ->orderBy('emrd.house_no');

        if ($blockId && $blockId !== 'all' && $blockId !== '') {
            $query->where('ehm.estate_block_master_pk', $blockId);
        }

        $rows = $query->get();

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
                    'edit_url' => route('admin.estate.update-meter-reading-of-other'),
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
                    'edit_url' => route('admin.estate.update-meter-reading') . '?possession_pk=' . $r->possession_pk . '&bill_month=' . urlencode($billMonth),
                ];
            }
        }

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * Estate Bill Report - Grid View: only notified bills (notify_employee_status = 1), filtered by bill month.
     * Data mapping per estate_module_tables: estate_month_reading_details (LBSNA) + estate_month_reading_details_other (Other).
     */
    public function getBillReportGridData(Request $request)
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
        $billMonthStr = date('F', mktime(0, 0, 0, $monthNum, 1));

        $rows = collect();

        // LBSNA: estate_month_reading_details (notify_employee_status = 1) + possession + home_request + employee + house + block
        $lbsna = DB::table('estate_month_reading_details as emrd')
            ->join('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->leftJoin('estate_block_master as b', 'ehm.estate_block_master_pk', '=', 'b.pk')
            ->leftJoin('employee_master as em', 'ehrd.employee_pk', '=', 'em.' . $this->estateEmployeePkColumn())
            ->leftJoin('employee_type_master as etm', 'em.emp_type', '=', 'etm.pk')
            ->leftJoin('department_master as dm', 'em.department_master_pk', '=', 'dm.pk')
            ->where('emrd.bill_month', $billMonthStr)
            ->where('emrd.bill_year', $billYearStr)
            ->where('emrd.notify_employee_status', 1)
            ->where('epd.return_home_status', 0)
            ->whereNotNull('epd.estate_house_master_pk')
            ->select([
                'emrd.from_date',
                'emrd.to_date',
                'emrd.house_no',
                'emrd.meter_one',
                'emrd.meter_two',
                'emrd.last_month_elec_red',
                'emrd.curr_month_elec_red',
                'emrd.last_month_elec_red2',
                'emrd.curr_month_elec_red2',
                'emrd.meter_one_consume_unit',
                'emrd.meter_two_consume_unit',
                'emrd.electricty_charges',
                'emrd.water_charges',
                'emrd.licence_fees',
                'ehrd.emp_name',
                'etm.category_type_name as employee_type',
                'dm.department_name as section',
                'b.block_name as building_name',
            ])
            ->orderBy('b.block_name')
            ->orderBy('emrd.house_no')
            ->get();

        foreach ($lbsna as $r) {
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
            $rows->push([
                'employee_type' => $r->employee_type ?? 'LBSNA Employee',
                'name' => $r->emp_name ?? 'N/A',
                'section' => $r->section ?? 'N/A',
                'building_name' => $r->building_name ?? 'N/A',
                'house_no' => $r->house_no ?? 'N/A',
                'from_date' => $r->from_date ? \Carbon\Carbon::parse($r->from_date)->format('d-m-Y') : '—',
                'to_date' => $r->to_date ? \Carbon\Carbon::parse($r->to_date)->format('d-m-Y') : '—',
                'meter_no' => trim(($r->meter_one ?? '') . (isset($r->meter_two) && (string) $r->meter_two !== '' ? "\n" . $r->meter_two : '')),
                'prev_reading' => (string) $prev . (($prev2 > 0 || $curr2 > 0) ? "\n" . $prev2 : ''),
                'curr_reading' => (string) $curr . (($prev2 > 0 || $curr2 > 0) ? "\n" . $curr2 : ''),
                'unit_consumed' => (string) $units,
                'total_charge' => $totalCharge,
                'licence_fee' => $licence,
                'water_charges' => $water,
                'grand_total' => $totalCharge + $licence + $water,
            ]);
        }

        // Other: estate_month_reading_details_other (notify_employee_status = 1) + possession_other + estate_other_req + block
        $other = DB::table('estate_month_reading_details_other as emro')
            ->join('estate_possession_other as epo', 'emro.estate_possession_other_pk', '=', 'epo.pk')
            ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
            ->leftJoin('estate_block_master as b', 'epo.estate_block_master_pk', '=', 'b.pk')
            ->where('emro.bill_month', $billMonthStr)
            ->where('emro.bill_year', $billYearStr)
            ->where('emro.notify_employee_status', 1)
            ->where('epo.return_home_status', 0)
            ->select([
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
                'eor.emp_name',
                'eor.section',
                'b.block_name as building_name',
            ])
            ->orderBy('b.block_name')
            ->orderBy('emro.house_no')
            ->get();

        foreach ($other as $r) {
            $prev = (int) ($r->last_month_elec_red ?? 0);
            $curr = (int) ($r->curr_month_elec_red ?? 0);
            $prev2 = (int) ($r->last_month_elec_red2 ?? 0);
            $curr2 = (int) ($r->curr_month_elec_red2 ?? 0);
            $units = (($curr >= $prev) ? $curr - $prev : 0) + (($curr2 >= $prev2) ? $curr2 - $prev2 : 0);
            $totalCharge = (float) ($r->electricty_charges ?? 0);
            $licence = (float) ($r->licence_fees ?? 0);
            $water = (float) ($r->water_charges ?? 0);
            $rows->push([
                'employee_type' => 'Other Employee',
                'name' => $r->emp_name ?? 'N/A',
                'section' => $r->section ?? 'N/A',
                'building_name' => $r->building_name ?? 'N/A',
                'house_no' => $r->house_no ?? 'N/A',
                'from_date' => $r->from_date ? \Carbon\Carbon::parse($r->from_date)->format('d-m-Y') : '—',
                'to_date' => $r->to_date ? \Carbon\Carbon::parse($r->to_date)->format('d-m-Y') : '—',
                'meter_no' => trim(($r->meter_one ?? '') . (isset($r->meter_two) && (string) $r->meter_two !== '' ? "\n" . $r->meter_two : '')),
                'prev_reading' => (string) $prev . (($prev2 > 0 || $curr2 > 0) ? "\n" . $prev2 : ''),
                'curr_reading' => (string) $curr . (($prev2 > 0 || $curr2 > 0) ? "\n" . $curr2 : ''),
                'unit_consumed' => (string) $units,
                'total_charge' => $totalCharge,
                'licence_fee' => $licence,
                'water_charges' => $water,
                'grand_total' => $totalCharge + $licence + $water,
            ]);
        }

        $data = $rows->values()->map(function ($row, $index) {
            $row['sno'] = $index + 1;
            return $row;
        })->all();

        return response()->json(['status' => true, 'data' => $data]);
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
     * Shows all readings for the selected month (return_home_status = 0); notify_employee_status not required for listing.
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
        $billMonthStr = date('F', mktime(0, 0, 0, $monthNum, 1));

        $other = DB::table('estate_month_reading_details_other as emro')
            ->join('estate_possession_other as epo', 'emro.estate_possession_other_pk', '=', 'epo.pk')
            ->join('estate_other_req as eor', 'epo.estate_other_req_pk', '=', 'eor.pk')
            ->leftJoin('estate_block_master as b', 'epo.estate_block_master_pk', '=', 'b.pk')
            ->where('emro.bill_month', $billMonthStr)
            ->where('emro.bill_year', $billYearStr)
            ->where('epo.return_home_status', 0)
            ->whereNotNull('epo.estate_house_master_pk')
            ->select([
                'emro.pk',
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
                'eor.emp_name',
                'eor.section',
                'b.block_name as building_name',
            ])
            ->orderBy('b.block_name')
            ->orderBy('emro.house_no')
            ->get();

        $rows = [];
        foreach ($other as $r) {
            $prev = (int) ($r->last_month_elec_red ?? 0);
            $curr = (int) ($r->curr_month_elec_red ?? 0);
            $prev2 = (int) ($r->last_month_elec_red2 ?? 0);
            $curr2 = (int) ($r->curr_month_elec_red2 ?? 0);
            $units = (($curr >= $prev) ? $curr - $prev : 0) + (($curr2 >= $prev2) ? $curr2 - $prev2 : 0);
            $totalCharge = (float) ($r->electricty_charges ?? 0);
            $licence = (float) ($r->licence_fees ?? 0);
            $water = (float) ($r->water_charges ?? 0);
            $grandTotal = $totalCharge + $licence + $water;
            $rows[] = [
                'pk' => $r->pk,
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
     * Tables: estate_possession_details, estate_house_master, estate_home_request_details, estate_month_reading_details.
     */
    public function pendingMeterReading()
    {
        return view('admin.estate.pending_meter_reading');
    }

    /**
     * API: Get pending meter reading list for selected bill month.
     * Returns possessions that do NOT have estate_month_reading_details for the given bill_month/bill_year.
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
        $billMonthStr = date('F', mktime(0, 0, 0, $month, 1)); // e.g. "December" – matches estate_month_reading_details.bill_month

        // If this month+year has no readings in DB, return empty (correct mapping: no data = no list).
        $hasReadingsForMonth = DB::table('estate_month_reading_details')
            ->where('bill_month', $billMonthStr)
            ->where('bill_year', $billYearStr)
            ->exists();
        if (!$hasReadingsForMonth) {
            return response()->json(['status' => true, 'data' => [], 'message' => 'No readings exist for this month.']);
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
            ->whereNull('emrd.pk')
            ->select([
                'epd.pk as possession_pk',
                'ehm.house_no',
                'ehrd.emp_name',
                'ehrd.emp_designation as employee_type',
            ])
            ->orderBy('ehm.house_no')
            ->get();

        $possessionIds = $pending->pluck('possession_pk')->unique()->values()->all();

        $monthOrderSql = "FIELD(emrd.bill_month, 'January','February','March','April','May','June','July','August','September','October','November','December')";
        $currentMonthOrder = (int) array_search($billMonthStr, ['January','February','March','April','May','June','July','August','September','October','November','December'], true) + 1;

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
                        'reading' => $row->curr_month_elec_red ?? $row->curr_month_elec_red2 ?? 'N/A',
                        'date' => $row->to_date ? \Carbon\Carbon::parse($row->to_date)->format('d/m/Y') : 'N/A',
                    ];
                }
            }
        }

        $expectedReadingDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('d/m/Y');

        $rows = [];
        $sno = 1;
        foreach ($pending as $row) {
            $last = $lastReadings[$row->possession_pk] ?? ['reading' => 'N/A', 'date' => 'N/A'];
            $rows[] = [
                'sno' => $sno++,
                'employee_type' => $row->employee_type ?? 'N/A',
                'name' => $row->emp_name ?? 'N/A',
                'house_no' => $row->house_no ?? 'N/A',
                'meter_reading_date' => $expectedReadingDate,
                'last_meter_reading' => is_numeric($last['reading']) ? (string) $last['reading'] : $last['reading'],
            ];
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
            'years', 'campuses', 'buildings', 'buildingTypes', 'departments', 'employeeTypes'
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
        $department = $request->query('department');

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

        // Departments: filtered by year, campus, building, type
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
        $response['departments'] = $deptQuery->select('department_name')
            ->whereNotNull('department_name')
            ->where('department_name', '!=', '')
            ->distinct()
            ->orderBy('department_name')
            ->pluck('department_name');

        // Employee types: filtered by year, campus, building, type, department
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
        if ($department !== null && $department !== '') {
            $empTypeQuery->where('department_name', $department);
        }
        $response['employeeTypes'] = $empTypeQuery->select('employee_type')
            ->whereNotNull('employee_type')
            ->where('employee_type', '!=', '')
            ->distinct()
            ->orderBy('employee_type')
            ->pluck('employee_type');

        return response()->json($response);
    }

    /**
     * API: Get house status data for report (one row per quarter/house).
     * Columns: Sno., QtrNo, Building Name, Type, Allottee Name, Section/Designation,
     * Mobile Number, Alloted Date, Occupied Date, Vacated Date, Status (O/V).
     *
     * Mapping rules (current occupancy-centric):
     * - If there is an active LBSNAA possession (estate_possession_details.return_home_status = 0, estate_change_id = -1),
     *   show it as Occupied (O) with name/dates from possession_details + home_request_details.
     * - Else if there is an active Other possession (estate_possession_other.return_home_status = 0),
     *   show it as Occupied (O) with name/dates from possession_other + estate_other_req.
     * - If neither stream has an active possession for a house, mark it as Vacant (V) with VACANT label and blank dates.
     */
    public function getHouseStatusData(Request $request)
    {
        $hasEmployeeMobile = \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'mobile');

        // Base house list
        $houses = DB::table('estate_house_master as ehm')
            ->leftJoin('estate_block_master as eb', 'ehm.estate_block_master_pk', '=', 'eb.pk')
            ->leftJoin('estate_unit_type_master as eut', 'ehm.estate_unit_master_pk', '=', 'eut.pk')
            ->select(
                'ehm.pk as house_pk',
                'ehm.house_no',
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
            // Active LBSNAA possessions (return_home_status = 0, estate_change_id = -1)
            $lbsnaaActive = DB::table('estate_possession_details as epd')
                ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
                ->leftJoin('employee_master as em', 'ehrd.employee_pk', '=', 'em.' . $this->estateEmployeePkColumn())
                ->whereIn('epd.estate_house_master_pk', $housePks)
                ->where('epd.return_home_status', 0)
                ->whereNotNull('epd.estate_house_master_pk')
                ->where('epd.estate_change_id', -1)
                ->select(
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

            $status = $pos ? 'O' : 'V';

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
                'status' => $status,
            ];
        }

        return response()->json(['status' => true, 'data' => $rows]);
    }
}
