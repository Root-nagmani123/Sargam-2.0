<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * fc_joining_documents_user_uploads is read by `whereIn('user_id', ...)` on the Document
 * Checklist report / export and the Joining Documents step, but user_id had no index — a
 * full scan per query that gets worse as the table grows. Add a plain index on user_id.
 * Idempotent: skips if user_id is already the leading column of an index.
 */
return new class extends Migration
{
    private string $table = 'fc_joining_documents_user_uploads';
    private string $index = 'fc_jdu_user_id_index';

    public function up(): void
    {
        if (! Schema::hasTable($this->table) || ! Schema::hasColumn($this->table, 'user_id')) {
            return;
        }
        if ($this->userIdIsIndexed()) {
            return;
        }

        Schema::table($this->table, function (Blueprint $t) {
            $t->index('user_id', $this->index);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable($this->table) && $this->namedIndexExists($this->index)) {
            Schema::table($this->table, function (Blueprint $t) {
                $t->dropIndex($this->index);
            });
        }
    }

    private function userIdIsIndexed(): bool
    {
        return ! empty(DB::select(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
               AND COLUMN_NAME = 'user_id' AND SEQ_IN_INDEX = 1 LIMIT 1",
            [$this->table]
        ));
    }

    private function namedIndexExists(string $index): bool
    {
        return ! empty(DB::select(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1",
            [$this->table, $index]
        ));
    }
};
