<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates vehicle_pass_tw_apply if it does not exist (e.g. when base migration was never run).
     */
    public function up(): void
    {
        if (Schema::hasTable('vehicle_pass_tw_apply')) {
            return;
        }

        Schema::createIfNotExists('vehicle_pass_tw_apply', function (Blueprint $table) {
            $table->id('vehicle_tw_pk');
            $table->string('employee_id_card', 100)->nullable();
            $table->unsignedBigInteger('emp_master_pk')->nullable();
            $table->unsignedBigInteger('vehicle_type')->nullable();
            $table->string('vehicle_no', 50)->nullable();
            $table->string('doc_upload', 255)->nullable();
            $table->integer('vehicle_card_reapply')->default(0);
            $table->date('veh_card_valid_from')->nullable();
            $table->date('vech_card_valid_to')->nullable();
            $table->tinyInteger('vech_card_status')->default(1)->comment('1=Pending, 2=Approved, 3=Rejected');
            $table->timestamp('created_date')->nullable();
            $table->unsignedBigInteger('veh_created_by')->nullable();
            $table->tinyInteger('veh_card_forward_status')->default(0)->comment('0=Not forwarded, 1=Forwarded, 2=Card ready');
            $table->string('vehicle_req_id', 50)->nullable();
            $table->tinyInteger('gov_veh')->default(0)->comment('0=Private, 1=Government');
            $table->string('applicant_type', 50)->nullable()->comment('employee, others, government_vehicle');
            $table->string('applicant_name', 255)->nullable();
            $table->string('designation', 255)->nullable();
            $table->string('department', 255)->nullable();
        });

        // Add foreign keys only if referenced tables exist
        if (Schema::hasTable('employee_master') && Schema::hasTable('sec_vehicle_type')) {
            Schema::table('vehicle_pass_tw_apply', function (Blueprint $table) {
                $table->foreign('emp_master_pk')->references('pk')->on('employee_master')->onDelete('cascade');
                $table->foreign('vehicle_type')->references('pk')->on('sec_vehicle_type')->onDelete('set null');
                $table->foreign('veh_created_by')->references('pk')->on('employee_master')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_pass_tw_apply');
    }
};
