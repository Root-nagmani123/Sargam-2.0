<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use App\Models\FC\FcTravelArrivalSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;

class FcTravelPlanReportService
{
    /**
     * Base query for FC travel plan report (matches admin export & DataTable).
     */
    public static function baseQuery(): Builder
    {
        $tpCol = fc_user_col('student_travel_plan_masters');
        $s1Col = fc_user_col('student_master_firsts');
        $smCol = fc_user_col('student_masters');

        // student_masters holds ONE ROW PER FORM the trainee has touched, so joining it on
        // user_id alone multiplied travel plans: a trainee registered on three forms appeared
        // three times in the list, and the Total / Submitted / Vehicle summary cards counted
        // those duplicates (16 plans rendered as 19 rows). The join now goes through a derived
        // table that picks exactly one student_masters row per trainee, so it is provably 1:1.
        // Form scoping moved to an EXISTS in applyFilters(), which cannot fan out either.
        $smPick = DB::table('student_masters')
            ->select(DB::raw("{$smCol} as uid"), DB::raw('MAX(id) as sm_id'))
            ->groupBy($smCol);

        $query = DB::table('student_travel_plan_masters as tp')
            ->leftJoin('student_master_firsts as s1', "tp.{$tpCol}", '=', "s1.{$s1Col}")
            ->leftJoinSub($smPick, 'smp', fn ($join) => $join->on('smp.uid', '=', "tp.{$tpCol}"))
            ->leftJoin('student_masters as sm', 'sm.id', '=', 'smp.sm_id')
            ->leftJoin('fc_travel_arrival_slots as fslot', 'tp.fc_travel_arrival_slot_id', '=', 'fslot.id')
            ->leftJoin('service_masters as svc', 's1.service_id', '=', 'svc.id')
            ->leftJoin('user_credentials as uc', 'uc.pk', '=', "tp.{$tpCol}");

        // A trainee who registered through /fc/login has no user_credentials row, so the join
        // above finds nothing and the Username column printed the numeric id. Their login name
        // lives on the roster instead. Joined on fc_registration_master.pk — a PRIMARY KEY, so
        // it stays 1:1 and cannot duplicate a plan.
        //
        // fc_report_apply_tracker_user_resolution() is deliberately NOT used here: it also
        // joins user_credentials a second time on `uc_frm.user_name = frm.user_id`, and
        // user_name is not unique — that turned 16 plans into 19 rows and would have inflated
        // the Total / Submitted / Vehicle summary cards, which count post-join rows. That
        // second join contributes nothing to the username anyway (uc_frm.user_name equals
        // frm.user_id whenever it matches).
        $hasRoster = fc_schema_has_table('fc_registration_master');
        if ($hasRoster) {
            $query->leftJoin('fc_registration_master as frm', 'frm.pk', '=', "tp.{$tpCol}");
        }

        $usernameSql = $hasRoster
            ? "COALESCE(NULLIF(TRIM(uc.user_name),''), NULLIF(TRIM(frm.user_id),''), CAST(tp.{$tpCol} AS CHAR))"
            : "COALESCE(NULLIF(TRIM(uc.user_name),''), CAST(tp.{$tpCol} AS CHAR))";

        return $query
            ->select([
                "tp.{$tpCol} as user_id",
                DB::raw($usernameSql.' as login_username'),
                'tp.joining_date',
                'tp.mode_of_journey',
                'tp.journey_vehicle_no',
                'tp.arrival_time_dehradun',
                'tp.require_academy_vehicle',
                'tp.is_submitted',
                // student_master_firsts.full_name is NULL on every record created through the
                // dynamic form (only the legacy flow populates it), so the report fell back to
                // the login username / numeric id and showed "41124" where a name belongs.
                // Same COALESCE-over-the-name-parts the other FC reports use.
                DB::raw("NULLIF(TRIM(COALESCE("
                    ."NULLIF(TRIM(s1.full_name),''), "
                    ."NULLIF(TRIM(CONCAT_WS(' ', NULLIF(TRIM(s1.first_name),''), NULLIF(TRIM(s1.middle_name),''), NULLIF(TRIM(s1.last_name),''))),'')"
                    .")), '') as full_name"),
                'sm.full_name as sm_full_name',
                DB::raw('COALESCE(NULLIF(TRIM(s1.roll_no), \'\'), sm.roll_no, s1.roll_no) AS roll_no'),
                's1.mobile_no',
                's1.session_id',
                'fslot.slot_label',
                'fslot.time_start',
                'fslot.time_end',
                DB::raw('COALESCE(svc.service_code, sm.service_code) AS service_code'),
            ]);
    }

    public static function applyFilters($query, Request $request): void
    {
        if ($request->filled('form_id') && fc_schema_has_column('student_masters', 'form_id')) {
            // EXISTS, not `sm.form_id = ?`: the sm join now resolves to a single arbitrary row
            // per trainee, which may not be the row for the requested form. "Has a registration
            // on this form" is also what the filter actually means, and it cannot fan out.
            $tpCol = fc_user_col('student_travel_plan_masters');
            $smCol = fc_user_col('student_masters');
            $formId = (int) $request->input('form_id');

            $query->whereExists(function ($sub) use ($tpCol, $smCol, $formId) {
                $sub->select(DB::raw(1))
                    ->from('student_masters as sm_f')
                    ->whereColumn("sm_f.{$smCol}", "tp.{$tpCol}")
                    ->where('sm_f.form_id', $formId);
            });
        }

        if ($request->filled('filter_slot_id')) {
            $query->where('tp.fc_travel_arrival_slot_id', (int) $request->filter_slot_id);
        }

        if ($request->filled('filter_mode') && $request->filter_mode !== '') {
            $query->where('tp.mode_of_journey', $request->filter_mode);
        }

        if ($request->filled('filter_vehicle') && $request->filter_vehicle !== '') {
            $v = $request->filter_vehicle;
            if ($v === 'yes') {
                $query->where('tp.require_academy_vehicle', 1);
            } elseif ($v === 'no') {
                $query->where(function ($q) {
                    $q->whereNull('tp.require_academy_vehicle')->orWhere('tp.require_academy_vehicle', 0);
                });
            }
        }

        // Rows with no joining_date yet (typical drafts) must not be dropped by arrival range.
        if ($request->filled('date_from')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('tp.joining_date')
                    ->orWhereDate('tp.joining_date', '>=', $request->date_from);
            });
        }
        if ($request->filled('date_to')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('tp.joining_date')
                    ->orWhereDate('tp.joining_date', '<=', $request->date_to);
            });
        }

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('tp.' . fc_user_col('student_travel_plan_masters'), 'like', $like)
                    ->orWhere('uc.user_name', 'like', $like)
                    ->orWhere('s1.full_name', 'like', $like)
                    ->orWhere('sm.full_name', 'like', $like)
                    ->orWhere('s1.roll_no', 'like', $like)
                    ->orWhere('s1.mobile_no', 'like', $like)
                    ->orWhere('sm.roll_no', 'like', $like);
            });
        }
    }

    /**
     * Human-readable filter summary for Excel/print headers.
     */
    public static function exportFilterDescription(Request $request): string
    {
        $bits = [];
        if ($request->filled('form_id')) {
            $form = FcForm::find((int) $request->input('form_id'));
            $bits[] = 'Form: '.($form?->form_name ?? $request->form_id);
        }
        if ($request->filled('filter_slot_id')) {
            $sl = FcTravelArrivalSlot::find((int) $request->filter_slot_id);
            $bits[] = 'Slot: '.($sl?->slot_label ?? $request->filter_slot_id);
        }
        if ($request->filled('filter_mode') && $request->filter_mode !== '') {
            $bits[] = 'Mode: '.$request->filter_mode;
        }
        if ($request->filled('filter_vehicle') && $request->filter_vehicle !== '') {
            $bits[] = 'Academy vehicle: '.($request->filter_vehicle === 'yes' ? 'Yes' : 'No');
        }
        if ($request->filled('date_from')) {
            $bits[] = 'Arrival from: '.$request->date_from;
        }
        if ($request->filled('date_to')) {
            $bits[] = 'Arrival to: '.$request->date_to;
        }

        return $bits !== [] ? implode(' | ', $bits) : 'No filters applied (all plans)';
    }
}
