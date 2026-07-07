<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migrates legacy fc_activity_department_user.username (user_credentials.user_name)
 * to user_credentials_pk (user_credentials.pk). Safe if column already user_credentials_pk.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fc_activity_department_user')) {
            return;
        }

        if (Schema::hasColumn('fc_activity_department_user', 'user_credentials_pk')) {
            return;
        }

        if (! Schema::hasColumn('fc_activity_department_user', 'username')) {
            return;
        }

        Schema::table('fc_activity_department_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_credentials_pk')->nullable()->after('fc_activity_department_id');
        });

        DB::statement("
            UPDATE fc_activity_department_user AS du
            INNER JOIN user_credentials AS uc
                ON uc.user_name COLLATE utf8mb4_unicode_ci = du.username COLLATE utf8mb4_unicode_ci
            SET du.user_credentials_pk = uc.pk
            WHERE du.username IS NOT NULL AND du.username != ''
        ");

        DB::table('fc_activity_department_user')->whereNull('user_credentials_pk')->delete();

        Schema::table('fc_activity_department_user', function (Blueprint $table) {
            try {
                $table->dropUnique('fc_activity_department_user_unique');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex('fc_activity_department_user_username_idx');
            } catch (\Throwable $e) {
            }
            $table->dropColumn('username');
        });

        DB::statement('ALTER TABLE fc_activity_department_user MODIFY user_credentials_pk BIGINT UNSIGNED NOT NULL');

        Schema::table('fc_activity_department_user', function (Blueprint $table) {
            $table->unique(['fc_activity_department_id', 'user_credentials_pk'], 'fc_act_dept_user_dept_uc_unique');
            $table->index('user_credentials_pk', 'fc_act_dept_user_uc_pk_idx');
        });

        if (Schema::hasTable('user_credentials')) {
            Schema::table('fc_activity_department_user', function (Blueprint $table) {
                $table->foreign('user_credentials_pk', 'fc_act_dept_user_uc_fk')
                    ->references('pk')
                    ->on('user_credentials')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Forward-only.
    }
};
