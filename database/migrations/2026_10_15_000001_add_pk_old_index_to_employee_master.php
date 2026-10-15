<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('employee_master', 'pk_old')) {
            return;
        }

        $exists = DB::select("SHOW INDEX FROM employee_master WHERE Key_name = 'employee_master_pk_old_index'");
        if (!empty($exists)) {
            return;
        }

        Schema::table('employee_master', function (Blueprint $table) {
            $table->index('pk_old', 'employee_master_pk_old_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('employee_master', 'pk_old')) {
            return;
        }

        $exists = DB::select("SHOW INDEX FROM employee_master WHERE Key_name = 'employee_master_pk_old_index'");
        if (empty($exists)) {
            return;
        }

        Schema::table('employee_master', function (Blueprint $table) {
            $table->dropIndex('employee_master_pk_old_index');
        });
    }
};
