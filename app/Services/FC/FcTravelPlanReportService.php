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

        return DB::table('student_travel_plan_masters as tp')
            ->leftJoin('student_master_firsts as s1', "tp.{$tpCol}", '=', "s1.{$s1Col}")
            ->leftJoin('student_masters as sm', "sm.{$smCol}", '=', "tp.{$tpCol}")
            ->leftJoin('fc_travel_arrival_slots as fslot', 'tp.fc_travel_arrival_slot_id', '=', 'fslot.id')
            ->leftJoin('service_masters as svc', 's1.service_id', '=', 'svc.id')
            ->leftJoin('user_credentials as uc', 'uc.pk', '=', "tp.{$tpCol}")
            ->select([
                "tp.{$tpCol} as user_id",
                DB::raw("COALESCE(NULLIF(TRIM(uc.user_name),''), CAST(tp.{$tpCol} AS CHAR)) as login_username"),
                'tp.joining_date',
                'tp.mode_of_journey',
                'tp.journey_vehicle_no',
                'tp.arrival_time_dehradun',
                'tp.require_academy_vehicle',
                'tp.is_submitted',
                's1.full_name',
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
        if ($request->filled('form_id') && \Illuminate\Support\Facades\Schema::hasColumn('student_masters', 'form_id')) {
            $query->where('sm.form_id', (int) $request->input('form_id'));
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
