<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repoints kitchen_issue_payment_details.kitchen_issue_master_pk at the live
 * kitchen_issue_master table (bug report 7 Sep 2026 — Process Mess Bills
 * "Payment" returned 422 for every kitchen-voucher bill).
 *
 * The create migration (2026_01_27_105626) always declared
 * ->references('pk')->on('kitchen_issue_master'). On the affected databases the
 * constraint instead pointed at kitchen_issue_master1, a stale snapshot left
 * behind by a restore (its sibling kitchen_issue_master_rollback_bak_20260824
 * is still present). That snapshot stops at pk 16197 while the live table now
 * reaches 17712, so recording a payment against any newer voucher failed with
 * SQLSTATE 23000 / errno 1452.
 *
 * ProcessMessBillsEmployeeController::kitchenIssueMasterPkForPayment() could
 * not catch this: it verifies the pk in kitchen_issue_master — the table the FK
 * was supposed to reference — so the guard passed and MySQL rejected the insert
 * afterwards. The fix belongs in the schema, not in that guard.
 *
 * Databases whose constraint already references kitchen_issue_master are left
 * untouched, so this is safe to run everywhere. Orphan rows are reported rather
 * than deleted: payment history is financial data, and dropping it silently to
 * satisfy a constraint is never the right call.
 */
return new class extends Migration
{
    private const CHILD_TABLE = 'kitchen_issue_payment_details';

    private const PARENT_TABLE = 'kitchen_issue_master';

    private const FK_COLUMN = 'kitchen_issue_master_pk';

    private const FK_NAME = 'kitchen_issue_payment_details_kitchen_issue_master_pk_foreign';

    public function up(): void
    {
        if (! Schema::hasTable(self::CHILD_TABLE) || ! Schema::hasTable(self::PARENT_TABLE)) {
            return;
        }

        $current = $this->referencedTable(self::FK_NAME);

        // Already correct (fresh installs from the create migration) — nothing to do.
        if ($current === self::PARENT_TABLE) {
            return;
        }

        $orphans = $this->orphanCount();
        if ($orphans > 0) {
            throw new RuntimeException(
                self::CHILD_TABLE.' has '.$orphans.' row(s) whose '.self::FK_COLUMN
                .' is missing from '.self::PARENT_TABLE.'. Reconcile these payment rows before'
                .' re-pointing the foreign key. To list them: SELECT d.* FROM '.self::CHILD_TABLE.' d'
                .' LEFT JOIN '.self::PARENT_TABLE.' m ON m.pk = d.'.self::FK_COLUMN.' WHERE m.pk IS NULL;'
            );
        }

        if ($current !== null) {
            DB::statement('ALTER TABLE `'.self::CHILD_TABLE.'` DROP FOREIGN KEY `'.self::FK_NAME.'`');
        }

        DB::statement(
            'ALTER TABLE `'.self::CHILD_TABLE.'`'
            .' ADD CONSTRAINT `'.self::FK_NAME.'`'
            .' FOREIGN KEY (`'.self::FK_COLUMN.'`)'
            .' REFERENCES `'.self::PARENT_TABLE.'` (`pk`)'
            .' ON DELETE CASCADE'
        );
    }

    /**
     * Not reversed: the previous target (kitchen_issue_master1) is a stale
     * restore artefact, and re-pointing back at it would reintroduce the bug.
     */
    public function down(): void
    {
        // no-op
    }

    /**
     * Table the named constraint currently references, or null if absent.
     */
    private function referencedTable(string $constraint): ?string
    {
        $row = DB::selectOne(
            'SELECT REFERENCED_TABLE_NAME AS referenced_table
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [self::CHILD_TABLE, $constraint]
        );

        return $row->referenced_table ?? null;
    }

    /**
     * Payment rows pointing at a pk the live master table does not have.
     */
    private function orphanCount(): int
    {
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c
             FROM `'.self::CHILD_TABLE.'` d
             LEFT JOIN `'.self::PARENT_TABLE.'` m ON m.pk = d.`'.self::FK_COLUMN.'`
             WHERE m.pk IS NULL'
        );

        return (int) ($row->c ?? 0);
    }
};
