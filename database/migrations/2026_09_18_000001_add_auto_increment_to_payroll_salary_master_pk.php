<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * payroll_salary_master.pk had no AUTO_INCREMENT, so new rows were keyed by
 * a manually computed max(pk) + 1 (see MemberController::saveStep6PayrollData()),
 * which races under concurrent writes: two requests can read the same max
 * and then collide on the INSERT.
 *
 * Adding AUTO_INCREMENT to an already-populated primary key is safe and
 * does not touch existing rows — MySQL sets the table's next auto-increment
 * value to max(pk) + 1 automatically. Existing pk values (including the
 * large legacy-style IDs already in this table) are left exactly as they
 * are; only how *new* rows get their key changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if ($this->alreadyAutoIncrement()) {
            return;
        }

        DB::statement(
            'ALTER TABLE `payroll_salary_master` MODIFY `pk` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT'
        );
    }

    public function down(): void
    {
        if (! $this->alreadyAutoIncrement()) {
            return;
        }

        DB::statement(
            'ALTER TABLE `payroll_salary_master` MODIFY `pk` BIGINT(20) UNSIGNED NOT NULL'
        );
    }

    private function alreadyAutoIncrement(): bool
    {
        $extra = DB::selectOne(
            "SELECT EXTRA FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payroll_salary_master' AND COLUMN_NAME = 'pk'"
        );

        return $extra && stripos($extra->EXTRA, 'auto_increment') !== false;
    }
};
