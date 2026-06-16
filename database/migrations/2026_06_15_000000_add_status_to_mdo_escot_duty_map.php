<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds an acknowledgement workflow to MDO/Escort duties:
     *  - duty_status: 'pending' (default) | 'completed'
     *  - acknowledged_by: student_master.pk of the OT who acknowledged
     *  - acknowledged_at: when it was acknowledged
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mdo_escot_duty_map', function (Blueprint $table) {
            if (!Schema::hasColumn('mdo_escot_duty_map', 'duty_status')) {
                $table->string('duty_status', 20)->default('pending')->after('Remark');
            }
            if (!Schema::hasColumn('mdo_escot_duty_map', 'acknowledged_by')) {
                $table->unsignedBigInteger('acknowledged_by')->nullable()->after('duty_status');
            }
            if (!Schema::hasColumn('mdo_escot_duty_map', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('acknowledged_by');
            }
        });
    }

    public function down()
    {
        Schema::table('mdo_escot_duty_map', function (Blueprint $table) {
            foreach (['acknowledged_at', 'acknowledged_by', 'duty_status'] as $col) {
                if (Schema::hasColumn('mdo_escot_duty_map', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
