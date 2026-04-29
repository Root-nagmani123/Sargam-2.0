<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_travel_plan_masters')) {
            return;
        }

        Schema::table('student_travel_plan_masters', function (Blueprint $table) {
            // Drop legacy foreign keys if present (safe to ignore when absent).
            try {
                $table->dropForeign(['pickup_type_id']);
            } catch (\Throwable $e) {
                // no-op
            }
            try {
                $table->dropForeign(['drop_type_id']);
            } catch (\Throwable $e) {
                // no-op
            }

            $dropColumns = array_values(array_filter([
                Schema::hasColumn('student_travel_plan_masters', 'pickup_type_id') ? 'pickup_type_id' : null,
                Schema::hasColumn('student_travel_plan_masters', 'needs_pickup') ? 'needs_pickup' : null,
                Schema::hasColumn('student_travel_plan_masters', 'pickup_from_location') ? 'pickup_from_location' : null,
                Schema::hasColumn('student_travel_plan_masters', 'pickup_datetime') ? 'pickup_datetime' : null,
                Schema::hasColumn('student_travel_plan_masters', 'needs_drop') ? 'needs_drop' : null,
                Schema::hasColumn('student_travel_plan_masters', 'drop_type_id') ? 'drop_type_id' : null,
                Schema::hasColumn('student_travel_plan_masters', 'drop_to_location') ? 'drop_to_location' : null,
                Schema::hasColumn('student_travel_plan_masters', 'drop_datetime') ? 'drop_datetime' : null,
                Schema::hasColumn('student_travel_plan_masters', 'departure_city') ? 'departure_city' : null,
                Schema::hasColumn('student_travel_plan_masters', 'departure_state') ? 'departure_state' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('student_travel_plan_masters')) {
            return;
        }

        // Keep rollback resilient: restore legacy columns only.
        // Avoid forcing FK recreation to prevent rollback failures when referenced
        // tables/constraints differ across environments.
        Schema::table('student_travel_plan_masters', function (Blueprint $table) {
            if (! Schema::hasColumn('student_travel_plan_masters', 'pickup_type_id')) {
                $table->unsignedBigInteger('pickup_type_id')->nullable()->after('joining_time');
            }
            if (! Schema::hasColumn('student_travel_plan_masters', 'needs_pickup')) {
                $table->boolean('needs_pickup')->default(false)->after('pickup_type_id');
            }
            if (! Schema::hasColumn('student_travel_plan_masters', 'pickup_from_location')) {
                $table->string('pickup_from_location', 200)->nullable()->after('needs_pickup');
            }
            if (! Schema::hasColumn('student_travel_plan_masters', 'pickup_datetime')) {
                $table->dateTime('pickup_datetime')->nullable()->after('pickup_from_location');
            }
            if (! Schema::hasColumn('student_travel_plan_masters', 'needs_drop')) {
                $table->boolean('needs_drop')->default(false)->after('pickup_datetime');
            }
            if (! Schema::hasColumn('student_travel_plan_masters', 'drop_type_id')) {
                $table->unsignedBigInteger('drop_type_id')->nullable()->after('needs_drop');
            }
            if (! Schema::hasColumn('student_travel_plan_masters', 'drop_to_location')) {
                $table->string('drop_to_location', 200)->nullable()->after('drop_type_id');
            }
            if (! Schema::hasColumn('student_travel_plan_masters', 'drop_datetime')) {
                $table->dateTime('drop_datetime')->nullable()->after('drop_to_location');
            }
            if (! Schema::hasColumn('student_travel_plan_masters', 'departure_city')) {
                $table->string('departure_city', 150)->nullable()->after('drop_datetime');
            }
            if (! Schema::hasColumn('student_travel_plan_masters', 'departure_state')) {
                $table->string('departure_state', 150)->nullable()->after('departure_city');
            }
        });
    }
};

