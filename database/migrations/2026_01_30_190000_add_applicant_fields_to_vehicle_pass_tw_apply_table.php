<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('vehicle_pass_tw_apply')) {
            return;
        }
        if (Schema::hasColumn('vehicle_pass_tw_apply', 'applicant_type')) {
            return;
        }
        Schema::table('vehicle_pass_tw_apply', function (Blueprint $table) {
            $table->string('applicant_type', 50)->nullable()->after('gov_veh')->comment('employee, others, government_vehicle');
            $table->string('applicant_name', 255)->nullable()->after('applicant_type');
            $table->string('designation', 255)->nullable()->after('applicant_name');
            $table->string('department', 255)->nullable()->after('designation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('vehicle_pass_tw_apply') || !Schema::hasColumn('vehicle_pass_tw_apply', 'applicant_type')) {
            return;
        }
        Schema::table('vehicle_pass_tw_apply', function (Blueprint $table) {
            $table->dropColumn(['applicant_type', 'applicant_name', 'designation', 'department']);
        });
    }
};
