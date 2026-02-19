<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\EstateChangeRequestDataTable;
use App\DataTables\EstateOtherRequestDataTable;
use App\DataTables\EstatePossessionOtherDataTable;
use App\DataTables\EstateRequestForEstateDataTable;
use App\Http\Controllers\Controller;
use App\Models\EstateChangeHomeReqDetails;
use App\Models\EstateMonthReadingDetailsOther;
use App\Models\EstateHomeRequestDetails;
use App\Models\EstateOtherRequest;
use App\Models\EstatePossessionOther;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EstateController extends Controller
{
    /**
     * Estate Request for Others - Listing (dynamic from DB).
     */
    public function requestForOthers(EstateOtherRequestDataTable $dataTable)
    {
        return $dataTable->render('admin.estate.estate_request_for_others');
    }

    /**
     * Request For Estate - Listing from estate_home_request_details with possession details.
     */
    public function requestForEstate(EstateRequestForEstateDataTable $dataTable)
    {
        return $dataTable->render('admin.estate.request_for_estate');
    }

    /**
     * Store or update Request For Estate (estate_home_request_details).
     */
    public function storeRequestForEstate(Request $request)
    {
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
            'status' => 'required|integer|in:0,1,2',
            'remarks' => 'nullable|string|max:500',
            'current_alot' => 'nullable|string|max:100',
            'pos_from' => 'nullable|date',
            'pos_to' => 'nullable|date',
            'extension' => 'nullable|string|max:255',
        ];
        $validated = $request->validate($rules);

        // Generate / resolve Request ID
        if ($request->filled('id')) {
            // Editing: keep existing ID if none provided, otherwise use given one
            $reqId = $validated['req_id'] ?? null;
            if ($reqId === null || $reqId === '') {
                $existing = EstateHomeRequestDetails::findOrFail($request->id);
                $reqId = $existing->req_id;
            }
        } else {
            // Creating: always auto-generate in the format home-req-{number}
            $latestReqId = EstateHomeRequestDetails::whereNotNull('req_id')
                ->where('req_id', 'like', 'home-req-%')
                ->orderBy('pk', 'desc')
                ->value('req_id');

            $nextNumber = 1;
            if ($latestReqId && preg_match('/home-req-(\d+)/', $latestReqId, $m)) {
                $nextNumber = ((int) $m[1]) + 1;
            }
            $reqId = 'home-req-' . $nextNumber;
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
            'status' => (int) $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
            'current_alot' => $validated['current_alot'] ?? null,
            'pos_from' => $validated['pos_from'] ?? null,
            'pos_to' => $validated['pos_to'] ?? null,
            'extension' => $validated['extension'] ?? null,
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
     * Get employees list for Request For Estate dropdown (distinct by employee_id from estate_home_request_details).
     * Optional query: include_pk = ensure this pk is in the list (for edit mode).
     */
    public function getRequestForEstateEmployees(Request $request)
    {
        $rows = EstateHomeRequestDetails::query()
            ->select('pk', 'emp_name', 'employee_id')
            ->orderBy('pk', 'desc')
            ->get();
        $unique = $rows->unique('employee_id')->values();
        $includePk = (int) $request->query('include_pk', 0);
        if ($includePk && ! $unique->contains(fn ($r) => (int) $r->pk === $includePk)) {
            $extra = EstateHomeRequestDetails::find($includePk);
            if ($extra) {
                $unique = $unique->prepend($extra)->values();
            }
        }
        $list = $unique->map(function ($row) {
            return [
                'pk' => (int) $row->pk,
                'emp_name' => $row->emp_name ?? '',
                'employee_id' => $row->employee_id ?? '',
                'label' => trim(($row->emp_name ?? '') . ' (' . ($row->employee_id ?? '') . ')'),
            ];
        })->values()->all();
        return response()->json($list);
    }

    /**
     * Get one employee's details for Request For Estate form (by estate_home_request_details pk).
     */
    public function getRequestForEstateEmployeeDetails($pk)
    {
        $row = EstateHomeRequestDetails::find($pk);
        if (! $row) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return response()->json([
            'emp_name' => $row->emp_name ?? '',
            'employee_id' => $row->employee_id ?? '',
            'emp_designation' => $row->emp_designation ?? '',
            'pay_scale' => $row->pay_scale ?? '',
            'doj_pay_scale' => $row->doj_pay_scale ? \Carbon\Carbon::parse($row->doj_pay_scale)->format('Y-m-d') : '',
            'doj_academic' => $row->doj_academic ? \Carbon\Carbon::parse($row->doj_academic)->format('Y-m-d') : '',
            'doj_service' => $row->doj_service ? \Carbon\Carbon::parse($row->doj_service)->format('Y-m-d') : '',
            'eligibility_type_pk' => (int) ($row->eligibility_type_pk ?? 62),
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
            ->select('h.pk', 'h.house_no', 'b.block_name')
            ->orderBy('b.block_name')
            ->orderBy('h.house_no');

        if ($occupiedHousePks->isNotEmpty()) {
            $query->whereNotIn('h.pk', $occupiedHousePks->toArray());
        }

        $houses = $query->get()->map(function ($row) {
            $label = trim(($row->block_name ?? '') . ' - ' . ($row->house_no ?? ''));
            if ($label === '-') {
                $label = $row->house_no ?? (string) $row->pk;
            }
            return [
                'pk' => (int) $row->pk,
                'house_no' => $row->house_no ?? '',
                'block_name' => $row->block_name ?? '',
                'label' => $label,
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
     * Change Requests (Approved by HAC) - Listing from estate_change_home_req_details.
     */
    public function changeRequestHacApproved(EstateChangeRequestDataTable $dataTable)
    {
        return $dataTable->render('admin.estate.change_request_hac_approved');
    }

    /**
     * Approve change request - set change_ap_dis_status = 1.
     */
    public function approveChangeRequest($id)
    {
        $record = EstateChangeHomeReqDetails::where('estate_change_hac_status', 1)->findOrFail($id);
        $record->change_ap_dis_status = 1;
        $record->remarks = null;
        $record->save();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Change request approved successfully.']);
        }
        return redirect()->route('admin.estate.change-request-hac-approved')
            ->with('success', 'Change request approved successfully.');
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
        ]);

        $data = [
            'emp_name' => $validated['employee_name'],
            'f_name' => $validated['father_name'],
            'section' => $validated['section'],
            'doj_acad' => $validated['doj_academy'],
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
     */
    public function possessionView(Request $request)
    {
        $requesters = EstateOtherRequest::orderBy('emp_name')
            ->get(['pk', 'emp_name', 'request_no_oth', 'section']);

        $campuses = DB::table('estate_campus_master')
            ->orderBy('campus_name')
            ->get(['pk', 'campus_name']);

        $unitTypes = DB::table('estate_unit_type_master')
            ->orderBy('unit_type')
            ->get(['pk', 'unit_type']);

        $record = null;
        $preselectedRequester = null;
        if ($request->filled('id')) {
            $record = EstatePossessionOther::find($request->id);
        }
        if ($request->filled('requester_id')) {
            $preselectedRequester = $request->requester_id;
        }

        return view('admin.estate.estate_possession_view', compact(
            'requesters', 'campuses', 'unitTypes', 'record', 'preselectedRequester'
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
            'estate_unit_type_master_pk' => 'required|integer',
            'estate_block_master_pk' => 'required|integer',
            'estate_unit_sub_type_master_pk' => 'required|integer',
            'estate_house_master_pk' => 'required|integer',
            'possession_date_oth' => 'nullable|date',
            'allotment_date' => 'nullable|date',
            'meter_reading_oth' => 'nullable|integer',
            'house_no' => 'nullable|string|max:100',
        ]);

        $house = DB::table('estate_house_master')
            ->where('pk', $validated['estate_house_master_pk'])
            ->first();

        $data = [
            'estate_other_req_pk' => $validated['estate_other_req_pk'],
            'estate_campus_master_pk' => $validated['estate_campus_master_pk'],
            'estate_unit_type_master_pk' => $validated['estate_unit_type_master_pk'],
            'estate_block_master_pk' => $validated['estate_block_master_pk'],
            'estate_unit_sub_type_master_pk' => $validated['estate_unit_sub_type_master_pk'],
            'estate_house_master_pk' => $validated['estate_house_master_pk'],
            'possession_date_oth' => $validated['possession_date_oth'] ?? null,
            'allotment_date' => $validated['allotment_date'] ?? null,
            'meter_reading_oth' => $validated['meter_reading_oth'] ?? null,
            'house_no' => $validated['house_no'] ?? ($house->house_no ?? null),
            'status' => 0,
            'create_date' => now(),
            'created_by' => Auth::id(),
        ];

        if ($request->filled('id')) {
            unset($data['create_date'], $data['created_by']);
            EstatePossessionOther::where('pk', $request->id)->update($data);
            $message = 'Possession updated successfully.';
        } else {
            EstatePossessionOther::create($data);
            $message = 'Possession added successfully.';
        }

        return redirect()
            ->route('admin.estate.possession-for-others')
            ->with('success', $message);
    }

    /**
     * API: Get blocks for estate possession (by campus).
     */
    public function getPossessionBlocks(Request $request)
    {
        $campusId = $request->get('campus_id');
        if (!$campusId) {
            return response()->json(['status' => true, 'data' => []]);
        }

        $blocks = DB::table('estate_house_master as h')
            ->join('estate_block_master as b', 'h.estate_block_master_pk', '=', 'b.pk')
            ->where('h.estate_campus_master_pk', $campusId)
            ->select('b.pk', 'b.block_name')
            ->distinct()
            ->orderBy('b.block_name')
            ->get();

        return response()->json(['status' => true, 'data' => $blocks]);
    }

    /**
     * API: Get unit sub types for estate possession (by campus + block).
     */
    public function getPossessionUnitSubTypes(Request $request)
    {
        $campusId = $request->get('campus_id');
        $blockId = $request->get('block_id');
        if (!$campusId || !$blockId) {
            return response()->json(['status' => true, 'data' => []]);
        }

        $items = DB::table('estate_house_master as h')
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
     * API: Get houses for estate possession (by campus + block + unit_sub_type).
     */
    public function getPossessionHouses(Request $request)
    {
        $campusId = $request->get('campus_id');
        $blockId = $request->get('block_id');
        $unitSubTypeId = $request->get('unit_sub_type_id');
        if (!$campusId || !$blockId || !$unitSubTypeId) {
            return response()->json(['status' => true, 'data' => []]);
        }

        $houses = DB::table('estate_house_master')
            ->where('estate_campus_master_pk', $campusId)
            ->where('estate_block_master_pk', $blockId)
            ->where('estate_unit_sub_type_master_pk', $unitSubTypeId)
            ->select('pk', 'house_no')
            ->orderBy('house_no')
            ->get();

        return response()->json(['status' => true, 'data' => $houses]);
    }

    /**
     * Estate Possession for Others - Listing (dynamic from DB).
     */
    public function possessionForOthers(EstatePossessionOtherDataTable $dataTable)
    {
        return $dataTable->render('admin.estate.estate_possession_for_others');
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
     * Update Meter Reading of Other - Form page.
     */
    public function updateMeterReadingOfOther()
    {
        $campuses = DB::table('estate_campus_master')
            ->orderBy('campus_name')
            ->get(['pk', 'campus_name']);

        $unitTypes = DB::table('estate_unit_type_master')
            ->orderBy('unit_type')
            ->get(['pk', 'unit_type']);

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
            'campuses', 'unitTypes', 'billMonths', 'unitSubTypes'
        ));
    }

    /**
     * API: Get meter reading list for "Update Meter Reading of Other" (filtered).
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

        if ($billMonth) {
            $query->where('estate_month_reading_details_other.bill_month', $billMonth);
        }
        if ($billYear) {
            $query->where('estate_month_reading_details_other.bill_year', $billYear);
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
     */
    public function getMeterReadingDatesOther(Request $request)
    {
        $billMonth = $request->get('bill_month');
        $billYear = $request->get('bill_year');
        if (!$billMonth || !$billYear) {
            return response()->json(['status' => true, 'data' => []]);
        }
        $dates = EstateMonthReadingDetailsOther::where('bill_month', $billMonth)
            ->where('bill_year', $billYear)
            ->select('to_date')
            ->distinct()
            ->orderBy('to_date')
            ->get()
            ->map(fn($r) => ['value' => $r->to_date->format('Y-m-d'), 'label' => $r->to_date->format('d/m/Y')]);
        return response()->json(['status' => true, 'data' => $dates]);
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

        // Filter dropdown data
        $years = DB::table('estate_month_reading_details')
            ->distinct()
            ->orderByDesc('bill_year')
            ->pluck('bill_year');

        $months = DB::table('estate_month_reading_details')
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
}
