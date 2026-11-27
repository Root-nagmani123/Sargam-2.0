<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PR #354 review, F-019.
 *
 * MemberController::update() now takes
 *     SELECT pk FROM user_credentials WHERE user_id = ? ORDER BY pk FOR UPDATE
 * as the first statement of its transaction (F-018). A locking read locks the rows its
 * access path reads, so without an index on user_id it would scan the table and lock every
 * login row until the wizard save commits, stalling logins and Users-screen role saves.
 *
 * user_credentials predates the repository's migrations, and no migration declares an
 * index on user_id. Code comments mention idx_user_id, but that is not evidence it exists
 * on every environment. This migration makes it certain: it adds an index only when no
 * index whose FIRST column is user_id exists, so on a database that already has
 * idx_user_id (or any equivalent) it is a no-op. Guarded on information_schema, so a
 * re-run is a genuine no-op.
 */
return new class extends Migration
{
    private const TABLE = 'user_credentials';
    private const INDEX = 'user_credentials_user_id_index';

    private function userIdLedIndexExists(): bool
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
               AND COLUMN_NAME = 'user_id' AND SEQ_IN_INDEX = 1",
            [self::TABLE]
        );

        return $row && (int) $row->c > 0;
    }

    private function ownIndexExists(): bool
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [self::TABLE, self::INDEX]
        );

        return $row && (int) $row->c > 0;
    }

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasColumn(self::TABLE, 'user_id')) {
            return;
        }

        if ($this->userIdLedIndexExists()) {
            return;
        }

        DB::statement('ALTER TABLE ' . self::TABLE . ' ADD INDEX ' . self::INDEX . ' (user_id)');
    }

    public function down(): void
    {
        // Drop only the index this migration created, never a pre-existing idx_user_id.
        if ($this->ownIndexExists()) {
            DB::statement('ALTER TABLE ' . self::TABLE . ' DROP INDEX ' . self::INDEX);
        }
    }
};
