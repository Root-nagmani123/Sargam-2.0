<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align student_master_firsts / student_master_seconds with the FC99 dynamic
 * form (build_fc99_fresh_form.sql): add columns the form writes to, drop
 * ad-hoc test / legacy document columns superseded by fc_joining_documents_user_uploads.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_master_firsts')) {
            $this->addFirstsColumns();
            $this->dropFirstsLegacyColumns();
        }

        if (Schema::hasTable('student_master_seconds')) {
            $this->addSecondsColumns();
            $this->dropSecondsLegacyColumns();
        }
    }

    public function down(): void
    {
        // Destructive cleanup is not reversed automatically.
    }

    private function addFirstsColumns(): void
    {
        $columns = [
            'background'         => fn (Blueprint $t) => $t->string('background', 100)->nullable(),
            'full_name_hindi'    => fn (Blueprint $t) => $t->string('full_name_hindi', 200)->nullable(),
            'first_name'         => fn (Blueprint $t) => $t->string('first_name', 100)->nullable(),
            'middle_name'        => fn (Blueprint $t) => $t->string('middle_name', 100)->nullable(),
            'last_name'          => fn (Blueprint $t) => $t->string('last_name', 100)->nullable(),
            'pan_card'           => fn (Blueprint $t) => $t->string('pan_card', 20)->nullable(),
            'aadhar_number'      => fn (Blueprint $t) => $t->string('aadhar_number', 20)->nullable(),
            'passport_no'        => fn (Blueprint $t) => $t->string('passport_no', 20)->nullable(),
            'alt_mobile_no'      => fn (Blueprint $t) => $t->string('alt_mobile_no', 20)->nullable(),
            'alt_email'          => fn (Blueprint $t) => $t->string('alt_email', 150)->nullable(),
            'instagram_id'       => fn (Blueprint $t) => $t->string('instagram_id', 100)->nullable(),
            'twitter_id'         => fn (Blueprint $t) => $t->string('twitter_id', 100)->nullable(),
            'vision_statement'   => fn (Blueprint $t) => $t->text('vision_statement')->nullable(),
            'vision_completed'   => fn (Blueprint $t) => $t->boolean('vision_completed')->default(false),
        ];

        foreach ($columns as $name => $callback) {
            if (! Schema::hasColumn('student_master_firsts', $name)) {
                Schema::table('student_master_firsts', $callback);
            }
        }
    }

    private function dropFirstsLegacyColumns(): void
    {
        $drop = [
            // Ad-hoc / test columns from form-builder experiments
            'phone', 'exxtraaa', 'firstname', 'phoneno', 'height', 'hobbies',
            'aadhar', 'subjects', 'fffef', 'dddd', 'dwdwdwd',
            // Legacy per-file paths on firsts — FC99 uses fc_joining_documents_user_uploads
            'doc_family_details_path', 'doc_close_relation_path', 'doc_dowry_decl_path',
            'doc_marital_status_path', 'doc_home_town_path', 'doc_immovable_prop_path',
            'doc_movable_prop_path', 'doc_debts_liabilities_path', 'doc_surety_bond_ias_path',
            'doc_surety_bond_others_path', 'doc_oath_affirmation_path', 'doc_assumption_charge_path',
            'doc_group_insurance_path', 'doc_nps_subscription_path', 'doc_employee_info_sheet_path',
            'docs_completed', 'doc_family_details',
        ];

        $existing = array_values(array_filter($drop, fn (string $col) => Schema::hasColumn('student_master_firsts', $col)));

        if ($existing !== []) {
            Schema::table('student_master_firsts', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }

    private function addSecondsColumns(): void
    {
        $columns = [
            'birth_state_id'            => fn (Blueprint $t) => $t->unsignedBigInteger('birth_state_id')->nullable(),
            'birth_district'            => fn (Blueprint $t) => $t->string('birth_district', 100)->nullable(),
            'birth_city'                => fn (Blueprint $t) => $t->string('birth_city', 100)->nullable(),
            'birth_area_type'           => fn (Blueprint $t) => $t->string('birth_area_type', 50)->nullable(),
            'mother_first_name'         => fn (Blueprint $t) => $t->string('mother_first_name', 100)->nullable(),
            'mother_middle_name'        => fn (Blueprint $t) => $t->string('mother_middle_name', 100)->nullable(),
            'mother_last_name'          => fn (Blueprint $t) => $t->string('mother_last_name', 100)->nullable(),
            'mother_qualification_id'   => fn (Blueprint $t) => $t->unsignedBigInteger('mother_qualification_id')->nullable(),
            'mother_profession_id'      => fn (Blueprint $t) => $t->unsignedBigInteger('mother_profession_id')->nullable(),
            'mother_annual_income'      => fn (Blueprint $t) => $t->string('mother_annual_income', 100)->nullable(),
            'father_first_name'         => fn (Blueprint $t) => $t->string('father_first_name', 100)->nullable(),
            'father_middle_name'        => fn (Blueprint $t) => $t->string('father_middle_name', 100)->nullable(),
            'father_last_name'          => fn (Blueprint $t) => $t->string('father_last_name', 100)->nullable(),
            'father_qualification_id'   => fn (Blueprint $t) => $t->unsignedBigInteger('father_qualification_id')->nullable(),
            'father_annual_income'      => fn (Blueprint $t) => $t->string('father_annual_income', 100)->nullable(),
            'guardian_or_spouse'        => fn (Blueprint $t) => $t->string('guardian_or_spouse', 20)->nullable(),
            'guardian_first_name'       => fn (Blueprint $t) => $t->string('guardian_first_name', 100)->nullable(),
            'guardian_middle_name'      => fn (Blueprint $t) => $t->string('guardian_middle_name', 100)->nullable(),
            'guardian_last_name'        => fn (Blueprint $t) => $t->string('guardian_last_name', 100)->nullable(),
            'guardian_contact_no'       => fn (Blueprint $t) => $t->string('guardian_contact_no', 20)->nullable(),
            'guardian_email'            => fn (Blueprint $t) => $t->string('guardian_email', 150)->nullable(),
            'perm_district'             => fn (Blueprint $t) => $t->string('perm_district', 100)->nullable(),
            'perm_city_name'            => fn (Blueprint $t) => $t->string('perm_city_name', 150)->nullable(),
            'pres_district'             => fn (Blueprint $t) => $t->string('pres_district', 100)->nullable(),
            'pres_city_name'            => fn (Blueprint $t) => $t->string('pres_city_name', 150)->nullable(),
            'domicile_district'         => fn (Blueprint $t) => $t->string('domicile_district', 100)->nullable(),
            'dietary_preference'        => fn (Blueprint $t) => $t->string('dietary_preference', 50)->nullable(),
            'high_altitude_condition'   => fn (Blueprint $t) => $t->string('high_altitude_condition', 10)->nullable(),
            'high_altitude_remarks'     => fn (Blueprint $t) => $t->text('high_altitude_remarks')->nullable(),
            'health_asthma'             => fn (Blueprint $t) => $t->string('health_asthma', 10)->nullable(),
            'health_lung_disease'       => fn (Blueprint $t) => $t->string('health_lung_disease', 50)->nullable(),
            'health_kidney_disease'     => fn (Blueprint $t) => $t->string('health_kidney_disease', 10)->nullable(),
            'health_diabetes'           => fn (Blueprint $t) => $t->string('health_diabetes', 10)->nullable(),
            'health_blood_disorder'     => fn (Blueprint $t) => $t->string('health_blood_disorder', 50)->nullable(),
            'health_immunocompromised'  => fn (Blueprint $t) => $t->string('health_immunocompromised', 100)->nullable(),
            'health_liver_disease'      => fn (Blueprint $t) => $t->string('health_liver_disease', 10)->nullable(),
            'health_cardiac_condition'  => fn (Blueprint $t) => $t->string('health_cardiac_condition', 100)->nullable(),
            'health_pregnant_lactating' => fn (Blueprint $t) => $t->string('health_pregnant_lactating', 10)->nullable(),
            'health_additional_info'    => fn (Blueprint $t) => $t->text('health_additional_info')->nullable(),
            'health_completed'          => fn (Blueprint $t) => $t->boolean('health_completed')->default(false),
            'highest_stream_id'         => fn (Blueprint $t) => $t->unsignedBigInteger('highest_stream_id')->nullable(),
            'matric_state_id'           => fn (Blueprint $t) => $t->unsignedBigInteger('matric_state_id')->nullable(),
            'matric_district'           => fn (Blueprint $t) => $t->string('matric_district', 100)->nullable(),
            'matric_city'               => fn (Blueprint $t) => $t->string('matric_city', 100)->nullable(),
            'matric_city_name'          => fn (Blueprint $t) => $t->string('matric_city_name', 150)->nullable(),
            'cse_attempts'              => fn (Blueprint $t) => $t->integer('cse_attempts')->nullable(),
            'previous_service_id'       => fn (Blueprint $t) => $t->unsignedBigInteger('previous_service_id')->nullable(),
        ];

        foreach ($columns as $name => $callback) {
            if (! Schema::hasColumn('student_master_seconds', $name)) {
                Schema::table('student_master_seconds', $callback);
            }
        }
    }

    private function dropSecondsLegacyColumns(): void
    {
        $drop = [
            'redddw', 'extraa2', 'middlename', 'height', 'cadre',
        ];

        $existing = array_values(array_filter($drop, fn (string $col) => Schema::hasColumn('student_master_seconds', $col)));

        if ($existing !== []) {
            Schema::table('student_master_seconds', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};
