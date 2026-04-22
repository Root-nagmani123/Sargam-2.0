<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────
        // STEP 1: Basic Registration (StudentMasterFirst)
        // ─────────────────────────────────────────────────────────────
        Schema::create('student_master_firsts', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();                 // same as jbp_users.username
            $table->string('roll_no', 50)->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('full_name', 200);
            $table->string('fathers_name', 200)->nullable();
            $table->string('mothers_name', 200)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10)->nullable();                  // Male / Female / Other
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('cadre', 100)->nullable();
            $table->unsignedBigInteger('allotted_state_id')->nullable();
            $table->string('mobile_no', 15)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('photo_path', 500)->nullable();
            $table->string('signature_path', 500)->nullable();
            $table->tinyInteger('step1_completed')->default(0);
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('session_masters')->nullOnDelete();
        });

        // ─────────────────────────────────────────────────────────────
        // STEP 2: Personal Details (StudentMasterSecond)
        // ─────────────────────────────────────────────────────────────
        Schema::create('student_master_seconds', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('religion_id')->nullable();
            $table->string('nationality', 100)->default('Indian');
            $table->string('domicile_state', 100)->nullable();
            $table->string('marital_status', 20)->nullable();         // Single/Married/Divorced/Widowed
            $table->string('blood_group', 10)->nullable();
            $table->string('height_cm', 10)->nullable();
            $table->string('weight_kg', 10)->nullable();
            $table->string('identification_mark1', 300)->nullable();
            $table->string('identification_mark2', 300)->nullable();

            // Permanent Address
            $table->string('perm_address_line1', 300)->nullable();
            $table->string('perm_address_line2', 300)->nullable();
            $table->string('perm_city', 100)->nullable();
            $table->unsignedBigInteger('perm_state_id')->nullable();
            $table->string('perm_pincode', 10)->nullable();
            $table->unsignedBigInteger('perm_country_id')->nullable();

            // Present Address
            $table->string('pres_address_line1', 300)->nullable();
            $table->string('pres_address_line2', 300)->nullable();
            $table->string('pres_city', 100)->nullable();
            $table->unsignedBigInteger('pres_state_id')->nullable();
            $table->string('pres_pincode', 10)->nullable();
            $table->unsignedBigInteger('pres_country_id')->nullable();

            // Emergency Contact
            $table->string('emergency_contact_name', 200)->nullable();
            $table->string('emergency_contact_relation', 100)->nullable();
            $table->string('emergency_contact_mobile', 15)->nullable();

            // Father / Mother details
            $table->unsignedBigInteger('father_profession_id')->nullable();
            $table->string('father_occupation_details', 300)->nullable();

            $table->tinyInteger('step2_completed')->default(0);
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────
        // Consolidated StudentMaster (used for reporting / admin view)
        // ─────────────────────────────────────────────────────────────
        Schema::create('student_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('roll_no', 50)->nullable();
            $table->string('full_name', 200)->nullable();
            $table->string('service_code', 50)->nullable();
            $table->string('cadre', 100)->nullable();
            $table->string('status', 50)->default('INCOMPLETE');      // INCOMPLETE / COMPLETE / SUBMITTED
            $table->tinyInteger('step1_done')->default(0);
            $table->tinyInteger('step2_done')->default(0);
            $table->tinyInteger('step3_done')->default(0);
            $table->tinyInteger('bank_done')->default(0);
            $table->tinyInteger('docs_done')->default(0);
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('session_masters')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_masters');
        Schema::dropIfExists('student_master_seconds');
        Schema::dropIfExists('student_master_firsts');
    }
};
