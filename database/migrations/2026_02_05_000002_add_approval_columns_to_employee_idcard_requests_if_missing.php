<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = ['employee_idcard_requests', 'employee_i_d_card_requests'];

    /**
     * Run the migrations.
     * Adds approval columns to both possible table names (Laravel may use employee_i_d_card_requests).
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'approved_by_a1')) {
                    $table->unsignedBigInteger('approved_by_a1')->nullable()->after('updated_by');
                }
                if (!Schema::hasColumn($tableName, 'approved_by_a1_at')) {
                    $table->timestamp('approved_by_a1_at')->nullable()->after('approved_by_a1');
                }
                if (!Schema::hasColumn($tableName, 'approved_by_a2')) {
                    $table->unsignedBigInteger('approved_by_a2')->nullable()->after('approved_by_a1_at');
                }
                if (!Schema::hasColumn($tableName, 'approved_by_a2_at')) {
                    $table->timestamp('approved_by_a2_at')->nullable()->after('approved_by_a2');
                }
                if (!Schema::hasColumn($tableName, 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable()->after('approved_by_a2_at');
                }
                if (!Schema::hasColumn($tableName, 'rejected_by')) {
                    $table->unsignedBigInteger('rejected_by')->nullable()->after('rejection_reason');
                }
                if (!Schema::hasColumn($tableName, 'rejected_at')) {
                    $table->timestamp('rejected_at')->nullable()->after('rejected_by');
                }
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
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $columns = ['approved_by_a1', 'approved_by_a1_at', 'approved_by_a2', 'approved_by_a2_at', 'rejection_reason', 'rejected_by', 'rejected_at'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn($tableName, $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
