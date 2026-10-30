<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * store() records issue_logger as NULL for a login that is no employee, so the column must
 * accept NULL wherever it was built from the NOT NULL create migration (PR #337 F-011).
 *
 * Shadows the real table with a session TEMPORARY one built as 2026_01_22_000005 builds it.
 * No rollback transaction: ALTER TABLE commits implicitly even on a temporary table, and the
 * only rows written are in the temporary table, which tearDown drops.
 */
class IssueLoggerNullableMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::statement("CREATE TEMPORARY TABLE issue_log_management (
            pk BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            description TEXT NOT NULL,
            created_by BIGINT UNSIGNED NOT NULL,
            issue_logger BIGINT UNSIGNED NOT NULL COMMENT 'Person who logged the issue',
            INDEX (issue_logger)
        )");
    }

    protected function tearDown(): void
    {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS issue_log_management');

        parent::tearDown();
    }

    private function migrate(): void
    {
        (require database_path('migrations/2026_10_30_000001_make_issue_log_management_issue_logger_nullable.php'))->up();
    }

    private function column(): object
    {
        return DB::selectOne("SHOW FULL COLUMNS FROM issue_log_management WHERE Field = 'issue_logger'");
    }

    public function test_a_not_null_logger_column_accepts_a_null_logger_after_the_migration(): void
    {
        $this->migrate();

        DB::table('issue_log_management')->insert(['description' => 'x', 'created_by' => 1, 'issue_logger' => null]);

        $this->assertSame(1, DB::table('issue_log_management')->whereNull('issue_logger')->count());
    }

    public function test_the_migration_keeps_the_columns_type_comment_and_index(): void
    {
        $this->migrate();
        $this->migrate();

        $column = $this->column();
        $this->assertSame('YES', $column->Null);
        $this->assertSame('bigint unsigned', $column->Type);
        $this->assertSame('Person who logged the issue', $column->Comment);
        $this->assertNotEmpty(DB::select("SHOW INDEX FROM issue_log_management WHERE Column_name = 'issue_logger'"));
    }
}
