<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Escort duties can now have more than one faculty. We keep the existing single
 * `faculty_master_pk` (set to the first/primary faculty so the existing belongsTo
 * relation, filters and display keep working) and store the full list of selected
 * faculty pks as a comma-separated string in the new `faculty_master_pks` column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdo_escot_duty_map', function (Blueprint $table) {
            if (!Schema::hasColumn('mdo_escot_duty_map', 'faculty_master_pks')) {
                $table->text('faculty_master_pks')->nullable()->after('faculty_master_pk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mdo_escot_duty_map', function (Blueprint $table) {
            if (Schema::hasColumn('mdo_escot_duty_map', 'faculty_master_pks')) {
                $table->dropColumn('faculty_master_pks');
            }
        });
    }
};
