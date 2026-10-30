<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE → Venue Allocation: record, per seat, whether the participant needs a
 * scriber — the same snapshot coe_seat_allocations.requires_laptop already is, so
 * the seating chart and the "Scriber Required" count do not change if the OT
 * edits their laptop status after seats were handed out.
 */
return new class extends Migration
{
    private const TABLE = 'coe_seat_allocations';

    public function up(): void
    {
        if (Schema::hasColumn(self::TABLE, 'requires_scriber')) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->boolean('requires_scriber')->default(false)->after('requires_laptop');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn(self::TABLE, 'requires_scriber')) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropColumn('requires_scriber');
        });
    }
};
