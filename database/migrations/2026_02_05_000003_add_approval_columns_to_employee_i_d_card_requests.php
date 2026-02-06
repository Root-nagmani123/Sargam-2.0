<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds approval columns to employee_i_d_card_requests (Laravel's default table for EmployeeIDCardRequest).
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('employee_i_d_card_requests')) {
            return;
        }
        Schema::table('employee_i_d_card_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_i_d_card_requests', 'approved_by_a1')) {
                $table->unsignedBigInteger('approved_by_a1')->nullable()->after('updated_by');
            }
            if (!Schema::hasColumn('employee_i_d_card_requests', 'approved_by_a1_at')) {
                $table->timestamp('approved_by_a1_at')->nullable()->after('approved_by_a1');
            }
            if (!Schema::hasColumn('employee_i_d_card_requests', 'approved_by_a2')) {
                $table->unsignedBigInteger('approved_by_a2')->nullable()->after('approved_by_a1_at');
            }
            if (!Schema::hasColumn('employee_i_d_card_requests', 'approved_by_a2_at')) {
                $table->timestamp('approved_by_a2_at')->nullable()->after('approved_by_a2');
            }
            if (!Schema::hasColumn('employee_i_d_card_requests', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_by_a2_at');
            }
            if (!Schema::hasColumn('employee_i_d_card_requests', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('employee_i_d_card_requests', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
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
        if (!Schema::hasTable('employee_i_d_card_requests')) {
            return;
        }
        Schema::table('employee_i_d_card_requests', function (Blueprint $table) {
            $columns = ['approved_by_a1', 'approved_by_a1_at', 'approved_by_a2', 'approved_by_a2_at', 'rejection_reason', 'rejected_by', 'rejected_at'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('employee_i_d_card_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
