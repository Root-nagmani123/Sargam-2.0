<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fc_ot_details', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('otname', 150);
            $table->string('otcode', 30)->unique();
            $table->string('course', 120)->nullable();
            $table->string('c_name', 100)->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('dob')->nullable();
            $table->string('age', 10)->nullable();
            $table->string('father_name', 150)->nullable();
            $table->string('mobileno', 15)->nullable();
            $table->string('blood_group', 10)->nullable();
            $table->string('aadhar_no', 20)->nullable();
            $table->string('abha_id', 30)->nullable();
            $table->string('house', 50)->nullable();
            $table->string('housen', 50)->nullable();
            $table->string('service', 40)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->index(['course', 'service']);
        });

        Schema::create('fc_activity_master', function (Blueprint $table) {
            $table->id();
            $table->string('menuid', 30)->unique();
            $table->string('menun', 100);
            $table->string('ccode', 120)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('fc_otactivity_details', function (Blueprint $table) {
            $table->id();
            $table->string('activityid', 50)->unique();
            $table->string('username', 50);
            $table->string('activity', 30);
            $table->text('activityval')->nullable();
            $table->string('activitydt', 30)->nullable();
            $table->string('submitedby', 50)->nullable();
            $table->string('course', 120)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->index(['username', 'activity']);
        });

        Schema::create('fc_pre_history', function (Blueprint $table) {
            $table->id();
            $table->string('userid', 50);
            $table->text('allergy_illness')->nullable();
            $table->text('prolonged_medication')->nullable();
            $table->text('hospital_history')->nullable();
            $table->text('altitude_illness')->nullable();
            $table->text('additional_info')->nullable();
            $table->string('doc_path', 255)->nullable();
            $table->string('course', 120)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('fc_path_report', function (Blueprint $table) {
            $table->id();
            $table->string('userid', 50);
            $table->string('path_report', 255)->nullable();
            $table->string('doc_report', 255)->nullable();
            $table->string('course', 120)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('submit_dt')->nullable();
            $table->timestamps();
        });

        Schema::create('fc_final_findings', function (Blueprint $table) {
            $table->id();
            $table->string('userid', 50);
            $table->text('findings');
            $table->string('course', 120)->nullable();
            $table->string('submited_by', 50)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('submit_dt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fc_final_findings');
        Schema::dropIfExists('fc_path_report');
        Schema::dropIfExists('fc_pre_history');
        Schema::dropIfExists('fc_otactivity_details');
        Schema::dropIfExists('fc_activity_master');
        Schema::dropIfExists('fc_ot_details');
    }
};
