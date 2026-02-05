<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_idcard_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_idcard_requests', 'joining_letter')) {
                $table->string('joining_letter')->nullable()->after('photo');
            }
            if (!Schema::hasColumn('employee_idcard_requests', 'duplication_reason')) {
                $table->string('duplication_reason')->nullable()->after('request_for')->comment('Lost or Damage for replacement requests');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_idcard_requests', function (Blueprint $table) {
            if (Schema::hasColumn('employee_idcard_requests', 'joining_letter')) {
                $table->dropColumn('joining_letter');
            }
            if (Schema::hasColumn('employee_idcard_requests', 'duplication_reason')) {
                $table->dropColumn('duplication_reason');
            }
        });
    }
};
