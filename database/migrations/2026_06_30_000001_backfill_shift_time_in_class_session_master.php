<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Some class_session_master rows (e.g. "MDO Morning" / "MDO Evening") have a NULL
 * shift_time even though start_time and end_time are set. The calendar UI builds the
 * Shift dropdown label and option value from shift_time, so these rows render as
 * "MDO Morning ()" and submit an empty value. Backfill shift_time as "HH:MM to HH:MM"
 * derived from start_time/end_time so the label, the submitted value, and the
 * calendar time lookup (keyed by shift_time) all work.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('class_session_master')) {
            return;
        }

        $rows = DB::table('class_session_master')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->where(function ($q) {
                $q->whereNull('shift_time')->orWhere('shift_time', '');
            })
            ->get(['pk', 'start_time', 'end_time']);

        foreach ($rows as $row) {
            $shiftTime = substr($row->start_time, 0, 5) . ' to ' . substr($row->end_time, 0, 5);
            DB::table('class_session_master')
                ->where('pk', $row->pk)
                ->update(['shift_time' => $shiftTime]);
        }
    }

    public function down(): void
    {
        // No-op: we cannot reliably tell which shift_time values were backfilled,
        // and clearing them would re-break the dropdown.
    }
};
