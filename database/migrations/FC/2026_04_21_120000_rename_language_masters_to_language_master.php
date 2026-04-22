<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Existing installs may still have language_masters from earlier FC migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('language_masters') && ! Schema::hasTable('language_master')) {
            if (Schema::hasTable('student_master_language_knowns')) {
                Schema::table('student_master_language_knowns', function (Blueprint $table) {
                    $table->dropForeign(['language_id']);
                });
            }

            Schema::rename('language_masters', 'language_master');

            if (Schema::hasTable('student_master_language_knowns')) {
                Schema::table('student_master_language_knowns', function (Blueprint $table) {
                    $table->foreign('language_id')->references('id')->on('language_master')->nullOnDelete();
                });
            }
        }

        if (Schema::hasTable('fc_form_group_fields')) {
            DB::table('fc_form_group_fields')
                ->where('lookup_table', 'language_masters')
                ->update(['lookup_table' => 'language_master']);
            foreach (DB::table('fc_form_group_fields')->whereNotNull('validation_rules')->cursor() as $row) {
                if (! str_contains((string) $row->validation_rules, 'language_masters')) {
                    continue;
                }
                DB::table('fc_form_group_fields')->where('id', $row->id)->update([
                    'validation_rules' => str_replace('language_masters', 'language_master', (string) $row->validation_rules),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('language_master') && ! Schema::hasTable('language_masters')) {
            if (Schema::hasTable('student_master_language_knowns')) {
                Schema::table('student_master_language_knowns', function (Blueprint $table) {
                    $table->dropForeign(['language_id']);
                });
            }

            Schema::rename('language_master', 'language_masters');

            if (Schema::hasTable('student_master_language_knowns')) {
                Schema::table('student_master_language_knowns', function (Blueprint $table) {
                    $table->foreign('language_id')->references('id')->on('language_masters')->nullOnDelete();
                });
            }
        }

        if (Schema::hasTable('fc_form_group_fields')) {
            DB::table('fc_form_group_fields')
                ->where('lookup_table', 'language_master')
                ->update(['lookup_table' => 'language_masters']);
            foreach (DB::table('fc_form_group_fields')->whereNotNull('validation_rules')->cursor() as $row) {
                if (! str_contains((string) $row->validation_rules, 'language_master')) {
                    continue;
                }
                DB::table('fc_form_group_fields')->where('id', $row->id)->update([
                    'validation_rules' => str_replace('language_master', 'language_masters', (string) $row->validation_rules),
                ]);
            }
        }
    }
};
