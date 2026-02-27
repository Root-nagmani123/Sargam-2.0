<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: Model EmployeeIDCardRequest uses table employee_i_d_card_requests (Laravel convention).
     *
     * @return void
     */
    public function up()
    {
        $table = 'employee_i_d_card_requests';
        if (!Schema::hasTable($table)) {
            return;
        }
        if (!Schema::hasColumn($table, 'joining_letter')) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('joining_letter')->nullable()->after('photo');
            });
        }
        if (!Schema::hasColumn($table, 'duplication_reason')) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('duplication_reason')->nullable()->after('request_for');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table = 'employee_i_d_card_requests';
        if (Schema::hasTable($table)) {
            Schema::table($table, function (Blueprint $t) {
                if (Schema::hasColumn($table, 'joining_letter')) {
                    $t->dropColumn('joining_letter');
                }
                if (Schema::hasColumn($table, 'duplication_reason')) {
                    $t->dropColumn('duplication_reason');
                }
            });
        }
    }
};
