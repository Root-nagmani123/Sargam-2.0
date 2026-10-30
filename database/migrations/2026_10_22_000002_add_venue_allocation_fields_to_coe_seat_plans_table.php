<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COE → Venue Allocation: what the "Map Seating Plan" dialog asks for that
 * coe_seat_plans did not have.
 *
 *   examination_drive_component_map_id — the Subject + Component being examined.
 *       coe_seat_plans was written before the Examination Drive module existed and
 *       kept exam_name / subject_name as free text; those stay, filled from the map
 *       row, so old plans still read.
 *   candidate_category — which participants of the course+batch the plan seats:
 *       0 = all, 1 = General (no disability declared), 2 = PH (disability declared
 *       on the laptop status screen).
 *   rule_odd_even — seat odd roll-number serials first, then even, so neighbours
 *       never hold consecutive roll numbers.
 *   seat_gap — empty desks left between two candidates. rule_skip_alternate_seats
 *       is the same thing with a gap of 1; the allocator takes the larger.
 *
 * All additive with defaults, so plans written before this keep behaving as before.
 */
return new class extends Migration
{
    private const TABLE = 'coe_seat_plans';

    public function up(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (! Schema::hasColumn(self::TABLE, 'examination_drive_component_map_id')) {
                $table->unsignedBigInteger('examination_drive_component_map_id')->nullable()->after('batch');
                $table->foreign('examination_drive_component_map_id', 'coe_sp_drive_component_foreign')
                    ->references('id')->on('examination_drive_component_maps')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn(self::TABLE, 'candidate_category')) {
                $table->tinyInteger('candidate_category')->default(0)->after('examination_drive_component_map_id')
                    ->comment('0=All, 1=General (no disability), 2=PH (disability declared)');
            }
            if (! Schema::hasColumn(self::TABLE, 'rule_odd_even')) {
                $table->boolean('rule_odd_even')->default(false)->after('rule_skip_alternate_seats')
                    ->comment('Seat odd roll-number serials first, then even');
            }
            if (! Schema::hasColumn(self::TABLE, 'seat_gap')) {
                $table->unsignedTinyInteger('seat_gap')->default(0)->after('rule_odd_even')
                    ->comment('Empty desks between two candidates');
            }
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table) {
            if (Schema::hasColumn(self::TABLE, 'examination_drive_component_map_id')) {
                $table->dropForeign('coe_sp_drive_component_foreign');
            }
            foreach (['seat_gap', 'rule_odd_even', 'candidate_category', 'examination_drive_component_map_id'] as $column) {
                if (Schema::hasColumn(self::TABLE, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
