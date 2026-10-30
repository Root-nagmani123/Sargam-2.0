<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Copy the departure / return time of stationed leaves filed before
     * time_from / time_to existed (PR #334 F-045).
     *
     * Before 2026_09_17_000003 the apply form posted from_date / to_date as
     * "<date>T<time>" into DATETIME columns, so those rows carry their times in
     * the date columns and nothing in the new ones. Without this, they show "-"
     * everywhere and an edit (time fields required, date-only save) drops them.
     *
     * A row qualifies when either side has a non-midnight time; both columns are
     * then filled, so the edit form never opens half-populated. Rows with both
     * times at 00:00 are left alone — that is indistinguishable from "no time".
     * Only NULL columns are written, so re-running is a no-op.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('leave_application', 'time_from')) {
            return;
        }

        DB::table('leave_application')
            ->where('leave_type', 'STATIONED_LEAVE')
            ->whereNull('time_from')
            ->whereNull('time_to')
            ->whereNotNull('from_date')
            ->whereNotNull('to_date')
            ->where(function ($q) {
                $q->whereRaw("TIME(from_date) <> '00:00:00'")
                    ->orWhereRaw("TIME(to_date) <> '00:00:00'");
            })
            ->update([
                'time_from' => DB::raw('TIME(from_date)'),
                'time_to' => DB::raw('TIME(to_date)'),
            ]);
    }

    /**
     * Not reversed: the copied times are identical to the date columns, and
     * clearing them would also wipe times entered after deploy.
     */
    public function down(): void
    {
    }
};
