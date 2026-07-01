<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the selected Duty Type is "Other", the admin can type a free-text
 * duty name. That value is stored in the new nullable `duty_other` column and
 * only populated for rows whose duty type is Other.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdo_escot_duty_map', function (Blueprint $table) {
            if (!Schema::hasColumn('mdo_escot_duty_map', 'duty_other')) {
                $table->string('duty_other')->nullable()->after('mdo_duty_type_master_pk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mdo_escot_duty_map', function (Blueprint $table) {
            if (Schema::hasColumn('mdo_escot_duty_map', 'duty_other')) {
                $table->dropColumn('duty_other');
            }
        });
    }
};
