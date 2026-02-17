<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\EstateOtherRequestDataTable;
use App\DataTables\EstatePossessionOtherDataTable;
use App\Http\Controllers\Controller;
use App\Models\EstateMonthReadingDetailsOther;
use App\Models\EstateOtherRequest;
use App\Models\EstatePossessionOther;
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
        } elseif ($request->filled('id')) {
            unset($data['create_date'], $data['created_by']);
            EstatePossessionOther::where('pk', $request->id)->update($data);
            $this->upsertMonthReadingOtherOnPossession(
                (int) $request->id,
                $validated['possession_date_oth'] ?? null,
                $validated['meter_reading_oth'] ?? null,
                $validated['house_no'] ?? ($house->house_no ?? null),
                $house?->meter_one ?? null,
                $house?->meter_two ?? null
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
                $house?->meter_two ?? null
            );
            $this->setHouseUsedStatus((int) $validated['estate_house_master_pk'], 1);
            $message = 'Possession added successfully.';
        }

        if ($request->get('redirect_to') === 'return-house') {
            return redirect()->route('admin.estate.return-house')->with('success', $message);
        }
        return redirect()
            ->route('admin.estate.request-for-others')
            ->with('success', $message);
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
            return [
                'pk' => $row->pk,
                'house_no' => $row->house_no ?? 'N/A',
                'name' => $req ? ($req->emp_name ?? 'N/A') : 'N/A',
                'last_reading_date' => $row->from_date ? $row->from_date->format('d/m/Y') : 'N/A',
                'meter_no' => $row->meter_one ?? $row->meter_two ?? 'N/A',
                'last_month_reading' => $row->last_month_elec_red ?? 'N/A',
                'curr_month_reading' => $row->curr_month_elec_red,
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
     * Generate next request number (oth-req-1, oth-req-2, ...)
     */
    private function generateRequestNo(): string
    {
        $nextPk = (int) EstateOtherRequest::max('pk') + 1;
        return 'oth-req-' . $nextPk;
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

        // Parse Y-m format to month number and year (DB stores bill_month as 1-12, bill_year as 4-digit)
        $parts = explode('-', $billMonth);
        $year = count($parts) === 2 ? (int) $parts[0] : (int) $billYear;
        $month = count($parts) === 2 ? (int) $parts[1] : (int) $billMonth;
        $billYearStr = (string) $year;
        $billMonthStr = (string) $month;
        if ($month < 1 || $month > 12) {
            return response()->json(['status' => true, 'data' => [], 'message' => 'Invalid bill month.']);
        }

        $pending = DB::table('estate_possession_details as epd')
            ->join('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->join('estate_home_request_details as ehrd', 'epd.estate_home_request_details', '=', 'ehrd.pk')
            ->leftJoin('estate_month_reading_details as emrd', function ($join) use ($billMonthStr, $billYearStr) {
                $join->on('emrd.estate_possession_details_pk', '=', 'epd.pk')
                    ->where('emrd.bill_month', '=', $billMonthStr)
                    ->where('emrd.bill_year', '=', $billYearStr);
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

        $lastReadings = [];
        if (!empty($possessionIds)) {
            $previousReadings = DB::table('estate_month_reading_details as emrd')
                ->whereIn('emrd.estate_possession_details_pk', $possessionIds)
                ->where(function ($q) use ($billYearStr, $billMonthStr) {
                    $q->where('emrd.bill_year', '<', $billYearStr)
                        ->orWhere(function ($q2) use ($billYearStr, $billMonthStr) {
                            $q2->where('emrd.bill_year', '=', $billYearStr)
                                ->whereRaw('CAST(emrd.bill_month AS UNSIGNED) < ?', [(int) $billMonthStr]);
                        });
                })
                ->select('emrd.estate_possession_details_pk', 'emrd.curr_month_elec_red', 'emrd.curr_month_elec_red2', 'emrd.to_date')
                ->orderByRaw('CAST(emrd.bill_year AS UNSIGNED) DESC, CAST(emrd.bill_month AS UNSIGNED) DESC')
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
     * API: Get house status data (dynamic from DB).
     * Per unit sub type: Types, Grade Pay, House Available, Under Construction, Total Projected,
     * Allotted to LBSNAA, Other, Vacant.
     */
    public function getHouseStatusData(Request $request)
    {
        $unitTypes = DB::table('estate_unit_sub_type_master as ust')
            ->select('ust.pk', 'ust.unit_sub_type')
            ->orderBy('ust.unit_sub_type')
            ->get();

        $houseCountsBySubType = DB::table('estate_house_master as ehm')
            ->whereNotNull('ehm.estate_unit_sub_type_master_pk')
            ->select('ehm.estate_unit_sub_type_master_pk', DB::raw('COUNT(*) as total'))
            ->groupBy('ehm.estate_unit_sub_type_master_pk')
            ->pluck('total', 'estate_unit_sub_type_master_pk');

        $allottedLbsnaaBySubType = DB::table('estate_possession_details as epd')
            ->join('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
            ->where('epd.return_home_status', 0)
            ->whereNotNull('epd.estate_house_master_pk')
            ->select('ehm.estate_unit_sub_type_master_pk', DB::raw('COUNT(DISTINCT ehm.pk) as cnt'))
            ->groupBy('ehm.estate_unit_sub_type_master_pk')
            ->pluck('cnt', 'estate_unit_sub_type_master_pk');

        $otherBySubType = DB::table('estate_possession_other as epo')
            ->join('estate_house_master as ehm', 'epo.estate_house_master_pk', '=', 'ehm.pk')
            ->where('epo.return_home_status', 0)
            ->select('ehm.estate_unit_sub_type_master_pk', DB::raw('COUNT(DISTINCT ehm.pk) as cnt'))
            ->groupBy('ehm.estate_unit_sub_type_master_pk')
            ->pluck('cnt', 'estate_unit_sub_type_master_pk');

        $gradePayBySubType = DB::table('estate_eligibility_mapping as eem')
            ->join('salary_grade_master as sgm', 'eem.salary_grade_master_pk', '=', 'sgm.pk')
            ->whereNotNull('eem.estate_unit_sub_type_master_pk')
            ->select('eem.estate_unit_sub_type_master_pk', DB::raw('GROUP_CONCAT(DISTINCT sgm.salary_grade ORDER BY sgm.salary_grade SEPARATOR ", ") as grade_pay'))
            ->groupBy('eem.estate_unit_sub_type_master_pk')
            ->pluck('grade_pay', 'estate_unit_sub_type_master_pk');

        $rows = [];
        foreach ($unitTypes as $ut) {
            $pk = $ut->pk;
            $total = (int) ($houseCountsBySubType[$pk] ?? 0);
            $underConstruction = 0;
            $allottedLbsnaa = (int) ($allottedLbsnaaBySubType[$pk] ?? 0);
            $other = (int) ($otherBySubType[$pk] ?? 0);
            $vacant = max(0, $total - $allottedLbsnaa - $other);
            $gradePay = $gradePayBySubType[$pk] ?? '-';

            $rows[] = [
                'types' => $ut->unit_sub_type ?? 'N/A',
                'grade_pay' => $gradePay,
                'house_available' => $total,
                'house_under_construction' => $underConstruction,
                'total_projected' => $total + $underConstruction,
                'allotted_lbsnaa' => $allottedLbsnaa,
                'other' => $other,
                'vacant' => $vacant,
            ];
        }

        return response()->json(['status' => true, 'data' => $rows]);
    }
}
