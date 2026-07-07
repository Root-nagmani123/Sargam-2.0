<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces string `username` / `userid` columns in all FC tables with
 * integer `user_id` (FK → user_credentials.pk).
 *
 * Run AFTER deleting the importuser1 test record.
 * NOT reversible – restore from backup to roll back.
 */
return new class extends Migration
{
    // One row per user (username was UNIQUE)
    private const UNIQUE_TABLES = [
        'student_master_firsts',
        'student_master_seconds',
        'student_master_spouse_masters',
        'student_knowledge_hindi_masters',
        'student_master_hobbies_details',
        'student_master_module_masters',
        'student_master_exempted_masters',
        'student_fc_scale_masters',
        'student_confirm_masters',
        'student_master_incomplet_masters',
        'new_registration_bank_details_masters',
        'registration_bank_details_masters',
        'fc_joining_attendance_ganga_masters',
        'fc_joining_attendance_kaveri_masters',
        'fc_joining_attendance_narmada_masters',
        'fc_joining_attendance_mahanadi_masters',
        'fc_joining_attendance_happy_valley_masters',
        'fc_joining_attendance_silverwood_masters',
        'fc_joining_medical_details_masters',
        'fc_joining_covid_details_masters',
        'student_travel_plan_masters',
        'student_iosr_details_masters',
        'student_iosr_reasonable_adjust_masters',
        'student_register_masters',
        'student_exemption_med_doc_masters',
        'registration_covid_report_masters',
        'online_assigment_masters',
        'fc_ot_details',
    ];

    // Multiple rows per user (username was NOT unique)
    private const MULTI_TABLES = [
        'student_master_qualification_details',
        'student_master_higher_educational_details',
        'student_master_employment_details',
        'student_master_language_knowns',
        'student_skill_details_masters',
        'student_master_academic_distinctions',
        'student_sports_fitness_teach_masters',
        'student_sports_trg_teach_masters',
        'fc_joining_related_documents_details_masters',
        'mctp_student_travel_plan_details',
        'student_iosr_details_doc_path_masters',
        'student_master_movable_property_details',
        'student_master_immovable_property_details',
        'fc_otactivity_details',
    ];

    // Use 'userid' column name (string) instead of 'username'
    private const USERID_TABLES = [
        'fc_pre_history',
        'fc_path_report',
    ];

    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // ── PHASE 1: Add user_id column ───────────────────────────────
        foreach (array_merge(self::UNIQUE_TABLES, self::MULTI_TABLES) as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'user_id')) {
                continue;
            }
            Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger('user_id')->nullable()->after('id'));
        }

        // student_masters has composite unique (username, form_id) — handle separately
        if (Schema::hasTable('student_masters') && ! Schema::hasColumn('student_masters', 'user_id')) {
            Schema::table('student_masters', fn (Blueprint $t) => $t->unsignedBigInteger('user_id')->nullable()->after('id'));
        }

        foreach (self::USERID_TABLES as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'user_id')) {
                continue;
            }
            Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger('user_id')->nullable()->after('id'));
        }

        // ── PHASE 2: Backfill user_id from user_credentials ──────────
        $backfillUsername = "
            UPDATE `%s` t
            INNER JOIN user_credentials uc
                ON uc.user_name COLLATE utf8mb4_unicode_ci = t.username COLLATE utf8mb4_unicode_ci
            SET t.user_id = uc.pk
            WHERE t.user_id IS NULL
        ";

        foreach (array_merge(['student_masters'], self::UNIQUE_TABLES, self::MULTI_TABLES) as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'username')) {
                DB::statement(sprintf($backfillUsername, $table));
            }
        }

        $backfillUserid = "
            UPDATE `%s` t
            INNER JOIN user_credentials uc
                ON uc.user_name COLLATE utf8mb4_unicode_ci = t.userid COLLATE utf8mb4_unicode_ci
            SET t.user_id = uc.pk
            WHERE t.user_id IS NULL
        ";

        foreach (self::USERID_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'userid')) {
                DB::statement(sprintf($backfillUserid, $table));
            }
        }

        // ── PHASE 3: Drop old indexes on student_masters ─────────────
        if (Schema::hasTable('student_masters')) {
            foreach (['student_masters_username_form_id_unique', 'student_masters_username_unique'] as $idx) {
                try { DB::statement("ALTER TABLE student_masters DROP INDEX `{$idx}`"); } catch (\Throwable) {}
            }
        }

        // ── PHASE 4: Drop username / userid columns ───────────────────
        foreach (array_merge(['student_masters'], self::UNIQUE_TABLES) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'username')) {
                continue;
            }
            try { DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$table}_username_unique`"); } catch (\Throwable) {}
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('username'));
        }

        foreach (self::MULTI_TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'username')) {
                continue;
            }
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('username'));
        }

        foreach (self::USERID_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'userid')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('userid'));
            }
        }

        // ── PHASE 5: Make user_id NOT NULL + add indexes ──────────────
        // student_masters: composite unique (user_id, form_id)
        if (Schema::hasTable('student_masters') && Schema::hasColumn('student_masters', 'user_id')) {
            DB::table('student_masters')->whereNull('user_id')->delete();
            DB::statement('ALTER TABLE student_masters MODIFY user_id BIGINT UNSIGNED NOT NULL');
            if (Schema::hasColumn('student_masters', 'form_id')) {
                DB::statement('ALTER TABLE student_masters ADD UNIQUE KEY student_masters_user_id_form_id_unique (user_id, form_id)');
            } else {
                DB::statement('ALTER TABLE student_masters ADD UNIQUE KEY student_masters_user_id_unique (user_id)');
            }
        }

        foreach (self::UNIQUE_TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
                continue;
            }
            DB::table($table)->whereNull('user_id')->delete();
            DB::statement("ALTER TABLE `{$table}` MODIFY user_id BIGINT UNSIGNED NOT NULL");
            DB::statement("ALTER TABLE `{$table}` ADD UNIQUE KEY `{$table}_user_id_unique` (user_id)");
        }

        foreach (self::MULTI_TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
                continue;
            }
            DB::table($table)->whereNull('user_id')->delete();
            DB::statement("ALTER TABLE `{$table}` MODIFY user_id BIGINT UNSIGNED NOT NULL");
            DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$table}_user_id_index` (user_id)");
        }

        foreach (self::USERID_TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
                continue;
            }
            DB::table($table)->whereNull('user_id')->delete();
            DB::statement("ALTER TABLE `{$table}` MODIFY user_id BIGINT UNSIGNED NOT NULL");
            DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$table}_user_id_index` (user_id)");
        }

        // ── PHASE 6: Update fc_forms.user_identifier ──────────────────
        if (Schema::hasTable('fc_forms') && Schema::hasColumn('fc_forms', 'user_identifier')) {
            DB::table('fc_forms')
                ->where('user_identifier', 'username')
                ->update(['user_identifier' => 'user_id']);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'This migration cannot be automatically reversed. Restore from a database backup.'
        );
    }
};
