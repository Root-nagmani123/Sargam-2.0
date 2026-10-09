<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ज्ञानकोश sync state for each notice (App\Services\Knowledge\NoticeKnowledgeSync).
 *
 * knowledge_version is the edition ज्ञानकोश currently holds live, null when it
 * holds none. knowledge_sync_status is the outcome of the latest attempt
 * (live, failed, withdrawn, skipped), so a re-push that keeps failing reads
 * "failed" while the previous edition is still live there — and stays visible
 * through `php artisan knowledge:sync-notices --status`.
 *
 * Guarded column-by-column: some dev databases already carry these columns.
 */
return new class extends Migration
{
    private const TABLE = 'notices_notification';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        $add = fn (string $column, callable $define) => Schema::hasColumn(self::TABLE, $column)
            ? null
            : Schema::table(self::TABLE, fn (Blueprint $table) => $define($table));

        $add('knowledge_sync_status', fn (Blueprint $t) => $t->string('knowledge_sync_status', 20)->nullable());
        $add('knowledge_doc_id', fn (Blueprint $t) => $t->string('knowledge_doc_id', 191)->nullable());
        $add('knowledge_version', fn (Blueprint $t) => $t->string('knowledge_version', 40)->nullable());
        $add('knowledge_synced_at', fn (Blueprint $t) => $t->timestamp('knowledge_synced_at')->nullable());
        $add('knowledge_attempts', fn (Blueprint $t) => $t->unsignedSmallInteger('knowledge_attempts')->default(0));
        $add('knowledge_error', fn (Blueprint $t) => $t->text('knowledge_error')->nullable());
        $add('knowledge_last_attempt_at', fn (Blueprint $t) => $t->timestamp('knowledge_last_attempt_at')->nullable());

        $hasIndex = collect(Schema::getConnection()->select(
            'SHOW INDEX FROM ' . self::TABLE . ' WHERE Key_name = ?',
            ['idx_nn_knowledge_status']
        ))->isNotEmpty();

        if (! $hasIndex) {
            Schema::table(self::TABLE, fn (Blueprint $t) => $t->index('knowledge_sync_status', 'idx_nn_knowledge_status'));
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        $hasIndex = collect(Schema::getConnection()->select(
            'SHOW INDEX FROM ' . self::TABLE . ' WHERE Key_name = ?',
            ['idx_nn_knowledge_status']
        ))->isNotEmpty();

        if ($hasIndex) {
            Schema::table(self::TABLE, fn (Blueprint $t) => $t->dropIndex('idx_nn_knowledge_status'));
        }

        $columns = array_values(array_filter([
            'knowledge_sync_status', 'knowledge_doc_id', 'knowledge_version', 'knowledge_synced_at',
            'knowledge_attempts', 'knowledge_error', 'knowledge_last_attempt_at',
        ], fn ($c) => Schema::hasColumn(self::TABLE, $c)));

        if ($columns !== []) {
            Schema::table(self::TABLE, fn (Blueprint $t) => $t->dropColumn($columns));
        }
    }
};
