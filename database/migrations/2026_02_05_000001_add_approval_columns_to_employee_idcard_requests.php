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
            $table->unsignedBigInteger('approved_by_a1')->nullable()->after('updated_by');
            $table->timestamp('approved_by_a1_at')->nullable()->after('approved_by_a1');
            $table->unsignedBigInteger('approved_by_a2')->nullable()->after('approved_by_a1_at');
            $table->timestamp('approved_by_a2_at')->nullable()->after('approved_by_a2');
            $table->text('rejection_reason')->nullable()->after('approved_by_a2_at');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('rejection_reason');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
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
            $table->dropColumn([
                'approved_by_a1',
                'approved_by_a1_at',
                'approved_by_a2',
                'approved_by_a2_at',
                'rejection_reason',
                'rejected_by',
                'rejected_at',
            ]);
        });
    }
};
