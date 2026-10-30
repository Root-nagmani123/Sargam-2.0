<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE Examination - Venue Master capacity fields (BRD "Venue Master", lines 431-446).
 *
 * The BRD asks a venue to carry Building, Floor, Room Number, Capacity,
 * Laptop Capacity and Seating Capacity so that automatic exam room allocation
 * can place candidates by laptop requirement and room capacity.
 *
 * Every column is nullable: venue_master already holds 116 rows that are read by
 * Timetable, Attendance, Calendar, Feedback and Memo, and none of those supply
 * these values. Existing rows and existing code stay valid untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venue_master', function (Blueprint $table) {
            if (! Schema::hasColumn('venue_master', 'building_master_pk')) {
                $table->unsignedBigInteger('building_master_pk')->nullable()->after('venue_short_name');
            }
            if (! Schema::hasColumn('venue_master', 'floor_master_pk')) {
                $table->unsignedBigInteger('floor_master_pk')->nullable()->after('building_master_pk');
            }
            if (! Schema::hasColumn('venue_master', 'room_number')) {
                $table->string('room_number', 50)->nullable()->after('floor_master_pk');
            }
            if (! Schema::hasColumn('venue_master', 'capacity')) {
                $table->unsignedInteger('capacity')->nullable()->after('room_number');
            }
            if (! Schema::hasColumn('venue_master', 'laptop_capacity')) {
                $table->unsignedInteger('laptop_capacity')->nullable()->after('capacity');
            }
            if (! Schema::hasColumn('venue_master', 'seating_capacity')) {
                $table->unsignedInteger('seating_capacity')->nullable()->after('laptop_capacity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('venue_master', function (Blueprint $table) {
            foreach ([
                'seating_capacity',
                'laptop_capacity',
                'capacity',
                'room_number',
                'floor_master_pk',
                'building_master_pk',
            ] as $column) {
                if (Schema::hasColumn('venue_master', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
