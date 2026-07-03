<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Converted from: activity (MySQL, procedural PHP)
 * Tables: ot_details, otactivity_details, activity_master,
 *         course_master, pre_history, path_report, final_findings
 * Skipped: user_login (handled by Laravel auth), countrecord (replaced by DB auto-increment)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── course_master ─────────────────────────────────────────────────
        Schema::create('course_master', function (Blueprint $table) {
            $table->id();
            $table->string('c_code', 20)->unique();
            $table->string('c_name', 100);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        // ── ot_details ────────────────────────────────────────────────────
        Schema::create('ot_details', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();   // OT login/ID
            $table->string('otname', 150);
            $table->string('otcode', 30)->unique();
            $table->string('course', 20)->nullable();
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
            $table->string('service', 30)->nullable();  // IAS / IPS / IRS etc.
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->index('course');
            $table->index('service');
        });

        // ── activity_master ───────────────────────────────────────────────
        Schema::create('activity_master', function (Blueprint $table) {
            $table->id();
            $table->string('menuid', 30)->unique();     // activity code: joined, idcard, etc.
            $table->string('menun', 100);               // display name
            $table->string('ccode', 20)->nullable();    // course code this belongs to
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        // ── otactivity_details ────────────────────────────────────────────
        Schema::create('otactivity_details', function (Blueprint $table) {
            $table->id();
            $table->string('activityid', 50)->unique();
            $table->string('username', 50);             // OT username (FK ot_details.username)
            $table->string('activity', 30);             // joined, idcard, biometric, height, etc.
            $table->text('activityval')->nullable();
            $table->string('activitydt', 30)->nullable();
            $table->string('submitedby', 50)->nullable();  // staff username
            $table->string('course', 20)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->index(['username', 'activity']);
            $table->index('submitedby');
        });

        // ── pre_history ───────────────────────────────────────────────────
        Schema::create('pre_history', function (Blueprint $table) {
            $table->id();
            $table->string('userid', 50);               // OT username
            $table->text('allergy_illness')->nullable();
            $table->text('prolonged_medication')->nullable();
            $table->text('hospital_history')->nullable();
            $table->text('altitude_illness')->nullable();
            $table->text('additional_info')->nullable();
            $table->string('doc_path', 255)->nullable();
            $table->string('course', 20)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->index('userid');
        });

        // ── path_report ───────────────────────────────────────────────────
        Schema::create('path_report', function (Blueprint $table) {
            $table->id();
            $table->string('userid', 50);
            $table->string('path_report', 255)->nullable();   // PDF file path
            $table->string('doc_report', 255)->nullable();
            $table->string('course', 20)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('submit_dt')->nullable();
            $table->timestamps();

            $table->index('userid');
        });

        // ── final_findings ────────────────────────────────────────────────
        Schema::create('final_findings', function (Blueprint $table) {
            $table->id();
            $table->string('userid', 50);
            $table->text('findings');
            $table->string('course', 20)->nullable();
            $table->string('submited_by', 50)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('submit_dt')->useCurrent();
            $table->timestamps();

            $table->index('userid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_findings');
        Schema::dropIfExists('path_report');
        Schema::dropIfExists('pre_history');
        Schema::dropIfExists('otactivity_details');
        Schema::dropIfExists('activity_master');
        Schema::dropIfExists('ot_details');
        Schema::dropIfExists('course_master');
    }
};
