<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds persistent login-lockout columns to both credential tables (CWE-307).
 *
 * user_credentials      — admin / employee / student (main login)
 * fc_registration_master — FC trainee (user-end login)
 *
 * After 5 consecutive failed attempts the account is locked for 15 minutes.
 * A successful login resets both counters.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Admin / Employee / Student login ──────────────────────────────────
        if (Schema::hasTable('user_credentials') &&
            ! Schema::hasColumn('user_credentials', 'failed_login_attempts')) {

            Schema::table('user_credentials', function (Blueprint $table) {
                $table->tinyInteger('failed_login_attempts')->unsigned()->default(0)
                      ->after('last_login')
                      ->comment('Consecutive failed login attempts (CWE-307)');

                $table->timestamp('locked_until')->nullable()->default(null)
                      ->after('failed_login_attempts')
                      ->comment('Account locked until this timestamp; NULL = unlocked');
            });
        }

        // ── FC Trainee login ───────────────────────────────────────────────────
        if (Schema::hasTable('fc_registration_master') &&
            ! Schema::hasColumn('fc_registration_master', 'failed_login_attempts')) {

            Schema::table('fc_registration_master', function (Blueprint $table) {
                $table->tinyInteger('failed_login_attempts')->unsigned()->default(0)
                      ->after('web_auth')
                      ->comment('Consecutive failed login attempts (CWE-307)');

                $table->timestamp('locked_until')->nullable()->default(null)
                      ->after('failed_login_attempts')
                      ->comment('Account locked until this timestamp; NULL = unlocked');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_credentials', 'failed_login_attempts')) {
            Schema::table('user_credentials', function (Blueprint $table) {
                $table->dropColumn(['failed_login_attempts', 'locked_until']);
            });
        }

        if (Schema::hasColumn('fc_registration_master', 'failed_login_attempts')) {
            Schema::table('fc_registration_master', function (Blueprint $table) {
                $table->dropColumn(['failed_login_attempts', 'locked_until']);
            });
        }
    }
};
