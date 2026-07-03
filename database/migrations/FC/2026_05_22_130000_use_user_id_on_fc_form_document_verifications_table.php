<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fc_form_document_verifications')) {
            return;
        }

        if (Schema::hasColumn('fc_form_document_verifications', 'username')) {
            if (! Schema::hasColumn('fc_form_document_verifications', 'user_id')) {
                Schema::table('fc_form_document_verifications', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                });
            }

            DB::statement('
                UPDATE fc_form_document_verifications v
                INNER JOIN student_masters sm ON sm.username = v.username
                SET v.user_id = sm.id
                WHERE v.user_id IS NULL
            ');

            DB::table('fc_form_document_verifications')->whereNull('user_id')->delete();

            Schema::table('fc_form_document_verifications', function (Blueprint $table) {
                $table->dropUnique('fc_form_doc_verif_user_field_unique');
            });

            Schema::table('fc_form_document_verifications', function (Blueprint $table) {
                $table->dropColumn('username');
            });

            DB::statement('ALTER TABLE fc_form_document_verifications MODIFY user_id BIGINT UNSIGNED NOT NULL');

            Schema::table('fc_form_document_verifications', function (Blueprint $table) {
                $table->unique(['user_id', 'form_field_id'], 'fc_form_doc_verif_user_field_unique');
                $table->foreign('user_id', 'fc_form_doc_verif_user_fk')
                    ->references('id')
                    ->on('student_masters')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('fc_form_document_verifications', 'verified_by')
            && ! Schema::hasColumn('fc_form_document_verifications', 'verified_by_user_id')) {
            Schema::table('fc_form_document_verifications', function (Blueprint $table) {
                $table->unsignedBigInteger('verified_by_user_id')->nullable()->after('is_verified');
            });

            foreach (DB::table('fc_form_document_verifications')->whereNotNull('verified_by')->get() as $row) {
                $verifiedBy = trim((string) $row->verified_by);
                $adminId = is_numeric($verifiedBy)
                    ? (int) $verifiedBy
                    : DB::table('user_credentials')->where('user_name', $verifiedBy)->value('pk');

                if ($adminId) {
                    DB::table('fc_form_document_verifications')
                        ->where('id', $row->id)
                        ->update(['verified_by_user_id' => $adminId]);
                }
            }

            Schema::table('fc_form_document_verifications', function (Blueprint $table) {
                $table->dropColumn('verified_by');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('fc_form_document_verifications')) {
            return;
        }

        if (! Schema::hasColumn('fc_form_document_verifications', 'username')) {
            Schema::table('fc_form_document_verifications', function (Blueprint $table) {
                $table->dropForeign('fc_form_doc_verif_user_fk');
                $table->dropUnique('fc_form_doc_verif_user_field_unique');
            });

            Schema::table('fc_form_document_verifications', function (Blueprint $table) {
                $table->string('username', 100)->nullable()->after('id');
            });

            DB::statement('
                UPDATE fc_form_document_verifications v
                INNER JOIN student_masters sm ON sm.id = v.user_id
                SET v.username = sm.username
            ');

            Schema::table('fc_form_document_verifications', function (Blueprint $table) {
                $table->dropColumn('user_id');
                $table->unique(['username', 'form_field_id'], 'fc_form_doc_verif_user_field_unique');
            });
        }

        if (! Schema::hasColumn('fc_form_document_verifications', 'verified_by')) {
            Schema::table('fc_form_document_verifications', function (Blueprint $table) {
                $table->string('verified_by', 100)->nullable()->after('is_verified');
            });

            Schema::table('fc_form_document_verifications', function (Blueprint $table) {
                $table->dropColumn('verified_by_user_id');
            });
        }
    }
};
