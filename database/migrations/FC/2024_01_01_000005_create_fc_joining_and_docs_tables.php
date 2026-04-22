<?php

use App\Support\MigrationSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Bank Details (new registration) ─────────────────────────
        MigrationSchema::createIfMissing('new_registration_bank_details_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('bank_name', 200)->nullable();
            $table->string('branch_name', 200)->nullable();
            $table->string('ifsc_code', 20)->nullable();
            $table->string('account_no', 50)->nullable();
            $table->string('account_holder_name', 200)->nullable();
            $table->string('account_type', 50)->nullable();           // Savings / Current
            $table->string('bank_passbook_path', 500)->nullable();
            $table->tinyInteger('is_verified')->default(0);
            $table->timestamps();
        });

        // ─── Registration Bank Details (alternate / older) ───────────
        MigrationSchema::createIfMissing('registration_bank_details_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('bank_name', 200)->nullable();
            $table->string('ifsc_code', 20)->nullable();
            $table->string('account_no', 50)->nullable();
            $table->string('account_holder_name', 200)->nullable();
            $table->timestamps();
        });

        // ─── FC Joining Related Documents Master (checklist) ─────────
        MigrationSchema::createIfMissing('fc_joining_related_documents_masters', function (Blueprint $table) {
            $table->id();
            $table->string('document_name', 300);
            $table->string('document_code', 100)->nullable();
            $table->tinyInteger('is_mandatory')->default(1);
            $table->tinyInteger('is_active')->default(1);
            $table->string('display_order', 10)->nullable();
            $table->timestamps();
        });

        // ─── FC Joining Related Documents Details (per student) ───────
        MigrationSchema::createIfMissing('fc_joining_related_documents_details_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->unsignedBigInteger('document_master_id')->nullable();
            $table->string('document_name', 300)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('file_original_name', 300)->nullable();
            $table->tinyInteger('is_uploaded')->default(0);
            $table->tinyInteger('is_verified')->default(0);
            $table->string('verified_by', 100)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('remarks', 500)->nullable();
            $table->timestamps();
            // ✅ FOREIGN KEY (DO NOT use foreignId here)
    $table->foreign('document_master_id', 'fk_doc_master_id')
        ->references('id')
        ->on('fc_joining_related_documents_masters')
        ->nullOnDelete();
        });

        // ─── FC Joining Attendance (hostel-wise) ─────────────────────
        // Ganga
        MigrationSchema::createIfMissing('fc_joining_attendance_ganga_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('room_no', 20)->nullable();
            $table->date('joining_date')->nullable();
            $table->time('joining_time')->nullable();
            $table->string('transport_mode', 100)->nullable();
            $table->tinyInteger('attended')->default(0);
            $table->string('remarks', 500)->nullable();
            $table->timestamps();
        });

        // Kaveri
        MigrationSchema::createIfMissing('fc_joining_attendance_kaveri_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('room_no', 20)->nullable();
            $table->date('joining_date')->nullable();
            $table->time('joining_time')->nullable();
            $table->tinyInteger('attended')->default(0);
            $table->string('remarks', 500)->nullable();
            $table->timestamps();
        });

        // Narmada
        MigrationSchema::createIfMissing('fc_joining_attendance_narmada_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('room_no', 20)->nullable();
            $table->date('joining_date')->nullable();
            $table->time('joining_time')->nullable();
            $table->tinyInteger('attended')->default(0);
            $table->string('remarks', 500)->nullable();
            $table->timestamps();
        });

        // Mahanadi
        MigrationSchema::createIfMissing('fc_joining_attendance_mahanadi_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('room_no', 20)->nullable();
            $table->date('joining_date')->nullable();
            $table->time('joining_time')->nullable();
            $table->tinyInteger('attended')->default(0);
            $table->string('remarks', 500)->nullable();
            $table->timestamps();
        });

        // Happy Valley
        MigrationSchema::createIfMissing('fc_joining_attendance_happy_valley_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('room_no', 20)->nullable();
            $table->date('joining_date')->nullable();
            $table->time('joining_time')->nullable();
            $table->tinyInteger('attended')->default(0);
            $table->string('remarks', 500)->nullable();
            $table->timestamps();
        });

        // Silverwood
        MigrationSchema::createIfMissing('fc_joining_attendance_silverwood_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('room_no', 20)->nullable();
            $table->date('joining_date')->nullable();
            $table->time('joining_time')->nullable();
            $table->tinyInteger('attended')->default(0);
            $table->string('remarks', 500)->nullable();
            $table->timestamps();
        });

        // ─── FC Joining Medical Details ───────────────────────────────
        MigrationSchema::createIfMissing('fc_joining_medical_details_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('height_cm', 10)->nullable();
            $table->string('weight_kg', 10)->nullable();
            $table->string('blood_pressure', 20)->nullable();
            $table->string('blood_group', 10)->nullable();
            $table->tinyInteger('is_fit')->default(1);
            $table->string('medical_remarks', 1000)->nullable();
            $table->string('examined_by', 200)->nullable();
            $table->date('examined_date')->nullable();
            $table->timestamps();
        });

        // ─── FC Joining Covid Details ─────────────────────────────────
        MigrationSchema::createIfMissing('fc_joining_covid_details_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->tinyInteger('is_vaccinated')->default(0);
            $table->string('vaccine_name', 100)->nullable();
            $table->string('dose_no', 10)->nullable();
            $table->date('vaccination_date')->nullable();
            $table->string('certificate_path', 500)->nullable();
            $table->tinyInteger('rtpcr_negative')->default(0);
            $table->date('rtpcr_date')->nullable();
            $table->string('rtpcr_doc_path', 500)->nullable();
            $table->timestamps();
        });

        // ─── Travel Details (MCTP) ────────────────────────────────────
        MigrationSchema::createIfMissing('student_travel_plan_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->unsignedBigInteger('travel_type_id')->nullable();
            $table->unsignedBigInteger('travel_mode_id')->nullable();
            $table->string('from_city', 150)->nullable();
            $table->string('to_city', 150)->nullable();
            $table->date('travel_date')->nullable();
            $table->string('train_flight_no', 100)->nullable();
            $table->string('pickup_required', 5)->nullable();         // Yes / No
            $table->unsignedBigInteger('pickup_type_id')->nullable();
            $table->timestamps();

            $table->foreign('travel_type_id')->references('id')->on('travel_type_masters')->nullOnDelete();
            $table->foreign('travel_mode_id')->references('id')->on('mctp_travel_mode_masters')->nullOnDelete();
            $table->foreign('pickup_type_id')->references('id')->on('pick_up_drop_type_masters')->nullOnDelete();
        });

        // ─── MCTP Student Travel Plan Detail ─────────────────────────
        MigrationSchema::createIfMissing('mctp_student_travel_plan_details', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->string('leg_no', 10)->nullable();
            $table->string('from_station', 150)->nullable();
            $table->string('to_station', 150)->nullable();
            $table->string('travel_mode', 100)->nullable();
            $table->date('travel_date')->nullable();
            $table->string('departure_time', 20)->nullable();
            $table->string('arrival_time', 20)->nullable();
            $table->string('train_flight_no', 100)->nullable();
            $table->string('pnr_ticket_no', 100)->nullable();
            $table->timestamps();
        });

        // ─── IOSR Details Master ──────────────────────────────────────
        MigrationSchema::createIfMissing('student_iosr_details_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('iosr_roll_no', 50)->nullable();
            $table->string('iosr_rank', 50)->nullable();
            $table->string('interview_marks', 20)->nullable();
            $table->string('written_marks', 20)->nullable();
            $table->string('total_marks', 20)->nullable();
            $table->string('home_state', 100)->nullable();
            $table->timestamps();
        });

        // ─── IOSR Details Doc Path Master ────────────────────────────
        MigrationSchema::createIfMissing('student_iosr_details_doc_path_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->string('document_type', 200)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->timestamps();
        });

        // ─── IOSR Reasonable Adjustment ──────────────────────────────
        MigrationSchema::createIfMissing('student_iosr_reasonable_adjust_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->tinyInteger('adjustment_required')->default(0);
            $table->string('adjustment_type', 300)->nullable();
            $table->string('doc_path', 500)->nullable();
            $table->timestamps();
        });

        // ─── Property Details (Movable) ───────────────────────────────
        MigrationSchema::createIfMissing('student_master_movable_property_details', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->string('property_type', 200)->nullable();
            $table->string('description', 500)->nullable();
            $table->string('value_in_lakhs', 50)->nullable();
            $table->string('location', 300)->nullable();
            $table->timestamps();
        });

        // ─── Property Details (Immovable) ────────────────────────────
        MigrationSchema::createIfMissing('student_master_immovable_property_details', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->string('property_type', 200)->nullable();
            $table->string('location', 300)->nullable();
            $table->string('area_sq_ft', 50)->nullable();
            $table->string('value_in_lakhs', 50)->nullable();
            $table->string('how_acquired', 200)->nullable();          // Purchased / Inherited
            $table->timestamps();
        });

        // ─── Joining Details (overall) ────────────────────────────────
        MigrationSchema::createIfMissing('student_register_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('allotted_hostel', 100)->nullable();
            $table->string('room_no', 20)->nullable();
            $table->date('joining_date')->nullable();
            $table->tinyInteger('joined')->default(0);
            $table->string('joining_remarks', 500)->nullable();
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('session_masters')->nullOnDelete();
        });

        // ─── Health Risk Factor ───────────────────────────────────────
        MigrationSchema::createIfMissing('student_exemption_med_doc_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('ailment', 300)->nullable();
            $table->string('doc_path', 500)->nullable();
            $table->tinyInteger('exemption_granted')->default(0);
            $table->string('granted_by', 200)->nullable();
            $table->timestamps();
        });

        // ─── COVID Registration Report ────────────────────────────────
        MigrationSchema::createIfMissing('registration_covid_report_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('covid_status', 50)->nullable();
            $table->string('remarks', 500)->nullable();
            $table->timestamps();
        });

        // ─── Online Assignment Master ─────────────────────────────────
        MigrationSchema::createIfMissing('online_assigment_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('assignment_title', 300)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('status', 50)->default('PENDING');
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('session_masters')->nullOnDelete();
        });

        // ─── Online Assignment Status Master ─────────────────────────
        MigrationSchema::createIfMissing('online_assigment_status_masters', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->string('status', 50)->default('SUBMITTED');
            $table->string('marks', 20)->nullable();
            $table->string('remarks', 500)->nullable();
            $table->timestamps();

            $table->foreign('assignment_id')->references('id')->on('online_assigment_masters')->nullOnDelete();
        });

        // ─── Cloth Size Details ───────────────────────────────────────
        MigrationSchema::createIfMissing('student_cloth_size_master_details', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('shirt_size', 10)->nullable();
            $table->string('trouser_size', 10)->nullable();
            $table->string('shoe_size', 10)->nullable();
            $table->string('blazer_size', 10)->nullable();
            $table->timestamps();
        });

        // ─── Login Attempts Logger ────────────────────────────────────
        MigrationSchema::createIfMissing('login_attempts_logs', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->tinyInteger('success')->default(0);
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('attempted_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts_logs');
        Schema::dropIfExists('student_cloth_size_master_details');
        Schema::dropIfExists('online_assigment_status_masters');
        Schema::dropIfExists('online_assigment_masters');
        Schema::dropIfExists('registration_covid_report_masters');
        Schema::dropIfExists('student_exemption_med_doc_masters');
        Schema::dropIfExists('student_register_masters');
        Schema::dropIfExists('student_master_immovable_property_details');
        Schema::dropIfExists('student_master_movable_property_details');
        Schema::dropIfExists('student_iosr_reasonable_adjust_masters');
        Schema::dropIfExists('student_iosr_details_doc_path_masters');
        Schema::dropIfExists('student_iosr_details_masters');
        Schema::dropIfExists('mctp_student_travel_plan_details');
        Schema::dropIfExists('student_travel_plan_masters');
        Schema::dropIfExists('fc_joining_covid_details_masters');
        Schema::dropIfExists('fc_joining_medical_details_masters');
        Schema::dropIfExists('fc_joining_attendance_silverwood_masters');
        Schema::dropIfExists('fc_joining_attendance_happy_valley_masters');
        Schema::dropIfExists('fc_joining_attendance_mahanadi_masters');
        Schema::dropIfExists('fc_joining_attendance_narmada_masters');
        Schema::dropIfExists('fc_joining_attendance_kaveri_masters');
        Schema::dropIfExists('fc_joining_attendance_ganga_masters');
        Schema::dropIfExists('fc_joining_related_documents_details_masters');
        Schema::dropIfExists('fc_joining_related_documents_masters');
        Schema::dropIfExists('registration_bank_details_masters');
        Schema::dropIfExists('new_registration_bank_details_masters');
    }
};
