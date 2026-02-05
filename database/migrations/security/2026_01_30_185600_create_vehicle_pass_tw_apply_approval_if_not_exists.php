<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates vehicle_pass_tw_apply_approval if it does not exist (required for Vehicle Pass approval flow).
     */
    public function up(): void
    {
        if (Schema::hasTable('vehicle_pass_tw_apply_approval')) {
            return;
        }

        Schema::createIfNotExists('vehicle_pass_tw_apply_approval', function (Blueprint $table) {
            $table->id('pk');
            $table->unsignedBigInteger('vehicle_TW_pk');
            $table->tinyInteger('veh_recommend_status')->nullable()->comment('1=Recommended, 2=Approved, 3=Rejected');
            $table->tinyInteger('status')->default(0)->comment('0=Pending, 2=Approved, 3=Rejected');
            $table->text('veh_approval_remarks')->nullable();
            $table->unsignedBigInteger('veh_approved_by')->nullable();
            $table->timestamp('created_date')->nullable();
            $table->timestamp('modified_date')->nullable();
        });

        if (Schema::hasTable('vehicle_pass_tw_apply') && Schema::hasTable('employee_master')) {
            Schema::table('vehicle_pass_tw_apply_approval', function (Blueprint $table) {
                $table->foreign('vehicle_TW_pk')->references('vehicle_tw_pk')->on('vehicle_pass_tw_apply')->onDelete('cascade');
                $table->foreign('veh_approved_by')->references('pk')->on('employee_master')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_pass_tw_apply_approval');
    }
};
