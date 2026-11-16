<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE → Venue Allocation: coe_seat_plan_venues.filled_by — the role a room was mapped
 * under (COE, else Super Admin, else the user's first role).
 *
 * The DBA added this column by hand on test, so no migration created it and servers
 * without that hand edit refuse every Map Seating Plan save ("Unknown column 'filled_by'").
 * Guarded, so it is a no-op where the column already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coe_seat_plan_venues', function (Blueprint $table) {
            if (! Schema::hasColumn('coe_seat_plan_venues', 'filled_by')) {
                $table->string('filled_by', 100)->nullable()
                    ->comment('Role the room was mapped under (COE / Super Admin / …)');
            }
        });
    }

    public function down(): void
    {
        // Not dropped: on test the column predates this migration and holds live data.
    }
};
